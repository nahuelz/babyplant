<?php

namespace App\Entity;

use App\Entity\Traits;
use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="remito")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Remito {

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity=RemitoProducto::class, mappedBy="remito", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $remitosProductos;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    
    public function __construct()
    {
        $this->remitosProductos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Remito N° '.$this->getId();
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCodigo(){
        return str_pad($this->id, 6, "0", STR_PAD_LEFT);
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
    public function getRemitosProductos()
    {
        return $this->remitosProductos;
    }

    /**
     * @param mixed $remitosProductos
     */
    public function setRemitosProductos($remitosProductos): void
    {
        $this->remitosProductos = $remitosProductos;

        foreach ($remitosProductos as $remitoProducto){
            $remitoProducto->setRemito($this);
        }
    }

    /**
     * @return mixed
     */
    public function addRemitosProductos($remitoProducto)
    {
        if (!$this->remitosProductos->contains($remitoProducto)) {
            $this->remitosProductos[] = $remitoProducto;
            $remitoProducto->setRemito($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param mixed $cliente
     */
    public function setCliente($cliente): void
    {
        $this->cliente = $cliente;
    }

    public function removeRemitosProducto(RemitoProducto $remitosProducto): static
    {
        if ($this->remitosProductos->removeElement($remitosProducto)) {
            // set the owning side to null (unless already changed)
            if ($remitosProducto->getRemito() === $this) {
                $remitosProducto->setRemito(null);
            }
        }

        return $this;
    }

    public function getTotal(){
        $total = 0;
        foreach ($this->remitosProductos as $remitoProducto){
            $total += $remitoProducto->getPrecioSubTotal();
        }
        return $total;
    }


}