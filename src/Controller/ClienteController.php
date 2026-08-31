<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Constants\ConstanteTipoUsuario;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cliente")
 * @IsGranted("ROLE_USUARIO_VIEW")
 */
class ClienteController extends BaseController {

    protected function getEntityName() {
        return 'Usuario';
    }

    protected function getEntityFullName() {
        return 'Usuario';
    }

    protected function getEntityPluralName() {
        return 'Clientes';
    }

    /**
     * @Route("/", name="cliente_index", methods={"GET"})
     * @Template("cliente/index.html.twig")
     */
    public function index(): array {
        $extraParams = [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ];

        return parent::baseIndexAction($extraParams);
    }

    /**
     * Tabla para cliente.
     *
     * @Route("/index_table/", name="cliente_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_usuario';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('apellido', 'apellido');
        $rsm->addScalarResult('celular', 'celular');
        $rsm->addScalarResult('grupos', 'grupos');
        $rsm->addScalarResult('habilitado', 'habilitado');
        $rsm->addScalarResult('id_tipo_usuario', 'id_tipo_usuario');

        $columnDefinition = [
            ['field' => 'email', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'apellido', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'celular', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "cliente/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage, [], [], true);
    }

    protected function getAditionalCustomWhereSQL($aliasTable, $request) {
        return "$aliasTable.id_tipo_usuario = " . ConstanteTipoUsuario::CLIENTE;
    }
}
