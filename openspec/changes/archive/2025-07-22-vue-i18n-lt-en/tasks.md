# Tasks — Vue I18n with English and Lithuanian

## 1. Install Dependency

- [x] 1.1 Run npm install vue-i18n@9 --save

## 2. Create Locale Files

- [x] 2.1 Create resources/spa/locales/en.json with all English keys (see design.md for full catalogue)
- [x] 2.2 Create resources/spa/locales/lt.json with all Lithuanian keys (see design.md for full catalogue)

## 3. Create i18n Plugin

- [x] 3.1 Create resources/spa/plugins/i18n.ts with createI18n setup (legacy: false, default locale lt, fallback en, localStorage persistence)

## 4. Register Plugin in main.ts

- [x] 4.1 Import i18n from @spa/plugins/i18n and add app.use(i18n) in resources/spa/main.ts

## 5. Update AppNavbar.vue

- [x] 5.1 Import useI18n from vue-i18n and destructure t and locale
- [x] 5.2 Add setLocale() function that sets locale.value and saves to localStorage
- [x] 5.3 Change publicLinks to use labelKey instead of label and update template to use t(link.labelKey)
- [x] 5.4 Replace all remaining hardcoded strings with t() calls (Continue booking, dropdown items, guest buttons)
- [x] 5.5 Add language switcher (LT/EN toggle buttons) to the right side of the navbar before user dropdown

## 6. Translate LandingView.vue

- [x] 6.1 Add useI18n and replace all hardcoded strings (stats labels, steps, section headings, CTA button, hero text) with t() calls

## 7. Translate LoginView.vue

- [x] 7.1 Add useI18n and replace all hardcoded strings (title, labels, button text, footer link) with t() calls

## 8. Translate RegisterView.vue

- [x] 8.1 Add useI18n and replace all hardcoded strings with t() calls
- [x] 8.2 Replace the consent span with i18n-t component for HTML interpolation of the Privacy Policy link

## 9. Translate DoctorsView.vue

- [x] 9.1 Add useI18n and replace all hardcoded strings with t() calls

## 10. Translate DoctorSlotsView.vue

- [x] 10.1 Add useI18n and replace all hardcoded strings with t() calls

## 11. Translate ServicesView.vue

- [x] 11.1 Add useI18n and replace all hardcoded strings with t() calls

## 12. Translate BookView.vue

- [x] 12.1 Add useI18n and replace all hardcoded strings (title, field labels, price breakdown, confirm button, loyalty messages) with t() calls

## 13. Translate AppointmentsView.vue

- [x] 13.1 Add useI18n and replace all hardcoded strings (title, status labels, action buttons, empty state) with t() calls

## 14. Translate RescheduleView.vue

- [x] 14.1 Add useI18n and replace all hardcoded strings with t() calls

## 15. Translate RequestBookingView.vue

- [x] 15.1 Add useI18n and replace all hardcoded strings with t() calls

## 16. Translate LoyaltyDashboardView.vue

- [x] 16.1 Add useI18n and replace all hardcoded strings with t() calls

## 17. Translate LoyaltyMarketingView.vue

- [x] 17.1 Add useI18n and replace all hardcoded strings with t() calls

## 18. Translate ProfileView.vue

- [x] 18.1 Add useI18n and replace all hardcoded strings with t() calls

## 19. Translate AboutView.vue

- [x] 19.1 Add useI18n and replace all hardcoded strings with t() calls

## 20. Translate ContactView.vue

- [x] 20.1 Add useI18n and replace all hardcoded strings with t() calls

## 21. Translate PrivacyView.vue

- [x] 21.1 Add useI18n and replace all hardcoded strings with t() calls

## 22. Translate remaining components

- [x] 22.1 Check PageHeader.vue, StatusBadge.vue, and any other components for hardcoded strings and translate if needed

## 23. Build verification

- [x] 23.1 Run npm run build and confirm zero TypeScript errors
