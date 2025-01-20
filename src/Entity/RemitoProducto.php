<?php /** @noinspection ALL */

namespace App\Entity;

use App\Entity\Constants\ConstanteEstadoRemitoProducto;
use App\Entity\Traits\Auditoria;
use App\Repository\RemitoProductoRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="remito_producto")
 * @ORM\Entity(repositoryClass=RemitoProductoRepository::class)
 *
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class RemitoProducto {

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Remito::class, inversedBy="remitosProductos")
     * @ORM\JoinColumn(name="id_remito", referencedColumnName="id", nullable=false)
     */
    private mixed $remito;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="remitosProductos")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $pedidoProducto;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="string", length=50, nullable=false)
     */
    private mixed $cantBandejas;

    /**
     * @ORM\Column(name="precio_unitario", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $precioUnitario;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getRemito(): mixed
    {
        return $this->remito;
    }

    public function setRemito(mixed $remito): void
    {
        $this->remito = $remito;
    }

    public function getCantBandejas(): mixed
    {
        return $this->cantBandejas;
    }

    public function setCantBandejas(mixed $cantBandejas): void
    {
        $this->cantBandejas = $cantBandejas;
    }

    public function getPedidoProducto()
    {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(mixed $pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    /**
     * @return mixed
     */
    public function getPrecioUnitario()
    {
        return $this->precioUnitario;
    }

    /**
     * @param mixed $precioUnitario
     */
    public function setPrecioUnitario($precioUnitario): void
    {
        $this->precioUnitario = $precioUnitario;
    }

    public function getPrecioSubTotal(){
        return $this->precioUnitario * $this->cantBandejas;
    }

    public function getCodigo(){
        $codigoPedido = str_pad($this->getPedidoProducto()->getPedido()->getId(), 5, "0", STR_PAD_LEFT);
        $codigoProducto = str_pad($this->getPedidoProducto()->getId(), 6, "0", STR_PAD_LEFT);
        return ($codigoPedido . '-'.$codigoProducto);
    }

}