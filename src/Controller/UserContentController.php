<?php

namespace App\Controller;

use App\Entity\Script;
use App\Entity\Logic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserContentController extends BaseController
{
    #[Route('/user/scripts', name: 'user_scripts')]
    public function listScripts(EntityManagerInterface $entityManager): Response
    {
        $user = $this->session->get('user');
        if (!$user) {
            return $this->redirectToRoute('homepage');
        }

        $scripts = $entityManager->getRepository(Script::class)
            ->findBy(
                ['creator' => $user->getId()],
                ['crdate' => 'DESC']
            );

        return $this->render('layouts/user_content.html.twig', [
            'type' => 'script',
            'title' => 'Meine Skripte',
            'items' => $scripts,
            'user' => $user
        ]);
    }

    #[Route('/user/logics', name: 'user_logics')]
    public function listLogics(EntityManagerInterface $entityManager): Response
    {
        $user = $this->session->get('user');
        if (!$user) {
            return $this->redirectToRoute('homepage');
        }

        $logics = $entityManager->getRepository(Logic::class)
            ->findBy(
                ['creator' => $user->getId()],
                ['crdate' => 'DESC']
            );

        return $this->render('layouts/user_content.html.twig', [
            'type' => 'logic',
            'title' => 'Meine Logiken',
            'items' => $logics,
            'user' => $user
        ]);
    }
}