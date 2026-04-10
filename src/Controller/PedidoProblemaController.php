<?php

namespace App\Controller;


use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\GlobalConfig;
use DateInterval;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pedidoproblema")
 */
class PedidoProblemaController extends BaseController {

    /**
     * @Route("/", name="pedidoproblema_index", methods={"GET"})
     * @Template("pedido_problema/index.html.twig")
     */
    public function index(): array
    {
        // dd('aca'); die();

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();
        $origenSemillaSelect = $this->getSelectService()->getOrigenSemillaSelect();

        $em = $this->doctrine->getManager();
        $columnasOcultas = $em->getRepository('App\Entity\GlobalConfig')->find($this->getUser()->getId());

        if (!$columnasOcultas) {
            $columnasOcultas = new GlobalConfig();
            $columnasOcultas->setColumnasOcultas('1,6,10,11,13');
            $columnasOcultas->setUsuario($this->getUser());
            $em->persist($columnasOcultas);
            $em->flush();
        }

        return array(
            'columnasOcultas' => $columnasOcultas->getColumnasOcultas(),
            'indicadorEstadoData' => $this->getIndicadorEstadoData(),
            'actividadReciente' => $this->getActividadRecienteData(),
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'origenSemillaSelect' => $origenSemillaSelect,
            'page_title' => 'Pedidos generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="pedidoproblema_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
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
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('mesada', 'mesada');
        $rsm->addScalarResult('diasEnCamara', 'diasEnCamara');
        $rsm->addScalarResult('diasEnInvernaculo', 'diasEnInvernaculo');
        $rsm->addScalarResult('celular', 'celular');
        $rsm->addScalarResult('origenSemilla', 'origenSemilla');
        $rsm->addScalarResult('cantidadSemillas', 'cantidadSemillas');

        $nativeQuery = $em->createNativeQuery('call sp_index_pedido_problema(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('pedido_problema/index_table.html.twig', array('entities' => $entities));
    }


    /**
     *
     * @return type
     */
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
            ConstanteEstadoPedidoProducto::EN_INVERNACULO,
            ConstanteEstadoPedidoProducto::ENTREGADO,
            ConstanteEstadoPedidoProducto::CANCELADO
        ];

        $sql = '
            SELECT
                est.nombre AS estado,
                COUNT(pp.id) AS cantidad,
                est.color AS colorClass,
                est.color_icono AS color,
                CASE
                    WHEN est.id = 0 THEN "fa-circle-o-notch"
                    WHEN est.id = 1 THEN "fa-spinner"
                    WHEN est.id = 2 THEN "fa-clipboard-list"
                    WHEN est.id = 3 THEN "fa-leaf"
                    WHEN est.id = 4 THEN "fa-border-all"
                    WHEN est.id = 5 THEN "fa-home"
                    WHEN est.id = 6 THEN "fa-tasks"
                    WHEN est.id = 9 THEN "fa-check"
                    WHEN est.id = 10 THEN "fa-exclamation-triangle"
                    ELSE "fa-check"
                    END AS iconClass,
                est.id
            FROM pedido_producto AS pp
                     INNER JOIN estado_pedido_producto AS est ON pp.id_estado_pedido_producto = est.id
            WHERE pp.fecha_baja IS NULL
              AND est.codigo_interno IN (?)
            GROUP BY est.id';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $nativeQuery->getResult();
    }

    /**
     *
     * @return type
     */
    private function getActividadRecienteData() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('actividad', 'actividad');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('colorClass', 'colorClass');

        $sql = '
            SELECT
                p.id AS id,
                CONCAT_WS(" ", "El pedido producto nº", LPAD(pp.id, 5, 0), "cambió su estado a", est.nombre) AS actividad,
                h.fecha_creacion AS fecha,
                est.color_icono as colorClass
            FROM estado_pedido_producto_historico AS h
                     INNER JOIN pedido_producto AS pp ON pp.id = h.id_pedido_producto
                     INNER JOIN pedido AS p ON p.id = pp.id_pedido
                     INNER JOIN estado_pedido_producto AS est ON h.id_estado_pedido_producto = est.id
            WHERE pp.fecha_baja IS NULL
              AND h.fecha_baja IS NULL
            ORDER BY h.id DESC
            LIMIT 0, 20';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getResult();
    }

}