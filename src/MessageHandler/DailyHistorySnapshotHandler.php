<?php

namespace App\MessageHandler;

use App\Message\DailyHistorySnapshotMessage;
use App\Repository\CoinRepository;
use App\Entity\CoinHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DailyHistorySnapshotHandler
{
    public function __construct( private CoinRepository $coinRepository, private EntityManagerInterface $em) {}

    public function __invoke(DailyHistorySnapshotMessage $message)
    {
        $coins = $this->coinRepository->findAll();

        foreach ($coins as $coin) {
            $history = new CoinHistory();
            $history->setCoin($coin);
            $history->setPrice($coin->getPrice());
            $history->setDate(new \DateTimeImmutable()); // Today's snapshot
            
            $this->em->persist($history);
        }

        $this->em->flush();
    }
}