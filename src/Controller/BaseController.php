<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\EmailVerifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class BaseController extends AbstractController
{
    protected ?Request $requestObject = null;
    protected ?array $request = null;
    protected ?EmailVerifierService $emailVerifierService = null;
    public ?Session $session = null;

    public function __construct(
        readonly RequestStack $requestStack,
        readonly TokenStorageInterface $tokenStorage,
        readonly VerifyEmailHelperInterface $verifyEmailHelper,
        readonly MailerInterface $mailer,
        readonly EntityManagerInterface $entityManager,
        readonly UrlGeneratorInterface $urlGenerator,)
    {
        $this->requestObject = $requestStack->getCurrentRequest();
        $this->request = $this->requestObject->request->all();

        if ($this->requestObject) {
            $this->session = $this->requestObject->getSession();

            if ($this->session->get(SecurityRequestAttributes::LAST_USERNAME)) {
                $user = $tokenStorage->getToken()?->getUser();

                if ($user instanceof User) {
                    $this->session->set('user', $user);
                } else {
                    $this->session->set('user', null);
                }
            }

            $this->session->set('currentPage', $this->requestObject->getRequestUri());

            $this->emailVerifierService = new EmailVerifierService($verifyEmailHelper, $mailer, $entityManager, $urlGenerator);
        }
    }
}
