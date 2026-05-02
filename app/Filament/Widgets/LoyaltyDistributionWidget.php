<?php

namespace App\Filament\Widgets;

use App\Models\LoyaltyAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoyaltyDistributionWidget extends StatsOverviewWidget
{
    public function getStats(): array
    {
        return [
            Stat::make(__('filament.widgets.standard_tier'), LoyaltyAccount::where('tier', 'standard')->count()),
            Stat::make(__('filament.widgets.silver_tier'), LoyaltyAccount::where('tier', 'silver')->count()),
            Stat::make(__('filament.widgets.gold_tier'), LoyaltyAccount::where('tier', 'gold')->count()),
        ];
    }
}
