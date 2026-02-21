<?php

namespace App\Repository;

use App\Entity\Biens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Biens>
 */
class BiensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Biens::class);
    }

    public function getFilteredQueryBuilder(?string $search, ?string $type, ?string $statut, ?string $ville)
    {
        $qb = $this->createQueryBuilder('b');

        if ($search) {
            $qb->andWhere('b.designation LIKE :search OR b.reference LIKE :search OR b.adresse LIKE :search OR b.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->join('b.categorie', 'c')
               ->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }

        if ($statut) {
            $qb->andWhere('b.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($ville) {
            $qb->andWhere('b.ville = :ville')
               ->setParameter('ville', $ville);
        }

        $qb->orderBy('b.id', 'DESC');

        return $qb;
    }
}
