# Spec — Vue I18n with English and Lithuanian

## Requirements

1. The SPA MUST load in Lithuanian (`lt`) by default when no locale preference has been stored.
2. A language switcher in the navbar MUST allow users to toggle between Lithuanian and English.
3. The selected locale MUST persist across page reloads (stored in `localStorage` under the key `'locale'`).
4. All UI chrome strings (labels, headings, buttons, placeholder text, error messages) across ALL Vue files MUST be sourced from the i18n locale files via `t()` calls.
5. API-returned data (doctor names, service names, descriptions) MUST NOT be passed through `t()` — it is rendered as-is.
6. Any key present in `lt.json` but missing from `en.json` (or vice versa) MUST fall back gracefully — the fallback locale (`en`) is used for missing keys.
7. The Lithuanian locale file (`lt.json`) MUST be complete — every key in `en.json` MUST have a corresponding key in `lt.json`.

## Scenarios

### Scenario 1 — Default locale is Lithuanian

Given a user visits the app for the first time (no localStorage entry for `'locale'`)  
Then the UI renders in Lithuanian  
And the navbar shows "Pagrindinis", "Gydytojai", "Paslaugos", "Lojalumas" etc.  
And the language switcher shows "lt" as active (highlighted)  

### Scenario 2 — Switch to English

Given the UI is in Lithuanian  
When the user clicks "EN" in the language switcher  
Then all visible UI strings switch to English immediately  
And the "EN" button becomes the active (highlighted) state  
And `localStorage.getItem('locale')` returns `'en'`  

### Scenario 3 — Locale persists after reload

Given the user has selected English  
When they reload the page  
Then the UI loads in English (not Lithuanian)  
And the "EN" button is highlighted in the switcher  

### Scenario 4 — Switch back to Lithuanian

Given the UI is in English  
When the user clicks "LT"  
Then the UI reverts to Lithuanian  
And `localStorage.getItem('locale')` returns `'lt'`  

### Scenario 5 — All views have translated strings

Given the user navigates to each public and authenticated route  
Then no hardcoded English-only or Lithuanian-only strings appear in the UI  
And all buttons, labels, headings, and status messages are translated correctly for the active locale  

### Scenario 6 — Fallback for missing key

Given a key is present in `en.json` but missing from `lt.json`  
When the locale is `lt`  
Then the string renders in English (fallback)  
And no error or blank string is shown  

### Scenario 7 — Login page translated

Given the locale is Lithuanian  
When the user visits `/login`  
Then the heading shows "Prisijungti"  
And labels show "El. paštas" and "Slaptažodis"  
And the submit button shows "Prisijungti"  

### Scenario 8 — Registration consent string uses HTML interpolation

Given the locale is either Lithuanian or English  
When the user views the registration form  
Then the consent label renders as a sentence with a clickable "Privacy Policy" / "Privatumo politika" link  
And the link opens in a new tab  
