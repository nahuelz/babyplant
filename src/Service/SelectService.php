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
