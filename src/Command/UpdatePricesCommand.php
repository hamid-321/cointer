<?php
namespace App\Command;

use App\Service\PriceUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:update-prices', description: 'Updates crypto prices from CoinGecko')]
class UpdatePricesCommand extends Command
{
    public function __construct(private PriceUpdater $updater) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updater->updateAllPrices();
        $output->writeln('Prices updated successfully.');
        return Command::SUCCESS;
    }
}