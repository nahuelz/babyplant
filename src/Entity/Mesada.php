<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mesada
 *
 * @ORM\Table(name="mesada")
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Mesada
{

    use Traits\Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var TipoProducto
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TipoProducto")
     * @ORM\JoinColumn(name="id_tipo_producto", referencedColumnName="id", nullable=false)
     *
     */
    protected $tipoProducto;

    /**
     * @var TipoMesada
     *
     * @ORM\ManyToOne(targetEntity=TipoMesada::class, cascade={"all"})
     * @ORM\JoinColumn(name="id_tipo_mesada", referencedColumnName="id", nullable=false)
     *
     */
    protected $tipoMesada;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="string", length=50, nullable=true)
     */
    private $cantidadBandejas;

    /**
     * Campo a mostrar
     *
     * @return string
     */
    public function __toString()
    {
        return $this->tipoMesada->getNombre();

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
     * @return TipoMesada
     */
    public function getTipoMesada()
    {
        return $this->tipoMesada;
    }

    /**
     * @param TipoMesada $tipoMesada
     */
    public function setTipoMesada(TipoMesada $tipoMesada): void
    {
        $this->tipoMesada = $tipoMesada;
    }

    /**
     * @return mixed
     */
    public function getCantidadBandejas()
    {
        return $this->cantidadBandejas;
    }

    /**
     * @param mixed $cantidadBandejas
     */
    public function setCantidadBandejas($cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }

    /**
     * @return TipoProducto
     */
    public function getTipoProducto()
    {
        return $this->tipoProducto;
    }

    /**
     * @param TipoProducto $tipoProducto
     */
    public function setTipoProducto(TipoProducto $tipoProducto): void
    {
        $this->tipoProducto = $tipoProducto;
    }




}
