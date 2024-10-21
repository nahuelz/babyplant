<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoPedido;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\EstadoPedido;
use App\Entity\EstadoPedidoHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\Usuario;
use App\Form\RegistrationFormType;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pedido")
 */
class PedidoController extends BaseController {

    /**
     * @Route("/", name="pedido_index", methods={"GET"})
     * @Template("pedido/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): Array {

        $clienteSelect = $this->getSelectService()->getClienteFilter();

        $bread = $this->baseBreadcrumbs;
        $bread['Pedidos generados'] = null;

        return array(
            'clienteSelect' => $clienteSelect,
            'breadcrumbs' => $bread,
            'page_title' => 'Pedidos generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="pedido_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->getDoctrine()->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();

        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idProducto', 'idProducto');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('producto', 'producto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('fechaSiembra', 'fechaSiembra');
        $rsm->addScalarResult('fechaEntrega', 'fechaEntrega');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('mesada', 'mesada');

        $nativeQuery = $em->createNativeQuery('call sp_index_pedido(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('pedido/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="pedido_new", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }


    /**
     * @Route("/insertar", name="pedido_create", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="pedido_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="pedido_edit", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="pedido_update", methods={"PUT"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="pedido_delete", methods={"GET"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    function execPrePersistAction($entity, $request): bool {
        /** @var Pedido $entity */
        $em = $this->getDoctrine()->getManager();
        $estadoPedido = $em->getRepository(EstadoPedido::class)->findOneByCodigoInterno(ConstanteEstadoPedido::NUEVO);
        $estadoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PENDIENTE);
        $this->cambiarEstado($em, $entity, $estadoPedido, $estadoProducto);

        /** @var PedidoProducto $pedidoProducto */
        foreach ($entity->getPedidosProductos() as $pedidoProducto){
            $pedidoProducto->setFechaEntregaPedido($pedidoProducto->getFechaEntrega());
            $pedidoProducto->setFechaSiembraPedido($pedidoProducto->getFechaSiembra());
            $pedidoProducto->setPedido($entity);
        }

        return true;
    }

    /**
     *
     * @param type $em
     * @param Pedido $pedido
     * @param type $estado
     * @param type $motivo
     */
    private function cambiarEstado($em, Pedido $pedido, $estadoPedido, $estadoProducto) {


        $pedido->setEstado($estadoPedido);
        /* SETEO EL ESTADO DEL PEDIDO */
        $estadoPedidoHistorico = new EstadoPedidoHistorico();
        $estadoPedidoHistorico->setPedido($pedido);
        $estadoPedidoHistorico->setFecha(new DateTime());
        $estadoPedidoHistorico->setEstado($estadoPedido);
        $estadoPedidoHistorico->setMotivo('Creacion del pedido.');
        $pedido->addHistoricoEstado($estadoPedidoHistorico);

        $em->persist($estadoPedidoHistorico);

        /* SETEO EL ESTADO DE CADA UNO DE LOS PRODUCTOS */
        foreach ($pedido->getPedidosProductos() as $pedidosProducto) {
            $pedidosProducto->setEstado($estadoProducto);
            $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
            $estadoPedidoProductoHistorico->setPedidoProducto($pedidosProducto);
            $estadoPedidoProductoHistorico->setFecha(new DateTime());
            $estadoPedidoProductoHistorico->setEstado($estadoProducto);
            $estadoPedidoProductoHistorico->setMotivo('Creacion del pedido.');
            $pedidosProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

            $em->persist($estadoPedidoProductoHistorico);
        }
    }

    /**
     * @Route("/{id}/historico_estados", name="pedido_historico_estado", methods={"POST"})
     * @Template("pedido/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id) {

        $em = $this->getDoctrine()->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        return array(
            'entity' => $pedidoProducto,
            'historicoEstados' => $pedidoProducto->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersNewAction($entity): Array {
        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register_ajax'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return [
            'preserve_values' => true,
            'registrationForm' => $form->createView()
        ];
    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersEditAction($entity): Array {
        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register_ajax'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return [
            'preserve_values' => true,
            'registrationForm' => $form->createView()
        ];
    }

    /**
     * @Route("/{id}/pedido_producto", name="pedido_producto", methods={"GET","POST"})
     * @Template("pedido/producto_show.html.twig")
     */
    public function showPedidoProductoAction($id) {

        $em = $this->getDoctrine()->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        return array(
            'entity' => $pedidoProducto,
            'page_title' => 'Pedido Producto'
        );
    }
}
