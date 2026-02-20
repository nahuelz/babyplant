<?php

namespace App\Controller;

use App\Entity\Gasto;
use App\Form\GastoType;
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
            'indicadorGastoData' => $this->getIndicadorGastoData(),
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
        $rsm->addScalarResult('modoPago', 'modoPago');

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
            $this->validarMonto($form->getData());
            $monto = $this->normalizarMonto($form->getData());
            $gasto->setMonto($monto);
            $entityManager->persist($gasto);
            $entityManager->flush();

            return $this->redirectToRoute('gasto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('gasto/new.html.twig', [
            'gasto' => $gasto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'gasto_show', methods: ['GET'])]
    public function show(Gasto $gasto): Response
    {
        return $this->render('gasto/show.html.twig', [
            'gasto' => $gasto,
        ]);
    }

    /**
     * @Route("/{id}/borrar", name="gasto_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|type
    {
        return parent::baseDeleteAction($id);
    }


    /**
     * @Route("/{id}/edit", name="gasto_edit", methods={"GET|POST"})
     */
    public function edit(Request $request, Gasto $gasto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GastoType::class, $gasto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('gasto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('gasto/edit.html.twig', [
            'gasto' => $gasto,
            'form' => $form,
        ]);
    }

    private function validarMonto(Gasto $gasto){
        if (($gasto->getMonto() == null) || $gasto->getMonto() <= 0) {
            throw new \DomainException('El monto debe ser mayor a 0');
        }
    }

    private function normalizarMonto(Gasto $gasto): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $gasto->getMonto());
    }

    /**
     *
     * @return type
     */
    private function getIndicadorGastoData()
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
        FROM gasto AS g
        WHERE g.fecha_baja IS NULL';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getSingleResult();
    }

}