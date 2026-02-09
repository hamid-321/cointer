<?php

namespace App\Service;

use App\Entity\Coin;
use App\Repository\CoinHistoryRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartService
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly CoinHistoryRepository $historyRepository
    ) {}

    /**
     * Build a price history chart for a coin
     */
    public function buildPriceHistoryChart(Coin $coin): Chart
    {
        // Fetch price history ordered by date ascending
        $history = $this->historyRepository->findBy(
            ['coin' => $coin],
            ['date' => 'ASC']
        );

        // Build chart data
        $labels = [];
        $prices = [];
        
        foreach ($history as $record)
        {
            $labels[] = $record->getDate()->format('M d, Y');
            $prices[] = (float) $record->getPrice();
        }

        // Create the chart
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $coin->getName() . ' Price (USD)',
                    'backgroundColor' => 'rgba(191, 0, 255, 0.2)',
                    'borderColor' => '#BF00FF',
                    'data' => $prices,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 6,
                ],
            ],
        ]);

        $chart->setOptions($this->getChartOptions());

        return $chart;
    }

    /**
     * Get default chart options for dark theme
     */
    private function getChartOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => '#222632',
                    'titleColor' => '#9CA3AF',
                    'bodyColor' => '#FFFFFF',
                    'borderColor' => '#BF00FF',
                    'borderWidth' => 1,
                    'padding' => 12,
                    'displayColors' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                    'ticks' => [
                        'color' => '#9CA3AF',
                        'maxTicksLimit' => 8,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                    'ticks' => [
                        'color' => '#9CA3AF',
                    ],
                ],
            ],
        ];
    }
}
