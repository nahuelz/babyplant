<?php

namespace App\Service;

use Exception;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Symfony\Component\HttpFoundation\Response;
use Afip;

class PrintService{

    /**
     * @throws Exception
     */
    public function printARCA($html, $ticket = true): Response
    {
        $afip = $this->createAfip();
        $options = $ticket ? $this->getOptionsTicketArca() : $this->getOptionsFactura();
        return $this->createAfipPDF($afip, $options, $html, 'Name');

    }

    /**
     * @throws MpdfException
     */
    public function printTicket($basePath, $filename, $html): ?string
    {
        $mpdfService = new Mpdf($this->getOptionsTicket());
        $mpdfService->WriteHTML($html);
        $mpdfService = $this->recortarTicket($mpdfService);
        $mpdfService->SetBasePath($basePath);
        $mpdfService->SetTitle($filename);
        $mpdfService->WriteHTML($html);
        return ($mpdfService->Output($filename, "I"));
    }

    /**
     * @throws MpdfException
     */
    public function printA4($basePath, $filename, $html): ?string
    {
        $mpdfService = new Mpdf($this->getOptionsA4());
        $mpdfService->SetBasePath($basePath);
        $mpdfService->SetTitle($filename);
        $mpdfService->WriteHTML($html);
        return ($mpdfService->Output($filename, "I"));
    }

    protected function createAfipPDF($afip, $options, $html, $name): Response
    {
        $res = $afip->ElectronicBilling->CreatePDF(array(
            "html" => $html,
            "file_name" => $name,
            "options" => $options
        ));

        $pdfContent = file_get_contents($res['file']);

        return (new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="factura.pdf"'
            ]
        ));
    }

    protected function getOptionsFactura(): array
    {
        return array(
            "width" => 8, // Ancho de pagina en pulgadas. Usar 3.1 para ticket
            "marginLeft" => 0.4, // Margen izquierdo en pulgadas. Usar 0.1 para ticket
            "marginRight" => 0.4, // Margen derecho en pulgadas. Usar 0.1 para ticket
            "marginTop" => 0.4, // Margen superior en pulgadas. Usar 0.1 para ticket
            "marginBottom" => 0.4 // Margen inferior en pulgadas. Usar 0.1 para ticket
        );
    }
    protected function getOptionsTicketArca(): array
    {
        return array(
            "width" => 3.1,
            "marginLeft" => 0.1,
            "marginRight" => 0.1,
            "marginTop" => 0.1,
            "marginBottom" => 0.1
        );
    }

    /**
     * @throws Exception
     */
    protected function createAfip(): Afip
    {
        $cert = file_get_contents('certificado/prueba.crt');

        $key = file_get_contents('certificado/prueba');

        $tax_id = 20382971923;

        return new Afip(array(
            'CUIT' => $tax_id,
            'cert' => $cert,
            'key' => $key
        ));
    }

    private function getOptionsA4(): array
    {
        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 0,
            'default_font' => '',
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 4,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'orientation' => 'P',
        ];
    }

    private function getOptionsTicket(): array
    {
        return [
            'mode' => 'utf-8',
            'format' => [80, 1000], // ancho x alto en milímetros
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
            'orientation' => 'P',
        ];
    }

    protected function recortarTicket($mpdfService){
        // Obtener altura usada en milímetros
        $usedHeight = $mpdfService->y; // posición vertical actual (mm)
        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, $usedHeight + 20], // ancho x alto en milímetros
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
            'orientation' => 'L',
        ]);
        return $mpdfService;
    }
}