<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use DateInterval;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/stock")
 * @IsGranted("ROLE_STOCK")
 */
class StockController extends BaseController {
    #[Route('/landing', name: 'stock_landing', methods: ['GET'])]
    public function landing(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STOCK');

        return $this->render('stock/landing.html.twig', array('productos' => $this->getProductos(),
            'page_title' => 'Stock'));
    }

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

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P30D'));
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
        $rsm->addScalarResult('cantidadBandejasDisponibles', 'cantidadBandejasDisponibles');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('fechaSiembraPedido', 'fechaSiembraPedido');
        $rsm->addScalarResult('fechaEntregaPedido', 'fechaEntregaPedido');
        $rsm->addScalarResult('fechaEntregaPedidoReal', 'fechaEntregaPedidoReal');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
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

    private function getIndicadorEstadoDataLanding() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('cantidad_total', 'cantidad_total');
        $rsm->addScalarResult('cantidad_hoy', 'cantidad_hoy');
        $rsm->addScalarResult('cantidad_en_7_dias', 'cantidad_en_7_dias');
        $rsm->addScalarResult('cantidad_en_14_dias', 'cantidad_en_14_dias');

        $estadosValidos = [
            ConstanteEstadoPedidoProducto::PENDIENTE,
            ConstanteEstadoPedidoProducto::PLANIFICADO,
            ConstanteEstadoPedidoProducto::SEMBRADO,
            ConstanteEstadoPedidoProducto::EN_CAMARA,
            ConstanteEstadoPedidoProducto::EN_INVERNACULO
        ];

