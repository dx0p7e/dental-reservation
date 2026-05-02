@component('mail::message')
# {{ config('app.name') }}

## Vizitas sėkmingai užregistruotas!

Sveiki, {{ $appointment->patient->name }},

Jūsų vizitas sėkmingai užregistruotas. Toliau pateikiama vizito informacija:

**Paslauga:** {{ $appointment->service->name }}
**Gydytojas:** {{ $appointment->doctor->user->name }}
**Data:** {{ $appointment->slot->date->format('Y-m-d') }}
**Laikas:** {{ substr($appointment->slot->start_time, 0, 5) }}
@if($appointment->final_price !== null)
**Kaina:** €{{ number_format($appointment->final_price, 2) }}@if($appointment->discount_pct > 0) *(pritaikyta {{ $appointment->discount_pct }}% nuolaida)*@endif
@endif

@component('mail::button', ['url' => config('app.url')])
Peržiūrėti vizitą
@endcomponent

Jei turite klausimų, susisiekite su mumis.

{{ config('app.name') }} komanda
@endcomponent
