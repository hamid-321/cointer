<?php
namespace App\MessageHandler;

use App\Message\UpdatePricesMessage;
use App\Service\PriceUpdater;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdatePricesHandler
{
    public function __construct(private PriceUpdater $updater) {}

    public function __invoke(UpdatePricesMessage $message)
    {
        $this->updater->updateAllPrices();
    }
}