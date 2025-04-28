<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 */
class Movimiento {
    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TipoMovimiento::class)
     * @ORM\JoinColumn(name="id_tipo_movimiento", referencedColumnName="id")
     */
    private $tipoMovimiento;

    /**
     * @ORM\ManyToOne(targetEntity=TipoReferencia::class)
     * @ORM\JoinColumn(name="id_tipo_referencia", referencedColumnName="id")
     */
    private $tipoReferencia;

    /**
     * @ORM\Column(name="monto", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $monto;


    /**
     * @ORM\Column(name="monto_cuenta", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $saldoCuenta;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $descripcion;

    /**
     * @ORM\ManyToOne(targetEntity=CuentaCorriente::class, inversedBy="movimientos")
     * @ORM\JoinColumn(name="id_cuenta_corriente", referencedColumnName="id")
     */
    private $cuentaCorriente;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class)
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id")
     */
    private $pedidoProducto;

    /**
     * @ORM\ManyToOne(targetEntity=Remito::class)
     * @ORM\JoinColumn(name="id_remito", referencedColumnName="id")
     */
    private $remito;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id")
     */
    private $modoPago;

    /**
     * @ORM\OneToOne(targetEntity=Pago::class)
     * @ORM\JoinColumn(name="id_pago", referencedColumnName="id")
     */
    private $pago;

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
    public function getTipoMovimiento()
    {
        return $this->tipoMovimiento;
    }

    /**
     * @param mixed $tipoMovimiento
     */
    public function setTipoMovimiento($tipoMovimiento): void
    {
        $this->tipoMovimiento = $tipoMovimiento;
    }

    /**
     * @return mixed
     */
    public function getTipoReferencia()
    {
        return $this->tipoReferencia;
    }

    /**
     * @param mixed $tipoReferencia
     */
    public function setTipoReferencia($tipoReferencia): void
    {
        $this->tipoReferencia = $tipoReferencia;
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

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    /**
     * @return mixed
     */
    public function getCuentaCorriente()
    {
        return $this->cuentaCorriente;
    }

    /**
     * @param mixed $cuentaCorriente
     */
    public function setCuentaCorriente($cuentaCorriente): void
    {
        $this->cuentaCorriente = $cuentaCorriente;
    }

    /**
     * @return mixed
     */
    public function getPedidoProducto()
    {
        return $this->pedidoProducto;
    }

    /**
     * @param mixed $pedidoProducto
     */
    public function setPedidoProducto($pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
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

    public function getProductoRemito(){
        if ($this->remito != null){
            return $this->remito;
        }

        if ($this->pedidoProducto != null){
            return $this->pedidoProducto->getNombreCorto();
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getPago()
    {
        return $this->pago;
    }

    /**
     * @param mixed $pago
     */
    public function setPago($pago): void
    {
        $this->pago = $pago;
    }

    /**
     * @return mixed
     */
    public function getSaldoCuenta()
    {
        return $this->saldoCuenta;
    }

    /**
     * @param mixed $saldoCuenta
     */
    public function setSaldoCuenta($saldoCuenta): void
    {
        $this->saldoCuenta = $saldoCuenta;
    }



}