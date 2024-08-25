<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 *
 * @Vich\Uploadable
 */
class ArchivoAdjunto {

    use Auditoria;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $descripcion;

    /**
     * @Vich\UploadableField(mapping="archivo_adjunto", fileNameProperty="nombreArchivo")
     *
     * @var File $archivo
     */
    protected $archivo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombreArchivo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customPath;

    public function getId(): ?int {
        return $this->id;
    }

    public function getNombre(): ?string {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * Set archivo
     *
     * @param File|UploadedFile $archivo
     *
     * @return ArchivoAdjunto
     */
    public function setArchivo(File $archivo = null) {
        $this->archivo = $archivo;
        return $this;
    }

    /**
     * Get archivo
     *
     * @return File|UploadedFile
     */
    public function getArchivo(): ?File {
        return $this->archivo;
    }

    public function getNombreArchivo(): ?string {
        return $this->nombreArchivo;
    }

    public function setNombreArchivo(string $nombreArchivo): self {
        $this->nombreArchivo = $nombreArchivo;

        return $this;
    }

    public function getCustomPath(): ?string {
        return $this->customPath;
    }

    public function setCustomPath(?string $customPath): self {
        $this->customPath = $customPath;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getNombreArchivoClear(): ?string {
        return API::stringCleaner($this->nombreArchivo);
    }

    /**
     *
     * @return string
     */
    public function getNombreClear(): ?string {
        return API::stringCleaner($this->nombre);
    }

}
