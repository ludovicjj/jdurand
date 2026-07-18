<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gallery>
 */
class GalleryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    /**
     * @return array<array{0: Gallery, picturesCount: int}>
     */
    public function findAllWithThumbnails(
        ?Category $category = null,
        bool $uncategorizedOnly = false,
        string $type = Gallery::TYPE_PHOTO
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.type = :type')
            ->setParameter('type', $type)

            ->leftJoin('g.thumbnail', 't')
            ->leftJoin('g.pictures', 'p')

            ->addSelect('t')
            ->addSelect('COUNT(p.id) AS picturesCount')

            ->groupBy('g.id')
            ->orderBy('g.createdAt', 'DESC');

        if ($category !== null) {
            $qb->innerJoin('g.galleryCategories', 'gc')
                ->andWhere('gc.category = :category')
                ->setParameter('category', $category)
                ->orderBy('gc.position', 'ASC')
                ->addOrderBy('g.createdAt', 'DESC');
        } elseif ($uncategorizedOnly) {
            $qb->andWhere('SIZE(g.galleryCategories) = 0');
        }

        // Press galleries carry their own global manual order.
        if ($type === Gallery::TYPE_PRESS) {
            $qb->orderBy('g.position', 'ASC')
                ->addOrderBy('g.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(
        ?Category $category = null,
        bool $uncategorizedOnly = false,
        string $type = Gallery::TYPE_PHOTO
    ): int {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(DISTINCT g.id)')
            ->andWhere('g.type = :type')
            ->setParameter('type', $type);

        if ($category !== null) {
            $qb->innerJoin('g.galleryCategories', 'gc')
                ->andWhere('gc.category = :category')
                ->setParameter('category', $category);
        } elseif ($uncategorizedOnly) {
            $qb->andWhere('SIZE(g.galleryCategories) = 0');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Gallery[]
     */
    public function findVisibleWithThumbnailsPaginated(
        ?Category $category,
        int $offset,
        int $limit,
        string $type = Gallery::TYPE_PHOTO
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.thumbnail', 't')
            ->addSelect('t')
            ->where('g.visibility = true')
            ->andWhere('g.type = :type')
            ->setParameter('type', $type)
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($category !== null) {
            $qb->innerJoin('g.galleryCategories', 'gc')
                ->andWhere('gc.category = :category')
                ->setParameter('category', $category)
                ->orderBy('gc.position', 'ASC')
                ->addOrderBy('g.createdAt', 'DESC');
        }

        // Press galleries carry their own global manual order.
        if ($type === Gallery::TYPE_PRESS) {
            $qb->orderBy('g.position', 'ASC')
                ->addOrderBy('g.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function countVisible(?Category $category, string $type = Gallery::TYPE_PHOTO): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(DISTINCT g.id)')
            ->where('g.visibility = true')
            ->andWhere('g.type = :type')
            ->setParameter('type', $type);

        if ($category !== null) {
            $qb->innerJoin('g.galleryCategories', 'gc')
                ->andWhere('gc.category = :category')
                ->setParameter('category', $category);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
