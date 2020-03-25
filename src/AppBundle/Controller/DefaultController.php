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
        // Initialize
        $wsConfig = $this->container->getParameter('afsy_chat.websocket');
        $itsChristmas = false;
        $xmasLink = '';

        // Check if it's Christmas Time
        // Only on december and before the 26th
        if ('12' === date('m') && (int) date('d') < 26) {
            // Set Christmas
            $itsChristmas = true;

            // Get Christmas link
            $christmasLinks = $this->container->getParameter('xmas_links');
            $xmasLink = $christmasLinks[rand(0, \count($christmasLinks) - 1)];
        }

        // Render template
        return $this->render('default/index.html.twig', [
            'ws_url' => $wsConfig['host'].':'.$wsConfig['port'],
            'its_christmas' => $itsChristmas,
            'xmas_link' => $xmasLink,
        ]);
    }
}
