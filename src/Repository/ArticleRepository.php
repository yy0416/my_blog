<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return Article[] Returns an array of Article objects
     */
    public function findLatestArticles(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article[] Returns an array of Article objects
     */
    public function findByAuthor($author): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.author = :author')
            ->setParameter('author', $author)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $categorySlug): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.category', 'c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $categorySlug)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.tags', 't')
            ->where('a.title LIKE :query')
            ->orWhere('a.content LIKE :query')
            ->orWhere('c.name LIKE :query')
            ->orWhere('t.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
