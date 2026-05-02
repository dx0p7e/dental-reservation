# 🦷 Šypsenos Klinika — Dental Reservation System

A full-stack dental clinic management and appointment booking system built as a thesis project. Patients can browse services, book appointments, manage their profile, and track loyalty rewards. Clinic staff manage everything through a powerful admin panel.

---

## ✨ Features

### Patient-facing SPA
- Browse clinic services and doctors
- Multi-step appointment booking flow
- Personal dashboard with upcoming and past appointments
- Appointment rescheduling and cancellation
- Loyalty programme with tiers (Standard, Silver, Gold) and rewards
- Profile management
- Lithuanian or English UI

### Admin panel (`/admin`)
- Appointment management (confirm, complete, no-show)
- Doctor and schedule management
- User management with role assignment
- Services and loyalty tier/rule configuration
- Audit log for all system activity
- Dashboard with revenue estimates and appointment statistics

### Doctor panel (`/doctor`)
- Personal appointment list with notes
- Own schedule management

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3, Inertia.js v3, TypeScript |
| Admin UI | Filament v4 |
| Styling | Tailwind CSS v4 |
| Auth | Laravel Fortify |
| Database | MySQL |
| Queue / Cache / Sessions | Database driver |
| Roles & Permissions | Spatie Laravel Permission |
| Activity Logging | Spatie Laravel Activitylog |
| Type-safe routes | Laravel Wayfinder |

---

## 🚀 Getting started on macOS

### Prerequisites

Install the required system tools via [Homebrew](https://brew.sh):

```bash
# Install Homebrew if not already installed
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP 8.4, MySQL, Node.js, and Composer
brew install php@8.4 mysql node composer

# Make PHP 8.4 the active version
echo 'export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

Verify:
```bash
php -v        # 8.4.x
mysql --version
node -v
composer -V
```

---

### 1. Start MySQL and create the database

```bash
brew services start mysql

mysql -u root -p
```

Inside the MySQL prompt:
```sql
CREATE DATABASE dental_reservation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dental_user'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON dental_reservation.* TO 'dental_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 2. Clone and install dependencies

```bash
git clone <your-repo-url> dental-reservation
cd dental-reservation

composer install
npm install
```

---

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dental_reservation
DB_USERNAME=dental_user
DB_PASSWORD=secret
```

---

### 4. Run migrations and seed data

```bash
php artisan migrate --seed
```

This creates all tables and seeds the database with sample doctors, services, users, and appointments.

---

### 5. Build frontend assets

```bash
npm run build
```

---

### 6. Start the development server

```bash
composer run dev
```

This starts everything in parallel:
- PHP dev server at **http://localhost:8000**
- Vite (hot reload)
- Queue worker
- Log tail

---

### Default accounts

After seeding, you can log in with:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `123` |
| Doctor | `marta.kazlauskiene@klinika.lt` | `password` |
| Doctor | `tomas.petrauskas@klinika.lt` | `password` |
| Doctor | `aiste.rimkute@klinika.lt` | `password` |

Admin panel: **http://localhost:8000/admin**
Doctor panel: **http://localhost:8000/doctor**

---

## 🧪 Running tests

```bash
php artisan test --compact
```
