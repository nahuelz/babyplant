<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Kernel;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogAuditoria controller.
 *
 * @Route("/logouditoria")
 */
class LogAuditoriaController extends BaseController {

    protected function getIndexBaseBreadcrumbs() {
        return array(
            'Inicio' => '',
            'Auditoría' => $this->generateUrl('logauditoria_index')
        );
    }

    /**
     * Lists all LogAuditoria entities.
     *
     * @Route("/", name="logauditoria_index")
     * @Method("GET")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     * @Template("log_auditoria/index.html.twig")
     */
    public function index() {

        $extraParams = [
            'select_boolean' => $this->selectService->getBooleanSelect()
        ];

        return parent::baseIndexAction($extraParams);
    }

    /**
     * @Route("/index_table/", name="logauditoria_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_log_auditoria';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idPedido', 'idPedido');
        $rsm->addScalarResult('idProducto', 'idProducto');
        $rsm->addScalarResult('accion', 'accion');
        $rsm->addScalarResult('modulo', 'modulo');
        $rsm->addScalarResult('usuario', 'usuario');
        $rsm->addScalarResult('fecha', 'fecha');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "log_auditoria/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }
}
