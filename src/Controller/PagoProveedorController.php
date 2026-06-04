<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Constants\ConstanteTipoMovimiento;
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
        return parent::baseUpdateAction($request, $id, true);
    }

    /**
     * @Route("/{id}/borrar", name="pagoproveedor_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|Type {
        return parent::baseDeleteAction($id);
    }

    function execPrePersistAction($entity, $request): bool {
        return true;
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
    }

    function execPreUpdateAction(
        $em,
        $entity,
        $request,
        $localVariablesArray
    ): bool {

        /** @var PagoProveedor $entity */

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
}