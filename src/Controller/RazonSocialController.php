<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteAPI;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\RazonSocial;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Type;

#[Route('/razonSocial')]
class RazonSocialController extends BaseController
{
    #[Route('/', name: 'razonsocial_index', methods: ['GET'])]
    public function index(): Array
    {
        return $this->baseIndexAction();
    }

    /**
     *
     * @Route("/index_table/", name="razonsocial_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_razonsocial';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('razonsocial', 'razonsocial');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'razonsocial', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "razonsocial/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_razonsocial_new', methods: ['GET', 'POST'])]
    public function new(): Array
    {
        return $this->baseNewAction();
    }

    #[Route('/create/', name: 'razonsocial_create', methods: ['GET', 'POST'])]
    public function create(Request $request, $isAjaxCall = false): type|RedirectResponse|Response
    {
        return $this->baseCreateAction($request, $isAjaxCall);
    }

    #[Route('/{id}', name: 'app_razonsocial_show', methods: ['GET'])]
    public function show($id): Array
    {
        return $this->baseShowAction($id);
    }

    #[Route('/{id}/edit', name: 'app_razonsocial_edit', methods: ['GET', 'POST'])]
    public function edit($id): Array
    {
        return $this->baseEditAction($id);
    }

    #[Route('/{id}', name: 'app_razonsocial_delete', methods: ['POST'])]
    public function delete($id): Response
    {
        return $this->baseDeleteAction($id);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_razonsocial_habilitar_deshabilitar", methods={"GET"})
     */
    public function RazonSocialHabilitarDeshabilitar($id): RedirectResponse
    {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(RazonSocial::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente a la razon social");
        return $this->redirectToRoute('razonsocial_index');
    }

    #[Route('/newAjax/', name: 'app_razonsocial_new_ajax', methods: ['GET', 'POST'])]
    public function newAjax(Request $request): Response
    {
        $entity = new RazonSocial();
        $form = $this->baseCreateCreateForm($entity);
        return $this->render('razonsocial/_form.html.twig', [
            'razonSocialForm' => $form->createView(),
            'entity' => $entity,
            'modal' => true
        ]);
    }


    #[Route('/createAjax/', name: 'app_razonsocial_create_ajax', methods: ['GET', 'POST'])]
    public function createAjax(Request $request): type|RedirectResponse|Response
    {
        $razonSocial = $request->get('razonSocial');
        $cuitParam = $request->get('cuit');
        $nombre = '';
        $cuit = '';
        $response = new Response();
        if ($cuitParam) {
            $em = $this->doctrine->getManager();
            $existeCUIT = $em->getRepository(RazonSocial::class)->findOneBy(array('cuit' => $cuitParam));
            $existeRazonSocial = $em->getRepository(RazonSocial::class)->findOneBy(array('razonSocial' => $razonSocial));
            if (!$existeCUIT and !$existeRazonSocial) {
                $entity = new RazonSocial();
                $entity->setCuit($cuitParam);
                $entity->setRazonSocial($razonSocial);
                $em->persist($entity);
                $em->flush();
                $msg = 'Razon Social Creada.';
                $id = $entity->getId();
                $nombre = $entity->getRazonSocial();
                $cuit = $entity->getCuit();
            }else{
                $msg = 'Error al crear Razon Social: El CUIT o RazonSocial ingresado ya existe.';
                if ($existeCUIT) {
                    $id = $existeCUIT->getId();
                    $nombre = $existeCUIT->getRazonSocial();
                    $cuit = $existeCUIT->getCuit();
                }else{
                    $id = $existeRazonSocial->getId();
                    $nombre = $existeRazonSocial->getRazonSocial();
                    $cuit = $existeRazonSocial->getCuit();
                }
            }
        } else {
            $msg = 'Error al crear Razon Social: Debe ingresar un CUIT.';
            $id = '';
        }
        $response->setContent(json_encode(array(
            'message' => $msg,
            'statusCode' => Response::HTTP_OK,
            'statusText' => ConstanteAPI::STATUS_TEXT_OK,
            'id' => $id,
            'nombre' => $nombre,
            'cuit' => $cuit
        )));
        return $response;

    }

}
