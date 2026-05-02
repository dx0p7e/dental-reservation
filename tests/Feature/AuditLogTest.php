<?php

use App\Filament\Resources\ActivityLog\Pages\ListActivityLog;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
});

test('creating a loyalty transaction writes an activity log entry', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');

    // Patient observer auto-creates a LoyaltyAccount; use it
    $account = $patient->loyaltyAccount ?? LoyaltyAccount::factory()->create(['patient_id' => $patient->id]);

    $transaction = LoyaltyTransaction::factory()->create([
        'loyalty_account_id' => $account->id,
        'points_delta' => 50,
        'type' => 'earn',
    ]);

    $entry = Activity::query()
        ->where('subject_type', LoyaltyTransaction::class)
        ->where('subject_id', $transaction->id)
        ->where('event', 'created')
        ->first();

    expect($entry)->not->toBeNull();
    expect($entry->attribute_changes['attributes']['points_delta'])->toBe(50);
    expect($entry->attribute_changes['attributes']['type'])->toBe('earn');
});

test('updating loyalty account balance writes an activity log entry with old and new values', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');

    // Patient observer auto-creates account with points_balance=0; set it to 100 first
    $account = $patient->loyaltyAccount;
    $account->update(['points_balance' => 100]);

    // Discard existing updated log entries before the real update
    Activity::query()->where('subject_type', LoyaltyAccount::class)->delete();

    $account->update(['points_balance' => 200]);

    $entry = Activity::query()
        ->where('subject_type', LoyaltyAccount::class)
        ->where('subject_id', $account->id)
        ->where('event', 'updated')
        ->first();

    expect($entry)->not->toBeNull();
    expect($entry->attribute_changes['old']['points_balance'])->toBe(100);
    expect($entry->attribute_changes['attributes']['points_balance'])->toBe(200);
});

test('updating loyalty account tier writes an activity log entry with old and new tier', function (): void {
    $patient = User::factory()->create();
    $patient->assignRole('patient');

    // Observer creates the account with tier='standard'; update directly
    $account = $patient->loyaltyAccount;

    $account->update(['tier' => 'gold']);

    $entry = Activity::query()
        ->where('subject_type', LoyaltyAccount::class)
        ->where('subject_id', $account->id)
        ->where('event', 'updated')
        ->first();

    expect($entry)->not->toBeNull();
    expect($entry->attribute_changes['old']['tier'])->toBe('standard');
    expect($entry->attribute_changes['attributes']['tier'])->toBe('gold');
});

test('activity log list page renders for admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(ListActivityLog::class)
        ->assertSuccessful();
});
