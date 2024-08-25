<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Form\GrupoType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/grupo")
 */
class GrupoController extends BaseController {

    /**
     * @Route("/", name="grupo_index", methods={"GET"})
     * @Template("grupo/index.html.twig")
     * @IsGranted("ROLE_GRUPO_VIEW")
     */
    public function index(): array {
        return parent::baseIndexAction();
    }

    /**
     * Tabla para grupo.
     *
     * @Route("/index_table/", name="grupo_table", methods={"GET|POST"})
     * @IsGranted("ROLE_GRUPO_VIEW")
     */
    public function indexTableAction(Request $request): Response {
        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'descripcion', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'roles', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false],
        ];

        return parent::baseIndexTableAction($request, $columnDefinition);
    }

    /**
     * @Route("/new", name="grupo_new", methods={"GET","POST"})
     * @Template("grupo/new.html.twig")
     * @IsGranted("ROLE_GRUPO_CREATE")
     */
    public function new(): array {
        return parent::baseNewAction();
    }

    /**
     * @Route("/insertar", name="grupo_create", methods={"GET","POST"})
     * @Template("grupo/new.html.twig")
     * @IsGranted("ROLE_GRUPO_CREATE")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="grupo_show", methods={"GET"})
     * @Template("grupo/show.html.twig")
     * @IsGranted("ROLE_GRUPO_VIEW")
     */
    public function show($id): array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="grupo_edit", methods={"GET","POST"})
     * @Template("grupo/new.html.twig")
     * @IsGranted("ROLE_GRUPO_EDIT")
     */
    public function edit($id): array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="grupo_update", methods={"PUT"})
     * @Template("grupo/new.html.twig")
     * @IsGranted("ROLE_GRUPO_EDIT")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="grupo_delete", methods={"GET"})
     * @IsGranted("ROLE_GRUPO_DELETE")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    /**
     *
     * @param string $entityFormTypeClassName
     * @param type $entity
     * @return type
     */
    protected function baseInitCreateCreateForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm(GrupoType::class, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_create'),
            'method' => 'POST',
            'roles' => $this->getParameter('security.role_hierarchy.roles')
        ));
    }

    /**
     *
     * @param type $entityFormTypeClassName
     * @param type $entity
     * @return FormInterface
     */
    protected function baseInitCreateEditForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm(GrupoType::class, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'roles' => $this->getParameter('security.role_hierarchy.roles')
        ));
    }

}
