<?php

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\DoctorSchedules\Pages\ListDoctorSchedules;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\LoyaltyTier;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Mail::fake();
});

// --- T7.1: slot-based booking stores discount_pct and final_price for silver tier ---

test('slot-based booking stores correct discount_pct and final_price for non-zero tier', function (): void {
    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $patient->assignRole('patient');

    $tier = LoyaltyTier::factory()->create(['tier' => 'silver', 'discount_bonus_pct' => 10.00]);
    $patient->loyaltyAccount->update(['tier' => 'silver']);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create(['price' => 100.00]);
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments', [
        'doctor_id'  => $doctor->id,
        'service_id' => $service->id,
        'slot_id'    => $slot->id,
    ])->assertCreated();

    $appointment = Appointment::where('patient_id', $patient->id)->first();

    expect((float) $appointment->discount_pct)->toBe(10.0)
        ->and((float) $appointment->final_price)->toBe(90.0);
});

// --- T7.2: standard tier (0%) stores discount_pct=0 and final_price=service.price ---

test('slot-based booking with standard tier stores discount_pct 0 and final_price equal to service price', function (): void {
    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $patient->assignRole('patient');

    LoyaltyTier::factory()->create(['tier' => 'standard', 'discount_bonus_pct' => 0.00]);
    $patient->loyaltyAccount->update(['tier' => 'standard']);

    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create(['price' => 80.00]);
    $slot = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    Sanctum::actingAs($patient);

    $this->postJson('/api/v1/appointments', [
        'doctor_id'  => $doctor->id,
        'service_id' => $service->id,
        'slot_id'    => $slot->id,
    ])->assertCreated();

    $appointment = Appointment::where('patient_id', $patient->id)->first();

    expect((float) $appointment->discount_pct)->toBe(0.0)
        ->and((float) $appointment->final_price)->toBe(80.0);
});

// --- T7.3: request-based booking stores discount_pct=0 and final_price=null ---

test('request-based booking stores discount_pct 0 and null final_price', function (): void {
    $patient = User::factory()->create(['role' => 'patient', 'phone_verified_at' => now()]);
    $patient->assignRole('patient');

    Sanctum::actingAs($patient);

    $service = Service::factory()->create();

    $this->postJson('/api/v1/appointments/request', [
        'service_id'     => $service->id,
        'preferred_date' => now()->addDays(3)->toDateString(),
    ])->assertCreated();

    $appointment = Appointment::where('patient_id', $patient->id)->first();

    expect((float) $appointment->discount_pct)->toBe(0.0)
        ->and($appointment->final_price)->toBeNull();
});

// --- T7.4: AppointmentResource includes discount_pct and final_price keys ---

test('appointment resource response includes discount_pct and final_price keys', function (): void {
    $patient = User::factory()->create(['role' => 'patient']);
    $patient->assignRole('patient');

    Sanctum::actingAs($patient);

    $appointment = Appointment::factory()->create([
        'patient_id'  => $patient->id,
        'discount_pct' => 5.00,
        'final_price'  => 95.00,
    ]);

    $this->getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonFragment(['discount_pct' => '5.00', 'final_price' => '95.00']);
});

// --- T7.5: MarkAppointmentNoShowAction sets status to NoShow ---

test('no-show action sets appointment status to NoShow', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);

    Livewire::test(ListAppointments::class)
        ->callAction(TestAction::make('markNoShow')->table($appointment))
        ->assertHasNoErrors();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::NoShow);
});

// --- T7.6: MarkAppointmentNoShowAction is hidden for Pending appointments ---

test('no-show action is not visible for pending appointments', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

    Livewire::test(ListAppointments::class)
        ->assertActionHidden(TestAction::make('markNoShow')->table($appointment));
});

// --- T7.7: generateSlots sends warning notification when created = 0 ---

test('slot generation action sends warning notification when no slots are created', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    // schedule for a day_of_week that doesn't match today → 1-day window produces 0 slots
    $notTodayIso = \Carbon\Carbon::now()->addDay()->isoWeekday();
    $schedule = DoctorSchedule::factory()->create(['day_of_week' => $notTodayIso, 'is_active' => true]);

    Livewire::test(ListDoctorSchedules::class)
        ->callAction(
            TestAction::make('generateSlots')->table($schedule),
            data: ['days' => 1],
        )
        ->assertNotified(
            Notification::make()
                ->warning()
                ->title('No slots generated')
                ->body('Check that this schedule has active days configured in the selected range.')
        );
});

// --- T7.8: ConfirmAppointmentRequestAction populates final_price and discount_pct ---

test('confirm appointment request action populates final_price and discount_pct', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $patient = User::factory()->create(['role' => 'patient']);
    $patient->assignRole('patient');
    LoyaltyTier::factory()->create(['tier' => 'gold', 'discount_bonus_pct' => 20.00]);
    $patient->loyaltyAccount->update(['tier' => 'gold']);

    $service = Service::factory()->create(['price' => 200.00]);
    $doctor  = Doctor::factory()->create();
    $slot    = ScheduleSlot::factory()->create(['doctor_id' => $doctor->id, 'is_booked' => false]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'doctor_id'  => null,
        'slot_id'    => null,
        'status'     => AppointmentStatus::Pending,
    ]);

    Livewire::test(ListAppointments::class)
        ->callAction(
            TestAction::make('confirmRequest')->table($appointment),
            data: ['doctor_id' => $doctor->id, 'slot_id' => $slot->id],
        )
        ->assertHasNoErrors();

    $appointment->refresh();

    expect($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and((float) $appointment->discount_pct)->toBe(20.0)
        ->and((float) $appointment->final_price)->toBe(160.0);
});

