<?php

namespace App\Service;

use App\Entity\API;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of SelectService
 */
class SelectService {

    private $em;

    public function __construct(EntityManagerInterface $entityManager) {
        // 3. Update the value of the private entityManager variable through injection
        $this->em = $entityManager;
    }

    /**
     *
     * @return type
     */
    public function getEstadoPedidoFilter() {

        $sql = "SELECT x.id, x.nombre
                FROM App\Entity\EstadoPedido x 
                WHERE x.fechaBaja IS NULL 
                ORDER BY x.nombre ASC";

        $query = $this->em->createQuery($sql);

        return $query->getResult();
    }

    /**
     *
     * @return type
     */
    public function getClienteFilter() {

        $sql = "SELECT x.id, CONCAT(x.apellido, ', ', x.nombre) AS nombre, IF(x.tieneRazonSocial = 1, CONCAT('(',r.razonSocial,')'),'') AS razon_social
                FROM App\Entity\Usuario x 
                LEFT JOIN x.razonSocial r
                WHERE x.fechaBaja IS NULL AND x.tipoUsuario = 1
                ORDER BY x.apellido ASC";

        $query = $this->em->createQuery($sql);
        return $query->getResult();

    }

    /**
     *
     * @param type $entities
     * @param type $useId
     * @param type $useEntities
     * @param type $showAll
     * @param type $localSearch
     * @return string
     */
    public function getResponseData($entities, $useId = false, $useEntities = false, $showAll = true, $localSearch = false) {

        $responseData = $showAll ? (!$localSearch ? "Todos:Todos;" : ":TODOS;") : "";

        $count = count($entities);

        foreach ($entities as $entity) {

            $id = $useEntities ? $entity->getId() : $entity['id'];
            $text = $useEntities ? $entity->__toString() : $entity['nombre'];

            $responseData .= ($useId ? $id : $text) . ':' . $text;

            if (--$count > 0) {
                $responseData .= ';';
            }
        }

        return $responseData;
    }

    /**
     *
     * @param type $useId
     * @return string
     */
    public function getMesesSelect($useId = false) {
        $months = API::getMonthArray();

        $responseData = "Todos:Todos;";

        $count = count($months);

        foreach ($months as $mes) {
            $id = $mes['id'];
            $text = $mes['denominacion'];

            $responseData .= ($useId ? $id : $text) . ':' . $text;

            if (--$count > 0) {
                $responseData .= ';';
            }
        }

        return $responseData;
    }

    /**
     *
     * @param type $useId
     * @return string
     */
    public function getBooleanSelect($useId = false) {

        if ($useId) {
            $responseData = "Todos:Todos;0:No;1:S&iacute";
        } else {
            $responseData = ":Todos;No:No;Si:S&iacute";
        }

        return $responseData;
    }

}
