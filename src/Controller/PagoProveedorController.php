<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoFactura;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\EstadoFactura;
use App\Entity\EstadoFacturaHistorico;
use App\Entity\Factura;
use App\Entity\ImputacionPagoFactura;
use App\Entity\MovimientoProveedor;
use App\Entity\Pago;
use App\Entity\PagoProveedor;
use App\Entity\Proveedor;
use App\Entity\TipoMovimiento;
use App\Form\PagoProveedorType;
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
 * @Route("/pago_proveedor")
 * @IsGranted("ROLE_GASTO")
 */
class PagoProveedorController extends BaseController
{
    /**
     * @Route("/", name="pagoproveedor_index", methods={"GET"})
     * @Template("pago_proveedor/index.html.twig")
     */
    public function index(): array
    {
        return [
            'page_title' => 'Pagos a proveedores'
        ];
    }

    /**
     *
     * @Route("/index_table/", name="pagoproveedor_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_pago_proveedor';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('proveedor', 'proveedor');
        $rsm->addScalarResult('fechaPago', 'fechaPago');
        $rsm->addScalarResult('monto', 'monto');
        $rsm->addScalarResult('tipoMoneda', 'tipoMoneda');
        $rsm->addScalarResult('modoPago', 'modoPago');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'proveedor', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'fechaPago', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'monto', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'tipoMoneda', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'modoPago', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "pago_proveedor/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/new", name="pagoproveedor_new", methods={"GET","POST"})
     * @Template("pago_proveedor/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function new(Request $request, EntityManagerInterface $em): Array {
        $entity = new PagoProveedor();
        if ($request->query->has('id')) {
            $id = $request->query->get('id');
            $proveedor = $em->getRepository(Proveedor::class)->find($id);
            $entity->setProveedor($proveedor);
        }
        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/insertar", name="pagoproveedor_create", methods={"GET","POST"})
     * @Template("pago_proveedor/new.html.twig")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request, true);
    }

    /**
     * @Route("/{id}", name="pagoproveedor_show", methods={"GET"})
     */
    public function show(PagoProveedor $pagoProveedor): Response
    {
        return $this->render('pago_proveedor/show.html.twig', [
            'pagoProveedor' => $pagoProveedor,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="pagoproveedor_edit", methods={"GET"})
     * @Template("pago_proveedor/new.html.twig")
     */
    public function edit($id): array
    {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="pagoproveedor_update", methods={"POST","PUT"})
     */
    public function updateAction(Request $request, $id) {
        $response = parent::baseUpdateAction($request, $id, true);

        $em = $this->doctrine->getManager();
        $pago = $em->getRepository(PagoProveedor::class)->find($id);

        if ($pago) {
            $this->actualizarEstadoFacturasDePago($em, $pago);
            $em->flush();
        }

        return $response;
    }

    /**
     * @Route("/{id}/borrar", name="pagoproveedor_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|Type {
        $em = $this->doctrine->getManager();
        $pago = $em->getRepository(PagoProveedor::class)->find($id);

        $facturas = [];
        if ($pago) {
            foreach ($pago->getImputaciones() as $imputacion) {
                $factura = $imputacion->getFactura();
                if ($factura) {
                    $facturas[$factura->getId()] = $factura;
                }
            }
        }

        $response = parent::baseDeleteAction($id);

        foreach ($facturas as $factura) {
            $this->actualizarEstadoFactura($em, $factura);
        }

        if (!empty($facturas)) {
            $em->flush();
        }

        return $response;
    }

    function execPrePersistAction($entity, $request): bool {
        /** @var PagoProveedor $entity */
        $em = $this->doctrine->getManager();
        $this->procesarImputaciones($em, $entity, $request);

        return true;
    }

    /**
     * Reconstruye las imputaciones del pago a partir del request.
     * El monto se guarda en la moneda de la factura.
     */
    private function procesarImputaciones($em, PagoProveedor $entity, $request): void
    {
        $submittedData = $request->request->get('pago_proveedor', []);
        $submitted = $submittedData['imputaciones'] ?? [];

        $facturaRepo = $em->getRepository(Factura::class);
        $imputacionRepo = $em->getRepository(ImputacionPagoFactura::class);

        $existingById = [];
        if ($entity->getId()) {
            foreach ($imputacionRepo->findBy(['pagoProveedor' => $entity->getId()]) as $imp) {
                $existingById[$imp->getId()] = $imp;
            }
        }

        $submittedIds = [];

        // Limpiar la coleccion en memoria para reconstruirla desde el request
        foreach ($entity->getImputaciones()->toArray() as $imp) {
            $entity->getImputaciones()->removeElement($imp);
        }

        foreach ($submitted as $data) {
            $id = !empty($data['id']) ? (int) $data['id'] : null;

            if ($id && isset($existingById[$id])) {
                $imputacion = $existingById[$id];
                $submittedIds[] = $id;
            } else {
                $imputacion = new ImputacionPagoFactura();
            }

            $factura = !empty($data['factura']) ? $facturaRepo->find($data['factura']) : null;

            if (!$factura) {
                continue;
            }

            $montoStr = (string) ($data['monto'] ?? '0');
            if (strpos($montoStr, ',') !== false) {
                $montoStr = str_replace('.', '', $montoStr);
                $montoStr = str_replace(',', '.', $montoStr);
            }

            $imputacion->setFactura($factura);
            $imputacion->setMonto((float) $montoStr);
            $imputacion->setPagoProveedor($entity);

            $entity->addImputacion($imputacion);
            $em->persist($imputacion);
        }

        // Eliminar las imputaciones que ya no vienen en el request
        foreach ($existingById as $id => $imp) {
            if (!in_array($id, $submittedIds, true)) {
                $em->remove($imp);
            }
        }
    }

    function execPostPersistAction($em, $entity, $request): void {

        /** @var PagoProveedor $entity */

        $proveedor = $entity->getProveedor();

        $cuentaCorriente = $proveedor
            ->getCuentaCorrienteProveedor();

        if (!$cuentaCorriente) {
            return;
        }

        $tipoMovimiento = $em
            ->getRepository(TipoMovimiento::class)
            ->find(ConstanteTipoMovimiento::PAGO_FACTURA);

        if (!$tipoMovimiento) {
            return;
        }

        $monto = $entity->getMonto();

        $tipoMoneda = $entity->getTipoMoneda();

        // Pago = reduce deuda
        $montoMovimiento = $monto;

        // Actualiza saldo
        $cuentaCorriente->sumarSaldo(
            $montoMovimiento,
            $tipoMoneda
        );

        // Saldo posterior según moneda
        $saldoPosterior = $tipoMoneda === 'USD'
            ? $cuentaCorriente->getSaldoUsd()
            : $cuentaCorriente->getSaldoArs();

        $movimiento = new MovimientoProveedor();

        $movimiento->setCuentaCorrienteProveedor(
            $cuentaCorriente
        );

        $movimiento->setTipoMovimiento(
            $tipoMovimiento
        );

        $movimiento->setMonto(
            $montoMovimiento
        );

        $movimiento->setSaldoPosterior(
            $saldoPosterior
        );

        $movimiento->setTipoMoneda(
            $tipoMoneda
        );

        $movimiento->setDescripcion(
            'Pago proveedor'
        );

        $movimiento->setPagoProveedor(
            $entity
        );

        $em->persist($movimiento);

        $em->flush();

        $this->actualizarEstadoFacturasDePago($em, $entity);

        $em->flush();
    }

    /**
     * Recalcula el estado de todas las facturas imputadas por un pago.
     */
    private function actualizarEstadoFacturasDePago($em, PagoProveedor $pago): void
    {
        $facturas = [];
        foreach ($pago->getImputaciones() as $imputacion) {
            $factura = $imputacion->getFactura();
            if ($factura) {
                $facturas[$factura->getId()] = $factura;
            }
        }

        foreach ($facturas as $factura) {
            $this->actualizarEstadoFactura($em, $factura, $pago);
        }
    }

    /**
     * Recalcula y asigna el estado de una factura segun el total imputado.
     * Registra un historico solo cuando el estado cambia.
     */
    private function actualizarEstadoFactura($em, Factura $factura, ?PagoProveedor $pago = null): void
    {
        $totalPagado = (float) $em->getRepository(ImputacionPagoFactura::class)
            ->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.monto), 0)')
            ->join('i.pagoProveedor', 'p')
            ->where('i.factura = :factura')
            ->andWhere('p.fechaBaja IS NULL')
            ->setParameter('factura', $factura)
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalPagado <= 0) {
            $estadoId = ConstanteEstadoFactura::PENDIENTE;
        } elseif ($totalPagado >= ($factura->getTotal() - 0.01)) {
            $estadoId = ConstanteEstadoFactura::PAGA;
        } else {
            $estadoId = ConstanteEstadoFactura::PAGO_PARCIAL;
        }

        $estadoActualId = $factura->getEstadoFactura()
            ? $factura->getEstadoFactura()->getId()
            : null;

        if ($estadoActualId === $estadoId) {
            return;
        }

        $estado = $em->getReference(EstadoFactura::class, $estadoId);
        $factura->setEstadoFactura($estado);

        $historico = new EstadoFacturaHistorico();
        $historico->setFactura($factura);
        $historico->setEstado($estado);
        $historico->setFecha(new \DateTime());
        $historico->setMotivo($this->getMotivoEstado($estadoId));
        if ($pago) {
            $historico->setPagoProveedor($pago);
        }
        $factura->addHistoricoEstado($historico);
        $em->persist($historico);
    }

    private function getMotivoEstado(int $estadoId): string
    {
        switch ($estadoId) {
            case ConstanteEstadoFactura::PAGA:
                return 'Factura pagada';
            case ConstanteEstadoFactura::PAGO_PARCIAL:
                return 'Pago parcial';
            default:
                return 'Pendiente de pago';
        }
    }

    function execPreUpdateAction(
        $em,
        $entity,
        $request,
        $localVariablesArray
    ): bool {

        /** @var PagoProveedor $entity */

        $this->procesarImputaciones($em, $entity, $request);

        $movimiento = $em
            ->getRepository(MovimientoProveedor::class)
            ->findOneBy([
                'pagoProveedor' => $entity
            ]);

        if ($movimiento) {

            $cuentaCorriente = $entity
                ->getProveedor()
                ->getCuentaCorrienteProveedor();

            $nuevoMonto = $entity->getMonto();

            $montoAnterior = $movimiento->getMonto();

            $tipoMoneda = $entity->getTipoMoneda();

            // Revertir monto anterior
            $cuentaCorriente->sumarSaldo(
                -$montoAnterior,
                $tipoMoneda
            );

            // Aplicar nuevo monto
            $cuentaCorriente->sumarSaldo(
                $nuevoMonto,
                $tipoMoneda
            );

            $saldoPosterior = $tipoMoneda === 'USD'
                ? $cuentaCorriente->getSaldoUsd()
                : $cuentaCorriente->getSaldoArs();

            $movimiento->setMonto(
                $nuevoMonto
            );

            $movimiento->setSaldoPosterior(
                $saldoPosterior
            );

            $movimiento->setTipoMoneda(
                $tipoMoneda
            );

            $movimiento->setDescripcion(
                'Pago proveedor'
            );
        }

        return true;
    }

    /**
     * @Route("/lista/facturas", name="pagoproveedor_lista_facturas")
     */
    public function listaFacturasAction(Request $request): JsonResponse
    {
        $idProveedor = $request->request->get('id_entity');
        $em = $this->doctrine->getManager();

        $result = [];

        if (!$idProveedor) {
            return new JsonResponse($result);
        }

        $facturas = $em->getRepository(Factura::class)
            ->findBy(['proveedor' => $idProveedor], ['fecha' => 'DESC', 'id' => 'DESC']);

        foreach ($facturas as $factura) {
            $total = $factura->getTotal();

            $pagado = (float) $em->getRepository(ImputacionPagoFactura::class)
                ->createQueryBuilder('i')
                ->select('COALESCE(SUM(i.monto), 0)')
                ->join('i.pagoProveedor', 'p')
                ->where('i.factura = :factura')
                ->andWhere('p.fechaBaja IS NULL')
                ->setParameter('factura', $factura)
                ->getQuery()
                ->getSingleScalarResult();

            $saldo = $total - $pagado;

            if ($saldo <= 0.01) {
                continue;
            }

            $simbolo = $factura->getTipoMoneda() === 'USD' ? 'US$' : '$';

            $result[] = [
                'id' => $factura->getId(),
                'denominacion' => 'Factura #' . $factura->getNumeroFactura()
                    . ' (' . $factura->getTipoMoneda() . ') - Saldo: '
                    . $simbolo . ' ' . number_format($saldo, 2, ',', '.'),
                'moneda' => $factura->getTipoMoneda(),
                'saldo' => $saldo,
            ];
        }

        return new JsonResponse($result);
    }
}