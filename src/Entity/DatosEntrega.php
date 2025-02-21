<?php /** @noinspection ALL */

namespace App\Entity;

use App\Entity\Constants\ConstanteEstadoDatosEntrega;
use App\Entity\Traits\Auditoria;
use App\Repository\DatosEntregaRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="datos_entrega")
 * @ORM\Entity
 *
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class DatosEntrega {

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
     * @ORM\OneToOne(targetEntity=Entrega::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_entrega", referencedColumnName="id", nullable=false)
     */
    private $entrega;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="remitosProductos")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $pedidoProducto;

    /**
     * @ORM\Column(name="cantidad_bandejas_entregadas", type="string", length=50, nullable=false)
     */
    private mixed $cantidadBandejasEntregadas;

    /**
     * @ORM\Column(name="cantidad_bandejas_sin_entregar", type="string", length=50, nullable=false)
     */
    private mixed $cantidadBandejasSinEntregar;

    /**
     * @ORM\Column(name="cantidad_bandejas_a_entregar", type="string", length=50, nullable=false)
     */
    private mixed $cantidadBandejasAEntregar;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class)
     * @ORM\JoinColumn(name="id_mesada_uno", referencedColumnName="id", nullable=false)
     */
    private $mesadaUno;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class)
     * @ORM\JoinColumn(name="id_mesada_dos", referencedColumnName="id", nullable=true)
     */
    private $mesadaDos;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEntrega(): mixed
    {
        return $this->entrega;
    }

    public function setEntrega(mixed $entrega): void
    {
        $this->entrega = $entrega;
    }

    public function getPedidoProducto(): mixed
    {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(mixed $pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    public function getCantidadBandejasEntregadas(): mixed
    {
        return $this->cantidadBandejasEntregadas;
    }

    public function setCantidadBandejasEntregadas(mixed $cantidadBandejasEntregadas): void
    {
        $this->cantidadBandejasEntregadas = $cantidadBandejasEntregadas;
    }

    public function getCantidadBandejasSinEntregar(): mixed
    {
        return $this->cantidadBandejasSinEntregar;
    }

    public function setCantidadBandejasSinEntregar(mixed $cantidadBandejasSinEntregar): void
    {
        $this->cantidadBandejasSinEntregar = $cantidadBandejasSinEntregar;
    }

    public function getCantidadBandejasAEntregar(): mixed
    {
        return $this->cantidadBandejasAEntregar;
    }

    public function setCantidadBandejasAEntregar(mixed $cantidadBandejasAEntregar): void
    {
        $this->cantidadBandejasAEntregar = $cantidadBandejasAEntregar;
    }

    /**
     * @return mixed
     */
    public function getMesadaUno()
    {
        return $this->mesadaUno;
    }

    /**
     * @param mixed $mesadaUno
     */
    public function setMesadaUno($mesadaUno): void
    {
        $this->mesadaUno = $mesadaUno;
    }

    /**
     * @return mixed
     */
    public function getMesadaDos()
    {
        return $this->mesadaDos;
    }

    /**
     * @param mixed $mesadaDos
     */
    public function setMesadaDos($mesadaDos): void
    {
        $this->mesadaDos = $mesadaDos;
    }


}