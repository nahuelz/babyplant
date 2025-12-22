<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteAPI;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\RazonSocial;
use DateInterval;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Type;

#[Route('/razonsocial')]
class RazonSocialController extends BaseController
{
    /**
     * @Route("/", name="razonsocial_index", methods={"GET"})
     * @Template("razonsocial/index.html.twig")
     * @IsGranted("ROLE_TIPO_USUARIO")
     */
    public function index(): array
    {
        $razonSocialSelect = $this->getSelectService()->getRazonSocialFilter();

        return array(
            'razonSocialSelect' => $razonSocialSelect,
            'page_title' => 'Razon Socal'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="razonsocial_table", methods={"GET|POST"})
     * @IsGranted("ROLE_RESERVA")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $razonSocial = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('razonSocial', 'razonSocial');
        $rsm->addScalarResult('cuit', 'cuit');

        $nativeQuery = $em->createNativeQuery('call sp_index_situacion_empresa(?)', $rsm);

        $nativeQuery->setParameter(1, $razonSocial);

        $entities = $nativeQuery->getResult();

        return $this->render('razonsocial/index_table.html.twig', array('entities' => $entities));
    }

    #[Route('/new', name: 'razonsocial_new', methods: ['GET', 'POST'])]
    public function new(): Array
    {
        return $this->baseNewAction();
    }

    #[Route('/create/', name: 'razonsocial_create', methods: ['GET', 'POST'])]
    public function create(Request $request, $isAjaxCall = false): type|RedirectResponse|Response
    {
        return $this->baseCreateAction($request, $isAjaxCall);
    }

    /**
     * @Route("/{id}", name="razonsocial_show", methods={"GET"})
     * @Template("razonsocial/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="razonsocial_edit", methods={"GET","POST"})
     * @Template("razonsocial/new.html.twig")
     */
    public function edit($id): array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="razonsocial_update", methods={"PUT"})
     * @Template("razonsocial/new.html.twig")
     */
    public function update(Request $request, $id): RedirectResponse|type|Response
    {
        return parent::baseUpdateAction($request, $id);
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
            if (!$existeCUIT) {
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
                $msg = 'Error al crear Razon Social: El CUIT ingresado ya existe.';
                $id = $existeCUIT->getId();
                $nombre = $existeCUIT->getRazonSocial();
                $cuit = $existeCUIT->getCuit();
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
