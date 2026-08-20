<?php

namespace App\Controller;

use App\Entity\Empleado;
use App\Entity\Prestamo;
use App\Form\PrestamoType;
use App\Service\PrintService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/prestamo")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class PrestamoController extends AbstractController
{
    private PrintService $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    #[Route('/empleado/{id}/nuevo', name: 'app_prestamo_new', methods: ['POST'])]
    public function new(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $prestamo = new Prestamo();
        $prestamo->setEmpleado($empleado);

        $form = $this->createForm(PrestamoType::class, $prestamo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($prestamo);
            $entityManager->flush();

            $this->addFlash('success', 'Préstamo registrado exitosamente.');
        } else {
            $this->addFlash('error', 'Error al registrar el préstamo. Verifique los datos.');
        }

        return $this->redirectToRoute('app_empleado_show', [
            'id' => $empleado->getId(),
            '_fragment' => 'tab-prestamos',
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_prestamo_delete', methods: ['POST'])]
    public function delete(Request $request, Prestamo $prestamo, EntityManagerInterface $entityManager): Response
    {
        $empleadoId = $prestamo->getEmpleado()->getId();

        if ($this->isCsrfTokenValid('delete_prestamo_' . $prestamo->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prestamo);
            $entityManager->flush();

            $this->addFlash('success', 'Préstamo eliminado correctamente.');
        } else {
            $this->addFlash('error', 'Token de seguridad inválido.');
        }

        return $this->redirectToRoute('app_empleado_show', [
            'id' => $empleadoId,
            '_fragment' => 'tab-prestamos',
        ]);
    }

    /**
     * Imprime el comprobante de préstamo.
     *
     * @Route("/{id}/imprimir", name="app_prestamo_imprimir", methods={"GET"})
     */
    public function imprimir(Prestamo $prestamo): Response
    {
        $html = $this->renderView('empleado/solicitud_prestamo_pdf.html.twig', [
            'empleado' => $prestamo->getEmpleado(),
            'prestamo' => $prestamo,
            'adelanto' => $prestamo,
        ]);

        $filename = 'ReciboPrestamo.pdf';
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }
}
