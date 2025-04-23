<?php

namespace App\Controller;

use App\Kernel;
use DateInterval;
use DatePeriod;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auditoria controller.
 *
 * @Route("/auditoria_interna")
 * @IsGranted("ROLE_AUDITORIA")
 */
class AuditoriaController extends BaseController {

    protected function getIndexBaseBreadcrumbs() {
        return array(
            'Inicio' => '',
            'Auditoría' => $this->generateUrl('auditoria_index')
        );
    }

    /**
     * Lists all Auditoria entities.
     *
     * @Route("/", name="auditoria_index")
     * @Method("GET")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     * @Template("auditoria/index.html.twig")
     */
    public function index() {

        $extraParams = [
            'select_boolean' => $this->selectService->getBooleanSelect()
        ];

        return parent::baseIndexAction($extraParams);
    }

    /**
     * Tabla para Auditoria.
     *
     * @Route("/index_table/", name="auditoria_table")
     * @Method("GET|POST")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     */
    public function indexTableAction(Request $request, Kernel $kernel) {

        $entities = [];
        $draw = 1;

        if (($request->get('fechaHasta') != null) && ($request->get('fechaDesde') != null )) {

            $fromDate = DateTime::createFromFormat('d/m/Y', $request->get('fechaDesde'));
            $toDate = DateTime::createFromFormat('d/m/Y', $request->get('fechaHasta'));
            $toDate->modify('+1 day');

            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($fromDate, $interval, $toDate);

            foreach ($daterange as $filterDate) {

                $filename403 = $kernel->getLogDir() . '/error_403/' . $filterDate->format('Y') . '/' . date('m') . '/error_403_' . $filterDate->format('Y_m_d') . '.json';
                $filename405 = $kernel->getLogDir() . '/error_405/' . $filterDate->format('Y') . '/' . date('m') . '/error_405_' . $filterDate->format('Y_m_d') . '.json';
                $filename500 = $kernel->getLogDir() . '/error_500/' . $filterDate->format('Y') . '/' . date('m') . '/error_500_' . $filterDate->format('Y_m_d') . '.json';

                // 403
                if (file_exists($filename403)) {
                    $jsonString = file_get_contents($filename403);
                    $daysErrors = [];
                    $daysErrors['timeStamp'] = $filterDate->getTimestamp();
                    $daysErrors['data'] = json_decode($jsonString, true)['error_403'];
                    $entities[] = $daysErrors;
                }

                // 405
                if (file_exists($filename405)) {
                    $jsonString = file_get_contents($filename405);
                    $daysErrors = [];
                    $daysErrors['timeStamp'] = $filterDate->getTimestamp();
                    $daysErrors['data'] = json_decode($jsonString, true)['error_405'];
                    $entities[] = $daysErrors;
                }

                // 500
                if (file_exists($filename500)) {
                    $jsonString = file_get_contents($filename500);
                    $daysErrors = [];
                    $daysErrors['timeStamp'] = $filterDate->getTimestamp();
                    $daysErrors['data'] = empty(json_decode($jsonString, true)['error_500']) ? '' : json_decode($jsonString, true)['error_500'];
                    $entities[] = $daysErrors;
                }
            }

            $draw = $request->get('draw') + 1;
        }

        return $this->render('auditoria/index_table.html.twig', array(
                    'entities' => $entities,
                    'draw' => $draw
        ));
    }

