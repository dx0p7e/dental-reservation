export interface BookingDraft {
  doctor: { id: number; name: string; specialization: string; photo_url: string | null }
  slot: { id: number; date: string; start_time: string } | null
  service: { id: number; name: string; price: string; loyalty_discount_pct: number | null; promo_discount_pct: number | null } | null
  storedAt: string
}

export interface Doctor {
  id: number
  name: string
  specialization: string
  bio: string
  photo_url: string | null
  services: Pick<Service, 'id' | 'name' | 'price' | 'loyalty_discount_pct' | 'promo_discount_pct'>[]
}

export interface Slot {
  id: number
  date: string
  start_time: string
  end_time: string
}

export interface Service {
  id: number
  name: string
  description: string
  duration_minutes: number
  price: string
  loyalty_discount_pct: number | null
  promo_discount_pct: number | null
}

export interface Appointment {
  id: number
  status: string
  notes: string | null
  preferred_date: string | null
  discount_pct: number
  final_price: string | null
  doctor: { id: number; name: string } | null
  service: { id: number; name: string; price: string }
  slot: { id: number; date: string; start_time: string; end_time: string } | null
}

export interface LoyaltyTransaction {
  id: number
  type: string
  points_delta: number
  service_name: string | null
  created_at: string
}

export interface LoyaltyAccount {
  points_balance: number
  tier: string
  next_tier: string | null
  points_to_next_tier: number | null
  transactions?: LoyaltyTransaction[]
}

export interface AuthUser {
  id: number
  name: string
  email: string
}

export interface Review {
  id: number
  rating: number
  title: string | null
  body: string
  patient_name: string
  created_at: string
}
