import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useBookingStore } from '../booking'

// ---------------------------------------------------------------------------
// localStorage mock
// ---------------------------------------------------------------------------
function createLocalStorageMock() {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] ?? null,
    setItem: (key: string, value: string) => {
      store[key] = value
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
  }
}

const localStorageMock = createLocalStorageMock()

vi.stubGlobal('localStorage', localStorageMock)

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function futureSlot() {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  return {
    id: 99,
    date: d.toISOString().slice(0, 10),
    start_time: '10:00',
  }
}

function pastSlot() {
  return { id: 88, date: '2000-01-01', start_time: '08:00' }
}

function makeDraft(slot = futureSlot()) {
  return JSON.stringify({
    doctor: { id: 3, name: 'Dr. Test', specialization: 'General' },
    slot,
    service: { id: 5, name: 'Cleaning', price: '50.00', loyalty_discount_pct: null },
    storedAt: new Date().toISOString(),
  })
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------
describe('useBookingStore', () => {
  beforeEach(() => {
    localStorageMock.clear()
    setActivePinia(createPinia())
  })

  // -------------------------------------------------------------------------
  // Staleness check
  // -------------------------------------------------------------------------
  describe('loadDraft() — staleness check', () => {
    it('discards a stale draft (slot datetime in the past)', () => {
      localStorage.setItem('booking_draft', makeDraft(pastSlot()))

      const store = useBookingStore()

      expect(store.selectedDoctor).toBeNull()
      expect(store.selectedSlot).toBeNull()
      expect(store.selectedService).toBeNull()
      expect(localStorage.getItem('booking_draft')).toBeNull()
    })

    it('rehydrates a valid, non-stale draft', () => {
      localStorage.setItem('booking_draft', makeDraft())

      const store = useBookingStore()

      expect(store.selectedDoctor?.id).toBe(3)
      expect(store.selectedSlot?.id).toBe(99)
      expect(store.selectedService?.id).toBe(5)
    })

    it('ignores missing booking_draft key gracefully', () => {
      const store = useBookingStore()

      expect(store.selectedDoctor).toBeNull()
      expect(store.selectedSlot).toBeNull()
      expect(store.selectedService).toBeNull()
    })

    it('ignores malformed JSON without throwing', () => {
      localStorage.setItem('booking_draft', '{not valid json}')

      expect(() => useBookingStore()).not.toThrow()

      const store = useBookingStore()
      expect(store.selectedDoctor).toBeNull()
    })
  })

  // -------------------------------------------------------------------------
  // bookingDraftState
  // -------------------------------------------------------------------------
  describe('bookingDraftState', () => {
    it('returns null when no doctor selected', () => {
      const store = useBookingStore()
      expect(store.bookingDraftState).toBeNull()
    })

    it('returns slot-selection when doctor is set but slot/service are null', () => {
      const store = useBookingStore()
      store.selectedDoctor = { id: 1, name: 'Dr. A', specialization: 'Ortho', bio: '', services: [] }

      expect(store.bookingDraftState).toBe('slot-selection')
    })

    it('returns confirmation when all three are set', () => {
      localStorage.setItem('booking_draft', makeDraft())
      const store = useBookingStore()

      expect(store.bookingDraftState).toBe('confirmation')
    })
  })

  // -------------------------------------------------------------------------
  // clearDraft()
  // -------------------------------------------------------------------------
  describe('clearDraft()', () => {
    it('nullifies all three refs and removes the localStorage key', () => {
      localStorage.setItem('booking_draft', makeDraft())
      const store = useBookingStore()

      // Confirm rehydrated
      expect(store.selectedDoctor).not.toBeNull()

      store.clearDraft()

      expect(store.selectedDoctor).toBeNull()
      expect(store.selectedSlot).toBeNull()
      expect(store.selectedService).toBeNull()
      expect(localStorage.getItem('booking_draft')).toBeNull()
    })
  })
})
