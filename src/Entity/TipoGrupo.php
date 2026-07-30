<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Grupo
 *
 * @ORM\Table(name="tipo_grupo")
 * @ORM\Entity
 */
class TipoGrupo extends EntidadBasica {

    /**
     * @ORM\OneToMany(targetEntity=TipoConcepto::class, mappedBy="tipoGrupo")
     */
    private $tipoConceptos;

    public function __construct()
    {
        $this->tipoConceptos = new ArrayCollection();
    }

    public function getTipoConceptos(): Collection
    {
        return $this->tipoConceptos;
    }

    public function addTipoConcepto(TipoConcepto $tipoConcepto): self
    {
        if (!$this->tipoConceptos->contains($tipoConcepto)) {
            $this->tipoConceptos[] = $tipoConcepto;
            $tipoConcepto->setTipoGrupo($this);
        }
        return $this;
    }

    public function removeTipoConcepto(TipoConcepto $tipoConcepto): self
    {
        if ($this->tipoConceptos->removeElement($tipoConcepto)) {
            if ($tipoConcepto->getTipoGrupo() === $this) {
                $tipoConcepto->setTipoGrupo(null);
            }
        }
        return $this;
    }
}
