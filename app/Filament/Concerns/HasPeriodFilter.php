<?php

namespace App\Filament\Concerns;

use Carbon\Carbon;

/**
 * Provides dateRange() for widgets that use InteractsWithPageFilters.
 * Reads $this->pageFilters['period'] and returns a [start, end] Carbon pair.
 *
 * @property array<string, mixed> $pageFilters
 */
trait HasPeriodFilter
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(): array
    {
        $period = $this->pageFilters['period'] ?? 'this_month';

        return match ($period) {
            'last_month'   => [
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_30_days' => [
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now()->endOfDay(),
            ],
            'all_time'     => [
                Carbon::createFromTimestamp(0),
                Carbon::now()->endOfDay(),
            ],
            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
    }
}
