<?php

namespace App\Service\Ai;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\PortfolioSummaryService;
use App\Entity\User;


#[AsTool(
    name: 'get_my_portfolio_summary', 
    description: 'Returns the total value, profit/loss, and a list of crypto holdings for all portfolios of the currently logged-in user.'
)]
class PortfolioTool
{
    public function __construct(
        private readonly PortfolioSummaryService $portfolioSummaryService,
        private readonly Security $security
    ) {}

    public function __invoke(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) 
        {
            return ['error' => 'User not logged in.'];
        }

        $portfolios = $user->getPortfolios()->toArray();
        if (empty($portfolios)) 
        {
            return ['error' => 'User has no portfolios.'];
        }

        $summary = $this->portfolioSummaryService->getCombinedSummary($portfolios);

        $portfolioIdToName = [];
        foreach ($portfolios as $portfolio) 
        {
            $portfolioIdToName[$portfolio->getId()] = $portfolio->getName();
        }


        $holdings = [];
        foreach ($summary['portfolioSummaries'] as $portfolioId => $portfolioSummary) 
        {
            foreach ($portfolioSummary['holdings'] as $holding)
            {
                $holdings[] = [
                    'coin_name' => $holding['coin']?->getName(),
                    'quantity' => $holding['quantity'],
                    'total_cost' => $holding['totalCost'],
                    'current_value' => $holding['currentValue'],
                    'profit_loss' => $holding['profitLoss'],
                    'profit_loss_percent' => $holding['profitLossPercent'],
                    'change24h' => $holding['change24h'],
                ];
            }
                $portfolioSummaries[] = [
                    'portfolio_id' => $portfolioId,
                    'portfolio_name' => $portfolioIdToName[$portfolioId],
                    'total_value' => $portfolioSummary['totalValue'],
                    'profit_loss' => $portfolioSummary['profitLoss'],
                    'profit_loss_percent' => $portfolioSummary['profitLossPercent'],
                    'holdings' => $holdings,
                ];
        }
        return 
        [
            'total_value' => $summary['totalValue'],
            'total_profit_loss' => $summary['profitLoss'],
            'total_profit_loss_percentage' => $summary['profitLossPercent'],
            'change24h' => $summary['change24h'],
            'portfolio_summaries' => $portfolioSummaries,
            'distribution_labels' => $summary['distributionLabels'],
            'distribution_data' => $summary['distributionData'],
        ];
    }
}