<?php

namespace App\Filament\Resources\Doctors\RelationManagers;

use App\Enums\AppointmentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament.relations.patients');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label(__('filament.fields.patient')),
                TextColumn::make('patient.email')
                    ->label(__('filament.fields.email')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AppointmentStatus $state): string => match ($state) {
                        AppointmentStatus::Pending => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Cancelled => 'danger',
                        AppointmentStatus::Completed => 'gray',
                        AppointmentStatus::NoShow => 'gray',
                    }),
                TextColumn::make('slot.date')
                    ->label(__('filament.fields.date'))
                    ->date(),
                TextColumn::make('slot.start_time')
                    ->label(__('filament.fields.time'))
                    ->time(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
