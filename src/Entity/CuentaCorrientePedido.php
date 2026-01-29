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
class CuentaCorrientePedido {

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
     * @ORM\OneToMany(targetEntity=Movimiento::class, mappedBy="cuentaCorrientePedido", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $movimientos;

    /**
     * @ORM\OneToOne(targetEntity=Pedido::class, mappedBy="cuentaCorrientePedido")
     */
    private $pedido;

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
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido($pedido): void
    {
        $this->pedido = $pedido;
    }

    public function getPendiente(){
        return $this->getPedido()->getPendiente();
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
            $movimiento->setCuentaCorrientePedido($this);
            $this->actualizarSaldo($movimiento->getMonto());
        }

        return $this;
    }

    public function puedeAjustarse($monto){
        return $this->getSaldo() >= (float) $monto;
    }
}