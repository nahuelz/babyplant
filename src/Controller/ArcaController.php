<?php

namespace App\Controller;

use Afip;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/arca")
 */
class ArcaController extends BaseController {
    /**
     * @Route("/CreateVoucher", name="arca_index", methods={"GET"})
     */
    public function index(): array {
        $data = array(
            'CantReg' 		=> 1, // Cantidad de comprobantes a registrar
            'PtoVta' 		=> 1, // Punto de venta
            'CbteTipo' 		=> 6, // Tipo de comprobante (ver tipos disponibles)
            'Concepto' 		=> 1, // Concepto del Comprobante: (1)Productos, (2)Servicios, (3)Productos y Servicios
            'DocTipo' 		=> 80, // Tipo de documento del comprador (ver tipos disponibles)
            'DocNro' 		=> 20111111112, // Numero de documento del comprador
            'CbteDesde' 	=> 1, // Numero de comprobante o numero del primer comprobante en caso de ser mas de uno
            'CbteHasta' 	=> 1, // Numero de comprobante o numero del ultimo comprobante en caso de ser mas de uno
            'CbteFch' 		=> intval(date('Ymd')), // (Opcional) Fecha del comprobante (yyyymmdd) o fecha actual si es nulo
            'ImpTotal' 		=> 121, // Importe total del comprobante
            'ImpTotConc' 	=> 0, // Importe neto no gravado
            'ImpNeto' 		=> 100, // Importe neto gravado
            'ImpOpEx' 		=> 0, // Importe exento de IVA
            'ImpIVA' 		=> 21, //Importe total de IVA
            'ImpTrib' 		=> 0, //Importe total de tributos
            'FchServDesde' 	=> NULL, // (Opcional) Fecha de inicio del servicio (yyyymmdd), obligatorio para Concepto 2 y 3
            'FchServHasta' 	=> NULL, // (Opcional) Fecha de fin del servicio (yyyymmdd), obligatorio para Concepto 2 y 3
            'FchVtoPago' 	=> NULL, // (Opcional) Fecha de vencimiento del servicio (yyyymmdd), obligatorio para Concepto 2 y 3
            'MonId' 		=> 'PES', //Tipo de moneda usada en el comprobante (ver tipos disponibles)('PES' para pesos argentinos)
            'MonCotiz' 		=> 1, // Cotización de la moneda usada (1 para pesos argentinos)
            "CondicionIVAReceptorId" => 5, // ID 6 = Responsable Monotributo
            'CbtesAsoc' 	=> array( // (Opcional) Comprobantes asociados
                array(
                    'Tipo' 		=> 91, // Tipo de comprobante (ver tipos disponibles)
                    'PtoVta' 	=> 1, // Punto de venta
                    'Nro' 		=> 1, // Numero de comprobante
                    'Cuit' 		=> 20111111112 // (Opcional) Cuit del emisor del comprobante
                )
            ),
            'Tributos' 		=> array( // (Opcional) Tributos asociados al comprobante
                array(
                    'Id' 		=>  99, // Id del tipo de tributo (ver tipos disponibles)
                    'Desc' 		=> 'Ingresos Brutos', // (Opcional) Descripcion
                    'BaseImp' 	=> 150, // Base imponible para el tributo
                    'Alic' 		=> 5.2, // Alícuota
                    'Importe' 	=> 0 // Importe del tributo
                )
            ),
            'Iva' 			=> array( // (Opcional) Alícuotas asociadas al comprobante
                array(
                    'Id' 		=> 5, // Id del tipo de IVA (ver tipos disponibles)
                    'BaseImp' 	=> 100, // Base imponible
                    'Importe' 	=> 21 // Importe
                )
            ),
            'Opcionales' 	=> array( // (Opcional) Campos auxiliares
                array(
                    'Id' 		=> 17, // Codigo de tipo de opcion (ver tipos disponibles)
                    'Valor' 	=> 2 // Valor
                )
            )
        );

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

        var_dump($afip->ElectronicBilling->CreateVoucher($data));

        die();



        // Descargamos el HTML de ejemplo (ver mas arriba)
        // y lo guardamos como bill.html
        $html = file_get_contents('certificado/bill.html');

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
        var_dump($res['file']);
    }

    /**
     * @Route("/pdf", name="app_arca_pdf", methods={"GET"})
     */
    public function pdf()
    {
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


        // Descargamos el HTML de ejemplo (ver mas arriba)
        // y lo guardamos como bill.html
        $html = file_get_contents('certificado/bill.html');

        //var_dump($html); die();


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
        return $this->redirect(($res['file']));
    }

}