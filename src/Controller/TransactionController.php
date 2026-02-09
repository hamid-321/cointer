<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\Transaction;
use App\Form\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TransactionController extends AbstractController
{
    #[Route('/portfolio/{id}/new', name: 'app_transaction_new', requirements: ['id' => '\d+'])]
    public function new(Portfolio $portfolio, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($portfolio->getUser() !== $this->getUser())
        {
            throw $this->createAccessDeniedException();
        }

        $transaction = new Transaction();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $transaction->setPortfolio($portfolio);
            $transaction->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($transaction);
            $entityManager->flush();

            return $this->redirectToRoute('app_portfolio_show', ['id' => $portfolio->getId()]);
        }

        return $this->render('transaction/new.html.twig', [
            'form' => $form,
            'portfolio' => $portfolio,
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
        $pricePerCoin = ($transaction->getQuantity() > 0)
            ? round($transaction->getPrice() / $transaction->getQuantity(), 2)
            : null;
        $form = $this->createForm(TransactionType::class, $transaction, [
            'price_per_coin' => $pricePerCoin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $entityManager->flush();

            return $this->redirectToRoute('app_portfolio_coin', [
                'id' => $portfolio->getId(),
                'coinId' => $coinId,
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
        }

        return $this->redirectToRoute('app_portfolio_coin', [
            'id' => $portfolio->getId(),
            'coinId' => $coinId,
        ]);
    }
}
