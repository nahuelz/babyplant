<?php

namespace App\Entity;

use App\Entity\Traits\CodigoInterno;
use App\Entity\Traits\Habilitado;
use Doctrine\ORM\Mapping as ORM;

/**
 * EstadoTarea
 *
 * @ORM\Table(name="estado_tarea")
 * @ORM\Entity()
 */
class EstadoTarea extends EntidadBasica
{
    use CodigoInterno;
    use Habilitado;
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $inicial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $icono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $colorIcono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $className;

    public function getInicial(): ?string
    {
        return $this->inicial;
    }

    public function setInicial(?string $inicial): self
    {
        $this->inicial = $inicial;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): self
    {
        $this->icono = $icono;

        return $this;
    }

    public function getColorIcono(): ?string
    {
        return $this->colorIcono;
    }

    public function setColorIcono(?string $colorIcono): self
    {
        $this->colorIcono = $colorIcono;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): self
    {
        $this->className = $className;

        return $this;
    }
}
