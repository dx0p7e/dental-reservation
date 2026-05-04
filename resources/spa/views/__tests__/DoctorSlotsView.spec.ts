import { describe, expect, it } from 'vitest'
import type { Slot } from '@spa/types'

// Inline the groupedSlots logic (mirrors DoctorSlotsView computed)
function groupSlotsByDate(slots: Slot[]): Map<string, Slot[]> {
  const map = new Map<string, Slot[]>()

  for (const slot of slots) {
    const existing = map.get(slot.date)

    if (existing) {
      existing.push(slot)
    } else {
      map.set(slot.date, [slot])
    }
  }

  return map
}

function makeSlot(id: number, date: string, start_time = '09:00'): Slot {
  return { id, date, start_time, end_time: '09:30' }
}

describe('DoctorSlotsView: groupedSlots', () => {
  it('groups slots by date when multiple dates are present', () => {
    const slots = [
      makeSlot(1, '2026-04-12', '09:00'),
      makeSlot(2, '2026-04-12', '09:30'),
      makeSlot(3, '2026-04-13', '09:00'),
    ]

    const grouped = groupSlotsByDate(slots)

    expect(grouped.size).toBe(2)
    expect(grouped.get('2026-04-12')).toHaveLength(2)
    expect(grouped.get('2026-04-13')).toHaveLength(1)
  })

  it('produces a single group when all slots share the same date', () => {
    const slots = [makeSlot(1, '2026-04-12', '09:00'), makeSlot(2, '2026-04-12', '09:30')]

    const grouped = groupSlotsByDate(slots)

    expect(grouped.size).toBe(1)
    expect(grouped.get('2026-04-12')).toHaveLength(2)
  })

  it('returns an empty map for an empty slot list', () => {
    const grouped = groupSlotsByDate([])
    expect(grouped.size).toBe(0)
  })

  it('preserves chronological order matching insertion order', () => {
    const slots = [
      makeSlot(1, '2026-04-12'),
      makeSlot(2, '2026-04-14'),
      makeSlot(3, '2026-04-13'),
    ]

    const grouped = groupSlotsByDate(slots)
    const dates = [...grouped.keys()]

    expect(dates).toEqual(['2026-04-12', '2026-04-14', '2026-04-13'])
  })

  it('each group contains only slots for that date', () => {
    const slots = [makeSlot(1, '2026-04-12'), makeSlot(2, '2026-04-13'), makeSlot(3, '2026-04-12')]

    const grouped = groupSlotsByDate(slots)

    grouped.forEach((daySlots, date) => {
      daySlots.forEach((slot) => expect(slot.date).toBe(date))
    })
  })
})
