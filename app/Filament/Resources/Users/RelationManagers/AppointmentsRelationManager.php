<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\AppointmentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament.relations.appointments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service.name')
                    ->label(__('filament.fields.service')),
                TextColumn::make('doctor.user.name')
                    ->label(__('filament.fields.doctor')),
                TextColumn::make('slot.date')
                    ->label(__('filament.fields.date'))
                    ->date(),
                TextColumn::make('slot.start_time')
                    ->label(__('filament.fields.time'))
                    ->time(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AppointmentStatus $state): string => match ($state) {
                        AppointmentStatus::Pending => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Cancelled => 'danger',
                        AppointmentStatus::Completed => 'gray',
                        AppointmentStatus::NoShow => 'gray',
                    }),
                TextColumn::make('final_price')
                    ->label(__('filament.fields.final_price'))
                    ->money('EUR')
                    ->placeholder('—'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
