<?php

namespace App\Controller;

use App\Entity\Coin;
use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Service\PortfolioSummaryService;
use App\Twig\DataFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TransactionController extends AbstractController
{
    public function __construct(
        private readonly PortfolioSummaryService $portfolioSummaryService,
        private readonly DataFormatter $dataFormatter,
    ) {}

    #[Route('/portfolio/{id}/coin-balance', name: 'app_portfolio_coin_balance', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function coinBalance(Portfolio $portfolio, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($portfolio->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $coinId = $request->query->getInt('coinId');
        if ($coinId <= 0) {
            return new JsonResponse(['balance' => 0.0]);
        }

        $coin = $entityManager->getRepository(Coin::class)->find($coinId);
        if (!$coin) {
            return new JsonResponse(['balance' => 0.0]);
        }

        $excludeTransaction = null;
        $excludeTransactionId = $request->query->getInt('excludeTransactionId');
        if ($excludeTransactionId > 0) {
            $excludeTransaction = $entityManager->getRepository(Transaction::class)->find($excludeTransactionId);
            if (!$excludeTransaction || $excludeTransaction->getPortfolio() !== $portfolio) {
                $excludeTransaction = null;
            }
        }

        $balance = $this->portfolioSummaryService->getAvailableQuantityToSell($portfolio, $coinId, $excludeTransaction);

        return new JsonResponse(['balance' => $balance]);
    }

    #[Route('/portfolio/{id}/new', name: 'app_transaction_new', requirements: ['id' => '\d+'])]
    public function new(Portfolio $portfolio, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        $holdingsByCoin = $this->portfolioSummaryService->getHoldingsQuantityByCoin($portfolio);

        $transaction = new Transaction();
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $coinId = $request->query->getInt('coinId');
        if ($coinId > 0) {
            $coin = $entityManager->getRepository(Coin::class)->find($coinId);
            if ($coin) {
                $transaction->setCoin($coin);
            }
        }
        $form = $this->createForm(TransactionType::class, $transaction, [
            'holdings_by_coin' => $holdingsByCoin,
            'quantity_formatter' => $this->dataFormatter,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $transaction->setPortfolio($portfolio);
            $transaction->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Your transaction has been added.');

            return $this->redirectToRoute('app_portfolio_coin', [
                'id' => $portfolio->getId(),
                'coinId' => $transaction->getCoin()->getId(),
            ]);
        }

        return $this->render('transaction/new.html.twig', [
            'form' => $form,
            'portfolio' => $portfolio,
            'coin' => $transaction->getCoin(),
        ]);
    }

    #[Route('/portfolio/{id}/transaction/{transactionId}/edit', name: 'app_transaction_edit', requirements: ['id' => '\d+', 'transactionId' => '\d+'])]
    public function edit(Portfolio $portfolio, int $transactionId, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        if (!$transaction || $transaction->getPortfolio() !== $portfolio)
        {
            throw $this->createNotFoundException('Transaction not found');
        }

        $coinId = $transaction->getCoin()->getId();
        $pricePerCoin = $transaction->getPricePerCoin();
        $holdingsByCoin = $this->portfolioSummaryService->getHoldingsQuantityByCoin($portfolio, $transaction);

        $form = $this->createForm(TransactionType::class, $transaction, [
            'price_per_coin' => $pricePerCoin,
            'holdings_by_coin' => $holdingsByCoin,
            'quantity_formatter' => $this->dataFormatter,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->flush();

            $this->addFlash('success', 'Your transaction has been updated.');

            return $this->redirectToRoute('app_portfolio_coin', [
                'id' => $portfolio->getId(),
                'coinId' => $transaction->getCoin()->getId(),
            ]);
        }

        return $this->render('transaction/edit.html.twig', [
            'form' => $form,
            'portfolio' => $portfolio,
            'transaction' => $transaction,
        ]);
    }

    #[Route('/portfolio/{id}/transaction/{transactionId}/delete', name: 'app_transaction_delete', requirements: ['id' => '\d+', 'transactionId' => '\d+'], methods: ['POST'])]
    public function delete(Portfolio $portfolio, int $transactionId, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        if (!$transaction || $transaction->getPortfolio() !== $portfolio)
        {
            throw $this->createNotFoundException('Transaction not found');
        }

        $coinId = $transaction->getCoin()->getId();

        if ($this->isCsrfTokenValid('delete-transaction-' . $transactionId, $request->request->get('_token')))
        {
            $entityManager->remove($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Your transaction has been deleted.');
        }

        return $this->redirectToRoute('app_portfolio_coin', [
            'id' => $portfolio->getId(),
            'coinId' => $coinId,
        ]);
    }
}
