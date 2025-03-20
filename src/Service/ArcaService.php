<?php

namespace App\Service;

use App\Entity\API;
use Afip;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Description of SelectService
 */
class ArcaService {

    /**
     *
     * @var type
     */
    protected $container;

    /**
     *
     * @var type
     */
    protected $doctrine;

    /**
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->doctrine = $container->get("doctrine");
    }

    /**
     *
     * @return type
     */
    public function imprimirFactura($remito) {


        // Certificado (Puede estar guardado en archivos, DB, etc)
        $cert = file_get_contents('certificado/prueba.crt');

        // Key (Puede estar guardado en archivos, DB, etc)
        $key = file_get_contents('certificado/prueba');


        // Tu CUIT
        $tax_id = 20382971923;
        $afip = new Afip(array(
            'CUIT' => $tax_id,
            'cert' => $cert,
            'key' => $key
        ));

        $html = $this->render('arca/factura.html.twig', array('remito' => 'asd'))->getContent();

        // Nombre para el archivo (sin .pdf)
        $name = 'PDF de prueba';

        // Opciones para el archivo
        $options = array(
            "width" => 8, // Ancho de pagina en pulgadas. Usar 3.1 para ticket
            "marginLeft" => 0.4, // Margen izquierdo en pulgadas. Usar 0.1 para ticket
            "marginRight" => 0.4, // Margen derecho en pulgadas. Usar 0.1 para ticket
            "marginTop" => 0.4, // Margen superior en pulgadas. Usar 0.1 para ticket
            "marginBottom" => 0.4 // Margen inferior en pulgadas. Usar 0.1 para ticket
        );

        // Creamos el PDF
        $res = $afip->ElectronicBilling->CreatePDF(array(
            "html" => $html,
            "file_name" => $name,
            "options" => $options
        ));

        // Mostramos la url del archivo creado
        return $res['file'];

    }
}