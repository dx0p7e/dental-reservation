<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Concerns\HasPeriodFilter;
use App\Models\Appointment;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueEstimateWidget extends StatsOverviewWidget
{
    use HasPeriodFilter, InteractsWithPageFilters;

    public function getStats(): array
    {
        [$start, $end] = $this->dateRange();

        $revenue = Appointment::query()
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->leftJoin('schedule_slots', 'schedule_slots.id', '=', 'appointments.slot_id')
            ->where('appointments.status', AppointmentStatus::Completed)
            ->whereBetween(
                DB::raw('COALESCE(schedule_slots.date, appointments.preferred_date, DATE(appointments.created_at))'),
                [$start->toDateString(), $end->toDateString()],
            )
            ->sum('services.price');

        return [
            Stat::make(__('filament.widgets.estimated_revenue'), '£'.number_format((float) $revenue, 2))
                ->description(__('filament.widgets.revenue_description')),
        ];
    }
}
