<?php

class MYPDF extends TCPDF {

    public function Header() {
        $file = __DIR__ . '/../../public/images/logo/pdf_header.jpg';
        $this->Image($file, 10, 10, 180, 0, 'jpeg');
        // Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
    }

}
