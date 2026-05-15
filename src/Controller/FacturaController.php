<?php

namespace App\Controller;

use App\Entity\Factura;
use App\Entity\FacturaDetalle;
use App\Form\FacturaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Type;


/**
 * @Route("/factura")
 * @IsGranted("ROLE_GASTO")
 */
class FacturaController extends BaseController {

    /**
     * @Route("/", name="factura_index", methods={"GET"})
     * @Template("factura/index.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function index(): array
    {
        $conceptoSelect = $this->getSelectService()->getConceptoFilter();

        return array(
            'conceptoSelect' => $conceptoSelect,
            'indicadorFacturaData' => $this->getIndicadorFacturaData(),
            'page_title' => 'Facturas'
        );
    }

    /**
     * @Route("/index_table/", name="factura_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('numeroFactura', 'numeroFactura');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('montoTotal', 'montoTotal');
        $rsm->addScalarResult('tipoMoneda', 'tipoMoneda');

        $nativeQuery = $em->createNativeQuery('call sp_index_factura()', $rsm);

        $entities = $nativeQuery->getResult();

        return $this->render('factura/index_table.html.twig', array('entities' => $entities));
    }


    /**
     * @Route("/new", name="factura_new", methods={"GET","POST"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function new(): Array {
        $entity = new Factura();
        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/insertar", name="factura_create", methods={"GET","POST"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request, true);
    }

    #[Route('/{id}', name: 'factura_show', methods: ['GET'])]
    public function show(Factura $factura): Response
    {
        return $this->render('factura/show.html.twig', [
            'factura' => $factura,
        ]);
    }

    /**
     * @Route("/{id}/borrar", name="factura_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|type
    {
        return parent::baseDeleteAction($id);
    }


    /**
     * @Route("/{id}/edit", name="factura_edit", methods={"GET"})
     * @Template("factura/new.html.twig")
     * @IsGranted("ROLE_GASTO")
     */
    public function edit($id): array
    {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="factura_update", methods={"POST","PUT"})
     * @IsGranted("ROLE_GASTO")
     */
    public function updateAction(Request $request, $id)
    {
        return parent::baseUpdateAction($request, $id, true);
    }

    function execPrePersistAction($entity, $request): bool
    {
        /** @var Factura $entity */
        foreach ($entity->getDetalles() as $detalle) {
            $detalle->setFactura($entity);
        }

        return true;
    }

    function execPreUpdateAction($em, $entity, $request, $localVariablesArray): bool
    {
        /** @var Factura $entity */
        $submittedData = $request->request->get('factura', []);
        $submittedDetalles = $submittedData['detalles'] ?? [];

        $detalleRepo = $em->getRepository(FacturaDetalle::class);
        $conceptoRepo = $em->getRepository(\App\Entity\TipoConcepto::class);
        $subConceptoRepo = $em->getRepository(\App\Entity\TipoSubConcepto::class);

        $existingDetalles = $detalleRepo->findBy(['factura' => $entity->getId()]);
        $existingById = [];
        foreach ($existingDetalles as $d) {
            $existingById[$d->getId()] = $d;
        }

        $submittedIds = [];
        $newCollection = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($submittedDetalles as $data) {
            $id = !empty($data['id']) ? (int) $data['id'] : null;

            if ($id && isset($existingById[$id])) {
                $detalle = $existingById[$id];
                $submittedIds[] = $id;
            } else {
                $detalle = new FacturaDetalle();
            }

            $detalle->setConcepto(!empty($data['concepto']) ? $conceptoRepo->find($data['concepto']) : null);
            $detalle->setSubConcepto(!empty($data['subConcepto']) ? $subConceptoRepo->find($data['subConcepto']) : null);
            $detalle->setCantidad($data['cantidad'] ?? null);
            $detalle->setPrecioUnitario($data['precioUnitario'] ?? null);
            $detalle->setDescripcion($data['descripcion'] ?? null);
            $detalle->setFactura($entity);

            $newCollection->add($detalle);
        }

        foreach ($existingById as $id => $detalle) {
            if (!in_array($id, $submittedIds, true)) {
                $em->remove($detalle);
            }
        }

        $entity->setDetalles($newCollection);

        return true;
    }
    /**
     *
     * @return type
     */
    private function getIndicadorFacturaData()
    {
        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('cantidad', 'cantidad');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('iconClass', 'iconClass');

        $sql = '
        SELECT
            COUNT(g.id) AS cantidad,
            "success" AS colorClass,
            "success" AS color,
            "fa-money-bill-wave" AS iconClass
        FROM factura AS g
        WHERE g.fecha_baja IS NULL';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getSingleResult();
    }
}