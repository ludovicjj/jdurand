<?php

namespace App\Repository;

use App\Entity\Entry;
use App\Enum\EntryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entry>
 */
class EntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entry::class);
    }

    /**
     * @return Entry[]
     */
    public function findAllOrdered(EntryType $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', $type)
            ->andWhere('e.isDraft = false')
            ->orderBy('e.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Entry[]
     */
    public function findPublicActive(EntryType $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.type = :type')
            ->setParameter('type', $type)
            ->andWhere('e.active = true')
            ->andWhere('e.visibility = true')
            ->andWhere('e.isDraft = false')
            ->orderBy('e.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextPosition(EntryType $type): int
    {
        $lastPosition = $this->createQueryBuilder('e')
            ->select('MAX(e.position)')
            ->where('e.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return ($lastPosition ?? -1) + 1;
    }

    public function countAll(EntryType $type): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
