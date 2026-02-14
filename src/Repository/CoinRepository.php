<?php

namespace App\Repository;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Entity\Transaction;
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

    public function getHoldingsPaginationQuery(Portfolio $portfolio, string $searchTerm = ''): \Doctrine\ORM\Query
    {
        $QueryBuilder = $this->createQueryBuilder('c')

            ->innerJoin(Transaction::class, 't', 'WITH', 't.coin = c AND t.portfolio = :portfolio')
            ->setParameter('portfolio', $portfolio)
            ->groupBy('c.id');

            if ($searchTerm !== '') 
            {
                $QueryBuilder->andWhere('c.name LIKE :search OR c.symbol LIKE :search')
                             ->setParameter('search', '%' . $searchTerm . '%');
            }

            return $QueryBuilder
                // Net Quantity
                ->addSelect('SUM(CASE WHEN t.type = \'buy\' THEN t.quantity 
                                    WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END)
                                as hidden net_quantity
                            ')
                // Holdings Value
                ->addSelect('SUM((CASE WHEN t.type = \'buy\' THEN t.quantity 
                                    WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price)
                                as hidden current_value
                            ')
                // Total Cost
                ->addSelect('SUM(CASE WHEN t.type = \'buy\' THEN t.price 
                                    WHEN t.type = \'sell\' THEN -t.price ELSE 0 END)
                                as hidden total_cost
                            ')
                // Profit & Loss
                ->addSelect('COALESCE(
                                ((SUM((CASE WHEN t.type = \'buy\' THEN t.quantity 
                                            WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price)
                                - SUM(CASE WHEN t.type = \'buy\' THEN t.price 
                                            WHEN t.type = \'sell\' THEN -t.price ELSE 0 END))
                                * 100.0)
                                / NULLIF(SUM(CASE WHEN t.type = \'buy\' THEN t.price 
                                                    WHEN t.type = \'sell\' THEN -t.price ELSE 0 END), 0), 0) 
                                as hidden profit_loss_percent
                            ')
                // 24h % Change
                ->addSelect('COALESCE(
                                SUM((CASE WHEN t.type = \'buy\' THEN t.quantity 
                                          WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price * COALESCE(c.change_24h, 0))
                                / NULLIF(SUM((CASE WHEN t.type = \'buy\' THEN t.quantity 
                                                   WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price), 0), 0)
                                as hidden change_24h
                            ')

                ->getQuery();
    }

}
