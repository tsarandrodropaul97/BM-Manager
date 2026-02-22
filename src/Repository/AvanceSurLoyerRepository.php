<?php

namespace App\Repository;

use App\Entity\AvanceSurLoyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvanceSurLoyer>
 */
class AvanceSurLoyerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvanceSurLoyer::class);
    }

    /**
     * Calcule le total des avances pour un locataire.
     */
    public function getTotalAvancesByLocataire($locataireId): float
    {
        return (float) $this->createQueryBuilder('a')
            ->select('SUM(a.montantTotal)')
            ->where('a.locataire = :locataireId')
            ->setParameter('locataireId', $locataireId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
