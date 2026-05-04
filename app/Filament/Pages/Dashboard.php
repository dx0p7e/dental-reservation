<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label(__('filament.fields.period'))
                ->options([
                    'this_month' => __('filament.options.this_month'),
                    'last_month' => __('filament.options.last_month'),
                    'last_30_days' => __('filament.options.last_30_days'),
                    'all_time' => __('filament.options.all_time'),
                ])
                ->default('this_month')
                ->selectablePlaceholder(false),
        ]);
    }
}
