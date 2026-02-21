<?php

namespace App\Repository;

use App\Entity\Contrat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contrat>
 */
class ContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contrat::class);
    }

    /**
     * @return Contrat[] Returns an array of Contrat objects nearing expiration (e.g., in 3 months)
     */
    public function findExpiringSoon(\DateTimeInterface $limitDate): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateFin <= :limitDate')
            ->andWhere('c.statut = :status')
            ->setParameter('limitDate', $limitDate)
            ->setParameter('status', 'actif')
            ->orderBy('c.dateFin', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveContracts(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.statut = :status')
            ->setParameter('status', 'actif')
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
