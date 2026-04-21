<?php

namespace App\Controller;


use App\Entity\GlobalConfig;
use App\Entity\PedidoProducto;
use App\Entity\TipoRevision;
use App\Entity\TipoSolucion;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();
        $origenSemillaSelect = $this->getSelectService()->getOrigenSemillaSelect();

        $em = $this->doctrine->getManager();
        $columnasOcultas = $em->getRepository('App\Entity\GlobalConfig')->find($this->getUser()->getId());

        if (!$columnasOcultas) {
            $columnasOcultas = new GlobalConfig();
            $columnasOcultas->setColumnasOcultasProblemas('1,6,10,11,13');
            $columnasOcultas->setUsuario($this->getUser());
            $em->persist($columnasOcultas);
            $em->flush();
        }

        return array(
            'columnasOcultas' => $columnasOcultas->getColumnasOcultasProblemas(),
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
        $tieneProblema = $request->get('tieneProblema') !== null ? filter_var($request->get('tieneProblema'), FILTER_VALIDATE_BOOLEAN) : true;
        $codigoSobre = $request->get('codigoSobre') ?: NULL;
        $tieneProblema == false ? $tieneProblema = null : $tieneProblema = true;
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
        $rsm->addScalarResult('observacionProblema', 'observacionProblema');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('tieneProblema', 'tieneProblema');
        $rsm->addScalarResult('tieneSolucion', 'tieneSolucion');
        $rsm->addScalarResult('tipoRevision', 'tipoRevision');

        $nativeQuery = $em->createNativeQuery('call sp_index_pedido_problema(?,?,?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);
        $nativeQuery->setParameter(4, $tieneProblema);
        $nativeQuery->setParameter(5, $codigoSobre);

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

        $sql = '
        SELECT
            1 AS id,
            "Productos Con Problemas" AS estado,
            COUNT(pp.id) AS cantidad,
            "label-light-warning" AS colorClass,
            "warning" AS color,
            "fa-exclamation-triangle" AS iconClass
        FROM pedido_producto AS pp
        WHERE pp.fecha_baja IS NULL
          AND pp.tiene_problema = 1';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

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

    /**
     * @Route("/save_columns/", name="problema_save_columns", methods={"GET","POST"})
     */
    public function guardarColumnas(Request $request): Response {

        $em = $this->doctrine->getManager();

        /* @var $columnas GlobalConfig */
        $columnas = $em->getRepository('App\Entity\GlobalConfig')->find($this->getUser()->getId());

        if (!$columnas) {
            throw $this->createNotFoundException('No se configuraron las columnas visibles en la base de datos.');
        }

        $columnasOcultas = json_decode($request->request->get('columns'), false);
        $columnas->setColumnasOcultasProblemas(implode(",", $columnasOcultas));
        $em->flush();

        $message = 'Se guardó la configuración de columnas.';
        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => $message,
            'statusCode' => 200,
            'statusText' => 'OK'
        )));

        return $response;
    }

    #[Route('/{id}/marcar-problema', name: 'pedido_producto_marcar_problema', methods: ['POST'])]
    public function marcarProblema(Request $request, PedidoProducto $pedidoProducto, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $observacionProblema = $data['observacionProblema'] ?? null;
        $revisionId = $data['revision'] ?? null;

        if (!$revisionId) {
            return $this->json([
                'success' => false,
                'message' => 'Debe enviar un problema'
            ], 400);
        }

        $tipoRevision = $entityManager->getRepository(TipoRevision::class)->find($revisionId);

        $pedidoProducto->setTieneProblema(true);
        $pedidoProducto->setObservacionProblema($observacionProblema);
        $pedidoProducto->setRevision($tipoRevision);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'tieneProblema' => $pedidoProducto->isTieneProblema(),
            'observacionProblema' => $pedidoProducto->getObservacionProblema()
        ]);
    }

    #[Route('/{id}/quitar-problema', name: 'pedido_producto_quitar_problema', methods: ['POST'])]
    public function quitarProblema(
        Request $request,
        PedidoProducto $pedidoProducto,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        $pedidoProducto->setTieneProblema(false);
        $pedidoProducto->setTieneSolucion(false);
        $pedidoProducto->setObservacionProblema(null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'tieneProblema' => $pedidoProducto->isTieneProblema(),
            'observacionProblema' => $pedidoProducto->getObservacionProblema()
        ]);
    }

    #[Route('/{id}/marcar-solucion', name: 'pedido_producto_marcar_solucion', methods: ['POST'])]
    public function marcarSolucion(
        Request $request,
        PedidoProducto $pedidoProducto,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $solucionId = $data['solucion'] ?? null;
        $observacion = $data['observacion'] ?? null;

        if (!$solucionId) {
            return $this->json([
                'success' => false,
                'message' => 'Debe enviar una revisión'
            ], 400);
        }

        $tipoSolucion = $entityManager->getRepository(Tiposolucion::class)
            ->find($solucionId);

        if (!$tipoSolucion) {
            return $this->json([
                'success' => false,
                'message' => 'Tipo de revisión no encontrado'
            ], 404);
        }

        // 🔥 acá cambiamos: ahora seteás la entidad
        $pedidoProducto->setSolucion($tipoSolucion);
        $pedidoProducto->setTieneSolucion(true);
        $pedidoProducto->setObservacionSolucion($observacion);

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'revision' => [
                'id' => $tipoSolucion->getId(),
                'nombre' => $tipoSolucion->getNombre()
            ],
            'observacionRevision' => $pedidoProducto->getObservacionSolucion()
        ]);
    }

    #[Route('/{id}/quitar-solucion', name: 'pedido_producto_quitar_solucion', methods: ['POST'])]
    public function quitarSolucion(
        PedidoProducto $pedidoProducto,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        $pedidoProducto->setSolucion(null);
        $pedidoProducto->setTieneSolucion(false);
        $pedidoProducto->setObservacionSolucion(null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Solución eliminada correctamente',
            'solucion' => null,
            'observacionSolucion' => null
        ]);
    }

}