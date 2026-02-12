<?php

namespace App\Repository;

use App\Entity\Portfolio;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Portfolio>
 */
class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    //    /**
    //     * @return Portfolio[] Returns an array of Portfolio objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Portfolio
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Returns portfolios orders by value in desc order ready for pagination
     */
    public function getAllPortfolios(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.transactions', 't')
            ->leftJoin('t.coin', 'c')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->addSelect('SUM(
                CASE
                    WHEN t.type = \'buy\' THEN t.quantity 
                    WHEN t.type = \'sell\' THEN -t.quantity 
                    ELSE 0
                END * c.price
            ) as hidden total_value')
            ->groupBy('p.id')
            ->orderBy('total_value', 'DESC')
            ->addorderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getPaginationQuery(User $user, string $searchTerm = ''): \Doctrine\ORM\Query
    {
        $QueryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.transactions', 't')
            ->leftJoin('t.coin', 'c')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

            if ($searchTerm !== '') 
            {
                $QueryBuilder->andWhere('p.name LIKE :search')
                             ->setParameter('search', '%' . $searchTerm . '%');
            }

            return $QueryBuilder
            // Current Total Value
                ->addSelect('SUM((CASE WHEN t.type = \'buy\' THEN t.quantity 
                                    WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price)
                                as hidden total_value
                            ')
                // Total cost
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
                                SUM((CASE WHEN t.type = \'buy\' THEN t.quantity WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price * COALESCE(c.change_24h, 0))
                                / NULLIF(SUM((CASE WHEN t.type = \'buy\' THEN t.quantity WHEN t.type = \'sell\' THEN -t.quantity ELSE 0 END) * c.price), 0), 0)
                                as hidden change_24h
                            ')
                
                ->groupBy('p.id')
                ->getQuery();
    }


    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
