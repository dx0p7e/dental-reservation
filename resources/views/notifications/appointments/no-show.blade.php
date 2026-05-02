@component('mail::message')
# {{ config('app.name') }}

## Neatvykimas į vizitą

Sveiki, {{ $appointment->patient->name }},

Pažymėta, kad neatvykote į vizitą. Jei tai klaida arba norėtumėte perkėlimo, susisiekite su mumis.

**Paslauga:** {{ $appointment->service->name }}
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}

@component('mail::button', ['url' => config('app.url')])
Susisiekti su klinika
@endcomponent

{{ config('app.name') }} komanda
@endcomponent
