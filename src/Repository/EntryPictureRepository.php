<?php

namespace App\Repository;

use App\Entity\Entry;
use App\Entity\EntryPicture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntryPicture>
 */
class EntryPictureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntryPicture::class);
    }

    /**
     * @return EntryPicture[]
     */
    public function findByEntryOrdered(Entry $entry): array
    {
        return $this->createQueryBuilder('ep')
            ->where('ep.entry = :entry')
            ->setParameter('entry', $entry)
            ->orderBy('ep.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByEntry(Entry $entry): int
    {
        return (int) $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.entry = :entry')
            ->setParameter('entry', $entry)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNextPositionByEntry(Entry $entry): int
    {
        $lastPosition = $this->createQueryBuilder('ep')
            ->select('MAX(ep.position)')
            ->where('ep.entry = :entry')
            ->setParameter('entry', $entry)
            ->getQuery()
            ->getSingleScalarResult();

        return ($lastPosition ?? -1) + 1;
    }

    /**
     * Single query to fetch all pictures for given entry ids, grouped by entry id.
     * Avoids N+1 when rendering a list of entries with their pictures.
     *
     * @param int[] $entryIds
     * @return array<int, EntryPicture[]> keyed by entry id
     */
    public function findGroupedByEntryIds(array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $pictures = $this->createQueryBuilder('ep')
            ->where('ep.entry IN (:ids)')
            ->setParameter('ids', $entryIds)
            ->orderBy('ep.entry', 'ASC')
            ->addOrderBy('ep.position', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($pictures as $picture) {
            $grouped[$picture->getEntry()->getId()][] = $picture;
        }

        return $grouped;
    }
}
