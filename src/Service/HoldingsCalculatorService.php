<?php

namespace App\Service;

use App\Entity\Coin;

class HoldingsCalculatorService
{
    public function calculate(array $transactions): array
    {
        $coin = null;
        $totalCostBought = 0.0;
        $totalProceeds = 0.0;
        $netQuantity = 0.0;

        foreach ($transactions as $transaction)
        {
            if ($coin === null) {
                $coin = $transaction->getCoin();
            }

            $quantity = (float) $transaction->getQuantity();
            $price = (float) $transaction->getPrice();

            if ($transaction->getType() === 'buy')
            {
                $totalCostBought += $price; // price is total transaction cost
                $netQuantity += $quantity;
            } 
            elseif ($transaction->getType() === 'sell')
            {
                $totalProceeds += $price; // total value received from sell
                $netQuantity -= $quantity;
            }
        }

        if ($coin === null)
        {
            return $this->emptyResult();
        }

        $currentPrice = (float) $coin->getPrice();
        // Average Net Cost = (Total Cost - Total Proceeds) / Holdings
        $avgNetCost = $netQuantity > 0 ? ($totalCostBought - $totalProceeds) / $netQuantity : 0.0;
        $currentValue = $netQuantity * $currentPrice;
        $totalCost = $totalCostBought;
        $netInvestment = $totalCostBought - $totalProceeds;
        $profitLoss = $currentValue - $netInvestment;
        $profitLossPercent = $netInvestment > 0 ? ($profitLoss / $netInvestment) * 100 : 0.0;
        $change24h = $coin->getChange24h() ?? 0.0;

        return [
            'coin' => $coin,
            'quantity' => $netQuantity,
            'avgNetCost' => $avgNetCost,
            'totalCost' => $totalCost,
            'currentValue' => $currentValue,
            'profitLoss' => $profitLoss,
            'profitLossPercent' => $profitLossPercent,
            'change24h' => $change24h,
        ];
    }

    private function emptyResult(): array
    {
        return [
            'coin' => null,
            'quantity' => 0.0,
            'avgNetCost' => 0.0,
            'totalCost' => 0.0,
            'currentValue' => 0.0,
            'profitLoss' => 0.0,
            'profitLossPercent' => 0.0,
            'change24h' => 0.0,
        ];
    }
}
