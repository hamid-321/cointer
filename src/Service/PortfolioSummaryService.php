<?php

namespace App\Service;

use App\Entity\Portfolio;

class PortfolioSummaryService
{
    public function __construct(
        private readonly HoldingsCalculatorService $holdingsCalculator,
    ) {}

    public function getPortfolioSummary(Portfolio $portfolio): array
    {
        $holdings = $this->getHoldingsByPortfolio($portfolio);

        $totalValue = 0.0;
        $totalCost = 0.0;
        $distribution = [];

        foreach ($holdings as $holding)
        {
            $totalValue += $holding['currentValue'];
            $totalCost += $holding['totalCost'];
        }

        $profitLoss = $totalValue - $totalCost;
        $profitLossPercent = $totalCost > 0 ? ($profitLoss / $totalCost) * 100 : 0.0;

        // Weighted 24h change based on value
        $change24h = 0.0;
        if ($totalValue > 0)
        {
            foreach ($holdings as $holding)
            {
                $weight = $holding['currentValue'] / $totalValue;
                $change24h += $holding['change24h'] * $weight;
            }
        }

        // Distribution percentages
        foreach ($holdings as $holding)
        {
            if ($holding['coin'] !== null && $totalValue > 0)
            {
                $distribution[] = [
                    'coin' => $holding['coin'],
                    'value' => $holding['currentValue'],
                    'percent' => ($holding['currentValue'] / $totalValue) * 100,
                ];
            }
        }

        return [
            'totalValue' => $totalValue,
            'totalCost' => $totalCost,
            'profitLoss' => $profitLoss,
            'profitLossPercent' => $profitLossPercent,
            'change24h' => $change24h,
            'holdings' => $holdings,
            'distribution' => $distribution,
        ];
    }

    public function getCombinedSummary(array $portfolios): array
    {
        $totalValue = 0.0;
        $totalCost = 0.0;
        $portfolioSummaries = [];

        foreach ($portfolios as $portfolio)
        {
            $summary = $this->getPortfolioSummary($portfolio);
            $portfolioSummaries[$portfolio->getId()] = $summary;
            $totalValue += $summary['totalValue'];
            $totalCost += $summary['totalCost'];
        }

        $profitLoss = $totalValue - $totalCost;
        $profitLossPercent = $totalCost > 0 ? ($profitLoss / $totalCost) * 100 : 0.0;

        // Weighted 24h change across all portfolios
        $change24h = 0.0;
        if ($totalValue > 0)
        {
            foreach ($portfolioSummaries as $summary)
            {
                if ($summary['totalValue'] > 0)
                {
                    $weight = $summary['totalValue'] / $totalValue;
                    $change24h += $summary['change24h'] * $weight;
                }
            }
        }

        return [
            'totalValue' => $totalValue,
            'totalCost' => $totalCost,
            'profitLoss' => $profitLoss,
            'profitLossPercent' => $profitLossPercent,
            'change24h' => $change24h,
            'portfolioSummaries' => $portfolioSummaries,
        ];
    }

    private function getHoldingsByPortfolio(Portfolio $portfolio): array
    {
        // Group transactions by coin
        $transactionsByCoin = [];
        foreach ($portfolio->getTransactions() as $transaction)
        {
            $coinId = $transaction->getCoin()->getId();
            $transactionsByCoin[$coinId][] = $transaction;
        }

        // Calculate holdings for each coin
        $holdings = [];
        foreach ($transactionsByCoin as $coinId => $transactions)
        {
            $holdings[$coinId] = $this->holdingsCalculator->calculate($transactions);
        }

        return $holdings;
    }
}
