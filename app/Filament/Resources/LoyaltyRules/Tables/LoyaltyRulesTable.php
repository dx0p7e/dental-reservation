<?php

namespace App\Filament\Resources\LoyaltyRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoyaltyRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('service.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('points_earned')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_pct')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('valid_months')
                    ->numeric()
                    ->suffix(' mo')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
