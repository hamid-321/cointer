<?php
namespace App\Service;

use App\Repository\CoinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PriceUpdater
{
    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em,
        private CoinRepository $coinRepository,
        private string $coingeckoApiKey // pulled from services.yaml
    ) {}

    public function updateAllPrices(): void
    {
        // fetch data for the 5 coins 
        $coinIds = 'bitcoin,ethereum,tether,binancecoin,ripple,usd-coin,solana,tron,staked-ether,dogecoin';
        
        $response = $this->client->request('GET', 'https://api.coingecko.com/api/v3/coins/markets', [
            'query' => [
                'vs_currency' => 'usd',
                'ids' => $coinIds,
                'x_cg_demo_api_key' => $this->coingeckoApiKey
            ]
        ]);

        $data = $response->toArray();

        foreach ($data as $marketData) {
            $coin = $this->coinRepository->findOneBy(['coin_gecko_id' => $marketData['id']]);
            if ($coin) {
                $coin->setPrice($marketData['current_price']);
                $coin->setMarketCap($marketData['market_cap']);
                $coin->setChange24h($marketData['price_change_percentage_24h']);
                $coin->setUpdatedAt(new \DateTimeImmutable());
            }
        }

        $this->em->flush();
    }
}