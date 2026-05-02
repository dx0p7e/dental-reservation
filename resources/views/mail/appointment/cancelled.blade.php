<!DOCTYPE html>
<html>
<body>
<p>Dear {{ $appointment->patient->name }},</p>
<p>Your appointment has been cancelled. Here are the details of the cancelled appointment:</p>
<p><strong>Service:</strong> {{ $appointment->service->name }}<br>
<strong>Doctor:</strong> {{ $appointment->doctor->user->name }}<br>
<strong>Date:</strong> {{ $appointment->slot->date->format('F j, Y') }}<br>
<strong>Time:</strong> {{ $appointment->slot->start_time }}</p>
<p>Please contact us to rebook at your earliest convenience.</p>
<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
