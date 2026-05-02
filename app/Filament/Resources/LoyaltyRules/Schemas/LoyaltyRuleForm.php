<?php

namespace App\Filament\Resources\LoyaltyRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LoyaltyRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->required()
                    ->rule(fn (?Model $record) => Rule::unique('loyalty_rules', 'service_id')->ignore($record?->id)),
                TextInput::make('points_earned')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('discount_pct')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                TextInput::make('valid_months')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('mo'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
