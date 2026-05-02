<?php

namespace App\Filament\Resources\DoctorSchedules\Schemas;

use App\Models\Doctor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DoctorScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('doctor_id')
                    ->label(__('filament.fields.doctor'))
                    ->options(Doctor::with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('day_of_week')
                    ->options([
                        1 => __('filament.days.monday'),
                        2 => __('filament.days.tuesday'),
                        3 => __('filament.days.wednesday'),
                        4 => __('filament.days.thursday'),
                        5 => __('filament.days.friday'),
                        6 => __('filament.days.saturday'),
                        7 => __('filament.days.sunday'),
                    ])
                    ->required(),
                TimePicker::make('start_time')
                    ->label(__('filament.fields.start_time'))
                    ->required(),
                TimePicker::make('end_time')
                    ->label(__('filament.fields.end_time'))
                    ->required(),
                TextInput::make('slot_duration_minutes')
                    ->label(__('filament.fields.slot_duration'))
                    ->numeric()
                    ->minValue(1)
                    ->suffix('min')
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('filament.fields.is_active'))
                    ->default(true),
            ]);
    }
}
