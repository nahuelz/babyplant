<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EstadoLiquidacionHistorico
 *
 * @ORM\Table(name="estado_liquidacion_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoLiquidacionHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Liquidacion::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_liquidacion", referencedColumnName="id", nullable=true)
     */
    protected $liquidacion;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoLiquidacion::class)
     * @ORM\JoinColumn(name="id_estado_liquidacion", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $fecha;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLiquidacion(): ?Liquidacion
    {
        return $this->liquidacion;
    }

    public function setLiquidacion(?Liquidacion $liquidacion): self
    {
        $this->liquidacion = $liquidacion;

        return $this;
    }

    public function getEstado(): ?EstadoLiquidacion
    {
        return $this->estado;
    }

    public function setEstado(?EstadoLiquidacion $estado): self
    {
        $this->estado = $estado;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function setMotivo(string $motivo): self
    {
        $this->motivo = $motivo;

        return $this;
    }

}
