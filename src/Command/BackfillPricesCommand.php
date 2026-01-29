<?php
namespace App\Command;

use App\Service\PriceUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:backfill-prices', description: 'Updates crypto prices from CoinGecko for the past year')]
class BackfillPricesCommand extends Command
{
    public function __construct(private PriceUpdater $updater) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updater->backfillYearlyHistory();
        $output->writeln('Prices backfilled successfully.');
        return Command::SUCCESS;
    }
}