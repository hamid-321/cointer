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
                    'backgroundColor' => '#BF00FF33',
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

    public function getColourPalette(int $count): array
    {
        $palette = [
            '#FF0000',
            '#FF0080',
            '#2CD42C',
            '#FF8000',
            '#00FF80',
            '#0080FF',
            '#80FF00',
            '#00FFFF',
            '#8000FF',
            '#FFEEE7',
        ];
        $colors = [];
        for ($i = 0; $i < $count; $i++) 
        {
            $colors[] = $palette[$i % \count($palette)];
        }
        return $colors;
    }

    public function buildDistributionChart(array $labels, array $data): Chart
    {
        $palette = [
            '#FF0000',
            '#FF0080',
            '#2CD42C',
            '#FF8000',
            '#00FF80',
            '#0080FF',
            '#80FF00',
            '#00FFFF',
            '#8000FF',
            '#FFEEE7',
        ];

        $backgroundColors = [];
        foreach (array_keys($labels) as $i) 
        {
            $backgroundColors[] = $palette[$i % \count($palette)];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => array_values($labels),
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#222632',
                    'borderWidth' => 2,
                    'hoverOffset' => 4,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
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
                ],
            ],
        ]);

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
                        'color' => '#FFFFFF1A',
                    ],
                    'ticks' => [
                        'color' => '#9CA3AF',
                        'maxTicksLimit' => 8,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'color' => '#FFFFFF1A',
                    ],
                    'ticks' => [
                        'color' => '#9CA3AF',
                    ],
                ],
            ],
        ];
    }
}
