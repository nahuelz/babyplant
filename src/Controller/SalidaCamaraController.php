<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoMesada;
use App\Entity\EstadoPedidoProducto;
use App\Entity\PedidoProducto;
use App\Entity\Mesada;
use App\Form\SalidaCamaraType;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/salida_camara")
 * @IsGranted("ROLE_SALIDA_CAMARA")
 */
class SalidaCamaraController extends BaseController
{

    /**
     * @Route("/", name="salidacamara_index", methods={"GET"})
     * @Template("salida_camara/index.html.twig")
     * @IsGranted("ROLE_SALIDA_CAMARA")
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
     * @IsGranted("ROLE_SALIDA_CAMARA")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_salida_camara';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombreCorto', 'nombreCorto');
        $rsm->addScalarResult('fechaSalidaCamara', 'fechaSalidaCamara');
        $rsm->addScalarResult('colorBandeja', 'colorBandeja');
        $rsm->addScalarResult('orden', 'orden');
        $rsm->addScalarResult('idPedido', 'idPedido');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
        $rsm->addScalarResult('fechaSalidaCamaraReal', 'fechaSalidaCamaraReal');
        $rsm->addScalarResult('className', 'className');
        $rsm->addScalarResult('producto', 'producto');
        $rsm->addScalarResult('tipoProducto', 'tipoProducto');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('pasaCamara', 'pasaCamara');
        $rsm->addScalarResult('camaraDestino', 'camaraDestino');
        $rsm->addScalarResult('observacion', 'observacion');
        $rsm->addScalarResult('observacionCamara', 'observacionCamara');

        $renderPage = "salida_camara/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="salida_camara_show", methods={"GET","POST"})
     * @IsGranted("ROLE_SALIDA_CAMARA")
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
                $this->estadoService->cambiarEstadoPedidoProducto($entity, $estado, 'EN INVERNACULO.');
                $entity->setFechaSalidaCamaraReal(new \DateTime());
            }
            $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::PENDIENTE);
            $this->estadoService->cambiarEstadoMesada($entity->getMesadaUno(), $estadoMesada, 'ENVIADO A INVERNACULO.');
            if ($entity->getMesadaDos()->getCantidadBandejas() != null) {
                $this->estadoService->cambiarEstadoMesada($entity->getMesadaDos(), $estadoMesada, 'ENVIADO A INVERNACULO.');
            } else {
                $entity->getMesadaDos()->setCantidadBandejas(0);
                $entity->setMesadaDos(null);
            }
            $entity->setPasaCamara(false);
            $em->flush();
            $this->actualizarMesadas($entity);
            $em->flush();
            $message = 'Producto enviado a mesada correctamente.';
            $this->get('session')->getFlashBag()->set('success', $message);
            return $this->redirectToRoute('salidacamara_index');
        }
        return $this->render('pedido_producto/show/salida_camara_show.html.twig', [
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

    private function actualizarMesadas(PedidoProducto $entity): void
    {
        if ($entity->getMesadaUno() != null) {
            $this->actualizarMesada($entity->getMesadaUno(), $entity);
        }

        if ($entity->getMesadaDos() != null) {
            $this->actualizarMesada($entity->getMesadaDos(), $entity);
        }
    }

    private function actualizarMesada(Mesada $mesada, PedidoProducto $entity): void
    {
        // SETEO EL PRODUCTO A LA MESADA
        $mesada->setPedidoProducto($entity);
        // ACTUALIZO TIPO PRODUCTO DE LA MESADA Y EN TIPO PRODUCTO GUARDO LA ULTIMA MESADA
        $mesada->getTipoMesada()->setTipoProducto($entity->getTipoProducto());
        // ACTUALIZO ESPACIO OCUPADO
        $mesada->getTipoMesada()->actualizarOcupado();
    }

    /**
     * @Route("/pasa-camara/", name="salida_camara_pasa_camara", methods={"POST"})
     * @IsGranted("ROLE_SALIDA_CAMARA")
     */
    public function pasaCamaraAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Obtener los datos del request
        $camaraDestino = $data['camaraDestino'] ?? null;
        $observaciones = $data['observaciones'] ?? null;
        $pedidoProductoId = $data['pedidoProductoId'] ?? null;

        if (!$camaraDestino || !$pedidoProductoId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Faltan parámetros requeridos'
            ], 400);
        }

        try {
            $entityManager = $this->getDoctrine()->getManager();
            
            // Obtener el pedido producto
            $pedidoProducto = $entityManager->getRepository(PedidoProducto::class)->find($pedidoProductoId);
            
            if (!$pedidoProducto) {
                throw $this->createNotFoundException('No se encontró el pedido producto con id ' . $pedidoProductoId);
            }

            // Actualizar los campos
            $pedidoProducto->setPasaCamara(true);
            $pedidoProducto->setCamaraDestino($camaraDestino);
            
            // Si hay observaciones, actualizarlas
            if ($observaciones) {
                $observacionActual = $pedidoProducto->getObservacionCamara() ? $pedidoProducto->getObservacionCamara() . "\n" : '';
                $pedidoProducto->setObservacionCamara($observacionActual . "[Pase de cámara] " . $observaciones);
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => 'success',
                'message' => 'Pase de cámara registrado correctamente'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => 'error',
                'message' => 'Error al procesar el pase de cámara: ' . $e->getMessage()
            ], 500);
        }
    }
}