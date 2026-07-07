<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_reventa_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoReventaHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Reventa::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_reventa", referencedColumnName="id", nullable=true)
     */
    protected $reventa;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoReventa::class)
     * @ORM\JoinColumn(name="id_estado_reventa", referencedColumnName="id", nullable=false)
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

    public function getId(): ?int {
        return $this->id;
    }

    public function getReventa(): ?Reventa {
        return $this->reventa;
    }

    public function setReventa(?Reventa $reventa): self {
        $this->reventa = $reventa;

        return $this;
    }

    public function getEstado(): ?EstadoReventa {
        return $this->estado;
    }

    public function setEstado(?EstadoReventa $estado): self {
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

}
