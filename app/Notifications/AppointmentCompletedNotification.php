<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class AppointmentCompletedNotification extends Notification implements ShouldQueue
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
            ->subject('Vizitas baigtas — '.config('app.name'))
            ->markdown('notifications.appointments.completed', [
                'appointment' => $this->appointment,
            ]);
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $slot = $this->appointment->slot;
        assert($slot !== null);
        $date = $slot->date->format('Y-m-d');
        $service = $this->appointment->service->name;
        $doctor = $this->appointment->doctor?->user->name ?? '';

        return (new VonageMessage)
            ->content("Ačiū už apsilankymą! {$service} {$date} pas {$doctor}. Laukiame vėl.");
    }
}
