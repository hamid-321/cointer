<?php

namespace App\Controller;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Form\PortfolioType;
use App\Repository\PortfolioRepository;
use App\Repository\TransactionRepository;
use App\Service\HoldingsCalculatorService;
use App\Service\PortfolioSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class PortfolioController extends AbstractController
{
    public function __construct(
        private readonly PortfolioSummaryService $portfolioSummaryService,
        private readonly HoldingsCalculatorService $holdingsCalculator,
    ) {}

    #[Route('/portfolio', name: 'app_portfolio_index')]
    public function index(PortfolioRepository $portfolioRepository): Response
    {
        $portfolios = $portfolioRepository->findAllByUser($this->getUser());
        $combinedSummary = $this->portfolioSummaryService->getCombinedSummary($portfolios);

        return $this->render('portfolio/index.html.twig', [
            'portfolios' => $portfolios,
            'combined' => $combinedSummary,
        ]);
    }

    #[Route('/portfolio/{id}', name: 'app_portfolio_show', requirements: ['id' => '\d+'])]
    public function show(Portfolio $portfolio): Response
    {
        if ($portfolio->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $summary = $this->portfolioSummaryService->getPortfolioSummary($portfolio);

        return $this->render('portfolio/show.html.twig', [
            'portfolio' => $portfolio,
            'summary' => $summary,
        ]);
    }

    #[Route('/portfolio/{id}/coin/{coinId}', name: 'app_portfolio_coin', requirements: ['id' => '\d+', 'coinId' => '\d+'])]
    public function showCoin(Portfolio $portfolio, int $coinId, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): Response
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

        $transactions = $transactionRepository->findBy(
            ['portfolio' => $portfolio, 'coin' => $coin],
            ['created_at' => 'DESC']
        );

        $holding = $this->holdingsCalculator->calculate($transactions);

        return $this->render('portfolio/show_coin.html.twig', [
            'portfolio' => $portfolio,
            'coin' => $coin,
            'transactions' => $transactions,
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

            return $this->redirectToRoute('app_portfolio_index');
        }

        return $this->render('portfolio/new.html.twig', [
            'form' => $form,
        ]);
    }
}
