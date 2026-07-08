export interface ItineraryActivity {
  id: number
  sort_order: number
  name: string
  description?: string
  place_id?: string
  lat?: number
  lng?: number
  address?: string
  start_time?: string
  end_time?: string
  duration_minutes?: number
  category: string
  estimated_cost: number
  cost_source: string
  price_source_name?: string
  price_source_url?: string
  price_level?: number
  is_ai_suggested: boolean
  user_modified: boolean
}

export interface ItineraryDay {
  id: number
  day_number: number
  title: string
  date?: string
  notes?: string
  activities: ItineraryActivity[]
}

export interface Itinerary {
  id: number
  title: string
  location: string
  duration_days: number
  total_estimated_cost: number
  budget_min?: number
  budget_max?: number
  budget_fit_percent?: number
  status: string
  currency?: string
  activity_preferences?: string[]
  travel_style?: string
  pace?: string
  start_date?: string
  summary?: string
  tips?: string[]
  budget_breakdown?: Record<string, number>
  days?: ItineraryDay[]
  created_at?: string
}

export interface GenerateParams {
  location: string
  duration_days: number
  activity_preferences: string[]
  budget_min?: number
  budget_max?: number
  travel_style?: string
  pace?: string
  start_date?: string
}

export interface ActivitySuggestion {
  name: string
  description?: string
  category: string
  start_time?: string
  end_time?: string
  estimated_cost_myr?: number
  search_query?: string
}
