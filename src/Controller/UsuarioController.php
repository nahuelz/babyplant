<?php

namespace App\Controller;

use App\Entity\RazonSocial;
use App\Form\RazonSocialType;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Usuario;
use App\Form\UsuarioType;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Form\FormInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @Route("/usuario")
 * @IsGranted("ROLE_USUARIO_VIEW")
 */
class UsuarioController extends BaseController {

    /**
     * @Route("/", name="usuario_index", methods={"GET"})
     * @Template("usuario/index.html.twig")
     */
    public function index(): array {
        $extraParams = [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ];

        return parent::baseIndexAction($extraParams);
    }

    /**
     * Tabla para usuario.
     *
     * @Route("/index_table/", name="usuario_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_usuario';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('username', 'username');
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('apellido', 'apellido');
        $rsm->addScalarResult('celular', 'celular');
        $rsm->addScalarResult('grupos', 'grupos');
        $rsm->addScalarResult('habilitado', 'habilitado');
        $rsm->addScalarResult('id_tipo_usuario', 'id_tipo_usuario');

        $columnDefinition = [
            ['field' => 'username', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'apellido', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'celular', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'grupos', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "usuario/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage, [], [], true);
    }

    protected function getAditionalCustomWhereSQL($aliasTable, $request) {
        return "($aliasTable.id_tipo_usuario != " . \App\Entity\Constants\ConstanteTipoUsuario::CLIENTE . " OR $aliasTable.id_tipo_usuario IS NULL)";
    }

    /**
     * @Route("/{id}", name="usuario_show", methods={"GET"})
     * @Template("usuario/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    protected function getExtraParametersShowAction($entity): array
    {
        $em = $this->doctrine->getManager();
        $connection = $em->getConnection();
        $request = $this->getRequest();

        $pagina = max(1, (int) ($request ? $request->query->get('pagina', 1) : 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        // Verificar si el usuario tiene sesiones activas
        $stmtLogueado = $connection->prepare('
            SELECT IF(COUNT(s.sess_id) > 0, 1, 0) AS logueado
            FROM sessions s
            WHERE s.user_id = :userId AND s.sess_lifetime > UNIX_TIMESTAMP()
        ');
        $resultLogueado = $stmtLogueado->executeQuery(['userId' => $entity->getId()]);
        $logueadoResult = $resultLogueado->fetchAssociative();
        $logueado = $logueadoResult ? (bool) $logueadoResult['logueado'] : false;

        // Contar total de sesiones
        $stmtCount = $connection->prepare('
            SELECT COUNT(*) AS total
            FROM sessions s
            WHERE s.user_id = :userId
        ');
        $resultCount = $stmtCount->executeQuery(['userId' => $entity->getId()]);
        $countResult = $resultCount->fetchAssociative();
        $totalSesiones = $countResult ? (int) $countResult['total'] : 0;
        $totalPaginas = (int) ceil($totalSesiones / $limite);

        // Obtener sesiones paginadas
        $stmtSesiones = $connection->prepare('
            SELECT 
                CONVERT(s.sess_id, CHAR) AS sess_id,
                from_unixtime(s.sess_time, "%Y-%m-%d %H:%i:%s") AS ultima_vez,
                from_unixtime(s.sess_lifetime, "%Y-%m-%d %H:%i:%s") AS expira_el,
                s.sess_lifetime,
                s.user_ip
            FROM sessions s
            WHERE s.user_id = :userId
            ORDER BY s.sess_time DESC
            LIMIT ' . (int) $offset . ', ' . (int) $limite . '
        ');
        $resultSesiones = $stmtSesiones->executeQuery(['userId' => $entity->getId()]);
        $sesiones = $resultSesiones->fetchAllAssociative();

        return [
            'usuario' => [
                'logueado' => $logueado,
            ],
            'sesiones' => $sesiones,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_sesiones' => $totalSesiones,
            'offset_sesiones' => $offset,
        ];
    }

    /**
     * @Route("/{id}/edit", name="usuario_edit", methods={"GET","POST"})
     * @Template("usuario/new.html.twig")
     */
    public function edit($id): array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="usuario_update", methods={"PUT"})
     * @Template("usuario/new.html.twig")
     */
    public function update(Request $request, $id): RedirectResponse|type|Response
    {
        return parent::baseUpdateAction($request, $id);
    }

    protected function getEditRedirectResponse(Request $request, $entity): RedirectResponse
    {
        return $this->redirectToRoute('cliente_index');
    }

    /**
     * @Route("/{id}/borrar", name="usuario_delete", methods={"GET"})
     */
    public function delete($id): RedirectResponse|JsonResponse|type
    {
        $em = $this->doctrine->getManager();
        $usuario = $em->getRepository('App\Entity\Usuario')->find($id);

        if ($usuario->getPedidos()->isEmpty()) {
            return parent::baseDeleteAction($id);
        } else {
            $this->get('session')->getFlashBag()->add('error', 'No puedes eliminar este Usuario ya que tiene pedidos realizados. Utilice la funcion "DESHABILITAR".');
            return $this->getDeleteRedirectResponse($usuario);
        }
    }

    /**
     *
     * @param UsuarioType $entityFormTypeClassName
     * @param Usuario $entity
     * @return FormInterface
     */
    protected function baseInitCreateEditForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm(UsuarioType::class, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        ));
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="usuario_habilitar_deshabilitar", methods={"GET"})
     */
    public function usuarioHabilitarDeshabilitar($id): RedirectResponse
    {
        $em = $this->doctrine->getManager();
        $usuario = $em->getRepository(Usuario::class)->findOneBy(array('id' => $id));
        $usuario->setHabilitado(!$usuario->getHabilitado());
        $message = ($usuario->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al usuario");

        return $this->redirectToRoute('usuario_index');
    }

    /**
     * @Route("/{usuario}/closeSessions", name="usuario_closesessions", methods={"GET"})
     */
    public function closeSessions(Usuario $usuario): RedirectResponse
    {

        $em = $this->doctrine->getManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $stmt = $connection->prepare('UPDATE `sessions` SET `sess_lifetime` = 0 WHERE `user_id` = :userId');
            $stmt->execute([
                'userId' => $usuario->getId()
            ]);
            $connection->commit();
            $this->get('session')->getFlashBag()->set('success', 'Se han cerrado todas las sesiones del usuario');
        } catch (Exception $e) {
            $connection->rollBack();
            $this->get('session')->getFlashBag()->set('error', 'No se pudieron cerrar las sesiones del usuario '.$e->getMessage());
        }

        return $this->redirectToRoute('usuario_index');
    }

    protected function getExtraParametersNewAction($entity): array
    {
        return $this->getForms();
    }

    protected function getExtraParametersEditAction($entity): array
    {
        return $this->getForms();
    }

    private function getForms(): array
    {
        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register_ajax'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        $razonSocial = new RazonSocial();

        $formRazonSocial = $this->createForm(RazonSocialType::class, $razonSocial, array(
            'action' => $this->generateUrl('app_razonsocial_create_ajax'),
            'method' => 'POST'
        ));

        $formRazonSocial->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return [
            'preserve_values' => true,
            'registrationForm' => $form->createView(),
            'razonSocialForm' => $formRazonSocial->createView()
        ];
    }
}
