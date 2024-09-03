<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\Auditoria;
use App\Entity\Traits\CodigoInterno;
use App\Entity\Traits\EntidadBasica as EntidadBasicaTrait;
use App\Entity\Traits\Habilitado;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * EntidadBasica
 *
 * @ORM\MappedSuperclass
 * @UniqueEntity(
 *      fields = {"nombre", "fechaBaja"},
 *      ignoreNull = false,
 *      message="El nombre ingresado ya se encuentra en uso."
 * )
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EntidadBasica
{
    use Auditoria, CodigoInterno, EntidadBasicaTrait, Habilitado;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
    }
}
