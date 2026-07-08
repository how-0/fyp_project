<template>
  <div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-2">Plan a New Trip</h1>
    <p class="text-gray-500 mb-6">Tell us your preferences and AI will build a Malaysia itinerary for you.</p>

    <a-card>
      <TripParamsForm :loading="generating" @submit="handleGenerate" />
    </a-card>

    <a-alert
      v-if="error"
      type="error"
      :content="error"
      class="mt-4"
      closable
      @close="error = ''"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'default',
})

const { generateOutline } = useItinerary()

const generating = ref(false)
const error = ref('')

const handleGenerate = async (params) => {
  generating.value = true
  error.value = ''

  try {
    // Fast call: only the trip outline is generated here. The detail page
    // detects 'generating' status and fills in the days one by one.
    const itinerary = await generateOutline(params)
    await navigateTo(`/itineraries/${itinerary.id}`)
  } catch (err) {
    error.value = err?.data?.message || 'Failed to generate itinerary. Check your Gemini API key.'
  } finally {
    generating.value = false
  }
}
</script>
