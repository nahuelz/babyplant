<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * NotificacionController.
 *
 * @Route("/notificacion")
 * @IsGranted("ROLE_USER")
 */
class NotificacionController extends AbstractController {

    /**
     * Muestra las notificaciones del usuario en el menú.
     * 
     * @Route("/ultimas_notificaciones/", name="notificacion_ultimas_notificaciones")
     * @Method("POST")
     */
    public function getMenuNotificaciones() {
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
    public function getDetalleNotificaciones() {
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

        $em = $this->getDoctrine()->getManager();

        $usuario = $this->getUser();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('prioridad', 'prioridad');
        $rsm->addScalarResult('titulo', 'titulo');
        $rsm->addScalarResult('contenido', 'contenido');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('leida', 'leida');

        $sql = '';

        if ($showAll) {
            $sql .= 'SELECT n.id,
                        n.prioridad,
                        n.titulo,
                        n.contenido,
                        n.fecha_creacion AS fechaCreacion,
                        IF(lu.id_notificacion IS NULL, FALSE, TRUE) AS leida
                    FROM notificacion n                        
                        LEFT JOIN (
                            SELECT nu.id_notificacion
                            FROM notificacion_usuario nu
                            WHERE nu.fecha_baja IS NULL
                                AND nu.id_usuario = ?
                        ) AS lu ON lu.id_notificacion = n.id
                    WHERE n.fecha_baja IS NULL
                        AND (compareDestinatarios(?, n.destinatarios) OR compareDestinatarios(?, n.destinatarios))';
        } else {
            $sql .= 'SELECT n.id,
                        n.prioridad,
                        n.titulo,
                        n.contenido,
                        n.fecha_creacion AS fechaCreacion,
                        false AS leida
                    FROM notificacion n
                    WHERE n.fecha_baja IS NULL
                        AND n.id NOT IN (
                            SELECT nu.id_notificacion
                            FROM notificacion_usuario nu
                            WHERE nu.fecha_baja IS NULL
                            AND nu.id_usuario = ?
                        )
                        AND (compareDestinatarios(?, n.destinatarios) OR compareDestinatarios(?, n.destinatarios))';
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

        $native_query->setParameter(1, $usuario);
        $native_query->setParameter(2, implode(",", $this->getUser()->getRoles()));
        $native_query->setParameter(3, $usuario);

        return $native_query->getResult();
    }

    /**
     * Marca una notificacion como vista.
     *
     * @Route("/marcar_vista/", name="notificacion_marcar_vista")
     * @Method("GET|POST")
     */
    public function marcarVistaAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

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

}
