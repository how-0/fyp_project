<template>
  <div class="overflow-x-auto">
    <table class="w-full text-sm border-collapse">
      <thead>
        <tr class="bg-gray-50">
          <th class="text-left p-3 border">Metric</th>
          <th
            v-for="it in itineraries"
            :key="it.id"
            class="text-left p-3 border min-w-40"
            :class="{ 'bg-green-50': isHighlighted(it.id) }"
          >
            {{ it.title }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.label">
          <td class="p-3 border font-medium text-gray-600">{{ row.label }}</td>
          <td
            v-for="(val, idx) in row.values"
            :key="idx"
            class="p-3 border"
            :class="{ 'font-semibold text-green-700': isHighlighted(itineraries[idx]?.id) }"
          >
            {{ val }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  itineraries: { type: Array, default: () => [] },
  highlights: { type: Object, default: () => ({}) },
})

const isHighlighted = (id) =>
  id === props.highlights.cheapest_id || id === props.highlights.best_budget_fit_id

const rows = computed(() => [
  {
    label: 'Location',
    values: props.itineraries.map((i) => i.location),
  },
  {
    label: 'Duration',
    values: props.itineraries.map((i) => `${i.duration_days} days`),
  },
  {
    label: 'Total Cost',
    values: props.itineraries.map((i) => `RM${Number(i.total_estimated_cost).toFixed(0)}`),
  },
  {
    label: 'Budget Range',
    values: props.itineraries.map((i) => `RM${i.budget_min || 0} - RM${i.budget_max || '—'}`),
  },
  {
    label: 'Budget Fit',
    values: props.itineraries.map((i) =>
      i.budget_fit_percent != null ? `${i.budget_fit_percent}%` : '—',
    ),
  },
  {
    label: 'Activities',
    values: props.itineraries.map((i) => String(i.activity_count)),
  },
  {
    label: 'Status',
    values: props.itineraries.map((i) => i.status),
  },
])
</script>
