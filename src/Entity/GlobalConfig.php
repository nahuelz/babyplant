<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 */
class GlobalConfig{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="columnas_ocultas", type="string", length=512, nullable=false)
     */
    protected $columnasOcultas;

    /**
     * @ORM\OneToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_usuario", referencedColumnName="id")
     */
    private $usuario;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getColumnasOcultas(): array
    {
        return explode(",", $this->columnasOcultas);
    }

    public function setColumnasOcultas($columnasOcultas): void
    {
        $this->columnasOcultas = $columnasOcultas;
    }

    /**
     * @return mixed
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * @param mixed $usuario
     */
    public function setUsuario($usuario): void
    {
        $this->usuario = $usuario;
    }






}