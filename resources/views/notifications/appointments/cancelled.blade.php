@component('mail::message')
# {{ config('app.name') }}

## Vizitas atšauktas

Sveiki, {{ $appointment->patient->name }},

Jūsų vizitas atšauktas. Susisiekite su mumis dėl naujo laiko.

**Paslauga:** {{ $appointment->service->name }}
@if($appointment->slot)
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}
@elseif($appointment->preferred_date)
**Pageidaujama data:** {{ $appointment->preferred_date->format('Y-m-d') }}
@endif

@component('mail::button', ['url' => config('app.url')])
Užsakyti naują vizitą
@endcomponent

{{ config('app.name') }} komanda
@endcomponent
