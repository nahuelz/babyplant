<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;

/**
 * SolicitudVacaciones
 *
 * Representa cada período de vacaciones efectivamente tomado por un empleado.
 *
 * @ORM\Table(name="solicitud_vacaciones")
 * @ORM\Entity
 */
class SolicitudVacaciones {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="solicitudesVacaciones")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * Año del período de vacaciones del cual se están tomando los días
     * (puede ser distinto al año en que se toman, ej. días pendientes de un período anterior).
     *
     * @ORM\Column(name="periodo", type="integer", nullable=false)
     */
    private $periodo;

    /**
     * @ORM\Column(name="fecha_solicitud", type="date", nullable=false)
     */
    private $fechaSolicitud;

    /**
     * @ORM\Column(name="fecha_desde", type="date", nullable=false)
     */
    private $fechaDesde;

    /**
     * @ORM\Column(name="fecha_hasta", type="date", nullable=false)
     */
    private $fechaHasta;

    /**
     * @ORM\Column(name="fecha_reincorporacion", type="date", nullable=true)
     */
    private $fechaReincorporacion;

    /**
     * Cantidad de días tomados (permite medios días, ej. 0.5).
     *
     * @ORM\Column(name="cantidad_dias", type="decimal", precision=5, scale=1, nullable=false)
     */
    private $cantidadDias;

    /**
     * Días que quedaban disponibles del período luego de registrar esta solicitud (snapshot histórico).
     *
     * @ORM\Column(name="dias_restantes_periodo", type="decimal", precision=6, scale=1, nullable=false)
     */
    private $diasRestantesPeriodo;

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

    public function getPeriodo(): ?int
    {
        return $this->periodo;
    }

    public function setPeriodo(int $periodo): void
    {
        $this->periodo = $periodo;
    }

    public function getFechaSolicitud(): ?\DateTimeInterface
    {
        return $this->fechaSolicitud;
    }

    public function setFechaSolicitud(\DateTimeInterface $fechaSolicitud): void
    {
        $this->fechaSolicitud = $fechaSolicitud;
    }

    public function getFechaDesde(): ?\DateTimeInterface
    {
        return $this->fechaDesde;
    }

    public function setFechaDesde(\DateTimeInterface $fechaDesde): void
    {
        $this->fechaDesde = $fechaDesde;
    }

    public function getFechaHasta(): ?\DateTimeInterface
    {
        return $this->fechaHasta;
    }

    public function setFechaHasta(\DateTimeInterface $fechaHasta): void
    {
        $this->fechaHasta = $fechaHasta;
    }

    public function getFechaReincorporacion(): ?\DateTimeInterface
    {
        return $this->fechaReincorporacion;
    }

    public function setFechaReincorporacion(?\DateTimeInterface $fechaReincorporacion): void
    {
        $this->fechaReincorporacion = $fechaReincorporacion;
    }

    public function getCantidadDias()
    {
        return $this->cantidadDias;
    }

    public function setCantidadDias($cantidadDias): void
    {
        $this->cantidadDias = $cantidadDias;
    }

    public function getDiasRestantesPeriodo()
    {
        return $this->diasRestantesPeriodo;
    }

    public function setDiasRestantesPeriodo($diasRestantesPeriodo): void
    {
        $this->diasRestantesPeriodo = $diasRestantesPeriodo;
    }

}
