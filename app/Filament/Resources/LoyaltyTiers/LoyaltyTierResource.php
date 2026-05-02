<?php

namespace App\Filament\Resources\LoyaltyTiers;

use App\Filament\Resources\LoyaltyTiers\Pages\CreateLoyaltyTier;
use App\Filament\Resources\LoyaltyTiers\Pages\EditLoyaltyTier;
use App\Filament\Resources\LoyaltyTiers\Pages\ListLoyaltyTiers;
use App\Filament\Resources\LoyaltyTiers\Schemas\LoyaltyTierForm;
use App\Filament\Resources\LoyaltyTiers\Tables\LoyaltyTiersTable;
use App\Models\LoyaltyTier;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoyaltyTierResource extends Resource
{
    protected static ?string $model = LoyaltyTier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.loyalty_tiers');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.loyalty_tier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.model.loyalty_tiers');
    }

    public static function form(Schema $schema): Schema
    {
        return LoyaltyTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyTiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoyaltyTiers::route('/'),
            'create' => CreateLoyaltyTier::route('/create'),
            'edit' => EditLoyaltyTier::route('/{record}/edit'),
        ];
    }
}
