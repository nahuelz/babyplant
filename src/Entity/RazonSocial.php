<?php

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="razon_social")
 * @ORM\Entity
 *
 * @UniqueEntity(fields="cuit", message="El cuit ya existe.")
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class RazonSocial
{

    use Traits\Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $razonSocial;

    /**
     * @ORM\Column(type="string", length=20, nullable=true, unique=true)
     */
    private $cuit;

    public function __toString(): string {
        return $this->razonSocial;
    }

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
     * @return mixed
     */
    public function getRazonSocial()
    {
        return $this->razonSocial;
    }

    /**
     * @param mixed $razonSocial
     */
    public function setRazonSocial($razonSocial): void
    {
        $this->razonSocial = $razonSocial;
    }

    /**
     * @return mixed
     */
    public function getCuit()
    {
        return $this->cuit;
    }

    /**
     * @param mixed $cuit
     */
    public function setCuit($cuit): void
    {
        $this->cuit = $cuit;
    }




}
