<!DOCTYPE html>
<html>
<body>
<p>Dear {{ $appointment->patient->name }},</p>
<p>Your appointment has been confirmed. Here are the details:</p>
<p><strong>Service:</strong> {{ $appointment->service->name }}<br>
<strong>Doctor:</strong> {{ $appointment->doctor->user->name }}<br>
<strong>Date:</strong> {{ $appointment->slot->date->format('F j, Y') }}<br>
<strong>Time:</strong> {{ $appointment->slot->start_time }}</p>
<p>If you need to make any changes, please contact us as soon as possible.</p>
<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
