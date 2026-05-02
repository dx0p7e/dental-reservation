<?php

namespace App\Filament\Resources\DoctorSchedules\Pages;

use App\Filament\Resources\DoctorSchedules\DoctorScheduleResource;
use App\Models\DoctorSchedule;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorSchedule extends CreateRecord
{
    protected static string $resource = DoctorScheduleResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $overlap = DoctorSchedule::where('doctor_id', $data['doctor_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlap) {
            Notification::make()
                ->danger()
                ->title(__('filament.notifications.time_overlap.title'))
                ->body(__('filament.notifications.time_overlap.body'))
                ->send();

            $this->halt();
        }
    }
}
