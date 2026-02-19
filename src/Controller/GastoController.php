<?php

namespace App\Controller;

use App\Entity\Gasto;
use App\Form\GastoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/gasto")
 * @IsGranted("ROLE_GASTO")
 */
class GastoController extends BaseController {

    /**
     * @Route("/", name="gasto_index", methods={"GET"})
     * @Template("gasto/index.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function index(): array
    {
        $conceptoSelect = $this->getSelectService()->getConceptoFilter();

        return array(
            'conceptoSelect' => $conceptoSelect,
            'page_title' => 'Gastos'
        );
    }

    /**
     * @Route("/index_table/", name="gasto_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $gasto = $request->get('idConcepto') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('concepto', 'concepto');
        $rsm->addScalarResult('monto', 'monto');
        $rsm->addScalarResult('modoPago', 'modoPAgo');

        $nativeQuery = $em->createNativeQuery('call sp_index_gasto(?)', $rsm);

        $nativeQuery->setParameter(1, $gasto);

        $entities = $nativeQuery->getResult();

        return $this->render('gasto/index_table.html.twig', array('entities' => $entities));
    }


    /**
     * @Route("/new", name="gasto_new", methods={"GET|POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $gasto = new Gasto();
        $form = $this->createForm(GastoType::class, $gasto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gasto);
            $entityManager->flush();

            return $this->redirectToRoute('gasto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('gasto/new.html.twig', [
            'gasto' => $gasto,
            'form' => $form,
        ]);
    }

}