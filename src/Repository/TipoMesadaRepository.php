<?php

namespace App\Repository;

use App\Entity\TipoMesada;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Type;

/**
 *
 */
class TipoMesadaRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, TipoMesada::class);
    }

    /**
     *
     * @return type
     */
    public function getSiguienteNumeroMesada(): int|type
    {

        $query = $this->createQueryBuilder('m')
            ->select('m.numero')
            ->orderBy('m.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        try {
            $siguienteNumero = $query->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            $siguienteNumero = 0;
        }

        return $siguienteNumero + 1;
    }
}
