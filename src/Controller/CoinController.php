<?php

namespace App\Controller;

use App\Entity\Coin;
use App\Form\CoinType;
use App\Repository\CoinRepository;
use App\Service\ChartService;
use App\Service\LastUpdateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/coin')]
final class CoinController extends AbstractController
{
    public function __construct(
        private readonly LastUpdateService $lastUpdateService,
        private readonly ChartService $chartService
    ) {}

    #[Route(name: 'app_coin_index', methods: ['GET'])]
    public function index(CoinRepository $coinRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $searchTerm = $request->query->get('q', '');
        $query = $coinRepository->getPaginationQuery($searchTerm);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10,
            ['defaultSortFieldName' => 'c.market_cap', 'defaultSortDirection' => 'desc']
        );

        $lastUpdated = $this->lastUpdateService->getLastUpdatedFromItems($pagination->getItems());

        return $this->render('coin/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'lastUpdated' => $lastUpdated,
        ]);
    }

    #[Route('/{id}', name: 'app_coin_show', methods: ['GET'])]
    public function show(Coin $coin): Response
    {
        $chart = $this->chartService->buildPriceHistoryChart($coin);

        return $this->render('coin/show.html.twig', [
            'coin' => $coin,
            'chart' => $chart,
        ]);
    }

}
