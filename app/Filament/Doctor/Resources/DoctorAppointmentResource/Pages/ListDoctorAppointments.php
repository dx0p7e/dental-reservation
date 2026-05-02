<?php

namespace App\Filament\Doctor\Resources\DoctorAppointmentResource\Pages;

use App\Filament\Doctor\Resources\DoctorAppointmentResource;
use Filament\Resources\Pages\ListRecords;

class ListDoctorAppointments extends ListRecords
{
    protected static string $resource = DoctorAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
