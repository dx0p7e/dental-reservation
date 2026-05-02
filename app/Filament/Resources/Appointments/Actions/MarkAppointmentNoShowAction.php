<?php

namespace App\Filament\Resources\Appointments\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Actions\Action;

class MarkAppointmentNoShowAction
{
    public static function make(): Action
    {
        return Action::make('markNoShow')
            ->label(__('filament.actions.no_show'))
            ->icon('heroicon-o-user-minus')
            ->requiresConfirmation()
            ->modalHeading(__('filament.modals.no_show.heading'))
            ->modalDescription(__('filament.modals.no_show.description'))
            ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Confirmed)
            ->action(fn (Appointment $record): bool => $record->update(['status' => AppointmentStatus::NoShow]));
    }
}
