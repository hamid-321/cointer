<?php

namespace App\Entity;

use App\Repository\CoinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoinRepository::class)]
class Coin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $coin_gecko_id = null;

    #[ORM\Column(length: 10)]
    private ?string $symbol = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 10)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 25, scale: 2)]
    private ?string $market_cap = null;

    #[ORM\Column(nullable: true)]
    private ?float $change_24h = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, CoinHistory>
     */
    #[ORM\OneToMany(targetEntity: CoinHistory::class, mappedBy: 'coin')]
    private Collection $coin_history;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'coin')]
    private Collection $transactions;

    public function __construct()
    {
        $this->coin_history = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoinGeckoId(): ?string
    {
        return $this->coin_gecko_id;
    }

    public function setCoinGeckoId(string $coin_gecko_id): static
    {
        $this->coin_gecko_id = $coin_gecko_id;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getMarketCap(): ?string
    {
        return $this->market_cap;
    }

    public function setMarketCap(string $market_cap): static
    {
        $this->market_cap = $market_cap;

        return $this;
    }

    public function getChange24h(): ?float
    {
        return $this->change_24h;
    }

    public function setChange24h(?float $change_24h): static
    {
        $this->change_24h = $change_24h;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, CoinHistory>
     */
    public function getCoinHistory(): Collection
    {
        return $this->coin_history;
    }

    public function addCoinHistory(CoinHistory $coinHistory): static
    {
        if (!$this->coin_history->contains($coinHistory)) {
            $this->coin_history->add($coinHistory);
            $coinHistory->setCoin($this);
        }

        return $this;
    }

    public function removeCoinHistory(CoinHistory $coinHistory): static
    {
        if ($this->coin_history->removeElement($coinHistory)) {
            // set the owning side to null (unless already changed)
            if ($coinHistory->getCoin() === $this) {
                $coinHistory->setCoin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCoin($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCoin() === $this) {
                $transaction->setCoin(null);
            }
        }

        return $this;
    }
}
