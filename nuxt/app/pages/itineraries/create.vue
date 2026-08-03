<template>
  <div>
    <h1 class="text-2xl font-bold mb-2">Plan a New Trip</h1>
    <p class="text-gray-500 mb-6 max-w-2xl">
      A travel planner that builds, verifies, and lets you continuously refine your itinerary.
    </p>

    <a-card>
      <TripParamsForm
        :loading="generating"
        :initial-location="initialLocation"
        @submit="handleGenerate"
      />
    </a-card>

    <a-alert
      v-if="error"
      type="error"
      class="mt-4"
      closable
      @close="error = ''"
    >
      {{ error }}
    </a-alert>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'default',
})

const { generateOutline } = useItinerary()
const route = useRoute()

const generating = ref(false)
const error = ref('')
const initialLocation = computed(() => {
  const location = route.query.location
  return typeof location === 'string' ? location : ''
})

const handleGenerate = async (params) => {
  generating.value = true
  error.value = ''

  try {
    // Fast call: only the trip outline is generated here. The detail page
    // detects 'generating' status and fills in the days one by one.
    const itinerary = await generateOutline(params)
    await navigateTo(`/itineraries/${itinerary.id}`)
  } catch (err) {
    error.value = getApiErrorMessage(err, 'Failed to generate itinerary. Check your Gemini API key.')
  } finally {
    generating.value = false
  }
}
</script>
