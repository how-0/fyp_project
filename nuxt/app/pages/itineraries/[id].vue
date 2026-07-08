<template>
  <div v-if="itinerary">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
      <div>
        <a-input
          v-if="editingTitle"
          v-model="titleInput"
          class="text-xl font-bold w-80"
          @press-enter="saveTitle"
          @blur="saveTitle"
        />
        <h1 v-else class="text-2xl font-bold cursor-pointer" @click="editingTitle = true">
          {{ itinerary.title }}
        </h1>
        <p class="text-gray-500">{{ itinerary.location }} · {{ itinerary.duration_days }} days</p>
        <p v-if="itinerary.summary" class="text-sm text-gray-600 mt-2 max-w-2xl">{{ itinerary.summary }}</p>
      </div>

      <div class="flex gap-2 flex-wrap">
        <a-select
          v-model="itinerary.status"
          :options="statusOptions"
          :disabled="progressiveGenerating"
          style="width: 140px"
          @change="saveStatus"
        />
        <a-button :loading="duplicating" @click="handleDuplicate">Duplicate</a-button>
        <a-button status="danger" @click="handleDelete">Delete</a-button>
      </div>
    </div>

    <div
      v-if="progressiveGenerating"
      class="mb-6 p-4 rounded-lg border border-blue-200 bg-blue-50"
    >
      <div class="flex items-center gap-3 mb-2">
        <a-spin :size="18" />
        <span class="font-medium text-blue-800">
          Building your itinerary... Day {{ currentGeneratingDay }} of {{ itinerary.duration_days }}
        </span>
      </div>
      <a-progress
        :percent="generationProgress"
        :show-text="false"
        status="normal"
      />
      <p class="text-sm text-blue-700 mt-2">
        Each day appears below as soon as it's ready — feel free to start reading.
      </p>
    </div>

    <a-alert
      v-if="generationError"
      type="warning"
      class="mb-6"
      :content="generationError"
    >
      <template #action>
        <a-button size="small" type="primary" @click="resumeGeneration">Retry</a-button>
      </template>
    </a-alert>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-4">
        <ItineraryTimeline
          :days="itinerary.days"
          :regenerating="regenerating"
          :pending-days="pendingDayNumbers"
          :generating-day="currentGeneratingDay"
          @reorder="handleReorder"
          @edit-activity="openEditModal"
          @suggest-activity="openSuggestDrawer"
          @regenerate-day="handleRegenerateDay"
        />

        <a-collapse v-if="itinerary.tips?.length" :default-active-key="['tips']">
          <a-collapse-item header="Travel Tips" key="tips">
            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600">
              <li v-for="(tip, i) in itinerary.tips" :key="i">{{ tip }}</li>
            </ul>
          </a-collapse-item>
        </a-collapse>
      </div>

      <div class="space-y-4">
        <BudgetSummary
          :total="itinerary.total_estimated_cost"
          :budget-min="itinerary.budget_min"
          :budget-max="itinerary.budget_max"
          :breakdown="itinerary.budget_breakdown"
          :budget-fit-percent="itinerary.budget_fit_percent"
        />

        <ItineraryMap
          :days="itinerary.days"
          :active-day="activeMapDay"
          @marker-click="scrollToActivity"
        />
      </div>
    </div>

    <a-modal v-model:visible="editModalVisible" title="Edit Activity" @ok="saveActivity">
      <a-form v-if="editingActivity" :model="editForm" layout="vertical">
        <a-form-item label="Name">
          <a-input v-model="editForm.name" />
        </a-form-item>
        <a-form-item label="Cost (MYR)">
          <a-input-number v-model="editForm.estimated_cost" :min="0" class="w-full" />
        </a-form-item>
        <a-form-item label="Start Time">
          <a-time-picker v-model="editForm.start_time" format="HH:mm" class="w-full" />
        </a-form-item>
        <a-form-item label="Description">
          <a-textarea v-model="editForm.description" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-drawer
      v-model:visible="suggestDrawerVisible"
      title="AI Suggestions"
      :width="400"
      :footer="false"
    >
      <a-spin :loading="suggestLoading">
        <div v-for="(suggestion, i) in suggestions" :key="i" class="mb-4 p-3 border rounded">
          <h4 class="font-medium">{{ suggestion.name }}</h4>
          <p class="text-sm text-gray-500">{{ suggestion.description }}</p>
          <p class="text-sm text-green-600 mt-1">~RM{{ suggestion.estimated_cost_myr || 0 }}</p>
          <a-button size="small" class="mt-2" @click="applySuggestion(suggestion)">
            Use This
          </a-button>
        </div>
        <a-empty v-if="!suggestLoading && !suggestions.length" description="No suggestions" />
      </a-spin>
    </a-drawer>
  </div>

  <a-spin v-else :loading="loading" class="w-full min-h-64" />
</template>

<script setup>
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'default',
})

const route = useRoute()
const {
  get,
  update,
  remove,
  duplicate,
  reorder,
  updateActivity,
  suggestAlternative,
  regenerateDay,
  generateDay,
} = useItinerary()

