<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        // Last past weekday (used for completed/no_show appointments)
        $pastDay = Carbon::today()->subDay();
        while ($pastDay->isWeekend()) {
            $pastDay->subDay();
        }

        // Next weekday from today (used for future confirmed appointments)
        $futureDay = Carbon::today()->addDay();
        while ($futureDay->isWeekend()) {
            $futureDay->addDay();
        }

        // A week after futureDay (used for pending preferred_date)
        $pendingDay = $futureDay->copy()->addDays(7);
        while ($pendingDay->isWeekend()) {
            $pendingDay->addDay();
        }

        // Patients
        $jonas   = User::where('email', 'jonas.s@example.lt')->first();
        $egle    = User::where('email', 'egle.m@example.lt')->first();
        $ruta    = User::where('email', 'ruta.j@example.lt')->first();
        $andrius = User::where('email', 'andrius.b@example.lt')->first();

        // Doctors (looked up by their linked user email)
        $marta = Doctor::whereHas('user', fn ($q) => $q->where('email', 'marta.kazlauskiene@klinika.lt'))->first();
        $tomas = Doctor::whereHas('user', fn ($q) => $q->where('email', 'tomas.petrauskas@klinika.lt'))->first();
        $aiste = Doctor::whereHas('user', fn ($q) => $q->where('email', 'aiste.rimkute@klinika.lt'))->first();

        // Services
        $teethCleaning = Service::where('name', 'Dantų valymas (higiena)')->first();
        $dentalExam    = Service::where('name', 'Dantų apžiūra ir rentgenas')->first();
        $toothFilling  = Service::where('name', 'Dantų plombavimas (kompozitas)')->first();
        $extraction    = Service::where('name', 'Danties šalinimas')->first();
        $rootCanal     = Service::where('name', 'Šaknies kanalo gydymas')->first();
        $whitening     = Service::where('name', 'Dantų balinimas')->first();
        $crown         = Service::where('name', 'Dantų karūnėlė')->first();
        $childrenCheck = Service::where('name', 'Vaikų dantų apžiūra')->first();

        $past   = $pastDay->toDateString();
        $future = $futureDay->toDateString();

        // 2 past completed appointments per patient (all on last past weekday)
        $completedData = [
            ['patient' => $jonas,   'doctor' => $marta, 'service' => $teethCleaning, 'time' => '09:00:00'],
            ['patient' => $jonas,   'doctor' => $tomas, 'service' => $dentalExam,    'time' => '09:30:00'],
            ['patient' => $egle,    'doctor' => $marta, 'service' => $toothFilling,  'time' => '10:00:00'],
            ['patient' => $egle,    'doctor' => $aiste, 'service' => $whitening,     'time' => '10:30:00'],
            ['patient' => $ruta,    'doctor' => $tomas, 'service' => $rootCanal,     'time' => '11:00:00'],
            ['patient' => $ruta,    'doctor' => $aiste, 'service' => $crown,         'time' => '11:30:00'],
            ['patient' => $andrius, 'doctor' => $marta, 'service' => $extraction,    'time' => '13:00:00'],
            ['patient' => $andrius, 'doctor' => $tomas, 'service' => $toothFilling,  'time' => '13:30:00'],
        ];

        foreach ($completedData as $data) {
            $slot = ScheduleSlot::where('doctor_id', $data['doctor']->id)
                ->where('date', $past)
                ->where('start_time', $data['time'])
                ->first();

            if (! $slot) {
                continue;
            }

            Appointment::firstOrCreate(
                ['patient_id' => $data['patient']->id, 'slot_id' => $slot->id],
                [
                    'doctor_id'   => $data['doctor']->id,
                    'service_id'  => $data['service']->id,
                    'status'      => AppointmentStatus::Completed,
                    'discount_pct' => 0,
                    'final_price'  => $data['service']->price,
                ]
            );

            $slot->update(['is_booked' => true]);
        }

        // 1 past no_show: Jonas + Aistė, Teeth Cleaning
        $noShowSlot = ScheduleSlot::where('doctor_id', $aiste->id)
            ->where('date', $past)
            ->where('start_time', '14:00:00')
            ->first();

        if ($noShowSlot) {
            Appointment::firstOrCreate(
                ['patient_id' => $jonas->id, 'slot_id' => $noShowSlot->id],
                [
                    'doctor_id'  => $aiste->id,
                    'service_id' => $teethCleaning->id,
                    'status'     => AppointmentStatus::NoShow,
                ]
            );
            $noShowSlot->update(['is_booked' => true]);
        }

        // 1 future confirmed appointment per patient
        $futureData = [
            ['patient' => $jonas,   'doctor' => $marta, 'service' => $dentalExam,    'time' => '09:00:00'],
            ['patient' => $egle,    'doctor' => $tomas, 'service' => $rootCanal,     'time' => '09:30:00'],
            ['patient' => $ruta,    'doctor' => $aiste, 'service' => $whitening,     'time' => '10:00:00'],
            ['patient' => $andrius, 'doctor' => $marta, 'service' => $childrenCheck, 'time' => '10:30:00'],
        ];

        foreach ($futureData as $data) {
            $slot = ScheduleSlot::where('doctor_id', $data['doctor']->id)
                ->where('date', $future)
                ->where('start_time', $data['time'])
                ->first();

            if (! $slot) {
                continue;
            }

            Appointment::firstOrCreate(
                ['patient_id' => $data['patient']->id, 'slot_id' => $slot->id],
                [
                    'doctor_id'  => $data['doctor']->id,
                    'service_id' => $data['service']->id,
                    'status'     => AppointmentStatus::Confirmed,
                ]
            );

            $slot->update(['is_booked' => true]);
        }

        // 2 pending request appointments (no slot, preferred_date only)
        Appointment::firstOrCreate(
            [
                'patient_id'     => $egle->id,
                'doctor_id'      => $tomas->id,
                'preferred_date' => $pendingDay->toDateString(),
            ],
            [
                'service_id'     => $dentalExam->id,
                'status'         => AppointmentStatus::Pending,
                'preferred_date' => $pendingDay->toDateString(),
            ]
        );

        Appointment::firstOrCreate(
            [
                'patient_id'     => $andrius->id,
                'doctor_id'      => $aiste->id,
                'preferred_date' => $pendingDay->toDateString(),
            ],
            [
                'service_id'     => $crown->id,
                'status'         => AppointmentStatus::Pending,
                'preferred_date' => $pendingDay->toDateString(),
            ]
        );
    }
}
