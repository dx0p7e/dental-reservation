<?php

namespace App\Filament\Resources\Appointments\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LoyaltyTier;
use App\Models\ScheduleSlot;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;

class ConfirmAppointmentRequestAction
{
    public static function make(): Action
    {
        return Action::make('confirmRequest')
            ->label(__('filament.actions.confirm'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (Appointment $record): bool => $record->slot_id === null && $record->status === AppointmentStatus::Pending)
            ->modalHeading(__('filament.modals.confirm_appointment.heading'))
            ->modalSubmitActionLabel(__('filament.modals.confirm_appointment.submit'))
            ->schema([
                Select::make('doctor_id')
                    ->label(__('filament.fields.doctor'))
                    ->options(fn (): array => Doctor::with('user')
                        ->where('is_active', true)
                        ->get()
                        ->pluck('user.name', 'id')
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->live(),
                Select::make('slot_id')
                    ->label(__('filament.fields.available_slot'))
                    ->options(fn (Get $get): array => $get('doctor_id')
                        ? ScheduleSlot::where('doctor_id', $get('doctor_id'))
                            ->where('is_booked', false)
                            ->whereDate('date', '>=', now()->toDateString())
                            ->orderBy('date')
                            ->orderBy('start_time')
                            ->get()
                            ->mapWithKeys(fn (ScheduleSlot $slot): array => [
                                $slot->id => $slot->date->format('Y-m-d').' '.$slot->start_time,
                            ])
                            ->toArray()
                        : [])
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Appointment $record): void {
                DB::transaction(function () use ($data, $record): void {
                    $account = $record->patient?->loyaltyAccount;
                    $discountPct = 0;
                    if ($account) {
                        $tier = LoyaltyTier::where('tier', $account->tier)->first();
                        $discountPct = $tier->discount_bonus_pct ?? 0;
                    }

                    $service = Service::find($record->service_id);
                    assert($service !== null);
                    $finalPrice = round($service->price * (1 - $discountPct / 100), 2);

                    $record->update([
                        'doctor_id' => $data['doctor_id'],
                        'slot_id' => $data['slot_id'],
                        'status' => AppointmentStatus::Confirmed,
                        'discount_pct' => $discountPct,
                        'final_price' => $finalPrice,
                    ]);

                    $startSlot = ScheduleSlot::find($data['slot_id']);
                    if ($startSlot !== null) {
                        $endTime = Carbon::parse($startSlot->date->format('Y-m-d').' '.$startSlot->start_time)
                            ->addMinutes($service->duration_minutes);

                        ScheduleSlot::where('doctor_id', $startSlot->doctor_id)
                            ->whereDate('date', $startSlot->date)
                            ->where('start_time', '>=', $startSlot->start_time)
                            ->where('start_time', '<', $endTime->format('H:i'))
                            ->update(['is_booked' => true]);
                    }
                });

                Notification::make()
                    ->title(__('filament.notifications.appointment_confirmed'))
                    ->success()
                    ->send();
            });
    }
}
