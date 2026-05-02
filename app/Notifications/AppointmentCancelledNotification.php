<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment = $appointment->loadMissing('patient', 'service', 'slot');
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
            ->subject('Vizitas atšauktas — '.config('app.name'))
            ->markdown('notifications.appointments.cancelled', [
                'appointment' => $this->appointment,
            ]);
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $slot = $this->appointment->slot;
        $date = $slot?->date?->format('Y-m-d') ?? $this->appointment->preferred_date?->format('Y-m-d') ?? '';
        $service = $this->appointment->service->name;

        return (new VonageMessage)
            ->content("Jūsų vizitas ({$service} {$date}) atšauktas. Susisiekite dėl perkėlimo.");
    }
}
