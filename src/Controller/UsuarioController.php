<?php

namespace App\Controller;

use App\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Usuario;
use App\Form\UsuarioType;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Form\FormInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/usuario")
 * @IsGranted("ROLE_USUARIO_VIEW")
 */
class UsuarioController extends BaseController {

    /**
     * @Route("/", name="usuario_index", methods={"GET"})
     * @Template("usuario/index.html.twig")
     */
    public function index(): Array {
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
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('apellido', 'apellido');
        $rsm->addScalarResult('telefono', 'telefono');
        $rsm->addScalarResult('grupos', 'grupos');
        $rsm->addScalarResult('habilitado', 'habilitado');
        /*$rsm->addScalarResult('last_seen', 'last_seen');
        $rsm->addScalarResult('logueado', 'logueado');
        $rsm->addScalarResult('sesiones', 'sesiones');*/

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'email', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'apellido', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'telefono', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'grupos', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            /*['field' => 'last_seen', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'logueado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],*/
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "usuario/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="usuario_show", methods={"GET"})
     * @Template("usuario/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     *
     * @return type
     */
    protected function getExtraParametersShowAction($entity): Array {

        $em = $this->getDoctrine()->getManager();

        $query = "SELECT
                    IF(s.sess_lifetime IS NULL || UNIX_TIMESTAMP() > MAX(s.sess_lifetime), false, true) AS logueado, 
                    GROUP_CONCAT(CONCAT(CONVERT(s.sess_id,char), '___', from_unixtime(s.sess_time, '%Y-%m-%d %H:%i:%s'), '___', from_unixtime(s.sess_lifetime, '%Y-%m-%d %H:%i'), '___', s.user_ip) ORDER BY s.sess_time DESC SEPARATOR '____') AS sesiones
                FROM sessions s
                WHERE s.user_id = ?
                GROUP BY s.user_id";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('logueado', 'logueado');
        $rsm->addScalarResult('sesiones', 'sesiones');

        $nativeQuery = $em->createNativeQuery($query, $rsm);
        $nativeQuery->setParameter(1, $entity->getId());

        $usuario = $nativeQuery->getArrayResult();

        return [
            'usuario' => count($usuario) ? $usuario[0] : []
        ];
    }

    /**
     * @Route("/{id}/edit", name="usuario_edit", methods={"GET","POST"})
     * @Template("usuario/new.html.twig")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="usuario_update", methods={"PUT"})
     * @Template("usuario/new.html.twig")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="usuario_delete", methods={"GET"})
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    /**
     *
     * @param type $entityFormTypeClassName
     * @param type $entity
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
    public function usuarioHabilitarDeshabilitar($id) {
        $em = $this->getDoctrine()->getManager();
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
    public function closeSessions(Usuario $usuario) {

        $em = $this->getDoctrine()->getManager();

        $query = "SELECT
                    IF(s.sess_lifetime IS NULL || UNIX_TIMESTAMP() > MAX(s.sess_lifetime), false, true) AS logueado, 
                    GROUP_CONCAT(CONCAT(CONVERT(s.sess_id,char), '___', from_unixtime(s.sess_time, '%Y-%m-%d %H:%i:%s'), '___', from_unixtime(s.sess_lifetime, '%Y-%m-%d %H:%i'), '___', s.user_ip) ORDER BY s.sess_time DESC SEPARATOR '____') AS sesiones
                FROM sessions s
                WHERE s.user_id = ?
                GROUP BY s.user_id";

        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $stmt = $connection->prepare('UPDATE `sessions` SET `sess_lifetime` = 0 WHERE `user_id` = :userId');
            $stmt->execute([
                'userId' => $usuario->getId()
            ]);
            $connection->commit();
            $this->get('session')->getFlashBag()->set('success', 'Se han cerrado todas las sesiones del usuario');
        } catch (DBALException $e) {
            $connection->rollBack();
            $this->get('session')->getFlashBag()->set('error', 'No se pudieron cerrar las sesiones del usuario');
        }

        return $this->redirectToRoute('usuario_index');
    }

}
