<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig', [
            'ws_url' => 'localhost:8080',
        ]);
    }

    /**
     * @Route ("/chat", name="chat")
     */
    public function chatAction()
    {
        return $this->render('ws-chat/base-chat.html.twig', [
        ]);
    }
}
