<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Repository\HorasEmpleadoRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * HorasEmpleado
 *
 * @ORM\Entity(repositoryClass=HorasEmpleadoRepository::class)
 * @ORM\Table(name="horas_empleado")
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class HorasEmpleado
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="horas")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private ?Empleado $empleado = null;

    /**
     * @ORM\Column(name="fecha", type="date", nullable=false)
     */
    private ?\DateTimeInterface $fecha = null;

    /**
     * @ORM\Column(name="motivo", type="string", length=255, nullable=true)
     */
    private ?string $motivo = null;

    /**
     * @ORM\Column(name="cantidad_horas", type="decimal", precision=8, scale=2, nullable=false)
     */
    private string $cantidadHoras = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmpleado(): ?Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(?Empleado $empleado): self
    {
        $this->empleado = $empleado;
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

    public function setMotivo(?string $motivo): self
    {
        $this->motivo = $motivo;
        return $this;
    }

    public function getCantidadHoras(): string
    {
        return $this->cantidadHoras;
    }

    public function setCantidadHoras(string $cantidadHoras): self
    {
        $this->cantidadHoras = $cantidadHoras;
        return $this;
    }
}
