<?php

namespace App\Controller;

use App\Entity\Logic;
use App\Entity\Script;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GeneralRoutingController extends BaseController
{
    #[Route('/', name: 'homepage')]
    public function viewHomepage(EntityManagerInterface $entityManager): Response
    {
        // Get latest public logics
        $latestLogics = $entityManager->getRepository(Logic::class)
            ->createQueryBuilder('l')
            ->where('l.published = :published')
            ->setParameter('published', true)
            ->orderBy('l.modified', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        // Get latest public scripts
        $latestScripts = $entityManager->getRepository(Script::class)
            ->createQueryBuilder('s')
            ->where('s.published = :published')
            ->setParameter('published', true)
            ->orderBy('s.modified', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        return $this->render('/layouts/home.html.twig', [
            'user' => $this->session->get('user'),
            'message' => $this->session->get('message'),
            'latestLogics' => $latestLogics,
            'latestScripts' => $latestScripts
        ]);
    }

    #[Route('/landing', name: 'landing')]
    public function viewLanding(): Response
    {
        return $this->render('/layouts/landing.html.twig', [
            'user' => $this->session->get('user'),
        ]);
    }

    #[Route('/{route}', name: 'not_found', requirements: ['route' => '.*'], priority: -100)]
    public function notFound(): Response
    {
        // Only handle GET requests
        if ($this->requestObject->getMethod() !== 'GET') {
            throw $this->createNotFoundException();
        }

        return $this->render('/layouts/error.html.twig', [
            'user' => $this->session->get('user'),
            'error_code' => 404,
            'error_message' => 'Die angeforderte Seite wurde nicht gefunden.',
            'error_description' => 'Die von Ihnen gesuchte Seite existiert nicht oder wurde verschoben.'
        ]);
    }
}