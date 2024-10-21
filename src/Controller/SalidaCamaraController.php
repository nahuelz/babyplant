<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\PedidoProductoMesada;
use App\Form\PedidoProductoMesadaType;
use App\Form\RegistrationFormType;
use App\Form\SalidaCamaraType;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/salida_camara")
 */
class SalidaCamaraController extends BaseController
{

    /**
     * @Route("/", name="salidacamara_index", methods={"GET"})
     * @Template("salida_camara/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): array
    {
        $bread = $this->baseBreadcrumbs;
        $bread['Camara'] = null;

        return array(
            'breadcrumbs' => $bread,
            'page_title' => 'Camara'
        );
    }

    /**
     *
     * @Route("/index_table/", name="salida_camara_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_salida_camara';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idProducto', 'idProducto');
        $rsm->addScalarResult('nombreCompleto', 'nombreCompleto');
        $rsm->addScalarResult('nombreCorto', 'nombreCorto');
        $rsm->addScalarResult('cantidadTipoBandejabandeja', 'cantidadTipoBandejabandeja');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorIcono', 'colorIcono');
        $rsm->addScalarResult('fechaSiembra', 'fechaSiembra');
        $rsm->addScalarResult('descripcion', 'descripcion');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('className', 'className');

        $renderPage = "salida_camara/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="salida_camara_show", methods={"GET","POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function show(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        /* @var $entity PedidoProducto */
        $entity = $em->getRepository("App\Entity\PedidoProducto")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('No se puede encontrar la entidad.');
        }

        $form = $this->createMesadaForm($entity);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($entity->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::EN_INVERNACULO) {
                $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::EN_INVERNACULO);
                $this->cambiarEstado($em, $entity, $estado);
                $entity->setFechaSalidaCamaraReal(new \DateTime());
            }
            foreach ($entity->getMesadas() as $mesada){
                $mesada->setPedidoProducto($entity);
            }
            $em->flush();
            $message = 'Producto enviado a mesada correctamente.';
            $this->get('session')->getFlashBag()->set('success', $message);
            return $this->redirectToRoute('salidacamara_index');
        }
        return $this->render('salida_camara/producto_show.html.twig', [
            'form' => $form->createView(),
            'pedidoProducto' => $entity,
            'entity' => $entity
        ]);
    }

    public function createMesadaForm($entity){

        $form = $this->createForm(SalidaCamaraType::class, $entity, array(
            'action' => $this->generateUrl('salida_camara_show', array('id' => $entity->getId())),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return $form;


    }

    /**
     *
     * @Route("/guardar/", name="guardar", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function guardarSalidaCamara(Request $request){

        $form = $request->get('form');

        $em = $this->getDoctrine()->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setCodigoSobre($codSobre);
        if ($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::PLANIFICADO) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PLANIFICADO);
            $this->cambiarEstado($em, $pedidoProducto, $estado);
        }
        $em->flush();

        $message = 'Se guardo correctamente el orden de siembra';
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }

    /**
     *
     * @param type $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstado($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto) {

        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Producto planificado.');
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }
}