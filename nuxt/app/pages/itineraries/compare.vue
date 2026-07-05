<template>
  <div>
    <h1 class="text-2xl font-bold mb-2">Compare Itineraries</h1>
    <p class="text-gray-500 mb-6">Select 2–3 saved trips to compare side by side.</p>

    <a-card class="mb-6">
      <a-select
        v-model="selectedIds"
        :options="options"
        multiple
        :max-tag-count="3"
        placeholder="Select itineraries to compare"
        class="w-full"
        allow-clear
      />
      <a-button
        type="primary"
        class="mt-4"
        :disabled="selectedIds.length < 2"
        :loading="comparing"
        @click="runCompare"
      >
        Compare Selected
      </a-button>
    </a-card>

    <CompareTable
      v-if="compareResult"
      :itineraries="compareResult.itineraries"
      :highlights="compareResult.highlights"
    />
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'default',
})

const { list, compare } = useItinerary()

const allItineraries = ref([])
const selectedIds = ref([])
const comparing = ref(false)
const compareResult = ref(null)

const options = computed(() =>
  allItineraries.value.map((i) => ({
    label: `${i.title} (${i.location})`,
    value: i.id,
  })),
)

onMounted(async () => {
  allItineraries.value = await list()
})

const runCompare = async () => {
  comparing.value = true
  try {
    compareResult.value = await compare(selectedIds.value)
  } finally {
    comparing.value = false
  }
}
</script>
