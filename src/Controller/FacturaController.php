<?php

namespace App\Controller;

use App\Entity\Factura;
use App\Form\FacturaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Type;


/**
 * @Route("/factura")
 * @IsGranted("ROLE_GASTO")
 */
class FacturaController extends BaseController {

    /**
     * @Route("/", name="factura_index", methods={"GET"})
     * @Template("factura/index.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function index(): array
    {
        $conceptoSelect = $this->getSelectService()->getConceptoFilter();

        return array(
            'conceptoSelect' => $conceptoSelect,
            'indicadorFacturaData' => $this->getIndicadorFacturaData(),
            'page_title' => 'Facturas'
        );
    }

    /**
     * @Route("/index_table/", name="factura_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $factura = $request->get('idConcepto') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('concepto', 'concepto');
        $rsm->addScalarResult('monto', 'monto');
        $rsm->addScalarResult('modoPago', 'modoPago');

        $nativeQuery = $em->createNativeQuery('call sp_index_factura(?)', $rsm);

        $nativeQuery->setParameter(1, $factura);

        $entities = $nativeQuery->getResult();

        return $this->render('factura/index_table.html.twig', array('entities' => $entities));
    }


    /**
     * @Route("/new", name="factura_new", methods={"GET|POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $factura = new Factura();
        $form = $this->createForm(FacturaType::class, $factura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->validarMonto($form->getData());
            $monto = $this->normalizarMonto($form->getData());
            $factura->setMonto($monto);
            $entityManager->persist($factura);
            $entityManager->flush();

            return $this->redirectToRoute('factura_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('factura/new.html.twig', [
            'factura' => $factura,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'factura_show', methods: ['GET'])]
    public function show(Factura $factura): Response
    {
        return $this->render('factura/show.html.twig', [
            'factura' => $factura,
        ]);
    }

    /**
     * @Route("/{id}/borrar", name="factura_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|type
    {
        return parent::baseDeleteAction($id);
    }


    /**
     * @Route("/{id}/edit", name="factura_edit", methods={"GET|POST"})
     */
    public function edit(Request $request, Factura $factura, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FacturaType::class, $factura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->validarMonto($form->getData());
            $monto = $this->normalizarMonto($form->getData());
            $factura->setMonto($monto);
            $entityManager->flush();

            return $this->redirectToRoute('factura_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('factura/edit.html.twig', [
            'factura' => $factura,
            'form' => $form,
        ]);
    }

    private function validarMonto(Factura $factura){
        if (($factura->getMonto() == null) || $factura->getMonto() <= 0) {
            throw new \DomainException('El monto debe ser mayor a 0');
        }
    }

    private function normalizarMonto(Factura $factura): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $factura->getMonto());
    }

    /**
     *
     * @return type
     */
    private function getIndicadorFacturaData()
    {
        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('montoTotal', 'montoTotal');
        $rsm->addScalarResult('cantidad', 'cantidad');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('iconClass', 'iconClass');

        $sql = '
        SELECT
            SUM(g.monto) AS montoTotal,
            COUNT(g.id) AS cantidad,
            "success" AS colorClass,
            "success" AS color,
            "fa-money-bill-wave" AS iconClass
        FROM factura AS g
        WHERE g.fecha_baja IS NULL';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getSingleResult();
    }

}