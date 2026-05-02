@component('mail::message')
# {{ config('app.name') }}

## Ačiū už apsilankymą!

Sveiki, {{ $appointment->patient->name }},

Ačiū už apsilankymą. Tikimės matyti jus vėl!

**Paslauga:** {{ $appointment->service->name }}
**Gydytojas:** {{ $appointment->doctor->user->name }}
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}

@component('mail::button', ['url' => config('app.url')])
Užsakyti naują vizitą
@endcomponent

{{ config('app.name') }} komanda
@endcomponent
