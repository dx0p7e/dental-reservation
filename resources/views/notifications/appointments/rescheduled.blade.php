@component('mail::message')
# {{ config('app.name') }}

## Jūsų vizitas sėkmingai perkeltas!

Sveiki, {{ $appointment->patient->name }},

Jūsų vizitas perkeltas į naują laiką. Toliau pateikiama atnaujinta vizito informacija:

**Paslauga:** {{ $appointment->service->name }}
**Gydytojas:** {{ $appointment->doctor->user->name }}
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}

@component('mail::button', ['url' => config('app.url')])
Peržiūrėti vizitą
@endcomponent

Jei turite klausimų, susisiekite su mumis.

{{ config('app.name') }} komanda
@endcomponent
