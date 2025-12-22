<?php

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="razon_social")
 * @ORM\Entity
 *
 * @UniqueEntity(fields="cuit", message="El cuit ya existe.")
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class RazonSocial
{

    use Traits\Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $razonSocial;

    /**
     * @ORM\Column(type="string", length=20, nullable=true, unique=true)
     */
    private $cuit;

    /**
     * @ORM\OneToMany(targetEntity=Usuario::class, mappedBy="razonSocial")
     */
    private $clientes;

    public function __construct()
    {
        $this->clientes = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->razonSocial;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getRazonSocial()
    {
        return $this->razonSocial;
    }

    /**
     * @param mixed $razonSocial
     */
    public function setRazonSocial($razonSocial): void
    {
        $this->razonSocial = $razonSocial;
    }

    /**
     * @return mixed
     */
    public function getCuit()
    {
        return $this->cuit;
    }

    /**
     * @param mixed $cuit
     */
    public function setCuit($cuit): void
    {
        $this->cuit = $cuit;
    }

    public function getClientes()
    {
        return $this->clientes;
    }

    public function setClientes(ArrayCollection $clientes): void
    {
        $this->clientes = $clientes;
    }

    public function getSaldoAdelantos(){
        $saldo = 0;
        foreach ($this->getClientes() as $cliente) {
            $saldo += $cliente->getSaldoAdelantos();
        }
        return $saldo;
    }
    public function getSaldoAdelantosReservas(){
        $saldo = 0;
        foreach ($this->getClientes() as $cliente) {
            $saldo += $cliente->getSaldoAdelantosReservas();
        }
        return $saldo;
    }

    public function getPendiente(){
        $pendiente = 0;
        foreach ($this->getClientes() as $cliente) {
            $pendiente += $cliente->getCuentaCorrienteUsuario()->getPendiente();
        }
        return $pendiente;
    }

    public function getSaldo(){
        $saldo = 0;
        foreach ($this->getClientes() as $cliente) {
            $saldo += $cliente->getCuentaCorrienteUsuario()->getSaldo();
        }
        return $saldo;
    }

    public function getPedidos() {
        $pedidos = [];

        foreach ($this->getClientes() as $cliente) {
            $pedidos = array_merge($pedidos, $cliente->getPedidos()->toArray());
        }

        return $pedidos;
    }

    public function getReservas() {
        $reservas = [];

        foreach ($this->getClientes() as $cliente) {
            $reservas = array_merge($reservas, $cliente->getReservas()->toArray());
        }

        return $reservas;
    }

    public function getEntregas() {
        $entregas = [];

        foreach ($this->getClientes() as $cliente) {
            $entregas = array_merge($entregas, $cliente->getEntregas()->toArray());
        }

        return $entregas;
    }

    public function getRemitos() {
        $remitos = [];

        foreach ($this->getClientes() as $cliente) {
            $remitos = array_merge($remitos, $cliente->getRemitos()->toArray());
        }
        return $remitos;
    }

    public function getTieneMovimientos(){
        foreach ($this->getClientes() as $cliente) {
            if ($cliente->getTieneMovimientos()) {
                return true;
            }
        }
        return false;
    }

    public function getMovimientos() {
        $movimientos = [];

        foreach ($this->getClientes() as $cliente) {
            $movimientos = array_merge($movimientos, $cliente->getCuentaCorrienteUsuario()->getMovimientos()->toArray());
        }
        return $movimientos;
    }



}
