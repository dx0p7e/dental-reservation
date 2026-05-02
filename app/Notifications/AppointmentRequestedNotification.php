<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class AppointmentRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment = $appointment->loadMissing('patient', 'service');
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
            ->subject('Vizito prašymas gautas — '.config('app.name'))
            ->markdown('notifications.appointments.requested', [
                'appointment' => $this->appointment,
            ]);
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $date = $this->appointment->preferred_date->format('Y-m-d');
        $service = $this->appointment->service->name;

        return (new VonageMessage)
            ->content("Jūsų prašymas gautas: {$service} {$date}. Patvirtinsime netrukus.");
    }
}
