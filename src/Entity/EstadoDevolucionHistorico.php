<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_devolucion_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoDevolucionHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Devolucion::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_devolucion", referencedColumnName="id", nullable=true)
     */
    protected $devolucion;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoDevolucion::class)
     * @ORM\JoinColumn(name="id_estado_devolucion", referencedColumnName="id", nullable=false)
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

    public function getDevolucion(): ?Devolucion {
        return $this->devolucion;
    }

    public function setDevolucion(?Devolucion $devolucion): self {
        $this->devolucion = $devolucion;

        return $this;
    }

    public function getEstado(): ?EstadoDevolucion {
        return $this->estado;
    }

    public function setEstado(?EstadoDevolucion $estado): self {
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
