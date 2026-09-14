<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="tarea_asignacion")
 * @ORM\Entity()
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class TareaAsignacion
{
    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Tarea", inversedBy="asignaciones")
     * @ORM\JoinColumn(name="id_tarea", referencedColumnName="id", nullable=false)
     */
    private $tarea;

    /**
     * @ORM\ManyToOne(targetEntity="Usuario")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * @ORM\Column(type="datetime")
     */
    private $fechaInicio;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaFin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $porcentajeAvance;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $observacionAvance;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTarea(): ?Tarea
    {
        return $this->tarea;
    }

    public function setTarea(?Tarea $tarea): self
    {
        $this->tarea = $tarea;
        return $this;
    }

    public function getEmpleado(): ?Usuario
    {
        return $this->empleado;
    }

    public function setEmpleado(?Usuario $empleado): self
    {
        $this->empleado = $empleado;
        return $this;
    }

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fechaInicio;
    }

    public function setFechaInicio(\DateTimeInterface $fechaInicio): self
    {
        $this->fechaInicio = $fechaInicio;
        return $this;
    }

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fechaFin;
    }

    public function setFechaFin(?\DateTimeInterface $fechaFin): self
    {
        $this->fechaFin = $fechaFin;
        return $this;
    }

    public function getPorcentajeAvance(): ?int
    {
        return $this->porcentajeAvance;
    }

    public function setPorcentajeAvance(?int $porcentajeAvance): self
    {
        $this->porcentajeAvance = $porcentajeAvance;
        return $this;
    }

    public function getObservacionAvance(): ?string
    {
        return $this->observacionAvance;
    }

    public function setObservacionAvance(?string $observacionAvance): self
    {
        $this->observacionAvance = $observacionAvance;
        return $this;
    }

    public function estaActiva(): bool
    {
        return $this->fechaFin === null;
    }

    public function getDuracionSegundos(?\DateTimeInterface $hasta = null): int
    {
        if (!$this->fechaInicio) {
            return 0;
        }

        $fin = $this->fechaFin ?: ($hasta ?: new \DateTime());
        return max(0, $fin->getTimestamp() - $this->fechaInicio->getTimestamp());
    }
}