const loading = ref(true)
const itinerary = ref(null)
const editingTitle = ref(false)
const titleInput = ref('')
const duplicating = ref(false)
const regenerating = ref(false)
const activeMapDay = ref(1)

const editModalVisible = ref(false)
const editingActivity = ref(null)
const editForm = reactive({ name: '', estimated_cost: 0, start_time: null, description: '' })

const suggestDrawerVisible = ref(false)
const suggestLoading = ref(false)
const suggestions = ref([])
const suggestTarget = ref(null)

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Generating...', value: 'generating', disabled: true },
  { label: 'Generated', value: 'generated' },
  { label: 'Finalized', value: 'finalized' },
]

// Progressive generation: fill empty days one by one so the user sees
// results batch by batch instead of a single long loading state.
const progressiveGenerating = ref(false)
const currentGeneratingDay = ref(0)
const generationError = ref('')

const pendingDayNumbers = computed(() => {
  if (!progressiveGenerating.value || !itinerary.value?.days) return []
  return itinerary.value.days
    .filter((day) => !day.activities?.length)
    .map((day) => day.day_number)
})

const generationProgress = computed(() => {
  const days = itinerary.value?.days || []
  if (!days.length) return 0
  const done = days.filter((day) => day.activities?.length).length
  return done / days.length
})

const fillPendingDays = async () => {
  if (progressiveGenerating.value) return
  progressiveGenerating.value = true
  generationError.value = ''

  try {
    while (true) {
      const nextDay = (itinerary.value?.days || []).find((day) => !day.activities?.length)
      if (!nextDay) break

      currentGeneratingDay.value = nextDay.day_number
      itinerary.value = await generateDay(route.params.id, nextDay.day_number)
    }
  } catch (err) {
    generationError.value =
      err?.data?.message ||
      `Day ${currentGeneratingDay.value} could not be generated. Click Retry to continue.`
  } finally {
    progressiveGenerating.value = false
    currentGeneratingDay.value = 0
  }
}

const resumeGeneration = () => fillPendingDays()

const loadItinerary = async () => {
  itinerary.value = await get(route.params.id)
  titleInput.value = itinerary.value.title
}

onMounted(async () => {
  try {
    await loadItinerary()
  } finally {
    loading.value = false
  }

  // Resumes automatically after a refresh mid-generation too.
  if (itinerary.value?.status === 'generating') {
    fillPendingDays()
  }
})

const saveTitle = async () => {
  editingTitle.value = false
  if (titleInput.value && titleInput.value !== itinerary.value.title) {
    itinerary.value = await update(route.params.id, { title: titleInput.value })
  }
}

const saveStatus = async () => {
  itinerary.value = await update(route.params.id, { status: itinerary.value.status })
}

const handleDuplicate = async () => {
  duplicating.value = true
  try {
    const copy = await duplicate(route.params.id)
    await navigateTo(`/itineraries/${copy.id}`)
  } finally {
    duplicating.value = false
  }
}

const handleDelete = async () => {
  await remove(route.params.id)
  await navigateTo('/itineraries')
}

const handleReorder = async (activities) => {
  itinerary.value = await reorder(route.params.id, activities)
}

const openEditModal = (activity) => {
  editingActivity.value = activity
  editForm.name = activity.name
  editForm.estimated_cost = Number(activity.estimated_cost)
  editForm.description = activity.description || ''
  editForm.start_time = activity.start_time || null
  editModalVisible.value = true
}

const saveActivity = async () => {
  if (!editingActivity.value) return

  const payload = {
    name: editForm.name,
    estimated_cost: editForm.estimated_cost,
    description: editForm.description,
    start_time: editForm.start_time,
  }

  itinerary.value = await updateActivity(route.params.id, editingActivity.value.id, payload)
  editModalVisible.value = false
}

const openSuggestDrawer = async (activity) => {
  suggestTarget.value = activity
  suggestDrawerVisible.value = true
  suggestLoading.value = true
  suggestions.value = []

  try {
    const result = await suggestAlternative(route.params.id, activity.id)
    suggestions.value = result.suggestions || []
  } finally {
    suggestLoading.value = false
  }
}

const applySuggestion = async (suggestion) => {
  if (!suggestTarget.value) return

  itinerary.value = await updateActivity(route.params.id, suggestTarget.value.id, {
    name: suggestion.name,
    description: suggestion.description,
    estimated_cost: suggestion.estimated_cost_myr,
    category: suggestion.category,
    start_time: suggestion.start_time,
    end_time: suggestion.end_time,
  })

  suggestDrawerVisible.value = false
}

const handleRegenerateDay = async (dayNumber) => {
  regenerating.value = true
  try {
    itinerary.value = await regenerateDay(route.params.id, dayNumber)
  } finally {
    regenerating.value = false
  }
}

const scrollToActivity = () => {
  // Marker click could scroll to activity card in future
}
</script>
