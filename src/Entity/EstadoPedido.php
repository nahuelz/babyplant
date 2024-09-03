<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EstadoPedido
 *
 * @ORM\Table(name="estado_pedido")
 * @ORM\Entity()
 */
class EstadoPedido extends EntidadBasica
{
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $inicial;

    public function getInicial(): ?string
    {
        return $this->inicial;
    }

    public function setInicial(?string $inicial): self
    {
        $this->inicial = $inicial;

        return $this;
    }
}
