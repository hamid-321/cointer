<?php

namespace App\DataFixtures;

use App\Entity\Coin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coins = [
            ['id' => 'bitcoin', 'name' => 'Bitcoin', 'symbol' => 'btc'],
            ['id' => 'ethereum', 'name' => 'Ethereum', 'symbol' => 'eth'],
            ['id' => 'tether', 'name' => 'Tether', 'symbol' => 'usdt'],
            ['id' => 'binancecoin', 'name' => 'BNB', 'symbol' => 'bnb'],
            ['id' => 'ripple', 'name' => 'XRP', 'symbol' => 'xrp'],
            ['id' => 'usd-coin', 'name' => 'USDC', 'symbol' => 'usdc'],
            ['id' => 'solana', 'name' => 'Solana', 'symbol' => 'sol'],
            ['id' => 'tron', 'name' => 'TRON', 'symbol' => 'trx'],
            ['id' => 'staked-ether', 'name' => 'Lido Staked Ether', 'symbol' => 'steth'],
            ['id' => 'dogecoin', 'name' => 'Dogecoin', 'symbol' => 'doge'],
        ];
        
        foreach ($coins as $c) {
            $coin = new Coin();
            $coin->setCoingeckoId($c['id']);
            $coin->setName($c['name']);
            $coin->setSymbol($c['symbol']);
            $coin->setPrice(0); 
            $coin->setMarketCap(0);
            $coin->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($coin);
        }
        
        $manager->flush();
    }
}
