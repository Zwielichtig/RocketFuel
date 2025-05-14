<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScriptController extends BaseController
{
    #[Route('/', name: 'homepage')]
    public function viewHomepage(): Response
    {
        return $this->render('/layouts/home.html.twig', [
            'user' => $this->session->get('user'),
            'message' => $this->session->get('message'),
        ]);
    }

    #[Route('/landing', name: 'landing')]
    public function viewLanding(): Response
    {
        return $this->render('/layouts/landing.html.twig', [
            'user' => $this->session->get('user'),
        ]);
    }

    #[Route('/editor', name: 'scriptEditor')]
    public function viewScriptEditor(): Response
    {
        return $this->render('/layouts/scriptEditor.html.twig', [
            'user' => $this->session->get('user'),
        ]);
    }
}