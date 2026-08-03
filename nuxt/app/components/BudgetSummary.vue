<template>
  <a-card title="Budget Summary">
    <div class="mb-4">
      <div v-if="budgetMax" class="text-xs text-gray-500 mb-3">
        Trip budget:
        <span v-if="budgetMin">RM{{ budgetMin.toFixed(2) }} – </span>
        RM{{ budgetMax.toFixed(2) }}
      </div>

      <div class="flex justify-between text-sm mb-1">
        <span>Total Estimated</span>
        <span class="font-bold text-lg">RM{{ total.toFixed(2) }}</span>
      </div>

      <a-progress
        v-if="budgetMax"
        :percent="budgetRatio"
        :status="budgetStatus"
        :show-text="true"
      >
        <template #text>{{ budgetPercent }}%</template>
      </a-progress>
      <a-tag v-if="budgetMax" :color="budgetStatus === 'danger' ? 'red' : 'green'" class="mt-2">
        {{ budgetFitLabel }}
      </a-tag>
    </div>

    <div v-if="chartSegments.length" class="mt-6">
      <p class="text-xs font-medium text-gray-500 mb-3 uppercase tracking-wide">Cost Breakdown</p>

      <div class="flex flex-col items-center">
        <div class="relative w-44 h-44">
          <div
            class="w-full h-full rounded-full shadow-inner"
            :style="donutStyle"
          />
          <div class="absolute inset-7 rounded-full bg-white flex flex-col items-center justify-center text-center px-2">
            <span class="text-[10px] text-gray-400 uppercase tracking-wide">Total</span>
            <span class="text-sm font-semibold text-gray-800">RM{{ chartTotal.toFixed(0) }}</span>
          </div>
        </div>
      </div>

      <div class="mt-4 space-y-2">
        <div
          v-for="segment in chartSegments"
          :key="segment.key"
          class="flex items-center justify-between text-sm gap-3"
        >
          <div class="flex items-center gap-2 min-w-0">
            <span
              class="w-3 h-3 rounded-full shrink-0"
              :style="{ backgroundColor: segment.color }"
            />
            <span class="text-gray-600 truncate">{{ segment.label }}</span>
          </div>
          <div class="text-right shrink-0">
            <span class="font-medium">RM{{ segment.value.toFixed(2) }}</span>
            <span class="text-xs text-gray-400 ml-1">({{ segment.percent.toFixed(0) }}%)</span>
          </div>
        </div>
      </div>
    </div>

    <a-empty
      v-else-if="effectiveBreakdown"
      description="No cost data yet"
      class="py-4"
    />

    <p class="text-xs text-gray-400 mt-4">
      Costs may come from verified attraction prices, Google price levels, or AI estimates.
    </p>
  </a-card>
</template>

<script setup>
const props = defineProps({
  total: { type: [Number, String], default: 0 },
  budgetMin: { type: [Number, String], default: null },
  budgetMax: { type: [Number, String], default: null },
  breakdown: { type: Object, default: null },
  budgetFitPercent: { type: [Number, String], default: null },
  days: { type: Array, default: () => [] },
})

const CATEGORY_LABELS = [
  { key: 'accommodation', label: 'Accommodation', color: '#165DFF' },
  { key: 'food', label: 'Food', color: '#FF7D00' },
  { key: 'activities', label: 'Activities', color: '#00B42A' },
  { key: 'transport', label: 'Transport', color: '#722ED1' },
]

const CATEGORY_MAP = {
  food: 'food',
  accommodation: 'accommodation',
  transport: 'transport',
  sightseeing: 'activities',
  other: 'activities',
}

const total = computed(() => Number(props.total) || 0)
const budgetMin = computed(() => {
  const value = Number(props.budgetMin)
  return Number.isFinite(value) && value > 0 ? value : null
})
const budgetMax = computed(() => {
  const value = Number(props.budgetMax)
  return Number.isFinite(value) && value > 0 ? value : null
})

const effectiveBreakdown = computed(() => {
  if (props.days?.length) {
    const totals = {
      accommodation: 0,
      food: 0,
      activities: 0,
      transport: 0,
    }

    for (const day of props.days) {
      for (const activity of day.activities || []) {
        const bucket = CATEGORY_MAP[activity.category] || 'activities'
        totals[bucket] += Number(activity.estimated_cost) || 0
      }
    }

    return totals
  }

  if (props.breakdown) {
    return {
      accommodation: Number(props.breakdown.accommodation) || 0,
      food: Number(props.breakdown.food) || 0,
      activities: Number(props.breakdown.activities) || 0,
      transport: Number(props.breakdown.transport) || 0,
    }
  }

  return null
})

const chartSegments = computed(() => {
  if (!effectiveBreakdown.value) return []

  const chartTotal = CATEGORY_LABELS.reduce(
    (sum, item) => sum + (Number(effectiveBreakdown.value[item.key]) || 0),
    0,
  )

  if (chartTotal <= 0) return []

  let cumulative = 0

  return CATEGORY_LABELS
    .map((item) => {
      const value = Number(effectiveBreakdown.value[item.key]) || 0
      const percent = (value / chartTotal) * 100
      const start = cumulative
      cumulative += percent

      return {
        ...item,
        value,
        percent,
        start,
        end: cumulative,
      }
    })
    .filter((segment) => segment.value > 0)
})

const chartTotal = computed(() =>
  chartSegments.value.reduce((sum, segment) => sum + segment.value, 0),
)

const donutStyle = computed(() => {
  if (!chartSegments.value.length) return {}

  const gradient = chartSegments.value
    .map((segment) => `${segment.color} ${segment.start}% ${segment.end}%`)
    .join(', ')

  return { background: `conic-gradient(${gradient})` }
})

const budgetRatio = computed(() => {
  if (!budgetMax.value) return 0
  return Math.min(1, total.value / budgetMax.value)
})

const budgetPercent = computed(() => {
  if (!budgetMax.value) return '0.00'
  return ((total.value / budgetMax.value) * 100).toFixed(2)
})

const budgetStatus = computed(() => {
  if (!budgetMax.value) return 'normal'
  return total.value > budgetMax.value ? 'danger' : 'success'
})

const budgetFitLabel = computed(() => {
  if (!budgetMax.value) return ''

  if (total.value > budgetMax.value) {
    return `Over budget by RM${(total.value - budgetMax.value).toFixed(2)}`
  }

  const remaining = budgetMax.value - total.value
  const percentUsed = budgetPercent.value

  if (budgetMin.value && total.value < budgetMin.value) {
    return `Under budget range (${percentUsed}% of max · RM${remaining.toFixed(2)} remaining)`
  }

  return `Within budget (${percentUsed}% of max · RM${remaining.toFixed(2)} remaining)`
})
</script>
