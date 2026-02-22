<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DataFormatter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_market_cap', [$this, 'formatMarketCap']),
            new TwigFilter('format_long_price', [$this, 'formatLongPrice']),
            new TwigFilter('format_value_short', [$this, 'formatValueShort']),
            new TwigFilter('format_price', [$this, 'formatPrice']),
            new TwigFilter('format_currency', [$this, 'formatCurrency']),
            new TwigFilter('format_quantity', [$this, 'formatQuantity']),
            new TwigFilter('format_holdings', [$this, 'formatHoldings']),
            new TwigFilter('format_quantity_input', [$this, 'formatQuantityInput']),
            new TwigFilter('format_price_input', [$this, 'formatPriceInput']),
            new TwigFilter('format_date', [$this, 'formatDate']),
        ];
    }

    /**
     * Format market cap with appropriate suffix (T, B, M, K)
     */
    public function formatMarketCap(?float $value): string
    {
        if ($value === null || $value == 0)
        {
            return '$0.00';
        }

        if ($value >= 1_000_000_000_000)
        {
            return '$' . number_format($value / 1_000_000_000_000, 2) . ' T';
        }
        elseif ($value >= 1_000_000_000)
        {
            return '$' . number_format($value / 1_000_000_000, 2) . ' B';
        }
        elseif ($value >= 1_000_000)
        {
            return '$' . number_format($value / 1_000_000, 2) . ' M';
        }
        elseif ($value >= 1_000)
        {
            return '$' . number_format($value / 1_000, 2) . ' K';
        }

        return '$' . number_format($value, 2);
    }

    /**
     * Format long numeric values with appropriate suffix ( M, K)
     */
    public function formatLongPrice(?float $value): string
    {
        if ($value === null || $value == 0)
        {
            return '$0.00';
        }

        $abs = abs($value);

        if ($abs >= 1_000_000_000_000)
        {
            return '$' . number_format($value / 1_000_000_000_000, 2) . ' T';
        }
        elseif ($abs >= 1_000_000_000)
        {
            return '$' . number_format($value / 1_000_000_000, 2) . ' B';
        }
        elseif ($abs >= 10_000_000)
        {
            return '$' . number_format($value / 1_000_000, 2) . ' M';
        }

        return '$' . number_format($abs, 2);
    }


    public function formatValueShort(?float $value): string
    {
        if ($value === null || $value == 0)
        {
            return '$0.00';
        }

        $abs = abs($value);

        if ($abs >= 1_000_000_000)
        {
            return '$' . number_format($value / 1_000_000_000, 2, '.', ',') . ' B';
        }
        if ($abs >= 1_000_000)
        {
            return '$' . number_format($value / 1_000_000, 2, '.', ',') . ' M';
        }

        return $this->formatPrice($value);
    }

    public function formatPrice(?float $value): string
    {
        if ($value === null)
        {
            return '$0.00';
        }

        $abs = abs($value);

        if ($abs == 0)
        {
            return '$0.00';
        }

        if ($abs >= 1)
        {
            return '$' . number_format(round($value, 2), 2, '.', ',');
        }

        $formatted = number_format($value, 8, '.', ',');

        return '$' . $this->stripTrailingZeros($formatted);
    }

    
    /**
     * Strip trailing zeros from decmial numbers
     */
    private function stripTrailingZeros(string $number): string
    {
        if (!str_contains($number, '.'))
        {
            return $number;
        }
        [$intPart, $decPart] = explode('.', $number, 2);
        $decPart = rtrim($decPart, '0');

        return $decPart === '' ? $intPart : $intPart . '.' . $decPart;
    }

    /**
     * Format currency with full value (no truncation)
     */
    public function formatCurrency(?float $value, int $decimals = 0): string
    {
        if ($value === null)
        {
            return '$0';
        }

        return '$' . number_format($value, $decimals, '.', ',');
    }

    public function formatHoldings(float|string|null $value): string
    {
        if ($value === null)
        {
            return '0';
        }

        $float = (float) $value;

        if ($float > 1)
        {
            $formatted = number_format(round($float, 2), 2, '.', ',');
            return $this->stripTrailingZeros($formatted) ?: '0';
        }

        $formatted = number_format(round($float, 6), 6, '.', ',');
        return $this->stripTrailingZeros($formatted) ?: '0';
    }

    /**
     * Format quantity, stripping unnecessary trailing zeros
     */
    public function formatQuantity(float|string|null $value): string
    {
        if ($value === null)
        {
            return '0';
        }

        // Remove trailing zeros after decimal point
        $formatted = rtrim(rtrim(number_format((float) $value, 8, '.', ','), '0'), '.');

        return $formatted;
    }

    public function formatQuantityInput(float|string|null $value): string
    {
        if ($value === null)
        {
            return '0';
        }
        // no thousands separator
        $formatted = rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');

        return $formatted ?: '0';
    }

    public function formatPriceInput(float|string|null $value): string
    {
        if ($value === null)
        {
            return '0.00';
        }

        $float = (float) $value;

        // no thousands separator
        $formatted = rtrim(rtrim(number_format($float, 8, '.', ''), '0'), '.');

        if ($formatted === '' || $float === 0.0)
        {
            return '0.00';
        }

        $hasDecimal = str_contains($formatted, '.');
        $decimalPart = $hasDecimal ? substr($formatted, strpos($formatted, '.') + 1) : '';

        if (!$hasDecimal || strlen($decimalPart) < 2)
        {
            return number_format($float, 2, '.', '');
        }

        return $formatted;
    }

    /**
     * Format date/datetime in a consistent way
     */
    public function formatDate(?\DateTimeInterface $date, bool $includeTime = true): string
    {
        if ($date === null)
        {
            return 'N/A';
        }

        if ($includeTime)
        {
            return $date->format('M d, Y H:i');
        }

        return $date->format('M d, Y');
    }
}
