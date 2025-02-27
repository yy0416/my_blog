<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findWithArticleCount()
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(a.id) as articleCount')
            ->leftJoin('c.articles', 'a')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}
