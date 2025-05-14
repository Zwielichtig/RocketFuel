<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class UserController extends BaseController
{
    #[Route(path: '/login', name: 'login')]
    public function login(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $this->request['username']]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $this->request['password'])) {
            $this->session->set('message', 'Benutzername oder Passwort ungültig.');
            return $this->redirectToRoute('homepage');
        }

        if (!$user->isVerified()) {
            $this->session->set('message', 'Bitte bestätigen Sie Ihre E-Mail-Adresse.');
            return $this->redirectToRoute('homepage');
        }

        $this->session->set('user', $user);

        return $this->redirectToRoute('homepage', [
            'user' => $this->session->get('user'),
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on the firewall.');
    }

    #[Route(path: '/register', name: 'register')]
    public function register(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if (null == $userRepository->findOneBy(['username' => $this->request['username']]) || null == $userRepository->findOneBy(['email' => $this->request['email']])) {
            $user = new User();
            $user->setUsername($this->request['username']);
            $user->setEmail($this->request['email']);
            $user->setRoles(['ROLE_USER']);
            $user->setVerified(false);
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $this->request['password']
            ));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifierService->sendEmailConfirmation('verifyEmail', $user,
                (new TemplatedEmail())
                    ->from(new Address('verification@rocketfuel.com', 'RocketFuel'))
                    ->to($user->getEmail())
                    ->subject('Bitte bestätigen Sie Ihre E-Mail-Adresse')
                    ->htmlTemplate('mail/auth.html.twig')
            );

            $this->session->set('message', 'Bitte bestätigen Sie Ihre E-Mail-Adresse.');

            return $this->redirectToRoute('homepage');
        }

        return $this->redirectToRoute('homepage', [
        ]);
    }

    #[Route('/verify/email/{id}', name: 'verifyEmail')]
    public function verifyUserEmail(int $id, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
       // Verify the user id exists and is not null
        if (null === $id) {
            return $this->redirectToRoute('homepage');
        }

        $user = $userRepository->find($id);

       // Ensure the user exists in persistence
        if (null === $user) {
            return $this->redirectToRoute('homepage');
        }
        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifierService->handleEmailConfirmation($this->requestObject, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('homepage');
        }

        $this->session->set('message', 'E-Mail-Adresse erfolgreich bestätigt. Bitte anmelden.');

        return $this->redirectToRoute('homepage');
    }
}