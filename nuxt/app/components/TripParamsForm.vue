<template>
  <a-form :model="form" layout="vertical" @submit="handleSubmit">
    <a-form-item label="Destination" required>
      <a-input
        ref="locationInput"
        v-model="form.location"
        placeholder="e.g. Penang, Malaysia"
      />
    </a-form-item>

    <a-form-item label="Duration (days)" required>
      <a-slider v-model="form.duration_days" :min="1" :max="14" :step="1" show-ticks />
      <span class="text-sm text-gray-500">{{ form.duration_days }} day(s)</span>
    </a-form-item>

    <a-form-item label="Interests" required>
      <a-checkbox-group v-model="form.activity_preferences" :options="preferenceOptions" />
    </a-form-item>

    <div class="grid grid-cols-2 gap-4">
      <a-form-item label="Budget Min (MYR)">
        <a-input-number v-model="form.budget_min" :min="0" class="w-full" placeholder="500" />
      </a-form-item>
      <a-form-item label="Budget Max (MYR)">
        <a-input-number v-model="form.budget_max" :min="0" class="w-full" placeholder="800" />
      </a-form-item>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <a-form-item label="Travel Style">
        <a-select v-model="form.travel_style" :options="styleOptions" />
      </a-form-item>
      <a-form-item label="Pace">
        <a-select v-model="form.pace" :options="paceOptions" />
      </a-form-item>
    </div>

    <a-form-item label="Start Date (optional)">
      <a-date-picker v-model="form.start_date" class="w-full" />
    </a-form-item>

    <a-button type="primary" html-type="submit" long :loading="loading" size="large">
      {{ loading ? 'Generating your itinerary...' : 'Generate Itinerary' }}
    </a-button>
  </a-form>
</template>

<script setup>
const emit = defineEmits(['submit'])

const props = defineProps({
  loading: { type: Boolean, default: false },
  initialLocation: { type: String, default: '' },
})

const form = reactive({
  location: props.initialLocation || 'Penang, Malaysia',
  duration_days: 3,
  activity_preferences: ['food', 'heritage'],
  budget_min: 500,
  budget_max: 800,
  travel_style: 'mid-range',
  pace: 'moderate',
  start_date: undefined,
})

const preferenceOptions = [
  { label: 'Food', value: 'food' },
  { label: 'Heritage', value: 'heritage' },
  { label: 'Nature', value: 'nature' },
  { label: 'Beaches', value: 'beaches' },
  { label: 'Shopping', value: 'shopping' },
  { label: 'Nightlife', value: 'nightlife' },
]

const styleOptions = [
  { label: 'Budget', value: 'budget' },
  { label: 'Mid-range', value: 'mid-range' },
  { label: 'Luxury', value: 'luxury' },
]

const paceOptions = [
  { label: 'Relaxed', value: 'relaxed' },
  { label: 'Moderate', value: 'moderate' },
  { label: 'Packed', value: 'packed' },
]

watch(
  () => props.initialLocation,
  (location) => {
    if (location) {
      form.location = location
    }
  },
)

const locationInput = ref(null)
const config = useRuntimeConfig()

const initAutocomplete = () => {
  const key = config.public.googleMapsKey
  if (!key || !window.google?.maps?.places) return

  const input = locationInput.value?.$el?.querySelector('input')
  if (!input) return

  const autocomplete = new window.google.maps.places.Autocomplete(input, {
    componentRestrictions: { country: 'my' },
    fields: ['formatted_address', 'name'],
  })

  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace()
    form.location = place.formatted_address || place.name || form.location
  })
}

onMounted(() => {
  if (window.google?.maps?.places) {
    initAutocomplete()
  } else {
    const check = setInterval(() => {
      if (window.google?.maps?.places) {
        clearInterval(check)
        initAutocomplete()
      }
    }, 300)
    setTimeout(() => clearInterval(check), 10000)
  }
})

const handleSubmit = () => {
  if (!form.activity_preferences.length) {
    Message.warning('Select at least one interest.')
    return
  }

  emit('submit', {
    ...form,
    start_date: form.start_date
      ? new Date(form.start_date).toISOString().split('T')[0]
      : undefined,
  })
}
</script>
