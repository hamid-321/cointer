<?php

namespace App\Service;

class LastUpdateService
{
    public function getLastUpdatedFromItems(array $items): string
    {
        if (empty($items)) {
            return 'Never';
        }

        return $this->getTimeAgo($items[0]->getUpdatedAt());
    }

    /**
     * Calculate the "time ago" string from a DateTimeImmutable
     */
    public function getTimeAgo(?\DateTimeImmutable $dateTime): string
    {
        if ($dateTime === null)
        {
            return 'Never';
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $dateTime->getTimestamp();

        if ($diff < 60)
        {
            return 'Just now';
        }
        elseif ($diff < 3600)
        {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        }
        elseif ($diff < 86400)
        {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        else
        {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}
