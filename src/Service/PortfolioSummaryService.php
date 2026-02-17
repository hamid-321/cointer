<?php

namespace App\Service;

use App\Entity\Portfolio;
use App\Entity\Transaction;

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

        $distributionLabels = [];
        $distributionData = [];
        foreach ($holdings as $holding)
        {
            if ($holding['coin'] !== null && $totalValue > 0)
            {
                $percent = ($holding['currentValue'] / $totalValue) * 100;
                $distribution[] = [
                    'coin' => $holding['coin'],
                    'value' => $holding['currentValue'],
                    'percent' => $percent,
                ];
                $distributionLabels[] = $holding['coin']->getName();
                $distributionData[] = round($percent, 1);
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
            'distributionLabels' => $distributionLabels,
            'distributionData' => $distributionData,
        ];
    }

    public function getSortedDistributionForChart(array $holdings, float $totalValue): array
    {
        $holdings = array_values($holdings);
        usort($holdings, fn ($a, $b) => $b['currentValue'] <=> $a['currentValue']);

        $labels = [];
        $data = [];
        foreach ($holdings as $item) {
            $labels[] = $item['coin']?->getName() ?? '';
            $percent = $totalValue > 0 ? ($item['currentValue'] / $totalValue * 100) : 0;
            $data[] = round($percent, 1);
        }

        return [
            'holdings' => $holdings,
            'labels' => $labels,
            'data' => $data,
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

        $distributionLabels = [];
        $distributionData = [];
        foreach ($portfolios as $portfolio)
        {
            $summary = $portfolioSummaries[$portfolio->getId()] ?? null;
            $percent = ($totalValue > 0 && $summary) ? ($summary['totalValue'] / $totalValue * 100) : 0.0;
            $distributionLabels[] = $portfolio->getName();
            $distributionData[] = round($percent, 1);
        }

        return [
            'totalValue' => $totalValue,
            'totalCost' => $totalCost,
            'profitLoss' => $profitLoss,
            'profitLossPercent' => $profitLossPercent,
            'change24h' => $change24h,
            'portfolioSummaries' => $portfolioSummaries,
            'distributionLabels' => $distributionLabels,
            'distributionData' => $distributionData,
        ];
    }


    public function getHoldingsQuantityByCoin(Portfolio $portfolio, ?Transaction $exclude = null): array
    {
        $holdings = $this->getHoldingsByPortfolio($portfolio, $exclude);
        $result = [];
        foreach ($holdings as $coinId => $holding) {
            $result[(int) $coinId] = (float) $holding['quantity'];
        }
        return $result;
    }

    public function getAvailableQuantityToSell(Portfolio $portfolio, int $coinId, ?Transaction $exclude = null): float
    {
        $byCoin = $this->getHoldingsQuantityByCoin($portfolio, $exclude);
        return $byCoin[$coinId] ?? 0.0;
    }

    private function getHoldingsByPortfolio(Portfolio $portfolio, ?Transaction $exclude = null): array
    {
        $transactionsByCoin = [];
        foreach ($portfolio->getTransactions() as $transaction)
        {
            if ($exclude !== null && $transaction->getId() === $exclude->getId()) 
            {
                continue;
            }
            $coinId = $transaction->getCoin()->getId();
            $transactionsByCoin[$coinId][] = $transaction;
        }

        $holdings = [];
        foreach ($transactionsByCoin as $coinId => $transactions)
        {
            $holdings[$coinId] = $this->holdingsCalculator->calculate($transactions);
        }

        return $holdings;
    }
}
