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
        // fetch data for the 10 coins 
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

    public function backfillYearlyHistory(): void
    {
        // fetch data for the 10 coins 
        $coinIds = ['bitcoin', 'ethereum', 'tether', 'binancecoin', 'ripple', 'usd-coin', 'solana', 'tron', 'staked-ether', 'dogecoin'];
        
        foreach ($coinIds as $id) {
            $coin = $this->coinRepository->findOneBy(['coin_gecko_id' => $id]);
            if (!$coin) continue;

            $response = $this->client->request('GET', "https://api.coingecko.com/api/v3/coins/$id/market_chart", [
                'query' => [
                    'vs_currency' => 'usd',
                    'days' => '365',
                    'interval' => 'daily',
                    'x_cg_demo_api_key' => $this->coingeckoApiKey
                ]
            ]);

            $data = $response->toArray();
            $uniquePrices = [];

            foreach ($data['prices'] as $pricePoint) {
                $date = (new \DateTimeImmutable())->setTimestamp((int)($pricePoint[0] / 1000));
                $dateKey = $date->format('Y-m-d');
                $uniquePrices[$dateKey] = ['price' => $pricePoint[1], 'date' => $date];
            }

            foreach ($uniquePrices as $point) {
                $history = new \App\Entity\CoinHistory();
                $history->setCoin($coin);
                $history->setPrice($point['price']);
                $history->setDate($point['date']);
                $this->em->persist($history);
            }

            try {
                $this->em->flush();
            } 
            catch (\Exception $e) {
                $this->em->clear(); 
            }
    
            sleep(2); 
        }
    }
}