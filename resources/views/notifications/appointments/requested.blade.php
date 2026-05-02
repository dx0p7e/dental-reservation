@component('mail::message')
# {{ config('app.name') }}

## Vizito prašymas gautas

Sveiki, {{ $appointment->patient->name }},

Jūsų vizito prašymas gautas. Netrukus patvirtinsime laiką ir susisieksime su jumis.

**Paslauga:** {{ $appointment->service->name }}
**Pageidaujama data:** {{ $appointment->preferred_date->format('Y-m-d') }}

@component('mail::button', ['url' => config('app.url')])
Mano vizitai
@endcomponent

Jei turite klausimų, susisiekite su mumis.

{{ config('app.name') }} komanda
@endcomponent
