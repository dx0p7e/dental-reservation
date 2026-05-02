<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Actions\ConfirmAppointmentRequestAction;
use App\Filament\Resources\Appointments\Actions\MarkAppointmentNoShowAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label(__('filament.fields.patient'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('doctor.user.name')
                    ->label(__('filament.fields.doctor'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label(__('filament.fields.service'))
                    ->sortable(),
                TextColumn::make('slot.date')
                    ->label(__('filament.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('slot.start_time')
                    ->label(__('filament.fields.time'))
                    ->time()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AppointmentStatus $state): string => match ($state) {
                        AppointmentStatus::Pending => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Cancelled => 'danger',
                        AppointmentStatus::Completed => 'gray',
                        AppointmentStatus::NoShow => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AppointmentStatus::class),
            ])
            ->recordActions([
                ConfirmAppointmentRequestAction::make(),
                MarkAppointmentNoShowAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
