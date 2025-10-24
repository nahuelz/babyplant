<?php

namespace App\Entity;

use App\Entity\EstadoMesada;
use App\Entity\Mesada;
use App\Entity\Traits\Auditoria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_mesada_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoMesadaHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    protected $mesada;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoMesada::class)
     * @ORM\JoinColumn(name="id_estado_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $fecha;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivo;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="decimal", precision=6, scale=1, nullable=true)
     */
    private mixed $cantidadBandejas;

    public function __construct()
    {
        $this->cantidadBandejas = 0;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getMesada(): ?Mesada {
        return $this->mesada;
    }

    public function setMesada(?Mesada $mesada): self {
        $this->mesada = $mesada;

        return $this;
    }

    public function getEstado(): ?EstadoMesada {
        return $this->estado;
    }

    public function setEstado(?EstadoMesada $estado): self {
        $this->estado = $estado;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self {
        $this->fecha = $fecha;

        return $this;
    }

    public function getMotivo(): ?string {
        return $this->motivo;
    }

    public function setMotivo(string $motivo): self
    {
        $this->motivo = $motivo;

        return $this;
    }

    /**
     * @return float|int|null
     */
    public function getCantidadBandejas(): float|int|null
    {
        if ($this->cantidadBandejas === null) {
            return null;
        }
        return $this->cantidadBandejas == (int)$this->cantidadBandejas
            ? (int)$this->cantidadBandejas
            : (float)$this->cantidadBandejas;
    }

    public function setCantidadBandejas(mixed $cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }


}
