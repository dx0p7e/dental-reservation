<!DOCTYPE html>
<html>
<body>
<p>Dear {{ $appointment->patient->name }},</p>
<p>This is a friendly reminder that you have an appointment tomorrow.</p>
<p><strong>Service:</strong> {{ $appointment->service->name }}<br>
<strong>Doctor:</strong> {{ $appointment->doctor->user->name }}<br>
<strong>Date:</strong> {{ $appointment->slot->date->format('F j, Y') }}<br>
<strong>Time:</strong> {{ $appointment->slot->start_time }}</p>
<p>We look forward to seeing you!</p>
<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
