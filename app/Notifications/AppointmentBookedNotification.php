<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment = $appointment->loadMissing('patient', 'doctor.user', 'service', 'slot');
    }

    public function via(object $notifiable): array
    {
        return match ($notifiable->notification_channel) {
            'sms' => ['vonage'],
            'both' => ['mail', 'vonage'],
            default => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vizitas užregistruotas — '.config('app.name'))
            ->markdown('notifications.appointments.booked', [
                'appointment' => $this->appointment,
            ]);
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $slot = $this->appointment->slot;
        assert($slot !== null);
        $date = $slot->date->format('Y-m-d');
        $time = substr($slot->start_time, 0, 5);
        $doctor = $this->appointment->doctor?->user->name ?? '';
        $service = $this->appointment->service->name;

        return (new VonageMessage)
            ->content("Vizitas užregistruotas: {$service} {$date} {$time} pas {$doctor}.");
    }
}
