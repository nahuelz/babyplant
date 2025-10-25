<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Notificacion;

/**
 * NotificacionController.
 *
 * @Route("/notificacion")
 * @IsGranted("ROLE_ADMIN")
 */
class NotificacionController extends BaseController {

    /**
     *
     * @Route("/", name="notificacion_index")
     * @Method("GET")
     */
    public function index() {
        return $this->render('notificacion/index_detalle.html.twig', //
            array('notificaciones' => $this->getNotificacionesByUsuario(true, 500, false)) //
        );
    }

    /**
     * Tabla para notificacion.
     *
     * @Route("/index_table/", name="notificacion_table", methods={"GET|POST"})
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
     * Muestra las notificaciones del usuario en el menú.
     * 
     * @Route("/ultimas_notificaciones/", name="notificacion_ultimas_notificaciones")
     * @Method("POST")
     */
    public function getMenuNotificaciones(): Response
    {
        return $this->render('notificacion/index_table.html.twig', //
                        array('notificaciones' => $this->getNotificacionesByUsuario(false, 50, true)) //
        );
    }

    /**
     * Muestra todas las notificaciones del usuario.
     * 
     * @Route("/todas_notificaciones/", name="notificacion_todas_notificaciones")
     * @Method("POST")
     */
    public function getDetalleNotificaciones(): Response
    {
        return $this->render('notificacion/index_detalle.html.twig', //
                        array('notificaciones' => $this->getNotificacionesByUsuario(true, 500, false)) //
        );
    }

    /**
     * 
     * @param type $showAll
     * @param type $limit
     * @return type
     */
    private function getNotificacionesByUsuario($showAll = false, $limit = null, $filterDate = true) {
        $em = $this->doctrine->getManager();
        $usuario = $this->getUser();
        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('titulo', 'titulo');
        $rsm->addScalarResult('contenido', 'contenido');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('leida', 'leida');
        $rsm->addScalarResult('imagen', 'imagen');

        $sql = '';

        // Get user roles and create JSON array conditions
        $userRoles = $this->getUser()->getRoles();
        $rolesConditions = [];
        foreach ($userRoles as $role) {
            $rolesConditions[] = "JSON_CONTAINS(n.destinatarios, '\"$role\"')";
        }
        $rolesCondition = !empty($rolesConditions) ? 'AND (' . implode(' OR ', $rolesConditions) . ')' : '';

        if ($showAll) {
            $sql .= 'SELECT n.id,
                n.titulo,
                n.contenido,
                n.fecha_creacion AS fechaCreacion,
                n.imagen,
                IF(lu.id_notificacion IS NULL, FALSE, TRUE) AS leida
            FROM notificacion n                        
                LEFT JOIN (
                    SELECT nu.id_notificacion
                    FROM notificacion_usuario nu
                    WHERE nu.fecha_baja IS NULL
                        AND nu.id_usuario = ?
                ) AS lu ON lu.id_notificacion = n.id
            WHERE n.fecha_baja IS NULL
                ' . $rolesCondition;
        } else {
            $sql .= 'SELECT n.id,
                n.titulo,
                n.contenido,
                n.fecha_creacion AS fechaCreacion,
                n.imagen,
                false AS leida
            FROM notificacion n
            WHERE n.fecha_baja IS NULL
                AND n.id NOT IN (
                    SELECT nu.id_notificacion
                    FROM notificacion_usuario nu
                    WHERE nu.fecha_baja IS NULL
                        AND nu.id_usuario = ?
                )
                ' . $rolesCondition;
        }

        if ($filterDate) {
            $sql .= " AND NOW() > n.fecha_desde AND (n.fecha_hasta IS NULL OR (n.fecha_hasta IS NOT NULL AND NOW() < n.fecha_hasta)) ";
        }

        $sql .= " GROUP BY n.id
            ORDER BY n.fecha_creacion DESC";

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $native_query = $em->createNativeQuery($sql, $rsm);
        $native_query->setParameter(1, $usuario->getId());

        return $native_query->getResult();
    }

    /**
     * Marca una notificacion como vista.
     *
     * @Route("/marcar_vista/", name="notificacion_marcar_vista")
     * @Method("GET|POST")
     */
    public function marcarVistaAction(Request $request) {
        $em = $this->doctrine->getManager();

        // IDS DE LAS NOTIFICACIONES A MARCAR COMO VISTAS
        $ids = json_decode($request->request->get('ids'), '[]');

        $notificaciones = $em->getRepository(\App\Entity\Notificacion::class)
                ->createQueryBuilder('n')
                ->where('n.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();

        /* @var $notificacion Notificacion */
        foreach ($notificaciones as $notificacion) {
            $notificacionUsuario = new \App\Entity\NotificacionUsuario();

            $notificacionUsuario->setNotificacion($notificacion);
            $notificacionUsuario->setUsuario($this->getUser());

            $em->persist($notificacionUsuario);
        }

        $em->flush();

        $response = new Response();
        $response->setContent(json_encode(array(
            'result' => 'OK'
        )));

        return $response;
    }

    /**
     * @Route("/new", name="notificacion_new", methods={"GET","POST"})
     * @Template("notificacion/new.html.twig")
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(): array {
        return parent::baseNewAction();
    }

    /**
     * @Route("/insertar", name="notificacion_create", methods={"GET","POST"})
     * @Template("notificacion/new.html.twig")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     *
     * @param string $entityFormTypeClassName
     * @param Notificacion $entity
     */
    protected function baseInitCreateCreateForm($entityFormTypeClassName, $entity): FormInterface {
        return ($this->createForm($entityFormTypeClassName, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_create'),
            'method' => 'POST',
            'roles' => $this->getParameter('security.role_hierarchy.roles')
        )));
    }

}
