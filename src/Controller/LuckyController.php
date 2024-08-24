<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LuckyController extends AbstractController
{

    /**
     * @Route("/homepage", name="homepage", methods={"GET|POST"}))
     */
    public function number(): Response
    {
        $number = random_int(0, 100);

        return new Response(
            '<html><body>Lucky number: ' . $number . '</body></html>'
        );
    }

    /**
     * @Route("/questions/{slug}")
     */
    public function show($slug)
    {
        return $this->render('base.html.twig', [
            'question' => ucwords(str_replace('-', ' ', $slug))
        ]);
    }
}