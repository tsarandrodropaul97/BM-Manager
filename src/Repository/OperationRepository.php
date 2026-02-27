<?php

namespace App\Repository;

use App\Entity\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    /**
     * @return Operation[]
     */
    public function findForLocataire(\App\Entity\Locataire $locataire): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.targetLocataires', 'l')
            ->where('o.isGlobal = :true')
            ->orWhere('l.id = :locataireId')
            ->setParameter('true', true)
            ->setParameter('locataireId', $locataire->getId())
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($filters['search'])) {
            $qb->andWhere('o.titre LIKE :search OR o.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('o.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['date'])) {
            $qb->andWhere('o.createdAt >= :dateStart AND o.createdAt <= :dateEnd')
                ->setParameter('dateStart', $filters['date'] . ' 00:00:00')
                ->setParameter('dateEnd', $filters['date'] . ' 23:59:59');
        }

        return $qb->getQuery()->getResult();
    }
}
