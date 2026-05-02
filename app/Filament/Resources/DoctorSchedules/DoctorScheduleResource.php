<?php

namespace App\Filament\Resources\DoctorSchedules;

use App\Filament\Resources\DoctorSchedules\Pages\CreateDoctorSchedule;
use App\Filament\Resources\DoctorSchedules\Pages\EditDoctorSchedule;
use App\Filament\Resources\DoctorSchedules\Pages\ListDoctorSchedules;
use App\Filament\Resources\DoctorSchedules\Schemas\DoctorScheduleForm;
use App\Filament\Resources\DoctorSchedules\Tables\DoctorSchedulesTable;
use App\Models\DoctorSchedule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DoctorScheduleResource extends Resource
{
    protected static ?string $model = DoctorSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.group.staff');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.schedule_slots');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.doctor_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.model.doctor_schedules');
    }

    public static function form(Schema $schema): Schema
    {
        return DoctorScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoctorSchedulesTable::configure($table);
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
            'index' => ListDoctorSchedules::route('/'),
            'create' => CreateDoctorSchedule::route('/create'),
            'edit' => EditDoctorSchedule::route('/{record}/edit'),
        ];
    }
}
