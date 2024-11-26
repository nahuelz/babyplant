<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Mesada;
use App\Entity\PedidoProducto;
use App\Entity\PedidoProductoMesada;
use App\Entity\TipoMesada;
use App\Form\SalidaCamaraType;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
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
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorIcono', 'colorIcono');
        $rsm->addScalarResult('fechaSalidaCamara', 'fechaSalidaCamara');
        $rsm->addScalarResult('fechaSalidaCamaraReal', 'fechaSalidaCamaraReal');
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
        $em = $this->doctrine->getManager();

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
            /* @var $pedidoProductoMesada PedidoProductoMesada */
            foreach ($entity->getMesadas() as $pedidoProductoMesada){
                /* @var $mesada Mesada */
                $mesada = $pedidoProductoMesada->getMesada();
                $pedidoProductoMesada->setPedidoProducto($entity);

                // ACTUALIZO ESPACIO OCUPADO
                $mesada->getTipoMesada()->sumarOcupado($mesada->getCantidadBandejas());

                // ACTUALIZO TIPO PRODUCTO DE LA MESADA Y EN TIPO PRODUCTO GUARDO LA ULTIMA MESADA
                $mesada->getTipoMesada()->setTipoProducto($entity->getTipoProducto());
            }
            $em->flush();
            $message = 'Producto enviado a mesada correctamente.';
            $this->get('session')->getFlashBag()->set('success', $message);
            return $this->redirectToRoute('salidacamara_index');
        }
        return $this->render('pedidoproducto/show/salida_camara_show.html.twig', [
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
     * @param ObjectManager $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstado(ObjectManager $em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto) {

        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Producto enviado a invernaculo.');
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     *
     * @Route("/cambiar_fecha_salida_camara/", name="cambiar_fecha_salida_camara", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function cambiarFechaSalidaCamara(Request $request){

        $nuevaFechaSalidaCamaraParam = $request->get('nuevaFechaSalidaCamara');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $datetime = new DateTime();
        $nuevaFechaSalidaCamara = $datetime->createFromFormat('Y-m-d', $nuevaFechaSalidaCamaraParam);

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $fechaSalidaCamaraOriginal = $pedidoProducto->getFechaSalidaCamaraReal();
        $pedidoProducto->setFechaSalidaCamaraReal($nuevaFechaSalidaCamara);
        $em->flush();

        $message = 'Se modifico correctamente la fecha de salida a camara del producto '.$pedidoProducto->getNombreCompleto().' del dia: '.$fechaSalidaCamaraOriginal->format('d/m/Y').' al dia: '.$nuevaFechaSalidaCamara->format('d/m/Y');
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }
}