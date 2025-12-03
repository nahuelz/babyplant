<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 */
class CuentaCorrienteReserva {

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="saldo", type="decimal", precision=10, scale=2)
     */
    private $saldo;

    /**
     * @ORM\OneToMany(targetEntity=Movimiento::class, mappedBy="cuentaCorrienteReserva", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $movimientos;

    /**
     * @ORM\OneToOne(targetEntity=Reserva::class, mappedBy="cuentaCorrienteReserva")
     */
    private $reserva;

    public function __construct() {
        $this->saldo = 0;
        $this->movimientos = new ArrayCollection();
    }

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

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function setSaldo(float $saldo): void
    {
        $this->saldo = $saldo;
    }

    /**
     * @return mixed
     */
    public function getReserva()
    {
        return $this->reserva;
    }

    /**
     * @param mixed $reserva
     */
    public function setReserva($reserva): void
    {
        $this->reserva = $reserva;
    }

    public function getPendiente(){
        return $this->getReserva()->getPendiente();
    }

    public function actualizarSaldo($monto){
        $this->saldo += $monto;
    }

    /**
     * @return mixed
     */
    public function getMovimientos()
    {
        return $this->movimientos;
    }

    /**
     * @param mixed $movimientos
     */
    public function setMovimientos($movimientos): void
    {
        $this->movimientos = $movimientos;
    }

    /**
     * @return mixed
     */
    public function addMovimiento(Movimiento $movimiento)
    {
        if (!$this->movimientos->contains($movimiento)) {
            $this->movimientos[] = $movimiento;
            $movimiento->setCuentaCorrienteReserva($this);
            $this->actualizarSaldo($movimiento->getMonto());
        }

        return $this;
    }
}