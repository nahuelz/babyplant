<?php

namespace App\EventListener;

use App\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ExceptionListener {

    private $kernel;
    private $twig;
    private $security;
    private $logger;

    /**
     * 
     * @param Kernel $kernel
     * @param Environment $twig
     * @param TokenStorageInterface $security
     * @param LoggerInterface $logger
     */
    public function __construct(Kernel $kernel, Environment $twig, TokenStorageInterface $security, LoggerInterface $logger = null) {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * 
     * @param ExceptionEvent $event
     * @return type
     */
    public function onKernelException(ExceptionEvent $event) {

        // If it is a HttpNotFoundException
        if ($event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $exception = $event->getThrowable();

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        }

        $request = $event->getRequest();

        $path = $this->kernel->getLogDir() . "/error_$statusCode/";

        if (!file_exists($path)) {
            mkdir($path);
        }

        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $renderer = new HtmlErrorRenderer(true, null, null, null, $currentContent, $this->logger);
        $errorHtml = $renderer->render($exception)->getAsString();

        $errorCountFile = $path . 'error_' . $statusCode . '_count.txt';
        if (file_exists($errorCountFile)) {
            $errorCount = intval(file_get_contents($errorCountFile)) + 1;
        } else {
            $errorCount = 1;
        }
        file_put_contents($errorCountFile, $errorCount);

        $errorNumber = $statusCode . '-' . $errorCount;
        $folder = date('Y') . '/' . date('m');
        $filename = $path . $folder . "/error_$statusCode" . "_" . date('Y_m_d') . '.json';

        $newData = [
            "error_$statusCode" => []
        ];

        if (file_exists($filename)) {
            $jsonString = file_get_contents($filename);
            $newData = json_decode($jsonString, true);
        } else {
            if (!file_exists($path . date('Y'))) {
                mkdir($path . date('Y'));
            }
            if (!file_exists($path . $folder)) {
                mkdir($path . $folder);
            }
            fopen($filename, "w");
        }

        $user = null;

        if ($this->security->getToken() != null && !($this->security->getToken() instanceof AnonymousToken)) {
            $user = $this->security->getToken()->getUser();
            $idUsuario = $user->getId();
            $usuario = $user->getUsername() . ' (' . $user->getNombreCompleto() . ')';
        } else {
            $idUsuario = '-';
            $usuario = 'Usuario no logueado';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            if (!isset($_SERVER['HTTP_CLIENT_IP'])) {
                $clientIp = $_SERVER['REMOTE_ADDR'];
            } else {
                $clientIp = @$_SERVER['HTTP_CLIENT_IP'];
            }
        }

        $errorArray = [
            "numero" => $errorNumber,
            "fecha" => date("d/m/Y H:i:s"),
            "mensaje" => $exception->getMessage(),
            "archivo" => $exception->getFile(),
            "linea" => $exception->getLine(),
            "usuario" => $usuario,
            "idUsuario" => $idUsuario,
            "session" => !empty($request->getSession()) ? $request->getSession()->all() : null,
            "ipCliente" => $clientIp,
            "agente" => $request->headers->get('User-Agent'),
            "html" => $errorHtml,
            "uri" => $request->getRequestUri(),
            "postParameters" => $request->request->all(),
            "getParameters" => $request->query->all(),
            "corregido" => 0,
        ];

        $newData["error_$statusCode"][] = $errorArray;

        $newJsonString = json_encode($newData, JSON_PRETTY_PRINT);

        file_put_contents($filename, $newJsonString);

        //Si está en modo debug, se guarda la excepcion, pero se muestra en pantalla
        if ($_SERVER['APP_DEBUG']) {
            return;
        }

        $response = new Response($this->twig->display('auditoria/error.html.twig', array('user' => $user, 'status_code' => $statusCode, 'errorNumber' => isset($errorNumber) ? $errorNumber : null)));

        $event->setResponse($response);
    }

    /**
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel) {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }

}
