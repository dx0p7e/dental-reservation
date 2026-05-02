<?php

namespace App\Filament\Resources\LoyaltyRules;

use App\Filament\Resources\LoyaltyRules\Pages\CreateLoyaltyRule;
use App\Filament\Resources\LoyaltyRules\Pages\EditLoyaltyRule;
use App\Filament\Resources\LoyaltyRules\Pages\ListLoyaltyRules;
use App\Filament\Resources\LoyaltyRules\Schemas\LoyaltyRuleForm;
use App\Filament\Resources\LoyaltyRules\Tables\LoyaltyRulesTable;
use App\Models\LoyaltyRule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LoyaltyRuleResource extends Resource
{
    protected static ?string $model = LoyaltyRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.loyalty_rules');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.loyalty_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.model.loyalty_rules');
    }

    public static function form(Schema $schema): Schema
    {
        return LoyaltyRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyRulesTable::configure($table);
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
            'index' => ListLoyaltyRules::route('/'),
            'create' => CreateLoyaltyRule::route('/create'),
            'edit' => EditLoyaltyRule::route('/{record}/edit'),
        ];
    }
}
