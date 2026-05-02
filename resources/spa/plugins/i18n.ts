import { createI18n } from 'vue-i18n'
import en from '@spa/locales/en.json'
import lt from '@spa/locales/lt.json'

const savedLocale = localStorage.getItem('locale') ?? 'lt'

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, lt },
})
