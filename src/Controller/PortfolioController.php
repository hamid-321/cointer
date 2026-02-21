<?php

namespace App\Controller;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Form\PortfolioType;
use App\Repository\PortfolioRepository;
use App\Repository\CoinRepository;
use App\Repository\TransactionRepository;
use App\Service\ChartService;
use App\Service\HoldingsCalculatorService;
use App\Service\PortfolioSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[IsGranted('ROLE_USER')]
final class PortfolioController extends AbstractController
{
    public function __construct(
        private readonly PortfolioSummaryService $portfolioSummaryService,
        private readonly HoldingsCalculatorService $holdingsCalculator,
        private readonly ChartService $chartService,
    ) {}

    #[Route('/portfolio', name: 'app_portfolio_index')]
    public function index(PortfolioRepository $portfolioRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $searchTerm = $request->query->get('q', '');

        $allPortfolios = $portfolioRepository->getAllPortfolios($this->getUser());
        $combinedSummary = $this->portfolioSummaryService->getCombinedSummary($allPortfolios);

        $distributionChart = $this->chartService->buildDistributionChart(
            $combinedSummary['distributionLabels'],
            $combinedSummary['distributionData']
        );
        $distributionColors = $this->chartService->getColourPalette(\count($combinedSummary['distributionLabels']));

        $query = $portfolioRepository->getPaginationQuery($this->getUser(), $searchTerm);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10, // Items per page
            ['defaultSortFieldName' => 'total_value', 'defaultSortDirection' => 'desc']
        );

        $currentPagePortfolios = $pagination->getItems();

        $currentPageSummary = $this->portfolioSummaryService->getCombinedSummary($currentPagePortfolios);

        return $this->render('portfolio/index.html.twig', [
            'portfolios' => $allPortfolios,
            'combined' => $combinedSummary,
            'pagination' => $pagination,
            'currentPageSummary' => $currentPageSummary,
            'distributionChart' => $distributionChart,
            'distributionColors' => $distributionColors,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/portfolio/{id}', name: 'app_portfolio_show', requirements: ['id' => '\d+'])]
    public function show(Portfolio $portfolio, CoinRepository $coinRepository, Request $request, PaginatorInterface $paginator): Response
    {
        if ($portfolio->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $summary = $this->portfolioSummaryService->getPortfolioSummary($portfolio);

        $searchTerm = $request->query->get('q', '');

        $totalValue = $summary['totalValue'] ?? 0;
        $distribution = $this->portfolioSummaryService->getSortedDistributionForChart(
            $summary['holdings'] ?? [],
            $totalValue
        );
        $holdings = $distribution['holdings'];
        $sortedLabels = $distribution['labels'];
        $sortedData = $distribution['data'];

        $distributionChart = $this->chartService->buildDistributionChart(
            $sortedLabels,
            $sortedData
        );

        $distributionColors = $this->chartService->getColourPalette(\count($sortedLabels));

        $query = $coinRepository->getHoldingsPaginationQuery($portfolio, $searchTerm);
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8,
            [
                'defaultSortFieldName' => 'current_value',
                'defaultSortDirection' => 'desc',
            ]
        );


        return $this->render('portfolio/show.html.twig', [
            'portfolio' => $portfolio,
            'summary' => $summary,
            'holdings' => $holdings,
            'pagination' => $pagination,
            'distributionChart' => $distributionChart,
            'distributionColors' => $distributionColors,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/portfolio/{id}/coin/{coinId}', name: 'app_portfolio_coin', requirements: ['id' => '\d+', 'coinId' => '\d+'])]
    public function showCoin(Portfolio $portfolio, int $coinId, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response 
    {
        if ($portfolio->getUser() !== $this->getUser()) 
        {
            throw $this->createAccessDeniedException();
        }

        $coin = $entityManager->getRepository(Coin::class)->find($coinId);
        if (!$coin) 
        {
            throw $this->createNotFoundException('Coin not found');
        }

        // All transactions for holding calculation
        $allTransactions = $transactionRepository->getPortfolioCoins($portfolio, $coin);

        $holding = $this->holdingsCalculator->calculate($allTransactions);

        // Paginated transactions for display
        $query = $transactionRepository->getPaginationQuery($portfolio, $coin);
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10,
            ['defaultSortFieldName' => 't.created_at', 'defaultSortDirection' => 'desc']
        );

        return $this->render('portfolio/show_coin.html.twig', [
            'portfolio' => $portfolio,
            'coin' => $coin,
            'pagination' => $pagination,
            'holding' => $holding,
        ]);
    }

    #[Route('/portfolio/new', name: 'app_portfolio_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $portfolio = new Portfolio();
        $form = $this->createForm(PortfolioType::class, $portfolio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $portfolio->setUser($this->getUser());
            $portfolio->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($portfolio);
            $entityManager->flush();

            return $this->redirectToRoute('app_portfolio_show', ['id' => $portfolio->getId()]);
        }

        return $this->render('portfolio/new.html.twig', [
            'form' => $form,
            'portfolio' => $portfolio,
        ]);
    }

    #[Route('/portfolio/{id}/edit', name: 'app_portfolio_edit', requirements: ['id' => '\d+'])]
    public function edit(Portfolio $portfolio, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PortfolioType::class, $portfolio);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->flush();

            return $this->redirectToRoute('app_portfolio_show', ['id' => $portfolio->getId()]);
        }

        return $this->render('portfolio/edit.html.twig', [
            'form' => $form,
            'portfolio' => $portfolio,
        ]);
    }

    #[Route('/portfolio/{id}/delete', name: 'app_portfolio_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Portfolio $portfolio, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete-portfolio-' . $portfolio->getId(), $request->request->get('_token')))
        {
            $entityManager->remove($portfolio);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_portfolio_index');
    }
}