        $sql = "
            SELECT
                v.nombre AS nombre,
                tp.color AS color,
                SUM(pp.cantidad_bandejas_reales) AS cantidad_total,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() 
                        THEN pp.cantidad_bandejas_reales 
                        ELSE 0 
                    END) AS cantidad_hoy,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 7 DAY
                        THEN pp.cantidad_bandejas_reales 
                        ELSE 0 
                    END) AS cantidad_en_7_dias,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 14 DAY
                        THEN pp.cantidad_bandejas_reales 
                        ELSE 0 
                    END) AS cantidad_en_14_dias
            FROM pedido_producto AS pp
            INNER JOIN estado_pedido_producto AS est 
                ON pp.id_estado_pedido_producto = est.id
            LEFT JOIN pedido AS p 
                ON pp.id_pedido = p.id
            LEFT JOIN usuario AS u 
                ON p.id_cliente = u.id
            LEFT JOIN tipo_variedad AS v 
                ON pp.id_tipo_variedad = v.id
            LEFT JOIN tipo_sub_producto AS sub
                ON v.id_tipo_sub_producto = sub.id
            LEFT JOIN tipo_producto AS tp
                ON sub.id_tipo_producto = tp.id
            WHERE pp.fecha_baja IS NULL
              AND u.apellido LIKE '%STOCK%'
              AND est.codigo_interno IN (?)
            GROUP BY v.id, v.nombre
            ORDER BY v.nombre;";

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $nativeQuery->getResult();
    }

    #[Route('/detalle-producto', name: 'detalle_producto', methods: ['GET'])]
    public function detalleProducto(Request $request): JsonResponse
    {
        $detalles = $this->getDetalleProducto($request);
        return new JsonResponse($detalles);
    }

    private function getDetalleProducto(Request $request){
        $em = $this->doctrine->getManager();

        $id_producto = $request->get('id_producto');

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('cantidad_total', 'cantidad_total');
        $rsm->addScalarResult('cantidad_hoy', 'cantidad_hoy');
        $rsm->addScalarResult('cantidad_en_7_dias', 'cantidad_en_7_dias');
        $rsm->addScalarResult('cantidad_en_14_dias', 'cantidad_en_14_dias');

        $estadosValidos = [
            ConstanteEstadoPedidoProducto::PENDIENTE,
            ConstanteEstadoPedidoProducto::PLANIFICADO,
            ConstanteEstadoPedidoProducto::SEMBRADO,
            ConstanteEstadoPedidoProducto::EN_CAMARA,
            ConstanteEstadoPedidoProducto::EN_INVERNACULO,
            ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL
        ];

        $sql = "SELECT
                    v.id AS id,
                    v.nombre AS nombre,
                    SUM(pp.cantidad_bandejas_disponibles) AS cantidad_total,
                    SUM(CASE 
                            WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() 
                            THEN pp.cantidad_bandejas_disponibles 
                            ELSE 0 
                        END) AS cantidad_hoy,
                    SUM(CASE 
                            WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 7 DAY
                            THEN pp.cantidad_bandejas_disponibles 
                            ELSE 0 
                        END) AS cantidad_en_7_dias,
                    SUM(CASE 
                            WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 14 DAY
                            THEN pp.cantidad_bandejas_disponibles 
                            ELSE 0 
                        END) AS cantidad_en_14_dias
                FROM pedido_producto AS pp
                INNER JOIN estado_pedido_producto AS est  ON pp.id_estado_pedido_producto = est.id
                LEFT JOIN pedido AS p  ON pp.id_pedido = p.id
                LEFT JOIN usuario AS u ON p.id_cliente = u.id
                LEFT JOIN tipo_variedad AS v ON pp.id_tipo_variedad = v.id
                LEFT JOIN tipo_sub_producto AS sub ON v.id_tipo_sub_producto = sub.id
                LEFT JOIN tipo_producto AS tp ON sub.id_tipo_producto = tp.id
                WHERE pp.fecha_baja IS NULL 
                  AND u.apellido LIKE '%STOCK%' 
                  AND est.codigo_interno IN (?) 
                  AND tp.id = (?)
                GROUP BY v.id, v.nombre
                ORDER BY v.nombre;";

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        $nativeQuery->setParameter(2, $id_producto);

        return $nativeQuery->getResult();
    }

    private function getProductos() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('cantidad_total', 'cantidad_total');
        $rsm->addScalarResult('cantidad_hoy', 'cantidad_hoy');
        $rsm->addScalarResult('cantidad_en_7_dias', 'cantidad_en_7_dias');
        $rsm->addScalarResult('cantidad_en_14_dias', 'cantidad_en_14_dias');

        $estadosValidos = [
            ConstanteEstadoPedidoProducto::PENDIENTE,
            ConstanteEstadoPedidoProducto::PLANIFICADO,
            ConstanteEstadoPedidoProducto::SEMBRADO,
            ConstanteEstadoPedidoProducto::EN_CAMARA,
            ConstanteEstadoPedidoProducto::EN_INVERNACULO,
            ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL

        ];

        $sql = "
            SELECT
                tp.id AS id,
                tp.nombre AS nombre,
                tp.color AS color,
                SUM(pp.cantidad_bandejas_disponibles) AS cantidad_total,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() 
                        THEN pp.cantidad_bandejas_disponibles 
                        ELSE 0 
                    END) AS cantidad_hoy,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 7 DAY
                        THEN pp.cantidad_bandejas_disponibles 
                        ELSE 0 
                    END) AS cantidad_en_7_dias,
                SUM(CASE 
                        WHEN DATE(pp.fecha_entrega_pedido_real) <= CURDATE() + INTERVAL 14 DAY
                        THEN pp.cantidad_bandejas_disponibles 
                        ELSE 0 
                    END) AS cantidad_en_14_dias
            FROM pedido_producto AS pp
            INNER JOIN estado_pedido_producto AS est  ON pp.id_estado_pedido_producto = est.id
            LEFT JOIN pedido AS p  ON pp.id_pedido = p.id
            LEFT JOIN usuario AS u ON p.id_cliente = u.id
            LEFT JOIN tipo_variedad AS v ON pp.id_tipo_variedad = v.id
            LEFT JOIN tipo_sub_producto AS sub ON v.id_tipo_sub_producto = sub.id
            LEFT JOIN tipo_producto AS tp ON sub.id_tipo_producto = tp.id
            WHERE pp.fecha_baja IS NULL 
              AND u.apellido LIKE '%STOCK%' 
              AND est.codigo_interno IN (?)
            GROUP BY tp.id, tp.nombre, tp.color
            ORDER BY tp.nombre;";

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $nativeQuery->getResult();
    }
}