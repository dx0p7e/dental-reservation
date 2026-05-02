# Design: Create Schedule Slots Resource

## Architecture

This change has two parts:
1. **Database migration** — alter `doctor_schedules` to match the target schema
2. **Filament resource** — `DoctorScheduleResource` backed by the `DoctorSchedule` model

### Model: `DoctorSchedule`

Updated `$fillable` and `$casts` after migration:

```php
protected $fillable = [
    'doctor_id',
    'day_of_week',      // 1=Mon … 7=Sun (ISO)
    'start_time',
    'end_time',
    'slot_duration_minutes',
    'is_active',
];

protected $casts = [
    'day_of_week'           => 'integer',
    'slot_duration_minutes' => 'integer',
    'is_active'             => 'boolean',
];
```

---

## Migration

**File:** `database/migrations/<timestamp>_update_doctor_schedules_add_slot_fields.php`

Changes to `doctor_schedules`:
- Add `slot_duration_minutes` — `unsignedSmallInteger`, not null
- Add `is_active` — `boolean`, default `true`
- Drop `is_break`

```php
Schema::table('doctor_schedules', function (Blueprint $table) {
    $table->unsignedSmallInteger('slot_duration_minutes')->after('end_time');
    $table->boolean('is_active')->default(true)->after('slot_duration_minutes');
    $table->dropColumn('is_break');
});
```

---

## Filament Resource

**Class:** `App\Filament\Resources\DoctorSchedules\DoctorScheduleResource`

**Navigation:**
- Group: `Staff`
- Icon: `heroicon-o-clock`
- Sort order: `2` (after DoctorResource)
- Label: `Schedule Slots`

### Table columns

| Column | Type | Sortable | Searchable | Notes |
|---|---|---|---|---|
| `doctor.user.name` | `TextColumn` | yes | yes | `doctors` has no `name` column; name lives on `users` via the doctor→user relation |
| `day_of_week` | `TextColumn` with `->badge()` | yes | — | Colors: Mon–Fri=primary, Sat=warning, Sun=danger |
| `start_time` | `TextColumn` | yes | — | Time format |
| `end_time` | `TextColumn` | yes | — | Time format |
| `slot_duration_minutes` | `TextColumn` | yes | — | Suffix `min` |
| `is_active` | `IconColumn` (boolean) | yes | — | |

Day-of-week badge label mapping (via `->formatStateUsing()`):
```
1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
```

**Table filters:** `SelectFilter` on `doctor_id` (relationship name + label), `SelectFilter` on `is_active`.

### Form fields

| Field | Type | Validation |
|---|---|---|
| `doctor_id` | `Select` with manual `->options()` loading `Doctor::with('user')->get()->pluck('user.name', 'id')` (or `->getOptionLabelFromRecordUsing`) | required |
| `day_of_week` | `Select` (options 1–7 with day names) | required |
| `start_time` | `TimePicker` | required |
| `end_time` | `TimePicker` | required, after start_time |
| `slot_duration_minutes` | `TextInput` (numeric, suffix `min`) | required, integer, min:1 |
| `is_active` | `Toggle` | boolean, default true |

---

## Overlap Validation

Overlap detection runs in `CreateDoctorSchedule` and `EditDoctorSchedule` page classes by overriding `beforeCreate()` and `beforeSave()`.

Logic:
```
SELECT EXISTS (
  SELECT 1 FROM doctor_schedules
  WHERE doctor_id = :doctor_id
    AND day_of_week = :day_of_week
    AND id != :current_id          -- excluded on edit
    AND start_time < :end_time
    AND end_time > :start_time
)
```

If an overlap exists:
```php
Notification::make()
    ->danger()
    ->title('Time overlap')
    ->body('This doctor already has a schedule slot that overlaps this time range.')
    ->send();

$this->halt();
```

The `halt()` call stops the create/save action without throwing an exception.

---

## File Structure

```
app/Filament/Resources/DoctorSchedules/
├── DoctorScheduleResource.php
├── Pages/
│   ├── ListDoctorSchedules.php
│   ├── CreateDoctorSchedule.php    ← overrides beforeCreate()
│   └── EditDoctorSchedule.php      ← overrides beforeSave()
├── Schemas/
│   └── DoctorScheduleForm.php
└── Tables/
    └── DoctorSchedulesTable.php
```

---

## Testing Strategy

Tests live in `tests/Feature/Filament/DoctorScheduleResourceTest.php`.

Standard five tests (list renders, create renders, can create, edit renders, can edit) plus:
- **Overlap blocked on create** — attempt to create a slot that overlaps an existing one, assert no record was created
- **No overlap allowed on edit** — save an edit that would overlap another slot, assert halted

Factory: `DoctorScheduleFactory` with all required fields.

## Key Constraints

- `DoctorSchedule` model must update `$fillable` and `$casts` to match new schema
- The `day_of_week` display must use ISO labels (1=Mon … 7=Sun) consistently in both table and form
- `slot_duration_minutes` must be positive integer
- `is_active` toggle defaults to `true` in the form
- Overlap check must exclude the record being edited (by `id`)
- Navigation sort order `2` requires `$navigationSort = 2` on the resource
