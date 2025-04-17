<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScriptController extends BaseController
{
    private $user = 1;

    #[Route('/', name: 'homepage')]
    public function viewHomepage(): Response
    {
        return $this->render('/layouts/home.html.twig', [
            'user' => $this->user,
        ]);
    }

    #[Route('/editor', name: 'scriptEditor')]
    public function viewScriptEditor(): Response
    {
        return $this->render('/layouts/scriptEditor.html.twig', [
            'user' => $this->user,
        ]);
    }
}