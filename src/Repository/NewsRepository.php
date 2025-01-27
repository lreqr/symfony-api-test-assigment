<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    //    /**
    //     * @return News[] Returns an array of News objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?News
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByFilters(array $filters, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('n');

        if (!empty($filters['author'])) {
            $queryBuilder->andWhere('n.author LIKE :author')
                ->setParameter('author', '%' . $filters['author'] . '%');
        }

        if (!empty($filters['title'])) {
            $queryBuilder->andWhere('n.title LIKE :title')
                ->setParameter('title', '%' . $filters['title'] . '%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->select('COUNT(n.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $newsList = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [$newsList, $totalItems];
    }

}
