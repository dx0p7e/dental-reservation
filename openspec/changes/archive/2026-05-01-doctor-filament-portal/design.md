## Context

The application has three user roles: `patient`, `doctor`, `admin`. Patients use a Vue Router SPA (authenticated via Sanctum API tokens). Admins use a Filament panel at `/admin`. Doctors had no working portal — the previous attempt built an Inertia SPA at `/doctor/*` with custom API routes, but the dual-auth architecture (Sanctum tokens for the SPA + Fortify web sessions for Inertia) proved unreliable. Filament already manages admin auth independently of Fortify, and supports multiple panels natively.

The existing codebase already has:
- `DoctorObserver` + `UserObserver` that assign the Spatie `doctor` role when a Doctor profile is created
- `SyncDoctorRoles` artisan command for syncing existing users
- `AppointmentResource` and `DoctorScheduleResource` in the admin panel (full-access, admin-scoped)
- A `Doctor` model with `hasMany` schedules and appointments

## Goals / Non-Goals

**Goals:**
- Doctors can log in at `/doctor` using Filament's built-in login page (no Fortify, no SPA)
- Doctors see only their own appointments and schedules — completely isolated from admin data
- Mark Complete / Mark No-Show actions on appointments from within the panel
- Doctor can add `doctor_notes` to an appointment on the edit page
- Schedule management: create, edit, delete own schedule rows (day, start, end, slot duration, active)
- Admins and doctors see entirely separate panels with no resource overlap
- Patient SPA and admin panel are completely unaffected

**Non-Goals:**
- Mobile-optimised doctor UI (desktop Filament panel is sufficient)
- Real-time notifications or live appointment updates
- Doctor availability calendar view (table list is sufficient for now)
- Modifying patient-facing booking flow

## Decisions

### Decision 1 — Separate Filament panel, not scoped resources in the admin panel

**Chosen:** `DoctorPanelProvider` at `/doctor` with its own resource discovery pointing at `app/Filament/Doctor/Resources/`.

**Rejected:** Adding doctor role checks to the existing admin panel resources via `canViewAny()` / `getEloquentQuery()` scoping.

**Rationale:** Scoping existing resources leaks admin UI concepts (patient selectors, doctor pickers, bulk actions) into the doctor view. A separate panel has a separate navigation, separate resource classes, and the admin panel is completely unmodified. Filament v4 multi-panel is a first-class pattern.

### Decision 2 — New resource classes, not extending admin ones

**Chosen:** `app/Filament/Doctor/Resources/DoctorAppointmentResource` and `DoctorScheduleResource` — fresh classes scoped to the authenticated doctor.

**Rejected:** Extending or reusing admin `AppointmentResource` / `DoctorScheduleResource`.

**Rationale:** The admin resources expose fields (patient selector, doctor picker, all statuses, delete bulk actions) that doctors should never touch. Fresh classes with minimal fields are safer and simpler.

### Decision 3 — `canAccessPanel()` branching by panel ID

**Chosen:** 
```php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin'  => $this->hasRole('admin'),
        'doctor' => $this->hasRole('doctor'),
        default  => false,
    };
}
```

**Rejected:** Separate `canAccessAdminPanel()` / `canAccessDoctorPanel()` methods.

**Rationale:** Filament's `FilamentUser` interface uses a single `canAccessPanel(Panel $panel)` method. Branching on `$panel->getId()` is the idiomatic Filament v4 pattern.

### Decision 4 — Force `doctor_id` in `DoctorScheduleResource` via `mutateFormDataBeforeCreate`

**Chosen:** Override `mutateFormDataBeforeCreate` and `mutateFormDataBeforeSave` on the Create/Edit pages to inject `doctor_id = auth()->user()->doctor->id`. The form does not show a doctor selector.

**Rejected:** Hiding the doctor selector and pre-filling it.

**Rationale:** Hiding a field still allows it to be manipulated. Forcing the value in the mutate hooks is the Filament-idiomatic, secure approach.

### Decision 5 — Appointment status actions via Filament table `Action` / `Action` on edit page

**Chosen:** `Action::make('markComplete')` and `Action::make('markNoShow')` as table row actions (and on the edit page header) that update the appointment status directly.

**Rationale:** Filament actions handle confirmation modals, optimistic UI feedback, and authorization — no need for a custom PATCH endpoint. The action checks that the appointment is in `confirmed` status before enabling.

## Risks / Trade-offs

- **Doctor has no doctor profile yet** → `auth()->user()->doctor` is null → `doctor_id` injection would fail. Mitigation: add a null-check and show a user-friendly error message; the admin must create a Doctor profile before the user can use the panel.
- **Deleting the old test files** removes coverage for code that's also being deleted — acceptable since the code is being removed.
- **`app.blade.php` reverted** — if any future Inertia usage is added for other roles, vite.config.ts will need updating again. Non-issue for current scope.

## Migration Plan

1. Delete all files from the Inertia/API doctor portal implementation
2. Revert modified files (routes, providers, vite config, blade views, LoginView)
3. Create `DoctorPanelProvider` and register it in `bootstrap/providers.php`
4. Update `User::canAccessPanel()`
5. Create `app/Filament/Doctor/Resources/` with the two resource classes
6. Run pint, run tests
7. Manually verify: log in as a doctor at `/doctor`, confirm appointments and schedule are visible and scoped

**Rollback:** Since there is no git, rollback means restoring from the archived `2026-05-01-doctor-portal` change. The clean state before the doctor portal was the baseline with no doctor-facing portal at all.
