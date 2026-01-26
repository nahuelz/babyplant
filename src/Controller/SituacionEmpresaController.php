<?php

namespace App\Controller;

use App\Entity\CuentaCorrientePedido;
use App\Entity\CuentaCorrienteReserva;
use App\Entity\CuentaCorrienteUsuario;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/situacion_empresa")
 * @IsGranted("ROLE_SITUACION_CLIENTE")
 */
class SituacionEmpresaController extends BaseController {

    /**
     * @Route("/", name="situacionempresa_index", methods={"GET"})
     * @Template("situacion_cliente/index.html.twig")
     * @IsGranted("ROLE_SITUACION_CLIENTE")
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
     * @Route("/{id}", name="situacionempresa_show", methods={"GET"})
     * @Template("situacion_cliente/empresa/show.html.twig")
     */
    public function empresaShow($id): Array {
        $em = $this->doctrine->getManager();
        $razonSocial = $em->getRepository("App\Entity\RazonSocial")->find($id);

        if (!$razonSocial) {
            throw $this->createNotFoundException("No se puede encontrar la razon social.");
        }

        foreach ($razonSocial->getClientes() as $cliente) {

            if ($cliente->getCuentaCorrienteUsuario() == null) {
                $cuentaCorrienteUsuario = new CuentaCorrienteUsuario();
                $cuentaCorrienteUsuario->setCliente($cliente);
                $cliente->setCuentaCorrienteUsuario($cuentaCorrienteUsuario);
                $em->persist($cuentaCorrienteUsuario);
                $em->flush();
            }

            foreach ($cliente->getPedidos() as $pedido) {
                if ($pedido->getCuentaCorrientePedido() == null) {
                    $cuentaCorrientePedido = new CuentaCorrientePedido();
                    $cuentaCorrientePedido->setPedido($pedido);
                    $pedido->setCuentaCorrientePedido($cuentaCorrientePedido);
                    $em->persist($cuentaCorrientePedido);
                    $em->flush();
                }
            }

            foreach ($cliente->getReservas() as $reserva) {
                if ($reserva->getCuentaCorrienteReserva() == null) {
                    $cuentaCorrienteReserva = new CuentaCorrienteReserva();
                    $cuentaCorrienteReserva->setReserva($reserva);
                    $reserva->setCuentaCorrienteReserva($cuentaCorrienteReserva);
                    $em->persist($cuentaCorrienteReserva);
                    $em->flush();
                }
            }

            $pagos = $em->createQueryBuilder()
                ->select('p')
                ->from(\App\Entity\Pago::class, 'p')
                ->join('p.remito', 'r')
                ->where('IDENTITY(r.cliente) = :idCliente')
                ->setParameter('idCliente', $cliente->getId())
                ->orderBy('p.fechaCreacion', 'DESC')
                ->getQuery()
                ->getResult();
        }

        $breadcrumbs = $this->getShowBaseBreadcrumbs($razonSocial);

        $parametros = array(
            'razonSocial' => $razonSocial,
            'pagos' => $pagos,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($razonSocial));
    }
}