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
 * @ORM\Table(name="entrega_pedido")
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EntregaPedido
{

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;


}