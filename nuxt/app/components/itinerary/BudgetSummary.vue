<template>
  <a-card title="Budget Summary">
    <div class="mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span>Total Estimated</span>
        <span class="font-bold text-lg">RM{{ Number(total).toFixed(0) }}</span>
      </div>

      <a-progress
        v-if="budgetMax"
        :percent="budgetPercent"
        :status="budgetStatus"
        :show-text="true"
      />
      <a-tag v-if="budgetMax" :color="budgetStatus === 'danger' ? 'red' : 'green'" class="mt-2">
        {{ budgetFitLabel }}
      </a-tag>
    </div>

    <div v-if="breakdown" class="grid grid-cols-2 gap-2 text-sm">
      <div v-for="(value, key) in breakdown" :key="key" class="flex justify-between">
        <span class="capitalize text-gray-500">{{ key }}</span>
        <span>RM{{ Number(value).toFixed(0) }}</span>
      </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">
      Costs are estimates based on Google price levels and AI suggestions.
    </p>
  </a-card>
</template>

<script setup>
const props = defineProps({
  total: { type: Number, default: 0 },
  budgetMin: { type: Number, default: null },
  budgetMax: { type: Number, default: null },
  breakdown: { type: Object, default: null },
  budgetFitPercent: { type: Number, default: null },
})

const budgetPercent = computed(() => {
  if (!props.budgetMax) return 0
  return Math.min(100, Math.round((props.total / props.budgetMax) * 100))
})

const budgetStatus = computed(() => {
  if (!props.budgetMax) return 'normal'
  return props.total > props.budgetMax ? 'danger' : 'success'
})

const budgetFitLabel = computed(() => {
  if (!props.budgetMax) return ''
  if (props.total > props.budgetMax) return `Over budget by RM${(props.total - props.budgetMax).toFixed(0)}`
  return `Within budget (${props.budgetFitPercent ?? budgetPercent.value}% used)`
})
</script>
