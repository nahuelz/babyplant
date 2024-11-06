<?php

namespace App\Entity;

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends \TCPDF {

    public function autor(){
        // set document information
        $this->SetCreator('Nahuel Zanelli');
        $this->SetAuthor('Nahuel Zanelli');
        $this->SetTitle('BabyPlant');
        $this->SetSubject('PDF Example');
    }

    public function header() {
        $fhtml = '
            <div>
                <div>
                    <img src="http://localhost/babyplant/public/images/logo/logo.png"  width="350" height="75" />
                    <p style="font-size:8px;">
                    </p>
                </div>
            </div>
      ';
        $this->writeHTML($fhtml, true, false, true, false, 'C');

    }

    public function footer() {

      $this->SetY(-70);
      $this->setX(-200);
      $fhtml = '
            <div>
                <div>
                    <img src="http://localhost/babyplant/public/images/logo/logo.png"  width="275" height="50" />
                    <p style="font-size:8px;">
                        <strong>BabyPlant SRL</strong><br>
                        Avda. 44 Nº 4303, Lisandro Olmos<br>
                        La Plata, Buenos Aires<br>
                        Tel.: +54 (221) 669-0199<br>
                        Whatsapp: +54 (221) 306-2118<br>
                        E-mail: babyplantsrl@gmail.com
                    </p>
                </div>
            </div>
      ';
      $this->writeHTML($fhtml, true, false, true, false, 'C');
      $this->SetFont('helvetica', 'I', 8);
      $this->Cell(10, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

}