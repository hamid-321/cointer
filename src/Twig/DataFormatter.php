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
            new TwigFilter('format_price', [$this, 'formatPrice']),
            new TwigFilter('format_currency', [$this, 'formatCurrency']),
            new TwigFilter('format_quantity', [$this, 'formatQuantity']),
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
     * Format price with appropriate decimal places
     */
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
        // For very small values (< $0.01), show more decimals
        if ($abs < 0.01)
        {
            return '$' . number_format($value, 6);
        }
        elseif ($abs < 1)
        {
            return '$' . number_format($value, 4);
        }

        return '$' . number_format($value, 2);
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
