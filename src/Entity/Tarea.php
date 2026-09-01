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
     * @ORM\ManyToOne(targetEntity="EstadoTarea")
     * @ORM\JoinColumn(name="id_estado_tarea", referencedColumnName="id")
     */
    private $estado;

    /**
     * @ORM\ManyToOne(targetEntity="Usuario")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=true)
     */
    private $empleado;

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

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
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

    public function getEstado(): ?EstadoTarea
    {
        return $this->estado;
    }

    public function setEstado(?EstadoTarea $estado): self
    {
        $this->estado = $estado;

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
}
