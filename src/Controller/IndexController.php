<?php

namespace App\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * @IsGranted("ROLE_USER")
 */
class IndexController extends BaseController {

    /**
     * @Route("/", name="index_index", methods={"GET"})
     */
    public function index(Request $request) {
        return $this->render('index.html.twig', [
            'controller_name' => 'IndexController',
        ]);
    }

}