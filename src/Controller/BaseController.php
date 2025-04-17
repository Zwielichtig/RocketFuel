<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends AbstractController
{
    protected $request;

    public function __construct()
    {
        $request = Request::createFromGlobals();
        $this->request = $request->request->all();
    }
}
