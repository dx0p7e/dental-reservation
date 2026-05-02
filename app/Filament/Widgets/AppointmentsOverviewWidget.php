<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Concerns\HasPeriodFilter;
use App\Models\Appointment;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AppointmentsOverviewWidget extends StatsOverviewWidget
{
    use HasPeriodFilter, InteractsWithPageFilters;

    public function getStats(): array
    {
        [$start, $end] = $this->dateRange();

        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        $coalesceExpr = DB::raw(
            'COALESCE(schedule_slots.date, appointments.preferred_date, DATE(appointments.created_at))'
        );

        $baseQuery = fn () => Appointment::query()
            ->leftJoin('schedule_slots', 'schedule_slots.id', '=', 'appointments.slot_id')
            ->whereBetween($coalesceExpr, [$startDate, $endDate]);

        $total     = $baseQuery()->count('appointments.id');
        $pending   = $baseQuery()->where('appointments.status', AppointmentStatus::Pending)->count('appointments.id');
        $confirmed = $baseQuery()->where('appointments.status', AppointmentStatus::Confirmed)->count('appointments.id');
        $completed = $baseQuery()->where('appointments.status', AppointmentStatus::Completed)->count('appointments.id');
        $cancelled = $baseQuery()->where('appointments.status', AppointmentStatus::Cancelled)->count('appointments.id');

        return [
            Stat::make(__('filament.widgets.total_appointments'), $total),
            Stat::make(__('filament.widgets.pending'), $pending),
            Stat::make(__('filament.widgets.confirmed'), $confirmed),
            Stat::make(__('filament.widgets.completed'), $completed),
            Stat::make(__('filament.widgets.cancelled'), $cancelled),
        ];
    }
}
