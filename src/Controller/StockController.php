<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stock')]
class StockController extends BaseController {
    #[Route('/', name: 'stock_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STOCK');

        $clienteSelect = $this->getSelectService()->getClienteFilterStock();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();

        return $this->render('stock/index.html.twig', array('indicadorEstadoData' => $this->getIndicadorEstadoData(),
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Stock'));
    }

    #[Route('/index_table/', name: 'stock_table', methods: ['GET|POST'])]
    public function indexTableAction(Request $request): Response {

        $this->denyAccessUnlessGranted('ROLE_STOCK');

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : new DateTime();
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idProducto', 'idProducto');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('nombreVariedad', 'nombreVariedad');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('nombreSubProducto', 'nombreSubProducto');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('fechaSiembraPedido', 'fechaSiembraPedido');
        $rsm->addScalarResult('fechaEntregaPedido', 'fechaEntregaPedido');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('mesada', 'mesada');
        $rsm->addScalarResult('diasEnCamara', 'diasEnCamara');
        $rsm->addScalarResult('diasEnInvernaculo', 'diasEnInvernaculo');
        $rsm->addScalarResult('celular', 'celular');

        $nativeQuery = $em->createNativeQuery('call sp_index_stock(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('stock/index_table.html.twig', array('entities' => $entities));
    }

    private function getIndicadorEstadoData() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('cantidad', 'cantidad');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('iconClass', 'iconClass');

        $estadosValidos = [
            ConstanteEstadoPedidoProducto::PENDIENTE,
            ConstanteEstadoPedidoProducto::PLANIFICADO,
            ConstanteEstadoPedidoProducto::SEMBRADO,
            ConstanteEstadoPedidoProducto::EN_CAMARA,
            ConstanteEstadoPedidoProducto::EN_INVERNACULO
        ];

        $sql = "
            SELECT
                est.nombre AS estado,
                SUM(pp.cantidad_bandejas_reales) AS cantidad,
                est.color AS colorClass,
                est.color_icono AS color,
                CASE
                    WHEN est.id = 1 THEN 'fa-spinner'
                    WHEN est.id = 2 THEN 'fa-clipboard-list'
                    WHEN est.id = 3 THEN 'fa-leaf'
                    WHEN est.id = 4 THEN 'fa-border-all'
                    WHEN est.id = 5 THEN 'fa-home'
                    END AS iconClass,
                est.id
            FROM pedido_producto AS pp
                INNER JOIN estado_pedido_producto AS est ON pp.id_estado_pedido_producto = est.id
                LEFT JOIN pedido AS p ON pp.id_pedido = p.id
                LEFT JOIN usuario AS u ON p.id_cliente = u.id
            WHERE pp.fecha_baja IS NULL
              AND u.apellido LIKE '%STOCK%'
              AND est.codigo_interno IN (?)
            GROUP BY est.id";

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $nativeQuery->getResult();
    }
}