## Why

Doctors and services are currently unlinked — any doctor can be booked for any service regardless of whether they actually provide it, which is semantically incorrect and misleading to patients. The booking flow also exposes a "View Slots" path to unauthenticated users who cannot complete a booking, creating a dead-end UX. This change introduces the `doctor_service` pivot, wires it into the admin panel, scopes service selection to the specific doctor, and gates slot browsing behind authentication.

## What Changes

- **New migration**: `doctor_service` pivot table with `doctor_id` and `service_id` foreign keys and a composite primary key (no timestamps).
- **New model relationships**: `Doctor::services()` (belongsToMany) and `Service::doctors()` (belongsToMany).
- **Admin panel**: Add `Select::make('services')->multiple()->relationship('services', 'name')` to `DoctorResource` edit form so admins can assign services per doctor.
- **API — `GET /api/v1/doctors`**: Eager-load `services` and include `[{ id, name, price, loyalty_discount_pct }]` per doctor in `DoctorResource`.
- **API — `GET /api/v1/doctors/{doctor}/services`**: New scoped endpoint returning only the services a specific doctor provides; used by the slot selection view.
- **`DoctorsView.vue`**: When authenticated — add horizontal service-chip filter row (client-side, from `doctor.services`), add service badges to each doctor card; "View Slots" remains. When guest — show doctor info and service badges; hide "View Slots", show "Log in to book" → `/login`.
- **`DoctorSlotsView.vue`**: Service `<select>` fetches from `GET /api/v1/doctors/{doctor}/services` instead of all services. Empty state if doctor has no services assigned.

## Capabilities

### New Capabilities

- `doctor-service-assignment`: Admin assignment of services to doctors via the pivot table and Filament form field.
- `doctor-scoped-services-api`: Scoped API endpoint (`GET /api/v1/doctors/{doctor}/services`) returning only services a specific doctor provides.

### Modified Capabilities

- `spa-booking-ui`: Service select in `DoctorSlotsView` now scoped to the selected doctor's services; empty state added for doctors with no services.
- `doctor-appointment-management`: `GET /api/v1/doctors` now includes a `services` array per doctor; API resource updated.
- `admin-dashboard`: `DoctorResource` edit form gains a multi-select service assignment field.

## Impact

- **Database**: New `doctor_service` pivot migration. Existing doctors will have no services assigned after migration — admin must assign before booking flow is usable.
- **Backend**: `Doctor`, `Service` models gain relationships; `DoctorController` eager-loads services; new `DoctorServiceController` (or method on `DoctorController`); `DoctorResource` API resource updated.
- **Frontend**: `DoctorsView.vue` (auth-aware filter + badges + gate), `DoctorSlotsView.vue` (scoped service fetch).
- **Admin panel**: `DoctorResource` edit form updated.
- **No impact**: Slot generation, appointment creation/cancellation logic, loyalty, notifications, patient profile, doctor panel.
