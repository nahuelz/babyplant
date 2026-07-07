<?php

namespace App\Controller;

use App\Entity\EstadoFactura;
use App\Entity\EstadoFacturaHistorico;
use App\Entity\Factura;
use App\Entity\FacturaDetalle;
use App\Entity\ImputacionPagoFactura;
use App\Entity\MovimientoProveedor;
use App\Entity\Proveedor;
use App\Entity\TipoGrupo;
use App\Entity\TipoMovimiento;
use App\Entity\TipoSubConcepto;
use App\Entity\Usuario;
use App\Entity\Constants\ConstanteEstadoFactura;
use App\Entity\Constants\ConstanteTipoMovimiento;
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

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('proveedor', 'proveedor');
        $rsm->addScalarResult('numeroFactura', 'numeroFactura');
        $rsm->addScalarResult('tipoGrupo', 'tipoGrupo');
        $rsm->addScalarResult('concepto', 'concepto');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('montoTotal', 'montoTotal');
        $rsm->addScalarResult('tipoMoneda', 'tipoMoneda');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('nombreEstado', 'nombreEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');

        $nativeQuery = $em->createNativeQuery('call sp_index_factura()', $rsm);

        $entities = $nativeQuery->getResult();

        return $this->render('factura/index_table.html.twig', array('entities' => $entities));
    }


    /**
     * @Route("/new", name="factura_new", methods={"GET","POST"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function new(Request $request, EntityManagerInterface $em): Array {
        $entity = new Factura();
        if ($request->query->has('proveedor_id')) {
            $id = $request->query->get('proveedor_id');
            $proveedor = $em->getRepository(Proveedor::class)->find($id);
            $entity->setProveedor($proveedor);
        }
        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/new_modal", name="factura_new_modal", methods={"GET"})
     * @IsGranted("ROLE_GASTO")
     */
    public function newModal(Request $request, EntityManagerInterface $em): Response {
        $entity = new Factura();
        if ($request->query->has('proveedor_id')) {
            $id = $request->query->get('proveedor_id');
            $proveedor = $em->getRepository(Proveedor::class)->find($id);
            $entity->setProveedor($proveedor);
        }
        $form = $this->createForm(FacturaType::class, $entity);
        return $this->render('factura/new_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
        ]);
    }

    /**
     * @Route("/{id}/edit_modal", name="factura_edit_modal", methods={"GET"})
     * @IsGranted("ROLE_GASTO")
     */
    public function editModal(Factura $factura): Response {
        $form = $this->createForm(FacturaType::class, $factura, [
            'action' => $this->generateUrl('factura_update', ['id' => $factura->getId()]),
            'method' => 'PUT',
        ]);
        return $this->render('factura/new_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $factura,
        ]);
    }

    /**
     * @Route("/insertar", name="factura_create", methods={"GET","POST"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request, true);
    }

    #[Route('/{id}', name: 'factura_show', methods: ['GET'])]
    public function show(Factura $factura): Response
    {
        $em = $this->doctrine->getManager();
        $imputaciones = $em->getRepository(ImputacionPagoFactura::class)
            ->createQueryBuilder('i')
            ->join('i.pagoProveedor', 'p')
            ->where('i.factura = :factura')
            ->andWhere('p.fechaBaja IS NULL')
            ->orderBy('p.fechaPago', 'DESC')
            ->setParameter('factura', $factura)
            ->getQuery()
            ->getResult();

        return $this->render('factura/show.html.twig', [
            'factura' => $factura,
            'imputaciones' => $imputaciones,
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
     * @Route("/{id}/edit", name="factura_edit", methods={"GET"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function edit($id): array
    {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="factura_update", methods={"POST","PUT"})
     * @IsGranted("ROLE_GASTO")
     */
    public function updateAction(Request $request, $id)
    {
        return parent::baseUpdateAction($request, $id, true);
    }

    function execPrePersistAction($entity, $request): bool
    {
        /** @var Factura $entity */
        foreach ($entity->getDetalles() as $detalle) {
            $detalle->setFactura($entity);
        }

        if (!$entity->getEstadoFactura()) {
            $em = $this->doctrine->getManager();
            $estado = $em->getReference(EstadoFactura::class, ConstanteEstadoFactura::PENDIENTE);
            $entity->setEstadoFactura($estado);

            $historico = new EstadoFacturaHistorico();
            $historico->setFactura($entity);
            $historico->setEstado($estado);
            $historico->setFecha(new \DateTime());
            $historico->setMotivo('Factura creada');
            $entity->addHistoricoEstado($historico);
        }

        return true;
    }

    function execPostPersistAction($em, $entity, $request): void
    {
        /** @var Factura $entity */
        $proveedor = $entity->getProveedor();

        $cuentaCorriente = $proveedor->getCuentaCorrienteProveedor();

        if (!$cuentaCorriente) {
            return;
        }

        $tipoMovimiento = $em->getRepository(TipoMovimiento::class)
            ->find(ConstanteTipoMovimiento::FACTURA);

        if (!$tipoMovimiento) {
            return;
        }

        $total = $entity->getTotal();

        $tipoMoneda = $entity->getTipoMoneda();

        // Factura = aumenta deuda
        $montoMovimiento = -$total;

        // Actualiza saldo según moneda
        $cuentaCorriente->sumarSaldo(
            $montoMovimiento,
            $tipoMoneda
        );

        // Obtiene saldo actualizado
        $saldoPosterior = $tipoMoneda === 'USD'
            ? $cuentaCorriente->getSaldoUsd()
            : $cuentaCorriente->getSaldoArs();

        $movimiento = new MovimientoProveedor();

        $movimiento->setCuentaCorrienteProveedor($cuentaCorriente);
        $movimiento->setTipoMovimiento($tipoMovimiento);

        $movimiento->setMonto($montoMovimiento);

        $movimiento->setSaldoPosterior($saldoPosterior);

        $movimiento->setTipoMoneda($tipoMoneda);

        $movimiento->setDescripcion(
            'Factura #' . $entity->getNumeroFactura()
        );

        $movimiento->setFactura($entity);

        $em->persist($movimiento);

        $em->flush();
    }

    function execPreUpdateAction($em, $entity, $request, $localVariablesArray): bool
    {
        /** @var Factura $entity */
        $submittedData = $request->request->get('factura', []);
        $submittedDetalles = $submittedData['detalles'] ?? [];

        $detalleRepo = $em->getRepository(FacturaDetalle::class);
        $conceptoRepo = $em->getRepository(\App\Entity\TipoConcepto::class);
        $subConceptoRepo = $em->getRepository(\App\Entity\TipoSubConcepto::class);
        $tipoGrupoRepo = $em->getRepository(TipoGrupo::class);

        $existingDetalles = $detalleRepo->findBy(['factura' => $entity->getId()]);
        $existingById = [];
        foreach ($existingDetalles as $d) {
            $existingById[$d->getId()] = $d;
        }

        $submittedIds = [];
        $newCollection = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($submittedDetalles as $data) {
            $id = !empty($data['id']) ? (int) $data['id'] : null;

            if ($id && isset($existingById[$id])) {
                $detalle = $existingById[$id];
                $submittedIds[] = $id;
            } else {
                $detalle = new FacturaDetalle();
            }

            $detalle->setTipoGrupo(!empty($data['tipoGrupo']) ? $tipoGrupoRepo->find($data['tipoGrupo']) : null);
            $detalle->setConcepto(!empty($data['concepto']) ? $conceptoRepo->find($data['concepto']) : null);
            $detalle->setSubConcepto(!empty($data['subConcepto']) ? $subConceptoRepo->find($data['subConcepto']) : null);
            $detalle->setCantidad($data['cantidad'] ?? null);
            $detalle->setPrecioUnitario($data['precioUnitario'] ?? null);
            $detalle->setDescripcion($data['descripcion'] ?? null);
            $detalle->setFactura($entity);

            $newCollection->add($detalle);
        }

        foreach ($existingById as $id => $detalle) {
            if (!in_array($id, $submittedIds, true)) {
                $em->remove($detalle);
            }
        }

        $entity->setDetalles($newCollection);

        $movimiento = $em->getRepository(MovimientoProveedor::class)
            ->findOneBy(['factura' => $entity]);

        if ($movimiento && $cuentaCorriente = $entity->getProveedor()->getCuentaCorrienteProveedor()) {
            $newTotal  = $entity->getTotal();
            $oldMonto  = $movimiento->getMonto();
            $oldMoneda = $movimiento->getTipoMoneda();
            $newMoneda = $entity->getTipoMoneda();

            // 1) Revertir el movimiento viejo sobre la moneda vieja
            $cuentaCorriente->sumarSaldo(-$oldMonto, $oldMoneda);

            // 2) Aplicar el nuevo total sobre la moneda nueva
            $nuevoMonto = -$newTotal;
            $cuentaCorriente->sumarSaldo($nuevoMonto, $newMoneda);

            $nuevoSaldo = $newMoneda === 'USD'
                ? $cuentaCorriente->getSaldoUsd()
                : $cuentaCorriente->getSaldoArs();

            // 3) Actualizar el movimiento con la nueva moneda y saldo
            $movimiento->setMonto($nuevoMonto);
            $movimiento->setTipoMoneda($newMoneda);
            $movimiento->setSaldoPosterior($nuevoSaldo);
            $movimiento->setDescripcion('Factura #' . $entity->getNumeroFactura());
        }

        return true;
    }
    /**
     *
     * @return type
     */
    private function getIndicadorFacturaData()
    {
        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('cantidad', 'cantidad');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('iconClass', 'iconClass');

        $sql = '
        SELECT
            COUNT(g.id) AS cantidad,
            "success" AS colorClass,
            "success" AS color,
            "fa-money-bill-wave" AS iconClass
        FROM factura AS g
        WHERE g.fecha_baja IS NULL';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getSingleResult();
    }

    /**
     * @Route("/lista/conceptos", name="lista_conceptos")
     */
    public function listaSubConceptosAction(Request $request) {
        $id_concepto = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(TipoSubConcepto::class);

        $query = $repository->createQueryBuilder('l')
            ->select("l.id, l.nombre AS denominacion")
            ->where('l.tipoConcepto = :concepto')
            ->andWhere('l.habilitado = 1')
            ->setParameter('concepto', $id_concepto)
            ->orderBy('l.nombre', 'ASC')
            ->getQuery();
        return new JsonResponse($query->getResult());
    }
}