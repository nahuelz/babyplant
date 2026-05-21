<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cuenta_corriente_proveedor")
 */
class CuentaCorrienteProveedor
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="App\Entity\Proveedor",
     *     inversedBy="cuentaCorrienteProveedor"
     * )
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    private Proveedor $proveedor;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $saldoArs = '0.00';

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $saldoUsd = '0.00';

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\MovimientoProveedor",
     *     mappedBy="cuentaCorrienteProveedor"
     * )
     * @ORM\OrderBy({"fechaCreacion" = "DESC"})
     */
    private Collection $movimientos;

    public function __construct()
    {
        $this->movimientos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProveedor(): Proveedor
    {
        return $this->proveedor;
    }

    public function setProveedor(
        Proveedor $proveedor
    ): self {
        $this->proveedor = $proveedor;

        return $this;
    }

    public function getMovimientos(): Collection
    {
        return $this->movimientos;
    }

    public function addMovimiento(
        MovimientoProveedor $movimiento
    ): self {
        if (!$this->movimientos->contains($movimiento)) {
            $this->movimientos->add($movimiento);

            $movimiento->setCuentaCorrienteProveedor($this);
        }

        return $this;
    }

    public function removeMovimiento(
        MovimientoProveedor $movimiento
    ): self {
        if ($this->movimientos->removeElement($movimiento)) {
            if (
                $movimiento->getCuentaCorrienteProveedor() === $this
            ) {
                $movimiento->setCuentaCorrienteProveedor(null);
            }
        }

        return $this;
    }

    public function getSaldoArs(): string
    {
        return $this->saldoArs;
    }

    public function setSaldoArs(string $saldoArs): void
    {
        $this->saldoArs = $saldoArs;
    }

    public function getSaldoUsd(): string
    {
        return $this->saldoUsd;
    }

    public function setSaldoUsd(string $saldoUsd): void
    {
        $this->saldoUsd = $saldoUsd;
    }

    public function sumarSaldo(
        float $monto,
        string $tipoMoneda
    ): void
    {
        if ($tipoMoneda === 'USD') {
            $this->saldoUsd = (string)(
                (float)$this->saldoUsd + $monto
            );

            return;
        }

        $this->saldoArs = (string)(
            (float)$this->saldoArs + $monto
        );
    }
}