<?php

namespace App\Controller;

use App\Entity\ArchivoAdjunto;
use App\Entity\Constants\ConstanteAPI;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Constants\ConstanteTipoFiltro;
use App\Entity\Usuario;
use App\Form\RegistrationFormType;
use App\Service\EntityManagementGuesser;
use App\Service\SelectService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BaseController extends AbstractController {

    /**
     *
     * @var Array
     */
    protected $baseBreadcrumbs;

    /**
     *
     * @var EntityManagementGuesser
     */
    protected $guesser;

    /**
     *
     * @var SelectService
     */
    protected $selectService;

    /**
     *
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     *
     * @var AuthorizationCheckerInterface
     */
    protected $authChecker;

    /**
     *
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     *
     * @var type
     */
    private $associationTypeArray = array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY);

    /**
     *
     * @param ManagerRegistry $doctrine
     * @param EntityManagementGuesser $emg
     * @param ContainerInterface $container
     * @param SelectService $selectService
     * @param ParameterBagInterface $parameterBag
     * @param AuthorizationCheckerInterface $authChecker
     * @return $this
     */
    public function __construct(ManagerRegistry $doctrine, EntityManagementGuesser $emg, ContainerInterface $container, SelectService $selectService, ParameterBagInterface $parameterBag, AuthorizationCheckerInterface $authChecker) {
        $this->container = $container;

        $this->guesser = $emg;
        $this->guesser->initialize($this);

        $this->selectService = $selectService;

        $this->parameterBag = $parameterBag;

        $this->authChecker = $authChecker;

        $this->doctrine = $doctrine;

        return $this;
    }

    /**
     *
     * @param ContainerInterface $container
     * @param type $overrideBreadcrumbs
     */
    public function setContainer(ContainerInterface $container = null, $overrideBreadcrumbs = true): ?ContainerInterface {
        if ($overrideBreadcrumbs) {
            $this->baseBreadcrumbs = $this->getBaseBreadcrumbs();
        }

        return parent::setContainer($container);
    }

    /**
     *
     * @param type $extraParam
     * @return type
     */
    public function baseIndexAction($extraParam = array()): array {

        $breadcrumbs = $this->getIndexBaseBreadcrumbs();

        $defaultParam = array(
            'breadcrumbs' => $breadcrumbs,
            'page_title' => $this->getEntityPluralName()
        );

        return array_merge($defaultParam, $extraParam);
    }

    /**
     *
     * @param Request $request
     * @param type $columnDefinition
     * @param type $entityTableParam
     * @param type $queryTypeParam
     * @param type $rsmParam
     * @param type $renderPageParam
     * @param type $extraParam
     * @param type $storedParameters
     * @param type $executeAditionalWhere
     * @return type
     */
    public function baseIndexTableAction(Request $request, $columnDefinition = [], $entityTableParam = null, $queryTypeParam = null, $rsmParam = null, $renderPageParam = null, $extraParam = array(), $storedParameters = array(), $executeAditionalWhere = false): Response {

        /* @var $em EntityManager */
        $em = $this->doctrine->getManager();

        $queryType = $queryTypeParam == null ? ConstanteTipoConsulta::TABLE : $queryTypeParam;

        $entityFullName = $this->getEntityFullName();

        $entityTable = $entityTableParam != null //
            ? $entityTableParam //
            : ($queryType == ConstanteTipoConsulta::VIEW || $queryType == ConstanteTipoConsulta::STORE_PROCEDURE //
                ? $this->getViewName() //
                : "App\Entity\\$entityFullName"
            );

        $rsm = $rsmParam != null ? $rsmParam : $this->getRSMResult();
        $storedWithCount = true;

        //REQUEST
        $page = $request->query->get('start');
        $rows = $request->query->get('length');
        $draw = $request->query->get('draw');
        $start = $page;

        /* GET ORDER BY PARAMS */
        $orderByArray = $this->getOrderArrayParams($request, $columnDefinition);
        /* FIN GET ORDER BY PARAMS */

        // Si el origen de datos es una Tabla o una Vista
        if ($queryType == ConstanteTipoConsulta::TABLE || $queryType == ConstanteTipoConsulta::VIEW) {

            $aliasTable = "t";
            $allFieldsTable = $queryType == ConstanteTipoConsulta::VIEW ? $aliasTable . ".*" : $aliasTable;

            /* INIT SQL */
            $sql = "SELECT $allFieldsTable FROM $entityTable AS $aliasTable ";

            /* SET WHERE */
            $generatedWhere = $this->getWhereSQL($aliasTable, $request, $queryType == ConstanteTipoConsulta::TABLE, $columnDefinition);
            $where = $generatedWhere['where'];
            if ($executeAditionalWhere) {
                $whereAditional = $this->getAditionalCustomWhereSQL($aliasTable, $request);
                if ($whereAditional !== '') {
                    if ($where === '') {
                        $where = ' WHERE ' . $whereAditional;
                    } else {
                        $where .= ' AND ' . $whereAditional;
                    }
                }
            }
            $sql .= $where;
            /* FIN SET WHERE */


            /* SET ORDER BY */
            $sql .= $this->getOrderBySQL($aliasTable, $orderByArray);
            /* FIN SET ORDER BY */
        }

        /* CREATE QUERY AND SET LIMIT AND OFFSET */
        if ($queryType == ConstanteTipoConsulta::VIEW) {

            $query = $em->createNativeQuery($sql, $rsm);

            if ($start != "" && $rows != "") {
                $query->setSQL($query->getSQL() . ' LIMIT ' . $start . ', ' . $rows);
            } else {
                $query->setSQL($query->getSQL());
            }

            foreach ($generatedWhere['queryParameters'] as $queryParameter) {
                $query->setParameter($queryParameter['index'], $queryParameter['parameter']);
            }

            foreach ($extraParam as $param) {
                $query->setParameter($param['key'], $param['value'], $param['type']);
            }
        } //
        elseif ($queryType == ConstanteTipoConsulta::TABLE) {
            $query = $em->createQuery($sql);

            foreach ($generatedWhere['queryParameters'] as $queryParameter) {
                $query->setParameter($queryParameter['index'], $queryParameter['parameter']);
            }

            $query->setFirstResult($start);
            $query->setMaxResults($rows);
            $query->useResultCache(false);
        } //
        elseif ($queryType == ConstanteTipoConsulta::STORE_PROCEDURE) {

            if (count($extraParam) > 0) {

                $storedWithCount = false;

                $sql = "call $entityTable";

                for ($index = 0; $index < count($extraParam); $index++) {

                    // Si es el primer parametro
                    if ($index == 0) {
                        $sql .= '(';
                    }

                    $sql .= '?';

                    // Si NO es el ultimo parametro
                    if ($index != count($extraParam) - 1) {
                        $sql .= ', ';
                    } // Sino
                    else {
                        $sql .= ')';
                    }
                }

                $query = $em->createNativeQuery($sql, $rsm);

                foreach ($extraParam as $param) {
                    $query->setParameter($param['key'], $param['value'], $param['type']);
                }
            } else {

                /* SET STORED PARAMETERS */
                $storedParameters = $this->getRequestStoredParameters($storedParameters, $request);
                /* FIN SET STORED PARAMETERS */

                /* INIT STORED CALL */
                $sql = 'CALL ' . $entityTable . '(';
                for ($index = 0; $index < count($storedParameters); $index++) {

                    $sql .= '?';

                    // Si NO es el ultimo parametro
                    if ($index != count($storedParameters) - 1) {
                        $sql .= ', ';
                    }
                }
                $sql .= ',?,?,?,?,?)';
                /* FIN INIT STORED CALL */

                /* SET ORDER */
                if (empty($orderByArray)) {
                    $orderBy = 'id';
                    $orderDirection = 'ASC';
                } else {
                    foreach ($orderByArray as $key => $value) {
                        $orderBy = $key;
                        $orderDirection = $value;
                    }
                }
                /* FIN SET ORDER */

                $query = $em->createNativeQuery($sql, $rsm);

                /* SET STORED PARAMETERS */
                $i = 1;

                foreach ($storedParameters as $parameter) {
                    $query->setParameter($i++, $parameter);
                }
                $query->setParameter($i++, $this->getUser()->getId());
                $query->setParameter($i++, $orderBy);
                $query->setParameter($i++, $orderDirection);
                $query->setParameter($i++, $start);
                $query->setParameter($i++, $rows);
                /* FIN SET STORED PARAMETERS */
            }
        }
        /* FIN SET LIMIT AND OFFSET */


        // GET ENTITIES
        $entities = $query->getResult();

        /* SET COUNT */
        $totalPages = NULL;
        $countTotalEntities = NULL;
        $countTotalEntitiesUnfiltered = NULL;
        if ($storedWithCount) {
            if ($queryType == ConstanteTipoConsulta::TABLE) {

                $countUnfilteredSql = "SELECT COUNT($aliasTable.id) AS cant FROM $entityTable AS $aliasTable ";
                $countUnfilteredQuery = $em
                    ->createQuery($countUnfilteredSql)
                    ->useResultCache(false);

                $countSql = $countUnfilteredSql . $where;

                $countQuery = $em
                    ->createQuery($countSql)
                    ->useResultCache(false);

                foreach ($generatedWhere['queryParameters'] as $queryParameter) {
                    $countQuery->setParameter($queryParameter['index'], $queryParameter['parameter']);
                }
            } //
            else {
                $countUnfilteredSql = "SELECT COUNT($aliasTable.id) AS cant FROM $entityTable AS $aliasTable ";

                $countSql = $countUnfilteredSql . $where;

                $rsm = new ResultSetMapping();
                $rsm->addScalarResult('cant', 'cant');

                $countQuery = $em->createNativeQuery($countSql, $rsm);

                foreach ($generatedWhere['queryParameters'] as $queryParameter) {
                    $countQuery->setParameter($queryParameter['index'], $queryParameter['parameter']);
                }

                $rsmUnfiltered = new ResultSetMapping();
                $rsmUnfiltered->addScalarResult('cant', 'cant');

                $countUnfilteredQuery = $em->createNativeQuery($countUnfilteredSql, $rsmUnfiltered);
            }

            //TOTAL FILTERED
            $countResult = $countQuery->getOneOrNullResult();

            $countTotalEntities = $countResult != null //
                ? $countResult['cant'] //
                : 0;

            $totalPages = $rows != 0 ? ceil($countTotalEntities / $rows) : 1;

            //TOTAL UNFILTERED
            $countUnfilteredResult = $countUnfilteredQuery->getOneOrNullResult();

            $countTotalEntitiesUnfiltered = $countUnfilteredResult != null //
                ? $countUnfilteredResult['cant'] //
                : 0;

            /* FIN SET COUNT */
        }


        $localParameters = array(
            "entities" => $entities,
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "totalRows" => $countTotalEntitiesUnfiltered,
            "totalFiltered" => $countTotalEntities,
            "draw" => $draw
        );

        $entityNameLower = strtolower(str_replace("\\", "/", $entityFullName));

        $renderPage = $renderPageParam != null //
            ? $renderPageParam //
            : "$entityNameLower/index_table.html.twig";

        return $this->render($renderPage, $localParameters);
    }

    /**
     *
     * @return string
     */
    protected function getIndexPath() {

        $routeName = $this->getRequest()->get('_route');

        $explode = explode('_', $routeName);

        if (count($explode) > 0) {
            $indexPath = array_shift($explode) . '_index';
        } else {
            $indexPath = '/';
        }

        return $indexPath;
    }

    /**
     *
     * @return ResultSetMapping
     */
    protected function getRSMResult() {
        return new ResultSetMapping();
    }

    /**
     *
     * @param type $request
     * @return type
     */
    protected function getBaseEntityName() {
        return $this->guesser->guessEntityName();
    }

    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return $this->guesser->guessEntityShortName();
    }

    /**
     *
     * @return string
     */
    protected function getEntityFullName() {
        $namespace = substr($this->guesser->getNamespace(), strpos($this->guesser->getNamespace(), "Controller") + 11);
        return $namespace ? $namespace . "\\" . $this->guesser->guessEntityShortName() : $this->guesser->guessEntityShortName();
    }

    /**
     *
     * @return string
     */
    protected function getEntityRenderName() {
        return strtolower($this->guesser->guessEntityShortName());
    }

    /**
     *
     * @return string
     */
    protected function getEntityPluralName() {
        return $this->guesser->guessEntityShortName() . 's';
    }

    /**
     *
     * @return string
     */
    protected function getFormTypeName(): string {
        return $this->guesser->guessFormTypeName();
    }

    /**
     *
     * @return string
     */
    protected function getEntityShowName($entity) {
        return $entity->__toString();
    }

    /**
     *
     * @return type
     */
    protected function getURLPrefix() {
        return strtolower($this->guesser->guessEntityShortName());
    }

    /**
     *
     * @param type $rule
     * @param type $ruleIndex
     * @param type $isTable
     * @return string
     */
    private function getFieldOperation($rule, $ruleIndex, $isTable) {

        $fieldOperation = "";

        if ($rule['value'] != "Todos") {

            switch ($rule['type']) {
                case ConstanteTipoFiltro::SELECT:
                    $fieldOperation = " = ?" . ($isTable ? $ruleIndex : '') . ' ';
                    break;
                case ConstanteTipoFiltro::STRING:
                    $fieldOperation = " LIKE ?" . ($isTable ? $ruleIndex : '');
                    break;
                default:
                    $fieldOperation = "";
                    break;
            }
        }

        return $fieldOperation;
    }

    /**
     *
     * @param type $rule
     * @return type
     */
    private function getFieldParameter($rule) {

        $parameter = null;

        if ($rule['value'] != "Todos") {

            $fieldData = $rule['value'];
            $typeFilter = $rule['type'];

            if (!empty($fieldData)) {
                if ($typeFilter == ConstanteTipoFiltro::DATE) {
                    $fieldData = DateTime::createFromFormat('d/m/Y H:i:s', $fieldData . ' 00:00:00')->format("Y-m-d");
                } //
                elseif ($typeFilter == ConstanteTipoFiltro::DATETIME) {
                    $fieldData = DateTime::createFromFormat('d/m/Y H:i', substr($fieldData, 0, 16))->format("Y-m-d H:i");
                }
            }

            switch ($typeFilter) {
                case ConstanteTipoFiltro::SELECT:
                    $parameter = $fieldData;
                    break;
                case ConstanteTipoFiltro::STRING:
                    $parameter = '%' . $fieldData . '%';
                    break;
                default:
                    $parameter = $fieldData;
                    break;
            }
        }

        return $parameter;
    }

    /**
     *
     * @param type $aliasTable
     * @param type $request
     * @param type $isTable
     * @param type $columnDefinition
     * @param type $filtersParam
     * @return type
     */
    private function getWhereSQL($aliasTable, $request, $isTable, $columnDefinition, $filtersParam = null) {

        $requestColumns = $request->get('columns') ?: [];

        $searchArray = [];

        foreach ($requestColumns as $columnData) {
            if ($columnData['search']['value'] !== '') {
                $column = $columnData['data'];
                $searchArray[] = [
                    'column' => $column,
                    'field' => $columnDefinition[$column]['field'],
                    'value' => $columnData['search']['value'],
                    'type' => $columnDefinition[$column]['type'],
                ];
            }
        }

        $where = "";
        $queryParameters = [];
        $ruleBaseIndex = 0;

        if (!empty($searchArray)) {

            $where = " WHERE ";
            $whereArray = [];

            foreach ($searchArray as $ruleIndex => $rule) {

                $typeFilter = $rule['type'];

                $fieldName = $aliasTable . "." . $rule['field'];

                if ($typeFilter == ConstanteTipoFiltro::DATE) {
                    $fieldName = "DATE($fieldName)";
                }

                if ($typeFilter == ConstanteTipoFiltro::DATETIME) {
                    $fieldName = "date_format($fieldName, '%Y-%m-%d %H:%i')";
                }

                $fieldOperation = $this->getFieldOperation($rule, $ruleIndex + 1, $isTable);
                $fieldParameter = $this->getFieldParameter($rule);

                if ($fieldOperation != "") {
                    $whereArray[] = $fieldName . $fieldOperation;
                }

                if ($fieldParameter != null) {
                    $queryParameters[] = [
                        'index' => $ruleIndex + 1,
                        'parameter' => $fieldParameter
                    ];
                }

                $ruleBaseIndex++;
            }

            if (count($whereArray) > 0) {
                $where .= join(" AND ", $whereArray);
            } else {
                $where .= " 1=1";
            }
        }

        return [
            'where' => $where,
            'queryParameters' => $queryParameters
        ];
    }

    /**
     *
     */
    private function getOrderArrayParams($request, $columnDefinition) {

        $requestOrder = $request->query->get('order');

        $orderByArray = array();

        if (!empty($requestOrder)) {

            foreach ($requestOrder as $option) {

                if (isset($option['column']) && isset($option['dir'])) {
                    $orderByArray[$columnDefinition[$option['column']]['field']] = strtoupper($option['dir']);
                }
            }
        }

        return $orderByArray;
    }

    /**
     *
     * @param type $aliasTable
     * @param type $orderByArray
     * @return string
     */
    private function getOrderBySQL($aliasTable, $orderByArray) {

        $orderBySQL = "";

        if (!empty($orderByArray)) {

            $orderBySQL .= " ORDER BY ";

            $i = 0;
            $len = count($orderByArray);

            foreach ($orderByArray as $key => $value) {

                $orderBySQL .= $aliasTable . "." . $key
                    . " " . $value;

                if ($i != $len - 1) {
                    $orderBySQL .= ", ";
                }

                $i++;
            }
        }

        return $orderBySQL;
    }

    /**
     *
     * @param type $entity
     */
    protected function updateArchivosAdjuntos($entity, $customPath = '', $archivosAdjuntosOriginales = []) {
        foreach ($entity->getArchivosAdjuntos() as $archivoAdjunto) {
            /* @var $archivoAdjunto ArchivoAdjunto */

            if ($archivoAdjunto->getArchivo() != null) {
                $archivoAdjunto->setCustomPath($customPath);
                $archivoAdjunto->setNombre($archivoAdjunto->getArchivo()->getClientOriginalName());
            }
        }

        // Por cada ArchivoAdjunto original
        $em = $this->doctrine->getManager();
        foreach ($archivosAdjuntosOriginales as $archivoAdjunto) {
            // Si fue eliminado
            if (false === $entity->getArchivosAdjuntos()->contains($archivoAdjunto)) {
                $entity->removeArchivosAdjunto($archivoAdjunto);
                $em->remove($archivoAdjunto);
            }
        }
    }

    /**
     *
     * @param type $filters
     */
    protected function addAditionalFiltersToRequest(Request $request, $newFilters) {

        $request->query->set('_search', true);

        $filters = json_decode($request->query->get('filters'));
        if ($filters == null) {
            $filters = (object) [];
        }

        $filters->groupOp = "AND";
        foreach ($newFilters as $newFilter) {
            $filters->rules[] = (object) $newFilter;
        }

        $filters = json_encode($filters);
        $request->query->set('filters', $filters);
    }

    /**
     *
     * @return type
     */
    protected function getBaseBreadcrumbs() {

        return array(
            'Inicio' => '',
            $this->getEntityPluralName() => $this->generateUrl($this->getURLPrefix() . '_index')
        );
    }

    /**
     *
     * @return type
     */
    protected function getIndexBaseBreadcrumbs() {

        $breadcrumbs = $this->baseBreadcrumbs;
        $breadcrumbs[$this->getEntityPluralName()] = null;

        return $breadcrumbs;
    }

    /**
     *
     * @param type $storedParameters
     * @param type $request
     * @return type
     */
    private function getRequestStoredParameters($storedParameters, $request) {

        $filters = str_replace('\"', '"', $request->query->get('filters'));
        $search = $request->query->get('_search');

        if (($search == "true") && ($filters != "")) {

            $filters = json_decode($filters);

            foreach ($filters->rules as $fil) {
                $storedParameters[$fil->field] = ($fil->type == ConstanteTipoFiltro::SELECT && $fil->data == 'Todos') ? NULL : $fil->data;
            }
        }

        return $storedParameters;
    }

    /**
     *
     * @param type $aliasTable
     * @param type $request
     * @return string
     */
    protected function getAditionalCustomWhereSQL($aliasTable, $request) {
        return "";
    }

    /**
     *
     * @return type
     */
    protected function getIsProdEnvironment() {
        return $this->container->get('kernel.environment') == "prod";
    }

    /**
     *
     * @return type
     */
    public function getRequest() {
        return $this->get('request_stack')->getCurrentRequest();
    }

    /**
     *
     * @param type $id
     * @return Array
     * @throws type
     */
    public function baseShowAction($id) {
        $em = $this->doctrine->getManager();

        $entityName = $this->getEntityFullName();

        $entity = $em->getRepository("App\Entity\\$entityName")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException("No se puede encontrar la entidad $entityName.");
        }

        $breadcrumbs = $this->getShowBaseBreadcrumbs($entity);

        $parametros = array(
            'entity' => $entity,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($entity));
    }

    /**
     * @param type $entity
     */
    protected function getShowBaseBreadcrumbs($entity): array {

        $breadcrumbs = $this->baseBreadcrumbs;
        $breadcrumbs[$this->getEntityShowName($entity)] = $this->generateUrl($this->getURLPrefix() . '_show', array('id' => $entity->getId()));
        $breadcrumbs['Detalle'] = null;

        return $breadcrumbs;
    }

    /**
     *
     * @return type
     */
    protected function getExtraParametersShowAction($entity): array {
        return [];
    }

    /**
     * @return SelectService
     */
    protected function getSelectService(): SelectService {
        return $this->selectService;
    }

    /**
     *
     * @param type $entity
     * @return type
     */
    public function baseNewAction($entity = null): array {

        if ($entity == null) {

            $entityClassName = $this->getBaseEntityName();

            $entity = new $entityClassName;
        }

        $this->baseInitPreCreateForm($entity);

        $form = $this->baseCreateCreateForm($entity);

        $this->setNewFormValues($form, $entity);

        $breadcrumbs = $this->getNewBaseBreadcrumbs($form, $entity);

        $parametros = array(
            'entity' => $entity,
            'form' => $form->createView(),
            'form_action' => $this->getURLPrefix() . '_create',
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Agregar ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersNewAction($entity));
    }

    /**
     *
     * @param type $entity
     */
    protected function baseInitPreCreateForm($entity) {

    }

    /**
     *
     * @param type $entity
     * @return type
     */
    protected function baseCreateCreateForm($entity): FormInterface {

        $entityFormTypeClassName = $this->getFormTypeName();

        $form = $this->baseInitCreateCreateForm($entityFormTypeClassName, $entity);

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return $form;
    }

    /**
     *
     * @param string $entityFormTypeClassName
     * @param type $entity
     * @return type
     */
    protected function baseInitCreateCreateForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm($entityFormTypeClassName, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_create'),
            'method' => 'POST',
        ));
    }

    /**
     *
     * @param type $form
     * @param type $entity
     */
    protected function setNewFormValues($form, $entity) {

    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersNewAction($entity): array {
        return [];
    }

    /**
     *
     * @param Request $request
     * @param type $isAjaxCall
     * @return type
     */
    public function baseCreateAction(Request $request, $isAjaxCall = false) {

        $entityClassName = $this->getBaseEntityName($request);

        $entity = new $entityClassName;

        $this->preHandleRequestBaseCreateAction($entity, $request);

        $form = $this->baseCreateCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $isValid = $this->execPrePersistAction($entity, $request);

            if ($isValid) {

                $em = $this->doctrine->getManager();

                if ($this->checkPersistEntityInCreateAction()) {
                    $em->persist($entity);
                }

                $em->flush();

                $this->execPostPersistAction($em, $entity, $request);

                $message = $this->getCreateMessage($entity, true);

                if (!$isAjaxCall) {

                    $this->get('session')->getFlashBag()->add('success', $message);

                    return $this->getCreateRedirectResponse($request, $entity);
                } else {

                    $response = new Response();

                    $response->setContent(json_encode(array(
                        'message' => $message,
                        'statusCode' => Response::HTTP_OK,
                        'statusText' => ConstanteAPI::STATUS_TEXT_OK
                    )));

                    return $response;
                }
            } else {
                $request->attributes->set('form-error', true);
            }
        } //.
        else {
            $request->attributes->set('form-error', true);
        }

        if (!$isAjaxCall) {

            $breadcrumbs = $this->getNewBaseBreadcrumbs($form, $entity);

            $parametros = array(
                'entity' => $entity,
                'form' => $form->createView(),
                'breadcrumbs' => $breadcrumbs,
                'page_title' => 'Agregar ' . $this->getEntityRenderName()
            );

            return array_merge($parametros, $this->getExtraParametersNewAction($entity));
        } else {

            $response = new Response();

            $response->setContent(json_encode(array(
                'statusCode' => Response::HTTP_OK,
                'statusText' => ConstanteAPI::STATUS_TEXT_ERROR,
                'message' => $this->getCreateErrorMessage(),
            )));

            return $response;
        }
    }

    /**
     *
     * @param type $entity
     * @param type $request
     */
    protected function preHandleRequestBaseCreateAction($entity, $request) {

    }

    /**
     *
     * @param type $entity
     * @param type $request
     * @return bool
     */
    protected function execPrePersistAction($entity, $request): bool {
        return true;
    }

    /**
     *
     * @return bool
     */
    protected function checkPersistEntityInCreateAction(): bool {
        return true;
    }

    /**
     *
     * @param type $em
     * @param type $entity
     * @param type $request
     */
    protected function execPostPersistAction($em, $entity, $request) {

    }

    /**
     *
     * @param type $entity
     * @param type $useDecode
     * @return string
     */
    protected function getCreateMessage($entity, $useDecode = false): string {

        $message = $this->getCreateSuccessMessage($entity);

        if ($useDecode) {
            $message = html_entity_decode($message);
        }

        return $message;
    }

    /**
     *
     * @param type $entity
     * @return string
     */
    protected function getCreateSuccessMessage($entity): string {
        return 'El alta se realiz&oacute; con &eacute;xito.';
    }

    /**
     *
     * @param Request $request
     * @param type $entity
     * @return RedirectResponse
     */
    protected function getCreateRedirectResponse(Request $request, $entity): RedirectResponse {
        return $this->redirectToRoute($this->getURLPrefix() . "_index");
    }

    /**
     *
     * @return string
     */
    protected function getCreateErrorMessage(): string {
        return 'Ocurri&oacute; un error al intentar guardar. Por favor int&eacute;ntelo nuevamente.';
    }

    /**
     *
     * @param type $form
     * @param type $entity
     * @return Array
     */
    protected function getNewBaseBreadcrumbs($form, $entity): array {

        $breadcrumbs = $this->baseBreadcrumbs;
        $breadcrumbs['Agregar'] = null;

        return $breadcrumbs;
    }

    /**
     *
     * @param type $id
     * @return type
     * @throws type
     */
    public function baseEditAction($id): array {

        $em = $this->doctrine->getManager();

        $entity = $em->getRepository($this->getBaseEntityName())->find($id);

        if (!$entity) {

            $entityShortName = $this->guesser->guessEntityShortName();

            throw $this->createNotFoundException("No se puede encontrar la entidad $entityShortName.");
        }

        if (!$this->baseEditActionAccess($entity)) {

            $request = $this->container->get('request_stack')->getCurrentRequest();

            return $this->getEditRedirectResponse($request, $entity);
        }

        $this->baseInitPreEditForm($entity);

        $editForm = $this->baseCreateEditForm($entity);

        $this->setEditFormValues($editForm, $entity);

        $breadcrumbs = $this->getEditBaseBreadcrumbs($editForm, $entity);

        $parametros = array(
            'entity' => $entity,
            'form' => $editForm->createView(),
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Editar ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersEditAction($entity));
    }

    /**
     *
     * @param type $entity
     * @return boolean
     */
    protected function baseEditActionAccess($entity) {
        return true;
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param Request $request
     * @param type $entity
     * @return RedirectResponse
     */
    protected function getEditRedirectResponse(Request $request, $entity): RedirectResponse {
        return $this->redirectToRoute($this->getURLPrefix() . "_index");
    }

    /**
     *
     * @param type $entity
     */
    protected function baseInitPreEditForm($entity) {

    }

    /**
     *
     * @param $entity
     * @return type
     */
    protected function baseCreateEditForm($entity) {

        $entityFormTypeClassName = $this->getFormTypeName();

        $form = $this->baseInitCreateEditForm($entityFormTypeClassName, $entity);

        $form->add('submit', SubmitType::class, array(
                'label' => 'Actualizar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return $form;
    }

    /**
     *
     * @param type $entityFormTypeClassName
     * @param type $entity
     * @return FormInterface
     */
    protected function baseInitCreateEditForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm($entityFormTypeClassName, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
    }

    /**
     *
     * @param type $form
     * @param type $entity
     * @return Array
     */
    protected function getEditBaseBreadcrumbs($form, $entity): array {

        $breadcrumbs = $this->baseBreadcrumbs;
        $breadcrumbs[$this->getEntityShowName($entity)] = $this->generateUrl($this->getURLPrefix() . '_show', array('id' => $entity->getId()));
        $breadcrumbs['Editar'] = null;

        return $breadcrumbs;
    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersEditAction($entity): array {
        return [];
    }

    /**
     *
     * @param type $editForm
     * @param type $entity
     */
    protected function setEditFormValues($editForm, $entity) {

    }

    /**
     *
     * @param Request $request
     * @param type $id
     * @param type $isAjaxCall
     * @return type
     * @throws type
     */
    public function baseUpdateAction(Request $request, $id, $isAjaxCall = false) {

        $em = $this->doctrine->getManager();

        $entityClassName = $this->getBaseEntityName($request);

        $entity = $em->getRepository($entityClassName)->find($id);

        if (!$entity) {

            $entityShortName = $this->guesser->guessEntityShortName();

            throw $this->createNotFoundException("No se puede encontrar la entidad $entityShortName.");
        }

        $localVariablesArray = $this->getUpdateActionVariables($entity);

        $editForm = $this->baseCreateEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $isValid = $this->execPreUpdateAction($em, $entity, $request, $localVariablesArray);

            if ($isValid) {

                $em->flush();

                $message = $this->getUpdateMessage($entity, true);

                if (!$isAjaxCall) {

                    $this->get('session')->getFlashBag()->add('success', $message);

                    return $this->getEditRedirectResponse($request, $entity);
                } //
                else {

                    $response = new Response();

                    $response->setContent(json_encode(array(
                        'statusCode' => Response::HTTP_OK,
                        'statusText' => ConstanteAPI::STATUS_TEXT_OK,
                        'message' => $message
                    )));

                    return $response;
                }
            } else {
                $request->attributes->set('form-error', true);
            }
        } //.
        else {
            $request->attributes->set('form-error', true);
        }



        if (!$isAjaxCall) {

            $breadcrumbs = $this->getEditBaseBreadcrumbs($editForm, $entity);

            $parametros = array(
                'entity' => $entity,
                'form' => $editForm->createView(),
                'breadcrumbs' => $breadcrumbs,
                'page_title' => 'Editar ' . $this->getEntityRenderName()
            );

            return array_merge($parametros, $this->getExtraParametersEditAction($entity));
        } //
        else {

            $response = new Response();

            $response->setContent(json_encode(array(
                'statusCode' => Response::HTTP_OK,
                'statusText' => ConstanteAPI::STATUS_TEXT_ERROR,
                'message' => $this->getCreateErrorMessage(),
            )));

            return $response;
        }
    }

    /**
     *
     * @param type $entity
     * @return ArrayCollection
     */
    protected function getUpdateActionVariables($entity) {

        $resultArray = [];

        $accessor = PropertyAccess::createPropertyAccessor();

        if ($accessor->isReadable($entity, 'archivosAdjuntos')) {

            $resultArray['archivosAdjuntosOriginales'] = new ArrayCollection();

            foreach ($entity->getArchivosAdjuntos() as $archivoAdjunto) {
                $resultArray['archivosAdjuntosOriginales']->add($archivoAdjunto);
            }
        }

        return $resultArray;
    }

    /**
     *
     * @param type $em
     * @param type $entity
     * @param type $request
     * @param type $localVariablesArray
     * @return boolean
     */
    protected function execPreUpdateAction($em, $entity, $request, $localVariablesArray) {

        // Actualiza los ArchivoAdjunto
        if (method_exists($entity, 'getCustomPath')) {

            if (!empty($localVariablesArray['archivosAdjuntosOriginales'])) {

                $archivosAdjuntosOriginales = $localVariablesArray['archivosAdjuntosOriginales'];

                if ($archivosAdjuntosOriginales != null) {
                    $this->updateArchivosAdjuntos($entity, $entity->getCustomPath(), $archivosAdjuntosOriginales);
                }
            }
        }

        return true;
    }

    /**
     *
     * @param type $entity
     * @param type $useDecode
     * @return string
     */
    protected function getUpdateMessage($entity, $useDecode = false): string {

        $message = $this->getUpdateSuccessMessage($entity);

        if ($useDecode) {
            $message = html_entity_decode($message);
        }

        return $message;
    }

    /**
     *
     * @param type $entity
     * @return string
     */
    protected function getUpdateSuccessMessage($entity): string {
        return 'La actualizaci&oacute;n se realiz&oacute; con &eacute;xito.';
    }

    /**
     *
     * @param type $id
     * @param type $indexParams
     * @param type $isAjaxCall
     * @return type
     * @throws type
     */
    public function baseDeleteAction($id, $indexParams = array(), $isAjaxCall = false) {

        $em = $this->doctrine->getManager();
        $entity = $em->getRepository($this->getBaseEntityName())->find($id);
        if (!$entity) {
            $entityShortName = $this->guesser->guessEntityShortName();
            throw $this->createNotFoundException("No se puede encontrar la entidad $entityShortName.");
        }

        $error = false;
        $em->remove($entity);

        try {
            $em->flush();
        } catch (DBALException $e) {
            $error = true;
        }

        // Si hubo un error
        if ($error) {
            $this->getRequest()->attributes->set('form-error', true);

            if (!$isAjaxCall) {
                $this->get('session')->getFlashBag()->set('error', $this->getDeleteErrorMessage());
                return $this->redirect($this->generateUrl($this->getIndexPath(), $indexParams));
            } else {
                $result = array(
                    'status' => 'ERROR',
                    'message' => $this->getDeleteErrorMessage()
                );

                return new JsonResponse($result);
            }
        } else {
            if (!$isAjaxCall) {
                $this->get('session')->getFlashBag()->add('success', $this->getDeleteMessage(true));
                return $this->getDeleteRedirectResponse($entity);
            } else {
                $result = array(
                    'status' => 'OK',
                    'message' => $this->getDeleteMessage()
                );

                return new JsonResponse($result);
            }
        }
    }

    /**
     *
     * @return string
     */
    protected function getDeleteErrorMessage(): string {
        return 'No se pudo eliminar la entidad. Ha ocurrido un error.';
    }

    /**
     *
     * @param type $useDecode
     * @return string
     */
    protected function getDeleteMessage($useDecode = false): string {
        $message = $this->getDeleteSuccessMessage();

        if ($useDecode) {
            $message = html_entity_decode($message);
        }

        return $message;
    }

    /**
     *
     * @return string
     */
    protected function getDeleteSuccessMessage(): string {
        return 'La eliminaci&oacute;n se realiz&oacute; con &eacute;xito.';
    }

    /**
     *
     * @return RedirectResponse
     */
    protected function getDeleteRedirectResponse($entity): RedirectResponse {
        return $this->redirectToRoute($this->getURLPrefix() . "_index");
    }

    protected function downloadAction(Request $request, $id) {

        $em = $this->doctrine->getManager();

        $roleHabilitado = $this->getRoleHabilitadoDownloadAction($request, $id);

        if ($this->authChecker->isGranted($roleHabilitado)) {

            $archivoAdjunto = $em->getRepository('App\Entity\ArchivoAdjunto')->find($id);

            if (!$archivoAdjunto) {
                throw $this->createNotFoundException("No se puede encontrar la entidad ArchivoAdjunto.");
            }

            $this->getCustomDownloadAction($archivoAdjunto, $request, $id);

            $response = new BinaryFileResponse($this->parameterBag->get('kernel.project_dir') . '/public/uploads/archivo_adjunto/' . $archivoAdjunto->getCustomPath() . '/' . $archivoAdjunto->getNombreArchivo());
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $archivoAdjunto->getNombre());
            return $response;
        } else {
            return new Response();
        }
    }

    protected function getRoleHabilitadoDownloadAction(Request $request, $id) {
        return 'ROLE_USER';
    }

    protected function getCustomDownloadAction(ArchivoAdjunto $archivoAdjunto, Request $request, $id) {
        return null;
    }

}
