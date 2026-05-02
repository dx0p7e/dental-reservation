<?php

namespace App\Filament\Resources\LoyaltyTiers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LoyaltyTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tier')
                    ->options([
                        'standard' => __('filament.options.standard'),
                        'silver' => __('filament.options.silver'),
                        'gold' => __('filament.options.gold'),
                    ])
                    ->required(),
                TextInput::make('points_threshold')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('discount_bonus_pct')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]);
    }
}
