<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Vacaciones
 *
 * @ORM\Table(name="vacaciones", uniqueConstraints={@ORM\UniqueConstraint(name="uniq_empleado_anio", columns={"id_empleado","anio"})})
 * @ORM\Entity
 */
class Vacaciones {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="vacaciones")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * @ORM\Column(name="anio", type="integer", nullable=false)
     */
    private $anio;

    /**
     * @ORM\Column(name="dias_correspondientes", type="integer", nullable=false)
     */
    private $diasCorrespondientes;

    public function getId()
    {
        return $this->id;
    }

    public function getEmpleado(): ?Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(?Empleado $empleado): void
    {
        $this->empleado = $empleado;
    }

    public function getAnio(): ?int
    {
        return $this->anio;
    }

    public function setAnio(int $anio): void
    {
        $this->anio = $anio;
    }

    public function getDiasCorrespondientes(): ?int
    {
        return $this->diasCorrespondientes;
    }

    public function setDiasCorrespondientes(int $diasCorrespondientes): void
    {
        $this->diasCorrespondientes = $diasCorrespondientes;
    }

}
