## 1. Migration

- [x] 1.1 Run `php artisan make:migration add_photo_path_to_doctors_table --table=doctors` — add nullable `photo_path VARCHAR(255) NULL AFTER bio`

## 2. Model

- [x] 2.1 Add `photo_path` to `Doctor::$fillable`

## 3. API Resource

- [x] 3.1 Add `'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null` to `DoctorResource::toArray()`; add `use Illuminate\Support\Facades\Storage;` import

## 4. Filament Admin

- [x] 4.1 Add `FileUpload::make('photo_path')->disk('public')->directory('doctors')->image()->nullable()` to `DoctorForm`; add necessary `use` imports

## 5. Factory

- [x] 5.1 Add `'photo_path' => null` to `DoctorFactory::definition()` (explicit null default; tests that need a photo can override)

## 6. TypeScript Types

- [x] 6.1 Add `photo_url: string | null` to the `Doctor` interface in `resources/spa/types/index.ts`
- [x] 6.2 Add `photo_url: string | null` to the `doctor` shape inside `BookingDraft` interface

## 7. Shared Avatar Component

- [x] 7.1 Create `resources/spa/components/DoctorAvatar.vue` — accepts props `photoUrl: string | null` and `name: string` and `size: string` (default `'w-16 h-16'`); renders `<img>` when `photoUrl` is non-null, else a `<div>` with initials computed from `name` (first letter of each word, max 2); styling: `rounded-full object-cover` for image, `rounded-full bg-clinic-teal text-white flex items-center justify-center font-semibold` for initials

## 8. DoctorsView

- [x] 8.1 Import `DoctorAvatar` in `DoctorsView.vue`
- [x] 8.2 Add `<DoctorAvatar :photo-url="doctor.photo_url" :name="doctor.name" size="w-20 h-20" />` above `<h3>` in the doctor card, centred with `flex flex-col items-center`

## 9. LandingView

- [x] 9.1 Import `DoctorAvatar` in `LandingView.vue`
- [x] 9.2 Add `<DoctorAvatar :photo-url="doctor.photo_url" :name="doctor.name" size="w-16 h-16" />` above the doctor name in the landing page doctor card, centred

## 10. Tests

- [x] 10.1 Update `DoctorResource` API test (or create one if absent) — assert `photo_url` is `null` when `photo_path` is null; assert `photo_url` is a string URL when `photo_path` is set
- [x] 10.2 Run `php artisan test --compact` and confirm all tests pass

## 11. Code Quality

- [x] 11.1 Run `vendor/bin/pint --dirty --format agent` and fix any style issues
