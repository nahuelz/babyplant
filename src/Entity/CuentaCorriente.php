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
class CuentaCorriente {

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
     * @ORM\OneToMany(targetEntity=Movimiento::class, mappedBy="cuentaCorriente", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $movimientos;

    /**
     * @ORM\OneToOne(targetEntity=Usuario::class, inversedBy="cuentaCorriente")
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

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

    public function getSaldo(): int
    {
        return $this->saldo;
    }

    public function setSaldo(int $saldo): void
    {
        $this->saldo = $saldo;
    }

    public function getMovimientos(): ArrayCollection
    {
        return $this->movimientos;
    }

    public function setMovimientos(ArrayCollection $movimientos): void
    {
        $this->movimientos = $movimientos;
    }

    /**
     * @return mixed
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param mixed $cliente
     */
    public function setCliente($cliente): void
    {
        $this->cliente = $cliente;
    }

    public function getPendiente(){
        return $this->getCliente()->getPendiente();
    }

    
}