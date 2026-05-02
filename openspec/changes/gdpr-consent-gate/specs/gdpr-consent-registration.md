# Spec — GDPR Consent at Registration

## Requirements

1. The registration API endpoint (`POST /api/v1/auth/register`) MUST reject requests that do not include `gdpr_consent: true` with a 422 response and a validation error on the `gdpr_consent` field.
2. When registration succeeds, the resulting `User` record MUST have `gdpr_consent_at` set to the current timestamp.
3. The registration form in the SPA MUST include a checkbox for consent with a link to the privacy policy; the form MUST NOT be submittable while the checkbox is unchecked.
4. The `/privacy` route MUST be publicly accessible and render a privacy policy stub page.
5. All seeded users in `AdminUserSeeder` MUST have `gdpr_consent_at` set (not null).

## Scenarios

### Scenario 1 — Registration fails without consent

Given the registration form has all required fields filled  
And the consent checkbox is NOT checked  
When the form is submitted  
Then the API returns 422  
And the response contains `errors.gdpr_consent`  
And no user record is created  

### Scenario 2 — Registration succeeds with consent

Given the registration form has all required fields filled  
And the consent checkbox IS checked  
When the form is submitted  
Then the API returns 201  
And the response contains a `token`  
And the created `User` has `gdpr_consent_at` not null  

### Scenario 3 — Registration with `gdpr_consent: false` fails

Given a direct API POST to `/api/v1/auth/register` with all valid fields and `gdpr_consent: false`  
Then the API returns 422  
And the response contains `errors.gdpr_consent`  

### Scenario 4 — Consent checkbox shows validation error

Given registration is submitted without checking the consent checkbox  
When the API returns 422 with `errors.gdpr_consent`  
Then the error message is displayed below the checkbox in the form  

### Scenario 5 — Privacy policy page is publicly accessible

Given an unauthenticated visitor navigates to `/privacy`  
Then the page renders without redirect  
And contains a heading or content about the privacy policy  

### Scenario 6 — Consent link opens privacy policy

Given the registration form is rendered  
When the user clicks the "Privacy Policy" link in the consent label  
Then the `/privacy` page opens in a new browser tab  
