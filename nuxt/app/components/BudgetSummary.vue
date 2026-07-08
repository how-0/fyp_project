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

    <div v-if="breakdown" class="grid grid-cols-2 gap-2 text-sm">
      <div v-for="(value, key) in breakdown" :key="key" class="flex justify-between">
        <span class="capitalize text-gray-500">{{ key }}</span>
        <span>RM{{ Number(value).toFixed(2) }}</span>
      </div>
    </div>

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
})

const total = computed(() => Number(props.total) || 0)
const budgetMin = computed(() => {
  const value = Number(props.budgetMin)
  return Number.isFinite(value) && value > 0 ? value : null
})
const budgetMax = computed(() => {
  const value = Number(props.budgetMax)
  return Number.isFinite(value) && value > 0 ? value : null
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
