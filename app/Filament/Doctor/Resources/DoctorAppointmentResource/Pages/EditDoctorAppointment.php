<?php

namespace App\Filament\Doctor\Resources\DoctorAppointmentResource\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Doctor\Resources\DoctorAppointmentResource;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDoctorAppointment extends EditRecord
{
    protected static string $resource = DoctorAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markComplete')
                ->label(__('filament.actions.mark_complete'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record instanceof Appointment
                    && $this->record->status === AppointmentStatus::Confirmed)
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Appointment $appointment */
                    $appointment = $this->record;
                    $appointment->update(['status' => AppointmentStatus::Completed]);
                    Notification::make()->success()->title(__('filament.notifications.appointment_completed'))->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('markNoShow')
                ->label(__('filament.actions.mark_no_show'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record instanceof Appointment
                    && $this->record->status === AppointmentStatus::Confirmed)
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Appointment $appointment */
                    $appointment = $this->record;
                    $appointment->update(['status' => AppointmentStatus::NoShow]);
                    Notification::make()->success()->title(__('filament.notifications.appointment_no_show'))->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
