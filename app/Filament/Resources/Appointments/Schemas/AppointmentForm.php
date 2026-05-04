<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('patient_id')
                    ->label(__('filament.fields.patient'))
                    ->options(fn () => User::role('patient')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('doctor_id')
                    ->label(__('filament.fields.doctor'))
                    ->options(fn () => Doctor::with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('service_id')
                    ->label(__('filament.fields.service'))
                    ->options(fn () => Service::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('slot_id')
                    ->label(__('filament.fields.slot'))
                    ->options(fn () => ScheduleSlot::with('doctor.user')
                        ->get()
                        ->mapWithKeys(fn (ScheduleSlot $slot) => [
                            $slot->id => 'Dr. '.($slot->doctor?->user->name ?? '').' — '.$slot->date->format('Y-m-d').' '.$slot->start_time,
                        ]))
                    ->searchable()
                    ->nullable(),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->default(AppointmentStatus::Pending->value)
                    ->required(),
                Textarea::make('notes')
                    ->rows(3)
                    ->nullable(),
                Textarea::make('doctor_notes')
                    ->label(__('filament.fields.doctor_notes'))
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
