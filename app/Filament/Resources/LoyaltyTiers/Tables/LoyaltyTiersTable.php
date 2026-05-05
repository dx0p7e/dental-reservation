<?php

namespace App\Filament\Resources\LoyaltyTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoyaltyTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'silver' => 'info',
                        'gold' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('points_threshold')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_bonus_pct')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                ColorColumn::make('color')
                    ->sortable(false),
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
