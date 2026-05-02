<?php

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\AppointmentsOverviewWidget;
use App\Filament\Widgets\RevenueEstimateWidget;
use App\Models\Appointment;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
    Mail::fake();
});

// ─── Task 5.3 ────────────────────────────────────────────────────────────────

test('admin dashboard page renders without error', function (): void {
    $this->get('/admin')->assertOk();
});

// ─── Task 5.1 ────────────────────────────────────────────────────────────────

test('appointments overview widget counts appointments in the current month via slot date', function (): void {
    $patient = User::factory()->create();

    // Two appointments this month via slot date
    Appointment::factory()->count(2)->create([
        'patient_id' => $patient->id,
        'slot_id'    => ScheduleSlot::factory()->create(['date' => now()->startOfMonth()->addDays(1)->toDateString()])->id,
        'status'     => AppointmentStatus::Pending,
    ]);

    // One appointment outside this month — should not be counted
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'slot_id'    => ScheduleSlot::factory()->create(['date' => now()->subMonths(2)->toDateString()])->id,
        'status'     => AppointmentStatus::Pending,
    ]);

    Livewire::test(AppointmentsOverviewWidget::class, ['pageFilters' => ['period' => 'this_month']])
        ->assertSuccessful()
        ->assertSeeText('2'); // Total stat value
});

test('appointments overview widget counts request-based appointments via preferred_date', function (): void {
    $patient = User::factory()->create();

    // Request-based appointment (no slot) with preferred_date this month
    Appointment::factory()->create([
        'patient_id'     => $patient->id,
        'slot_id'        => null,
        'doctor_id'      => null,
        'preferred_date' => now()->startOfMonth()->addDays(3)->toDateString(),
        'status'         => AppointmentStatus::Pending,
    ]);

    // Request-based appointment preferred_date last month — should not be counted
    Appointment::factory()->create([
        'patient_id'     => $patient->id,
        'slot_id'        => null,
        'doctor_id'      => null,
        'preferred_date' => now()->subMonths(1)->startOfMonth()->toDateString(),
        'status'         => AppointmentStatus::Pending,
    ]);

    Livewire::test(AppointmentsOverviewWidget::class, ['pageFilters' => ['period' => 'this_month']])
        ->assertSuccessful()
        ->assertSeeText('1'); // Total stat value
});

// ─── Task 5.2 ────────────────────────────────────────────────────────────────

test('revenue estimate widget sums service prices for completed appointments in period', function (): void {
    $patient = User::factory()->create();
    $service = Service::factory()->create(['price' => 75.00]);

    // Two completed appointments this month
    Appointment::factory()->count(2)->create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'slot_id'    => ScheduleSlot::factory()->create(['date' => now()->startOfMonth()->addDays(2)->toDateString()])->id,
        'status'     => AppointmentStatus::Completed,
    ]);

    // Completed appointment outside this month — should not be included
    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'slot_id'    => ScheduleSlot::factory()->create(['date' => now()->subMonths(2)->toDateString()])->id,
        'status'     => AppointmentStatus::Completed,
    ]);

    Livewire::test(RevenueEstimateWidget::class, ['pageFilters' => ['period' => 'this_month']])
        ->assertSuccessful()
        ->assertSeeText('£150.00'); // 2 × 75.00
});

test('revenue estimate widget shows zero when no completed appointments in period', function (): void {
    Livewire::test(RevenueEstimateWidget::class, ['pageFilters' => ['period' => 'this_month']])
        ->assertSuccessful()
        ->assertSeeText('£0.00');
});
