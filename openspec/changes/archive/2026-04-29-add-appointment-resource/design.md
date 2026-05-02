# Design: Add Appointment Resource

## Architecture

Three parts:
1. **`AppointmentResource`** — standard Filament resource with table, form, and pages
2. **`PatientsRelationManager`** — relation manager on `DoctorResource` showing patients via appointments
3. **Tests** — feature tests for both

No migrations required. All underlying models and relationships already exist.

---

## File Structure

```
app/Filament/Resources/Appointments/
├── AppointmentResource.php
├── Pages/
│   ├── ListAppointments.php
│   ├── CreateAppointment.php
│   └── EditAppointment.php
├── Schemas/
│   └── AppointmentForm.php
└── Tables/
    └── AppointmentsTable.php

app/Filament/Resources/Doctors/
└── RelationManagers/
    └── PatientsRelationManager.php
```

---

## AppointmentResource

**Navigation:**
- Group: `Appointments` (new group)
- Icon: `heroicon-o-calendar-days`
- Sort order: `1`
- Label: `Appointments` (default)

### Table columns (`AppointmentsTable`)

| Column | Type | Sortable | Searchable | Notes |
|---|---|---|---|---|
| `patient.name` | `TextColumn` | yes | yes | `patient` relation → `User`, use `name` attribute |
| `doctor.user.name` | `TextColumn` | yes | yes | doctor → user → name |
| `service.name` | `TextColumn` | yes | — | |
| `slot.date` | `TextColumn` | yes | — | Date format |
| `slot.start_time` | `TextColumn` | yes | — | Time format |
| `status` | `TextColumn` with `->badge()` | yes | — | Colors per spec |

Status badge colors:
```php
match ($state) {
    AppointmentStatus::Pending   => 'warning',
    AppointmentStatus::Confirmed => 'success',
    AppointmentStatus::Cancelled => 'danger',
    AppointmentStatus::Completed => 'gray',
    AppointmentStatus::NoShow    => 'gray',
}
```

**Table filters:** `SelectFilter` on `status` using `AppointmentStatus` cases.

**Table actions:** `EditAction` per row; `DeleteBulkAction` in toolbar.

### Form fields (`AppointmentForm`)

| Field | Type | Notes |
|---|---|---|
| `patient_id` | `Select` | `->options(User::role('patient')->get()->pluck('name', 'id'))`, searchable, required |
| `doctor_id` | `Select` | `->options(Doctor::with('user')->get()->pluck('user.name', 'id'))`, searchable, required |
| `service_id` | `Select` | `->options(Service::all()->pluck('name', 'id'))`, searchable, required |
| `slot_id` | `Select` | `->options(ScheduleSlot::with('doctor.user')->get()->map(...)->pluck('label', 'id'))`, searchable, required — label format: `"Dr. {name} — {date} {start_time}"` |
| `status` | `Select` | `->options(AppointmentStatus::class)`, required, default `pending` |
| `notes` | `Textarea` | nullable, `->rows(3)` |
| `doctor_notes` | `Textarea` | nullable, `->rows(3)`, label `Doctor notes` |

---

## PatientsRelationManager

**Class:** `App\Filament\Resources\Doctors\RelationManagers\PatientsRelationManager`

**Relationship:** `Doctor::appointments()` (hasMany), then through `Appointment::patient()`.

Since Filament relation managers work on a direct relationship, this manager uses the `appointments` relationship on `Doctor` but displays patient info. The preferred approach is a `hasManyThrough` or displaying appointment rows with patient columns.

**Implementation approach:** Use `appointments` as the `$relationship` and display patient columns from the related appointment data. The manager reads from `Doctor → appointments → patient`.

```php
protected static string $relationship = 'appointments';
protected static ?string $title = 'Patients';
```

**Table columns:**
| Column | Notes |
|---|---|
| `patient.name` | Patient's name |
| `patient.email` | Patient's email |
| `status` | Badge with same color map as AppointmentResource |
| `slot.date` | Appointment date |
| `slot.start_time` | Start time |

No create/edit actions in this manager — it is read-only (view only, with delete).

---

## Testing Strategy

**File:** `tests/Feature/Filament/AppointmentResourceTest.php`

Setup: `beforeEach` creates admin role, admin user, `actingAs`.

Tests:
1. `list page renders`
2. `create page renders`
3. `can create an appointment` — fill all fields, call `create`, assert record exists
4. `edit page renders` — create appointment via factory, test edit page loads
5. `can edit an appointment` — change status to `confirmed`, assert `fresh()` status
6. `patients relation manager renders` — create doctor with appointment, test relation manager loads

**AppointmentFactory** — needs to be created. Definition:
```php
[
    'patient_id' => User::factory(),
    'doctor_id'  => Doctor::factory(),
    'service_id' => Service::factory(),
    'slot_id'    => ScheduleSlot::factory(),
    'status'     => AppointmentStatus::Pending,
    'notes'      => null,
    'doctor_notes' => null,
]
```

---

## Key Constraints

- `Appointment::patient()` is `belongsTo(User::class, 'patient_id')` — not `belongsTo(Patient::class)`
- `slot_id` → `schedule_slots` table; `ScheduleSlot` has `date`, `start_time`, `end_time`, `is_booked`, `doctor_id`
- `AppointmentStatus` is a backed string enum — Filament's `Select::make('status')->options(AppointmentStatus::class)` works natively
- No `requested_datetime` column exists on `appointments` — datetime comes from the slot
