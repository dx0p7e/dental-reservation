## Context

Currently `Doctor` and `Service` are completely unlinked — `DoctorController::index()` eager-loads only `user`, and `DoctorResource` returns `{ id, name, specialization, bio }` with no service information. The `DoctorSlotsView.vue` fetches all services from `GET /api/v1/services` regardless of the selected doctor, meaning patients can select a service that doctor does not provide. `DoctorForm` (Filament) has no service assignment field.

Key files affected:
- `app/Models/Doctor.php` — add `services()` relationship
- `app/Models/Service.php` — add `doctors()` relationship
- `app/Http/Resources/Api/V1/DoctorResource.php` — add `services` array
- `app/Http/Controllers/Api/V1/DoctorController.php` — eager-load services; new `services()` action
- `app/Filament/Resources/Doctors/Schemas/DoctorForm.php` — add multi-select
- `resources/spa/views/DoctorsView.vue` — auth-aware service filter + badges + book gate
- `resources/spa/views/DoctorSlotsView.vue` — scoped service fetch

## Goals / Non-Goals

**Goals:**
- Introduce `doctor_service` pivot with cascade deletes
- Expose `GET /api/v1/doctors/{doctor}/services` for scoped service data
- Include `services` in `GET /api/v1/doctors` response
- Admin can assign services to a doctor from the edit form
- `DoctorsView` shows service badges; authenticated users get a client-side chip filter; guests see "Log in to book" instead of "View Slots"
- `DoctorSlotsView` service select only shows services the selected doctor provides

**Non-Goals:**
- Per-doctor service pricing
- Doctor-managed service assignment (admin-only)
- Slot generation changes
- Appointment creation logic changes
- Waiting lists or search-by-service-name

## Decisions

### 1. Pivot table: no timestamps
The `doctor_service` pivot is a simple assignment table. Timestamps would add no value and create noise in admin activity. Composite PK on `(doctor_id, service_id)` prevents duplicates at the DB level.

### 2. Filament: inline Select on DoctorForm, not a RelationManager
Adding `Select::make('services')->multiple()->relationship('services', 'name')` directly to `DoctorForm` keeps everything on one page and is the idiomatic Filament v4 approach for BelongsToMany on a form. A RelationManager would add a separate tab with a full table — overkill for a simple assignment.

### 3. New scoped endpoint: `GET /api/v1/doctors/{doctor}/services`
Rather than filtering client-side from the already-fetched doctor list (which `DoctorSlotsView` may not have in context), a dedicated endpoint keeps the slot view self-contained and avoids over-fetching all services. The endpoint is public (no auth) to match the existing public `/doctors/{doctor}/slots` route. Added to `DoctorController` as a `services()` method to avoid creating a new controller for two lines of logic.

**Alternative considered**: Filter from the `doctor.services` already in the booking store. Rejected — the store may be populated from a previous doctor selection, causing stale data.

### 4. `DoctorsView` client-side chip filter
The full doctor list (with embedded services) is already fetched on mount. Client-side filtering by `doctor.services` avoids an extra API call per chip click. The filter is hidden for guests since they cannot book — simplifying the guest view and avoiding any impression that filtering leads to booking.

### 5. Guest gate: hide "View Slots", show "Log in to book"
`DoctorSlotsView` requires an authenticated booking store flow. Sending guests there creates a dead end. Showing a "Log in to book" secondary button on each doctor card communicates the path forward without blocking browsing.

## Risks / Trade-offs

- **Stale pivot data after admin change**: If an admin removes a service from a doctor while a patient is mid-flow, the slot view may briefly show the removed service. Risk is low (admin-driven, low frequency). Mitigation: the scoped endpoint always reflects current DB state on fresh load.
- **Empty doctor list after migration**: All doctors start with zero services. The service chip filter "All" still shows all doctors; only a specific chip filter would show zero results. Not a bug — expected state until admin assigns services.
- **`DoctorSlotsView` empty state**: If a doctor has no services, the select is empty and the "Continue" button stays disabled. An explanatory message ("No services available — contact the clinic") prevents confusion.

## Migration Plan

1. Run `php artisan migrate` — creates `doctor_service` pivot (non-destructive, additive).
2. Admin assigns services to each doctor via the Filament edit form before going live.
3. No data seeder needed — service assignments are clinic-specific operational data.
4. Rollback: `php artisan migrate:rollback` drops only the `doctor_service` table; all other data is unaffected.
