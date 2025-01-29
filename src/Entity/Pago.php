<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 */
class Pago {
    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id")
     */
    private $modoPago;

    /**
     * @ORM\Column(name="monto", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $monto;

    /**
     * @ORM\ManyToOne(targetEntity=Remito::class, inversedBy="pagos")
     * @ORM\JoinColumn(name="id_remito", referencedColumnName="id")
     */
    private $remito;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getModoPago()
    {
        return $this->modoPago;
    }

    /**
     * @param mixed $modoPago
     */
    public function setModoPago($modoPago): void
    {
        $this->modoPago = $modoPago;
    }

    /**
     * @return mixed
     */
    public function getMonto()
    {
        return $this->monto;
    }

    /**
     * @param mixed $monto
     */
    public function setMonto($monto): void
    {
        $this->monto = $monto;
    }

    /**
     * @return mixed
     */
    public function getRemito()
    {
        return $this->remito;
    }

    /**
     * @param mixed $remito
     */
    public function setRemito($remito): void
    {
        $this->remito = $remito;
    }




}