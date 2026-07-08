<template>
  <div>
    <section class="mb-10">
      <h1 class="text-3xl font-bold mb-2">Plan your Malaysia trip</h1>
      <p class="text-gray-500 text-lg mb-6">
        Discover featured destinations or create a custom itinerary with AI.
      </p>

      <div v-if="isAuthenticated" class="flex gap-3">
        <NuxtLink to="/itineraries/create">
          <a-button type="primary" size="large">Plan a custom trip</a-button>
        </NuxtLink>
        <NuxtLink to="/itineraries">
          <a-button size="large">View my trips</a-button>
        </NuxtLink>
      </div>

      <div v-else class="flex gap-3">
        <NuxtLink to="/login">
          <a-button type="primary" size="large">Login to plan</a-button>
        </NuxtLink>
        <NuxtLink to="/register">
          <a-button size="large">Create account</a-button>
        </NuxtLink>
      </div>
    </section>

    <FeaturedDestinations
      :destinations="destinations"
      :loading="loading"
      @select="handleSelectDestination"
    />
  </div>
</template>

<script setup>
import { formatDestinationLocation } from '~/types/destination'

definePageMeta({
  layout: 'default',
})

const { isAuthenticated } = useSanctumAuth()
const { featured } = useDestination()

const loading = ref(true)
const destinations = ref([])

onMounted(async () => {
  try {
    destinations.value = await featured()
  } finally {
    loading.value = false
  }
})

const handleSelectDestination = (destination) => {
  const location = formatDestinationLocation(destination)
  const createPath = `/itineraries/create?location=${encodeURIComponent(location)}`

  if (isAuthenticated.value) {
    navigateTo(createPath)
    return
  }

  navigateTo(`/login?redirect=${encodeURIComponent(createPath)}`)
}
</script>
