<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Empleado
 *
 * @ORM\Table(name="empleado")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Empleado {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="nombre", type="string", length=100, nullable=false)
     */
    private $nombre;

    /**
     * @ORM\Column(name="apellido", type="string", length=100, nullable=false)
     */
    private $apellido;

    /**
     * @ORM\Column(name="nacionalidad", type="string", length=50, nullable=true)
     */
    private $nacionalidad;

    /**
     * @ORM\Column(name="dni", type="string", length=20, nullable=false)
     */
    private $dni;

    /**
     * @ORM\Column(name="cuil", type="string", length=20, nullable=true)
     */
    private $cuil;

    /**
     * @ORM\Column(name="fecha_ingreso", type="date", nullable=false)
     */
    private $fechaIngreso;

    /**
     * @ORM\ManyToOne(targetEntity=Categoria::class)
     * @ORM\JoinColumn(name="id_categoria", referencedColumnName="id", nullable=true)
     */
    private $categoria;

    /**
     * @ORM\ManyToOne(targetEntity=ObraSocial::class)
     * @ORM\JoinColumn(name="id_obra_social", referencedColumnName="id", nullable=true)
     */
    private $obraSocial;

    /**
     * @ORM\ManyToOne(targetEntity=Banco::class)
     * @ORM\JoinColumn(name="id_banco", referencedColumnName="id", nullable=true)
     */
    private $banco;

    /**
     * @ORM\Column(name="telefono", type="string", length=50, nullable=true)
     */
    private $telefono;

    /**
     * @ORM\Column(name="observaciones", type="text", nullable=true)
     */
    private $observaciones;

    /**
     * @ORM\ManyToOne(targetEntity=TipoModalidadPago::class)
     * @ORM\JoinColumn(name="id_tipo_modalidad_pago", referencedColumnName="id", nullable=false)
     */
    private $modalidadPago;

    /**
     * @ORM\Column(name="activo", type="boolean", nullable=false, options={"default": true})
     */
    private $activo = true;

    /**
     * @ORM\OneToMany(targetEntity=Vacaciones::class, mappedBy="empleado", cascade={"all"})
     */
    private $vacaciones;

    /**
     * @ORM\OneToMany(targetEntity=Adelanto::class, mappedBy="empleado", cascade={"all"})
     */
    private $adelantos;

    /**
     * @ORM\OneToMany(targetEntity=Prestamo::class, mappedBy="empleado", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $prestamos;

    /**
     * @ORM\OneToMany(targetEntity=SolicitudVacaciones::class, mappedBy="empleado", cascade={"all"})
     */
    private $solicitudesVacaciones;

    /**
     * @ORM\OneToMany(targetEntity=Liquidacion::class, mappedBy="empleado", cascade={"all"})
     */
    private $liquidaciones;

    public function __construct()
    {
        $this->vacaciones = new ArrayCollection();
        $this->adelantos = new ArrayCollection();
        $this->prestamos = new ArrayCollection();
        $this->solicitudesVacaciones = new ArrayCollection();
        $this->liquidaciones = new ArrayCollection();
        $this->activo = true;
    }

    public function __toString(): string
    {
        return $this->getNombreCompleto();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): void
    {
        $this->apellido = $apellido;
    }

    public function getNombreCompleto(): string
    {
        return trim($this->apellido . ' ' . $this->nombre);
    }

    public function getNacionalidad(): ?string
    {
        return $this->nacionalidad;
    }

    public function setNacionalidad(?string $nacionalidad): void
    {
        $this->nacionalidad = $nacionalidad;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): void
    {
        $this->dni = $dni;
    }

    public function getCuil(): ?string
    {
        return $this->cuil;
    }

    public function setCuil(?string $cuil): void
    {
        $this->cuil = $cuil;
    }

    public function getFechaIngreso()
    {
        return $this->fechaIngreso;
    }

    public function setFechaIngreso($fechaIngreso): void
    {
        $this->fechaIngreso = $fechaIngreso;
    }

    /**
     * Antigüedad calculada al momento de la consulta (no se persiste).
     */
    public function getAntiguedad(): ?\DateInterval
    {
        if (!$this->fechaIngreso) {
            return null;
        }

        return $this->fechaIngreso->diff(new \DateTime());
    }

    public function getAntiguedadTexto(): string
    {
        $antiguedad = $this->getAntiguedad();
        if (!$antiguedad) {
            return '-';
        }

        return sprintf('%d años, %d meses', $antiguedad->y, $antiguedad->m);
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getObraSocial(): ?ObraSocial
    {
        return $this->obraSocial;
    }

    public function setObraSocial(?ObraSocial $obraSocial): void
    {
        $this->obraSocial = $obraSocial;
    }

    public function getBanco(): ?Banco
    {
        return $this->banco;
    }

    public function setBanco(?Banco $banco): void
    {
        $this->banco = $banco;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getModalidadPago(): ?TipoModalidadPago
    {
        return $this->modalidadPago;
    }

    public function setModalidadPago(?TipoModalidadPago $modalidadPago): void
    {
        $this->modalidadPago = $modalidadPago;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    public function getVacaciones()
    {
        return $this->vacaciones;
    }

    public function addVacaciones(Vacaciones $vacaciones): self
    {
        if (!$this->vacaciones->contains($vacaciones)) {
            $this->vacaciones[] = $vacaciones;
            $vacaciones->setEmpleado($this);
        }

        return $this;
    }

    public function getAdelantos()
    {
        return $this->adelantos;
    }

    public function addAdelanto(Adelanto $adelanto): self
    {
        if (!$this->adelantos->contains($adelanto)) {
            $this->adelantos[] = $adelanto;
            $adelanto->setEmpleado($this);
        }

        return $this;
    }

    public function getPrestamos()
    {
        return $this->prestamos;
    }

    public function addPrestamo(Prestamo $prestamo): self
    {
        if (!$this->prestamos->contains($prestamo)) {
            $this->prestamos[] = $prestamo;
            $prestamo->setEmpleado($this);
        }

        return $this;
    }

    public function removePrestamo(Prestamo $prestamo): self
    {
        if ($this->prestamos->removeElement($prestamo)) {
            if ($prestamo->getEmpleado() === $this) {
                $prestamo->setEmpleado(null);
            }
        }

        return $this;
    }

    public function getSolicitudesVacaciones()
    {
        return $this->solicitudesVacaciones;
    }

    public function addSolicitudVacaciones(SolicitudVacaciones $solicitudVacaciones): self
    {
        if (!$this->solicitudesVacaciones->contains($solicitudVacaciones)) {
            $this->solicitudesVacaciones[] = $solicitudVacaciones;
            $solicitudVacaciones->setEmpleado($this);
        }

        return $this;
    }

    public function getLiquidaciones()
    {
        return $this->liquidaciones;
    }

    public function addLiquidacion(Liquidacion $liquidacion): self
    {
        if (!$this->liquidaciones->contains($liquidacion)) {
            $this->liquidaciones[] = $liquidacion;
            $liquidacion->setEmpleado($this);
        }

        return $this;
    }

}
