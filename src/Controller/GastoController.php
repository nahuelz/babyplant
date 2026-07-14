<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoGasto;
use App\Entity\EstadoGasto;
use App\Entity\EstadoGastoHistorico;
use App\Entity\Gasto;
use App\Form\GastoType;
use DateTime;
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
            'actividadReciente' => $this->getActividadRecienteData(),
            'page_title' => 'Gastos'
        );
    }

    /**
     * @Route("/index_table/", name="gasto_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $gasto = $request->get('idConcepto') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('concepto', 'concepto');
        $rsm->addScalarResult('monto', 'monto');
        $rsm->addScalarResult('modoPago', 'modoPago');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('nombreEstado', 'nombreEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');

        $nativeQuery = $em->createNativeQuery('call sp_index_gasto(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $gasto);

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

            $estado = $entityManager->getReference(EstadoGasto::class, ConstanteEstadoGasto::PENDIENTE);
            $gasto->setEstadoGasto($estado);

            $historico = new EstadoGastoHistorico();
            $historico->setGasto($gasto);
            $historico->setEstado($estado);
            $historico->setFecha(new \DateTime());
            $historico->setMotivo('Gasto creado');
            $entityManager->persist($historico);

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
     * @Route("/{id}/modal_cambiar_estado", name="gasto_modal_cambiar_estado", methods={"GET"})
     */
    public function modalCambiarEstado(Gasto $gasto): Response
    {
        $em = $this->doctrine->getManager();
        $estados = $em->getRepository(EstadoGasto::class)->findAll();
        
        return $this->render('gasto/modal_cambiar_estado.html.twig', [
            'gasto' => $gasto,
            'estados' => $estados,
        ]);
    }

    /**
     * @Route("/{id}/cambiar_estado", name="gasto_cambiar_estado", methods={"POST"})
     */
    public function cambiarEstado(Request $request, Gasto $gasto): JsonResponse
    {
        $idEstado = $request->request->get('id_estado');
        $motivo = $request->request->get('motivo', 'Cambio manual de estado');
        
        $em = $this->doctrine->getManager();
        $estado = $em->getRepository(EstadoGasto::class)->find($idEstado);
        
        if (!$estado) {
            return new JsonResponse(['success' => false, 'message' => 'Estado no encontrado'], 404);
        }
        
        $this->estadoService->cambiarEstadoGasto($gasto, $estado, $motivo);
        $em->flush();
        
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/tiles/data/", name="gasto_tiles_data", methods={"POST"})
     * @IsGranted("ROLE_GASTO")
     */
    public function indicadoresAction(Request $request): JsonResponse
    {
        $fechaDesde = $request->request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $idConcepto = $request->request->get('idConcepto') ?: NULL;

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('montoTotal', 'montoTotal');
        $rsm->addScalarResult('cantidad', 'cantidad');

        $sql = '
        SELECT
            SUM(g.monto) AS montoTotal,
            COUNT(g.id) AS cantidad
        FROM gasto AS g
        LEFT JOIN tipo_concepto tc ON g.id_tipo_concepto = tc.id
        WHERE g.fecha_baja IS NULL
        AND (g.fecha >= ? AND g.fecha <= ?)
        AND (? IS NULL OR (? IS NOT NULL AND tc.id = ?))';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);
        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $idConcepto);
        $nativeQuery->setParameter(4, $idConcepto);
        $nativeQuery->setParameter(5, $idConcepto);

        $result = $nativeQuery->getSingleResult();

        return new JsonResponse($result);
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

    /**
     *
     * @return type
     */
    private function getActividadRecienteData()
    {
        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('actividad', 'actividad');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('monto', 'monto');

        $sql = '
            SELECT
                g.id AS id,
                CONCAT_WS(" ", "El gasto nº", LPAD(g.id, 5, 0), "cambió su estado a", eg.nombre) AS actividad,
                h.fecha AS fecha,
                COALESCE(eg.color_icono, "default") AS colorClass,
                g.monto AS monto
            FROM estado_gasto_historico AS h
                     INNER JOIN gasto AS g ON g.id = h.id_gasto
                     INNER JOIN estado_gasto AS eg ON h.id_estado_gasto = eg.id
            WHERE g.fecha_baja IS NULL
              AND h.fecha_baja IS NULL
            ORDER BY h.id DESC
            LIMIT 0, 20';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getResult();
    }
}