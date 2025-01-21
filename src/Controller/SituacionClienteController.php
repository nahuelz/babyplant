<?php

namespace App\Controller;

use App\Entity\CuentaCorriente;
use App\Entity\ModoPago;
use App\Entity\Movimiento;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/situacion_cliente")
 */
class SituacionClienteController extends BaseController {

    /**
     * @Route("/", name="situacioncliente_index", methods={"GET"})
     * @Template("situacion_cliente/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): array
    {
        $clienteSelect = $this->getSelectService()->getClienteFilter();

        return array(
            'clienteSelect' => $clienteSelect,
            'page_title' => 'Situacion Cliente'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="situacion_cliente_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('apellido', 'apellido');
        $rsm->addScalarResult('cuit', 'cuit');
        $rsm->addScalarResult('telefono', 'telefono');
        $rsm->addScalarResult('celular', 'celular');
        $rsm->addScalarResult('razonSocial', 'razonSocial');

        $nativeQuery = $em->createNativeQuery('call sp_index_situacion_cliente(?)', $rsm);

        $nativeQuery->setParameter(1, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('situacion_cliente/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="situacion_cliente_new", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }


    /**
     * @Route("/insertar", name="situacion_cliente_create", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="situacioncliente_show", methods={"GET"})
     * @Template("situacion_cliente/show.html.twig")
     */
    public function show($id): Array {
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException("No se puede encontrar el usuario.");
        }

        if ($entity->getCuentaCorriente() == null){
            $cuentaCorriente = new CuentaCorriente();
            $cuentaCorriente->setCliente($entity);
            $entity->setCuentaCorriente($cuentaCorriente);
            $em->persist($entity);
            $em->flush();
        }

        $breadcrumbs = $this->getShowBaseBreadcrumbs($entity);

        $parametros = array(
            'entity' => $entity,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($entity));
    }

    /**
     * @Route("/{id}/edit", name="situacion_cliente_edit", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="situacion_cliente_update", methods={"PUT"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="situacion_cliente_delete", methods={"GET"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }
    /**
     * @Route("/movimiento/new", name="movimiento_new", methods={"GET","POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function movimientoNewAction(Request $request): Response
    {
        $entity = new Movimiento();
        $form = $this->baseCreateCreateForm($entity);
        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
            'modal' => true
        ]);
    }


    /**
     * @Route("/movimiento/create", name="situacioncliente_create", methods={"GET","POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function movimientoCreateAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $id = $request->request->get('id');

        /* @var $entity CuentaCorriente */
        $entity = $em->getRepository("App\Entity\CuentaCorriente")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('No se puede encontrar la entidad.');
        }

        $monto = $request->request->get('monto');
        $modoPagoValue = $request->request->get('modoPago');
        $descripcion = $request->request->get('descripcion');

        if ((isset($modoPagoValue) and $modoPagoValue !== '') and (isset($monto) and $monto !== '')) {
            $movimiento = new Movimiento();
            $movimiento->setMonto($monto);
            $modoPago = $em->getRepository(ModoPago::class)->findOneByCodigoInterno($modoPagoValue);
            $movimiento->setModoPago($modoPago);
            $movimiento->setDescripcion($descripcion);
            $entity->addMovimiento($movimiento);
            $em->persist($movimiento);
            $em->flush();
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'SALDO AGREGADO',
            'statusCode' => 200,
            'statusText' => 'OK'
        )));

        return $response;

    }

}