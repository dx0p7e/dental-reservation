import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import type { BookingDraft, Doctor, Service, Slot } from '@spa/types'

const DRAFT_KEY = 'booking_draft'

function loadDraft(): BookingDraft | null {
  try {
    const raw = localStorage.getItem(DRAFT_KEY)
    if (!raw) {
      return null
    }
    const draft = JSON.parse(raw) as BookingDraft
    if (!draft.doctor || !draft.slot) {
      return null
    }
    if (new Date(`${draft.slot.date}T${draft.slot.start_time}`) <= new Date()) {
      localStorage.removeItem(DRAFT_KEY)
      return null
    }
    return draft
  } catch {
    return null
  }
}

export const useBookingStore = defineStore('booking', () => {
  const draft = loadDraft()

  const selectedDoctor = ref<Doctor | null>(
    draft ? ({ id: draft.doctor.id, name: draft.doctor.name, specialization: draft.doctor.specialization, bio: '', services: [] } as Doctor) : null,
  )
  const selectedSlot = ref<Slot | null>(
    draft?.slot ? ({ id: draft.slot.id, date: draft.slot.date, start_time: draft.slot.start_time, end_time: '' } as Slot) : null,
  )
  const selectedService = ref<Service | null>(
    draft?.service
      ? ({ id: draft.service.id, name: draft.service.name, price: draft.service.price, loyalty_discount_pct: draft.service.loyalty_discount_pct, description: '', duration_minutes: 0 } as Service)
      : null,
  )

  watch(
    [selectedDoctor, selectedSlot, selectedService],
    () => {
      if (!selectedDoctor.value) {
        localStorage.removeItem(DRAFT_KEY)
        return
      }
      const payload: BookingDraft = {
        doctor: {
          id: selectedDoctor.value.id,
          name: selectedDoctor.value.name,
          specialization: selectedDoctor.value.specialization,
        },
        slot: selectedSlot.value
          ? { id: selectedSlot.value.id, date: selectedSlot.value.date, start_time: selectedSlot.value.start_time }
          : null,
        service: selectedService.value
          ? { id: selectedService.value.id, name: selectedService.value.name, price: selectedService.value.price, loyalty_discount_pct: selectedService.value.loyalty_discount_pct }
          : null,
        storedAt: new Date().toISOString(),
      }
      localStorage.setItem(DRAFT_KEY, JSON.stringify(payload))
    },
    { deep: true },
  )

  const bookingDraftState = computed<null | 'slot-selection' | 'confirmation'>(() => {
    if (!selectedDoctor.value) {
      return null
    }
    if (!selectedSlot.value || !selectedService.value) {
      return 'slot-selection'
    }
    return 'confirmation'
  })

  function clearDraft() {
    selectedDoctor.value = null
    selectedSlot.value = null
    selectedService.value = null
    localStorage.removeItem(DRAFT_KEY)
  }

  function reset() {
    clearDraft()
  }

  return { selectedDoctor, selectedSlot, selectedService, bookingDraftState, clearDraft, reset }
})
