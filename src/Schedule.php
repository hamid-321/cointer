<?php

namespace App;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use App\Message\UpdatePricesMessage;
use App\Message\DailyHistorySnapshotMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\CronExpression;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct( private CacheInterface $cache,) {}

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run

            // add your own tasks here
            // see https://symfony.com/doc/current/scheduler.html#attaching-recurring-messages-to-a-schedule

            ->add( RecurringMessage::every('10 minutes', new UpdatePricesMessage()) )

            ->add( RecurringMessage::cron('0 0 * * *', new DailyHistorySnapshotMessage()) )
        ;
    }
}
