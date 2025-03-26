<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\Mesada;
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
     * @IsGranted("ROLE_CAMARA")
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
     * @IsGranted("ROLE_CAMARA")
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
     * @IsGranted("ROLE_CAMARA")
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
            $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::PENDIENTE);
            $this->cambiarEstadoMesada($em, $entity->getMesadaUno(), $estadoMesada);
            if ($entity->getMesadaDos()->getCantidadBandejas() != null) {
                $this->cambiarEstadoMesada($em, $entity->getMesadaDos(), $estadoMesada);
            } else {
                $entity->setMesadaDos(null);
            }
            $em->flush();
            $this->actualizarMesadas($entity);
            $em->flush();
            $message = 'Producto enviado a mesada correctamente.';
            $this->get('session')->getFlashBag()->set('success', $message);
            return $this->redirectToRoute('salidacamara_index');
        }
        return $this->render('pedidoproducto/show/salida_camara_show.html.twig', [
            'form' => $form->createView(),
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
     * @param ObjectManager $em
     * @param Mesada $mesada
     * @param EstadoMesada $estadoMesada
     */
    private function cambiarEstadoMesada(ObjectManager $em, Mesada $mesada, EstadoMesada $estadoMesada) {

        $mesada->setEstado($estadoMesada);
        $estadoMesadaHistorico = new EstadoMesadaHistorico();
        $estadoMesadaHistorico->setMesada($mesada);
        $estadoMesadaHistorico->setFecha(new DateTime());
        $estadoMesadaHistorico->setEstado($estadoMesada);
        $estadoMesadaHistorico->setCantidadBandejas($mesada->getCantidadBandejas());
        $estadoMesadaHistorico->setMotivo('Producto enviado a invernaculo.');
        $mesada->addHistoricoEstado($estadoMesadaHistorico);

        $em->persist($estadoMesadaHistorico);
    }

    private function actualizarMesadas(PedidoProducto $entity) {
        if ($entity->getMesadaUno() != null) {
            $this->actualizarMesada($entity->getMesadaUno(), $entity);
        }

        if ($entity->getMesadaDos() != null) {
            $this->actualizarMesada($entity->getMesadaDos(), $entity);
        }
    }

    private function actualizarMesada(Mesada $mesada, PedidoProducto $entity)
    {
        // SETEO EL PRODUCTO A LA MESADA
        $mesada->setPedidoProducto($entity);
        // ACTUALIZO TIPO PRODUCTO DE LA MESADA Y EN TIPO PRODUCTO GUARDO LA ULTIMA MESADA
        $mesada->getTipoMesada()->setTipoProducto($entity->getTipoProducto());
        // ACTUALIZO ESPACIO OCUPADO
        $mesada->getTipoMesada()->actualizarOcupado();
    }
}