    /**
     * Finds and displays Auditoria entity.
     *
     * @Route("/{id}", name="auditoria_interna_show")
     * @Method("GET")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     * @Template("auditoria/show.html.twig")
     */
    public function showAction($id, Request $request, Kernel $kernel) {

        $timeStamp = $request->get('timeStamp');

        $entities = [];

        if ($timeStamp != null) {

            $filterDate = new DateTime();
            $filterDate->setTimestamp($timeStamp);

            if ($filterDate) {

                $numero = $request->get('numero');
                $numeroErrorArray = explode('-', $numero);
                $codigoError = $numeroErrorArray[0];

                if (!empty($numeroErrorArray[1])) {

                    $filename = $kernel->getLogDir() . '/error_' . $codigoError . '/' . $filterDate->format('Y') . '/' . date('m') . '/error_' . $codigoError . '_' . $filterDate->format('Y_m_d') . '.json';

                    if (file_exists($filename)) {
                        $jsonString = file_get_contents($filename);
                        $entities = empty(json_decode($jsonString, true)['error_' . $codigoError]) ? json_decode($jsonString, true)['error' . $codigoError] : json_decode($jsonString, true)['error_' . $codigoError];
                    }
                } else {
                    $filename = $kernel->getLogDir() . '/error_500/' . $filterDate->format('Y') . '/' . date('m') . '/error_500_' . $filterDate->format('Y_m_d') . '.json';

                    if (file_exists($filename)) {
                        $jsonString = file_get_contents($filename);
                        $entities = empty(json_decode($jsonString, true)['error_500']) ? json_decode($jsonString, true)['error500'] : json_decode($jsonString, true)['error_500'];
                    }
                }
            }

            $entity = $entities[$id];

            $breadcrumbs = $this->baseBreadcrumbs;
            $breadcrumbs['Detalle'] = null;

            return array(
                'entity' => $entity,
                'breadcrumbs' => $breadcrumbs,
            );
        }
    }

    /**
     * @Route("/toggle-selected/", name="auditoria_toggle_selected_fix")
     * @Method("POST")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     */
    public function toggleSelectedFixedAction(Request $request, Kernel $kernel) {

        $dataArray = json_decode($request->request->get('ids'), true);

        if (!empty($dataArray)) {

            foreach ($dataArray as $data) {

                $filterDate = new DateTime();
                $filterDate->setTimestamp($data['timestamp']);

                if ($filterDate) {

                    $this->toggleFixedIssue($data['numero'], $filterDate, $data['id'], 1, $kernel);
                }

                $message = 'Las incidencias se marcaron como corregidas';
                $statusText = 'OK';
                $statusCode = Response::HTTP_OK;
            }

            $response = new Response();

            $response->setContent(json_encode(array(
                'message' => $message,
                'statusCode' => $statusCode,
                'statusText' => $statusText
            )));

            return $response;
        }
    }

    /**
     * @Route("/toggle-fix/{id}", name="auditoria_toggle_fix")
     * @Method("POST")
     * @IsGranted("ROLE_AUDITORIA_VIEW")
     */
    public function toggleFixedAction($id, Request $request, Kernel $kernel) {

        $timeStamp = $request->get('timeStamp');

        if ($timeStamp != null) {

            $filterDate = new DateTime();
            $filterDate->setTimestamp($timeStamp);

            if ($filterDate) {

                $numero = $request->get('numero');
                $corregido = $request->get('corregido');

                $this->toggleFixedIssue($numero, $filterDate, $id, $corregido, $kernel);

                $message = 'La incidencia nº ' . $numero . ' se marcó como ' . (!$corregido ? 'NO ' : '') . 'corregida';
                $statusText = 'OK';
                $statusCode = Response::HTTP_OK;
            }

            $response = new Response();

            $response->setContent(json_encode(array(
                'message' => $message,
                'statusCode' => $statusCode,
                'statusText' => $statusText
            )));

            return $response;
        }
    }

    /**
     * 
     * @param type $numero
     * @param type $filterDate
     * @param type $id
     * @param type $corregido
     * @param Kernel $kernel
     */
    private function toggleFixedIssue($numero, $filterDate, $id, $corregido, Kernel $kernel) {

        $numeroErrorArray = explode('-', $numero);
        $codigoError = $numeroErrorArray[0];

        if (empty($numeroErrorArray[1])) {
            $codigoError = 500;
        }

        $filename = $kernel->getLogDir() . '/error_' . $codigoError . '/' . $filterDate->format('Y') . '/' . date('m') . '/error_' . $codigoError . '_' . $filterDate->format('Y_m_d') . '.json';

        if (file_exists($filename)) {

            $jsonString = file_get_contents($filename);

            $jsonData = json_decode($jsonString, true);

            $index = empty($jsonData['error_' . $codigoError]) ? 'error' . $codigoError : 'error_' . $codigoError;

            $jsonData[$index][$id]['corregido'] = $corregido;
        }

        $newJsonString = json_encode($jsonData, JSON_PRETTY_PRINT);

        file_put_contents($filename, $newJsonString);
    }

}
