### Requirement: doctors table stores a nullable photo path
The system SHALL have a `photo_path` column of type `VARCHAR(255)` on the `doctors` table. The column SHALL be nullable with no default value. A doctor record with no uploaded photo SHALL have `photo_path = NULL`.

#### Scenario: Migration adds nullable column
- **WHEN** `php artisan migrate` is run
- **THEN** the `doctors` table has a `photo_path` column that accepts NULL

#### Scenario: Existing doctors are unaffected
- **WHEN** the migration runs on a database with existing doctor rows
- **THEN** all existing rows have `photo_path = NULL` and are otherwise unchanged

### Requirement: Doctor API response includes photo_url
The system SHALL include a `photo_url` field in the `DoctorResource` JSON response. When `photo_path` is non-null, `photo_url` SHALL be the absolute URL of the file on the `public` storage disk (`Storage::disk('public')->url($this->photo_path)`). When `photo_path` is null, `photo_url` SHALL be `null`.

This field SHALL be present on both `GET /api/v1/doctors` (collection) and `GET /api/v1/doctors/{doctor}` (single resource) responses.

#### Scenario: Doctor with photo returns absolute URL
- **WHEN** a doctor has `photo_path = 'doctors/abc123.jpg'`
- **THEN** `GET /api/v1/doctors/{id}` returns `photo_url: "http://localhost/storage/doctors/abc123.jpg"` (or the environment's base URL)

#### Scenario: Doctor without photo returns null
- **WHEN** a doctor has `photo_path = NULL`
- **THEN** `GET /api/v1/doctors/{id}` returns `photo_url: null`

### Requirement: Filament admin allows uploading a doctor photo
The `DoctorResource` Filament form SHALL include a `FileUpload` field bound to `photo_path` using the `public` disk and `doctors/` directory. The field SHALL accept image files only. When a new photo is uploaded, the previous file SHALL be deleted from storage. The field SHALL be optional (not required).

#### Scenario: Admin uploads a photo
- **WHEN** an admin uploads a JPEG to the doctor form and saves
- **THEN** the file is stored under `storage/app/public/doctors/` and `photo_path` is updated to the new relative path

#### Scenario: Admin removes a photo
- **WHEN** an admin clears the photo field and saves
- **THEN** `photo_path` is set to NULL and the previous file is removed from storage

### Requirement: DoctorsView renders a circular avatar above each doctor's name
The system SHALL display a circular avatar at the top of each doctor card in `DoctorsView.vue`. When `photo_url` is non-null, the avatar SHALL render an `<img>` with `src=photo_url`, sized `w-20 h-20`, with `rounded-full object-cover` styling. When `photo_url` is null, the avatar SHALL render a `<div>` with `w-20 h-20 rounded-full bg-clinic-teal text-white` displaying the doctor's initials (first letter of first name + first letter of last name, uppercase).

#### Scenario: Doctor with photo shows circular image avatar
- **WHEN** the `/doctors` page loads and a doctor has a non-null `photo_url`
- **THEN** the doctor card shows a circular `<img>` with the photo above the doctor's name

#### Scenario: Doctor without photo shows initials avatar
- **WHEN** the `/doctors` page loads and a doctor has `photo_url = null`
- **THEN** the doctor card shows a teal circular `<div>` with the doctor's initials above the doctor's name
