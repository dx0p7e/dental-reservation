<?php

namespace App\Filament\Doctor\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Doctor\Resources\DoctorAppointmentResource\Pages\EditDoctorAppointment;
use App\Filament\Doctor\Resources\DoctorAppointmentResource\Pages\ListDoctorAppointments;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoctorAppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.appointments');
    }

    public static function getModelLabel(): string
    {
        return __('filament.model.appointment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.nav.appointments');
    }

    public static function getEloquentQuery(): Builder
    {
        $doctor = auth()->user()?->doctor;

        if (! $doctor) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('doctor_id', $doctor->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('patient_name')
                    ->label(__('filament.fields.patient'))
                    ->content(fn (Appointment $record): string => $record->patient->name ?? '—'),
                Placeholder::make('service_name')
                    ->label(__('filament.fields.service'))
                    ->content(fn (Appointment $record): string => $record->service->name ?? '—'),
                Placeholder::make('slot_info')
                    ->label(__('filament.fields.slot'))
                    ->content(fn (Appointment $record): string => $record->slot
                        ? $record->slot->date->format('Y-m-d').' '.$record->slot->start_time
                        : '—'),
                Placeholder::make('status_display')
                    ->label(__('filament.fields.status'))
                    ->content(fn (Appointment $record): string => $record->status->value),
                Textarea::make('doctor_notes')
                    ->label(__('filament.fields.doctor_notes'))
                    ->rows(4)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label(__('filament.fields.patient'))
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
                        AppointmentStatus::Pending   => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Cancelled => 'danger',
                        AppointmentStatus::Completed => 'gray',
                        AppointmentStatus::NoShow    => 'gray',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('markComplete')
                    ->label(__('filament.actions.mark_complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Confirmed)
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['status' => AppointmentStatus::Completed]);
                        Notification::make()->success()->title(__('filament.notifications.appointment_completed'))->send();
                    }),
                Action::make('markNoShow')
                    ->label(__('filament.actions.mark_no_show'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Confirmed)
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['status' => AppointmentStatus::NoShow]);
                        Notification::make()->success()->title(__('filament.notifications.appointment_no_show'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoctorAppointments::route('/'),
            'edit'  => EditDoctorAppointment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canUpdate(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }
}
