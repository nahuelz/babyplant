<?php

namespace App\Controller;

use App\Entity\Devolucion;
use App\Entity\Reventa;
use App\Form\ReventaType;
use App\Service\ReventaService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reventa")
 * @IsGranted("ROLE_DEVOLUCION")
 */
class ReventaController extends BaseController {

    /**
     * @Route("/", name="reventa_index", methods={"GET"})
     * @Template("reventa/index.html.twig")
     */
    public function index(): array
    {
        $em = $this->doctrine->getManager();

        $reventas = $em->getRepository(Reventa::class)
            ->findBy([], ['id' => 'DESC']);

        return array(
            'reventas' => $reventas,
            'page_title' => 'Reventas'
        );
    }

    /**
     * @Route("/new", name="reventa_new", methods={"GET","POST"})
     * @Template("reventa/new.html.twig")
     */
    public function new(Request $request, EntityManagerInterface $em, ReventaService $reventaService): array|RedirectResponse
    {
        $entity = new Reventa();

        // PRESELECCIONAR LA DEVOLUCION SI VIENE DESDE EL LISTADO DE DEVOLUCIONES
        if ($request->query->has('devolucion')) {
            $devolucion = $em->getRepository(Devolucion::class)->find($request->query->get('devolucion'));
            if ($devolucion) {
                $entity->setDevolucion($devolucion);
                $entity->setPrecioUnitario($devolucion->getPrecioUnitario());
            }
        }

        $form = $this->createForm(ReventaType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reventaService->crear($entity);

                $this->addFlash('success', 'La reventa fue registrada correctamente.');

                return $this->redirectToRoute('reventa_index');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'page_title' => 'Agregar Reventa'
        );
    }

    /**
     * @Route("/{id}", name="reventa_show", methods={"GET"}, requirements={"id"="\d+"})
     * @Template("reventa/show.html.twig")
     */
    public function show(Reventa $reventa): array
    {
        return array(
            'entity' => $reventa,
            'page_title' => 'Detalle Reventa'
        );
    }

    /**
     * Materializa la entrega de la reventa (crea Entrega + EntregaProducto de reventa).
     *
     * @Route("/{id}/entregar", name="reventa_entregar", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    public function entregar(Reventa $reventa, ReventaService $reventaService): RedirectResponse
    {
        try {
            $entrega = $reventaService->entregar($reventa);
            $this->addFlash('success', 'Se generó la ' . $entrega . ' para la ' . $reventa . '.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('reventa_index');
    }

    /**
     * @Route("/{id}/cancelar", name="reventa_cancelar", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    public function cancelar(Reventa $reventa, ReventaService $reventaService): RedirectResponse
    {
        try {
            $reventaService->cancelar($reventa);
            $this->addFlash('success', 'La reventa fue cancelada correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('reventa_index');
    }

    /**
     * @Route("/{id}/historico_estados", name="reventa_historico_estado", methods={"POST"})
     * @Template("reventa/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id): array
    {
        $em = $this->doctrine->getManager();
        $reventa = $em->getRepository(Reventa::class)->find($id);

        return array(
            'entity' => $reventa,
            'historicoEstados' => $reventa->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }
}
