<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="tarea")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Tarea
{
    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $descripcion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $titulo;

    /**
     * @ORM\Column(name="porcentaje_avance", type="integer", options={"default": 0})
     */
    private $porcentajeAvance = 0;

    /**
     * @ORM\Column(name="observacion_avance", type="text", nullable=true)
     */
    private $observacionAvance;

    /**
     * @ORM\ManyToOne(targetEntity="EstadoTarea")
     * @ORM\JoinColumn(name="id_estado_tarea", referencedColumnName="id")
     */
    private $estado;

    /**
     * @ORM\ManyToMany(targetEntity="Usuario")
     * @ORM\JoinTable(
     *     name="tarea_empleado",
     *     joinColumns={@ORM\JoinColumn(name="id_tarea", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="id_usuario", referencedColumnName="id")}
     * )
     */
    private $empleados;

    /**
     * @ORM\ManyToOne(targetEntity="Usuario")
     * @ORM\JoinColumn(name="id_asignado_por", referencedColumnName="id", nullable=true)
     */
    private $asignadoPor;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $asignadoEn;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $terminadoEn;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $canceladoEn;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fechaProgramada;

    /**
     * @ORM\OneToMany(targetEntity="EstadoTareaHistorico", mappedBy="tarea", cascade={"persist", "remove"})
     * @ORM\OrderBy({"fechaCreacion" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\OneToMany(targetEntity="TareaAsignacion", mappedBy="tarea", cascade={"persist", "remove"})
     * @ORM\OrderBy({"fechaInicio" = "DESC"})
     */
    private $asignaciones;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
        $this->empleados = new ArrayCollection();
        $this->asignaciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): self
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getPorcentajeAvance(): int
    {
        return $this->porcentajeAvance;
    }

    public function setPorcentajeAvance(int $porcentajeAvance): self
    {
        if (!in_array($porcentajeAvance, range(0, 100, 10), true)) {
            throw new \InvalidArgumentException('El porcentaje de avance debe estar entre 0 y 100, en intervalos de 10.');
        }

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

    public function getEstado(): ?EstadoTarea
    {
        return $this->estado;
    }

    public function setEstado(?EstadoTarea $estado): self
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection<int, Usuario>
     */
    public function getEmpleados(): Collection
    {
        return $this->empleados;
    }

    public function addEmpleado(Usuario $empleado): self
    {
        if (!$this->empleados->contains($empleado)) {
            $this->empleados[] = $empleado;
        }

        return $this;
    }

    public function removeEmpleado(Usuario $empleado): self
    {
        $this->empleados->removeElement($empleado);

        return $this;
    }

    public function getAsignadoPor(): ?Usuario
    {
        return $this->asignadoPor;
    }

    public function setAsignadoPor(?Usuario $asignadoPor): self
    {
        $this->asignadoPor = $asignadoPor;

        return $this;
    }

    public function getAsignadoEn(): ?\DateTimeInterface
    {
        return $this->asignadoEn;
    }

    public function setAsignadoEn(?\DateTimeInterface $asignadoEn): self
    {
        $this->asignadoEn = $asignadoEn;

        return $this;
    }

    public function getTerminadoEn(): ?\DateTimeInterface
    {
        return $this->terminadoEn;
    }

    public function setTerminadoEn(?\DateTimeInterface $terminadoEn): self
    {
        $this->terminadoEn = $terminadoEn;

        return $this;
    }

    public function getCanceladoEn(): ?\DateTimeInterface
    {
        return $this->canceladoEn;
    }

    public function setCanceladoEn(?\DateTimeInterface $canceladoEn): self
    {
        $this->canceladoEn = $canceladoEn;

        return $this;
    }

    public function getFechaProgramada(): ?\DateTimeInterface
    {
        return $this->fechaProgramada;
    }

    public function setFechaProgramada(?\DateTimeInterface $fechaProgramada): self
    {
        $this->fechaProgramada = $fechaProgramada;

        return $this;
    }

    /**
     * @return Collection<int, EstadoTareaHistorico>
     */
    public function getHistoricoEstados(): Collection
    {
        return $this->historicoEstados;
    }

    public function addHistoricoEstado(EstadoTareaHistorico $historico): self
    {
        if (!$this->historicoEstados->contains($historico)) {
            $this->historicoEstados[] = $historico;
            $historico->setTarea($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoTareaHistorico $historico): self
    {
        if ($this->historicoEstados->contains($historico)) {
            $this->historicoEstados->removeElement($historico);
            if ($historico->getTarea() === $this) {
                $historico->setTarea(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TareaAsignacion>
     */
    public function getAsignaciones(): Collection
    {
        return $this->asignaciones;
    }

    public function addAsignacion(TareaAsignacion $asignacion): self
    {
        if (!$this->asignaciones->contains($asignacion)) {
            $this->asignaciones[] = $asignacion;
            $asignacion->setTarea($this);
        }
        return $this;
    }

    public function getAsignacionActiva(Usuario $empleado): ?TareaAsignacion
    {
        foreach ($this->asignaciones as $asignacion) {
            if ($asignacion->getEmpleado() === $empleado && $asignacion->estaActiva()) {
                return $asignacion;
            }
        }
        return null;
    }

    public function getDuracionTotalSegundosPorEmpleado(Usuario $empleado): int
    {
        $total = 0;
        foreach ($this->asignaciones as $asignacion) {
            if ($asignacion->getEmpleado() === $empleado) {
                $total += $asignacion->getDuracionSegundos();
            }
        }
        return $total;
    }

    public function getDuracionesTotalesPorEmpleado(): array
    {
        $totales = [];
        foreach ($this->asignaciones as $asignacion) {
            $empleado = $asignacion->getEmpleado();
            if (!$empleado) {
                continue;
            }
            $id = $empleado->getId();
            if (!isset($totales[$id])) {
                $totales[$id] = ['empleado' => $empleado, 'segundos' => 0];
            }
            $totales[$id]['segundos'] += $asignacion->getDuracionSegundos();
        }
        return array_values($totales);
    }
}
