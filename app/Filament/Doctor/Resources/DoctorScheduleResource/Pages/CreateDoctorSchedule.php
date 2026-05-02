<?php

namespace App\Filament\Doctor\Resources\DoctorScheduleResource\Pages;

use App\Filament\Doctor\Resources\DoctorScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorSchedule extends CreateRecord
{
    protected static string $resource = DoctorScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['doctor_id'] = auth()->user()?->doctor?->id;

        return $data;
    }
}
