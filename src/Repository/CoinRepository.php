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
    public function findAllCoins(): array
    {
        return $this->createQueryBuilder('c')
            ->getQuery()
            ->getResult();
    }

    public function getPaginationQuery(?string $searchTerm = null): \Doctrine\ORM\Query
    {
        $QueryBuilder = $this->createQueryBuilder('c');

        if ($searchTerm !== '') 
        {
            $QueryBuilder->andWhere('c.name LIKE :search OR c.symbol LIKE :search')
                         ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $QueryBuilder->getQuery();
    }

}
