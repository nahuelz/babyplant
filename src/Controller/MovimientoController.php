<?php

namespace App\Controller;

use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/movimiento')]
class MovimientoController extends BaseController {
    #[Route('/', name: 'movimiento_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SITUACION_CLIENTE');

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();


        return $this->render('movimiento/index.html.twig', [
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Movimientos generados'
        ]);
    }

    #[Route('/index_table/', name: 'movimiento_table', methods: ['GET|POST'])]
    public function indexTableAction(Request $request): Response {

        $this->denyAccessUnlessGranted('ROLE_SITUACION_CLIENTE');

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : new DateTime();
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('monto', 'monto');
        $rsm->addScalarResult('tipoMovimiento', 'tipoMovimiento');
        $rsm->addScalarResult('modoPago', 'modoPago');
        $rsm->addScalarResult('nombreCliente', 'nombreCliente');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('idCliente', 'idCliente');

        $nativeQuery = $em->createNativeQuery('call sp_index_movimiento(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('movimiento/index_table.html.twig', array('entities' => $entities));
    }
}