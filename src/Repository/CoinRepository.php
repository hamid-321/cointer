<?php

namespace App\Repository;

use App\Entity\Coin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coin>
 */
class CoinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coin::class);
    }

    /**
     * Find all coins ordered by market cap (highest first)
     * @return Coin[]
     */
    public function findAllOrderedByMarketCap(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.market_cap', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a single coin with its price history
     */
    public function findOneWithHistory(int $id): ?Coin
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.coin_history', 'h')
            ->addSelect('h')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->orderBy('h.date', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search coins by name or symbol
     * @return Coin[]
     */
    public function search(?string $query): array
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($query !== null && $query !== '') {
            $queryBuilder
                ->where($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('LOWER(c.name)', ':query'),
                    $queryBuilder->expr()->like('LOWER(c.symbol)', ':query')
                ))
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        return $queryBuilder
            ->orderBy('c.market_cap', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the top N coins by market cap
     * @return Coin[]
     */
    public function findTopByMarketCap(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.market_cap', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
