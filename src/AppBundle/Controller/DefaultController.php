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
        $itsChristmas = false;
        $xmasLink = '';

        // Check if it's Christmas Time
        // Only on december and before the 26th
        if (date('m') === '12' && (int) date('d') < 26) {
            // Set Christmas
            $itsChristmas = true;

            // Get christmas link
            $christmasLinks = $this->container->getParameter('xmas_links');
            $xmasLink = $christmasLinks[rand(0, count($christmasLinks) - 1)];
        }

        // Render template
        return $this->render('default/index.html.twig', [
            'ws_url' => 'localhost:8080',
            'its_christmas' => $itsChristmas,
            'xmas_link' => $xmasLink,
        ]);
    }
}
