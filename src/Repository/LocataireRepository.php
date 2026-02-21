<?php

namespace App\Repository;

use App\Entity\Locataire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Locataire>
 */
class LocataireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Locataire::class);
    }

    public function getFilteredQueryBuilder(?string $search, ?string $statut, ?string $ville, ?string $dateEntree)
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.bien', 'b');

        if ($search) {
            $qb->andWhere('l.nom LIKE :search OR l.prenom LIKE :search OR l.email LIKE :search OR l.telephone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('l.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($ville) {
            // Ici on filtre sur la ville du bien loué par le locataire
            $qb->andWhere('b.ville = :ville')
                ->setParameter('ville', $ville);
        }

        if ($dateEntree) {
            $qb->andWhere('l.dateEntree = :dateEntree')
                ->setParameter('dateEntree', $dateEntree);
        }

        $qb->orderBy('l.id', 'DESC');

        return $qb;
    }
}
