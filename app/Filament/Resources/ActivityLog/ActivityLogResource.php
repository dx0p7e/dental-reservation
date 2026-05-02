<?php

namespace App\Filament\Resources\ActivityLog;

use App\Filament\Resources\ActivityLog\Pages\ListActivityLog;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.audit_log');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.model.activity_log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ActivityLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLog::route('/'),
        ];
    }
}
