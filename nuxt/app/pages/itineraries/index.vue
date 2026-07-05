<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">My Itineraries</h1>
      <div class="flex gap-2">
        <NuxtLink to="/itineraries/compare">
          <a-button>Compare Trips</a-button>
        </NuxtLink>
        <NuxtLink to="/itineraries/create">
          <a-button type="primary">Plan New Trip</a-button>
        </NuxtLink>
      </div>
    </div>

    <a-spin :loading="loading" class="w-full">
      <div v-if="itineraries.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a-card
          v-for="item in itineraries"
          :key="item.id"
          hoverable
          class="cursor-pointer"
          @click="navigateTo(`/itineraries/${item.id}`)"
        >
          <h3 class="font-semibold text-lg mb-1">{{ item.title }}</h3>
          <p class="text-gray-500 text-sm mb-3">{{ item.location }}</p>
          <div class="flex justify-between text-sm">
            <span>{{ item.duration_days }} days</span>
            <span class="font-medium text-green-600">RM{{ Number(item.total_estimated_cost).toFixed(0) }}</span>
          </div>
          <div class="flex justify-between items-center mt-3">
            <a-tag :color="statusColor(item.status)">{{ item.status }}</a-tag>
            <a-checkbox
              v-if="compareMode"
              :model-value="selectedIds.includes(item.id)"
              @click.stop
              @change="toggleSelect(item.id)"
            />
          </div>
        </a-card>
      </div>

      <a-empty v-else description="No itineraries yet. Plan your first trip!" />
    </a-spin>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'default',
})

const route = useRoute()
const { list } = useItinerary()

const loading = ref(true)
const itineraries = ref([])
const compareMode = computed(() => route.query.compare === '1')
const selectedIds = ref([])

const statusColor = (status) => {
  const map = { draft: 'gray', generated: 'blue', finalized: 'green' }
  return map[status] || 'gray'
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else if (selectedIds.value.length < 3) selectedIds.value.push(id)
}

onMounted(async () => {
  try {
    itineraries.value = await list()
  } finally {
    loading.value = false
  }
})
</script>
