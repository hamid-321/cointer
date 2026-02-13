<?php

namespace App\Repository;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getPaginationQuery(Portfolio $portfolio, Coin $coin): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.portfolio = :portfolio')
            ->andWhere('t.coin = :coin')
            ->setParameter('portfolio', $portfolio)
            ->setParameter('coin', $coin)
            ->addSelect('(t.price / t.quantity) as hidden price_per_coin')
            ->orderBy('t.created_at', 'DESC')
            ->getQuery();
    }   

    public function getPortfolioCoins(Portfolio $portfolio, Coin $coin): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.portfolio = :portfolio')
            ->andWhere('t.coin = :coin')
            ->setParameter('portfolio', $portfolio)
            ->setParameter('coin', $coin)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
