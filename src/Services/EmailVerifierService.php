<?php

namespace App\Services;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\ByteString;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifierService
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        $expirationTime = '1 hour';

        $signature = $this->generateSignature(
            $user,
            $expirationTime,
        );

        $signedUrl = $this->urlGenerator->generate(
            $verifyEmailRouteName,
            [
                'id' => $user->getId(),
                'signature' => $signature
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signedUrl;
        $context['expirationTime'] = $expirationTime;

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        // Check if the signature exists in the request
        if (!$request->query->has('signature') || !$request->attributes->has('id')) {
            throw new \InvalidArgumentException('Missing required query parameters.');
        }

        // Retrieve the signature from the URL query parameters
        $signature = $request->query->get('signature');
        $userId = $request->attributes->get('id');

        // Ensure the user ID matches
        if ($user->getId() != $userId) {
            throw new \InvalidArgumentException('User ID does not match.');
        }

        $token = $user->getAuthToken();

        // Check if the signature matches the stored signature for the user
        if ($token->getToken() !== $signature) {
            throw new \InvalidArgumentException('Invalid signature.');
        }

        // Check if the token is expired

        if ($token->getExpiresAt() < time()) {
            throw new \InvalidArgumentException('The verification link has expired.');
        }

        // Mark the user as verified
        $user->setVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function generateSignature(User $user, string $expirationTime): string
    {
        $expiresAt = (new \DateTime())->modify('+' . $expirationTime)->getTimestamp();
        $signature = ByteString::fromRandom(32)->toString();

        // Store the token and expiration time for the user
        $token = new AuthToken;
        $token->setToken($signature);
        $token->setExpiresAt($expiresAt);

        $this->entityManager->persist($token);

        $user->setAuthToken($token);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $signature;
    }
}
