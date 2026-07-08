<template>
  <a-card size="small" class="activity-card">
    <div class="flex items-start justify-between gap-2">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs text-gray-400 cursor-grab">⠿</span>
          <a-tag v-if="activity.is_ai_suggested" color="arcoblue" size="small">AI</a-tag>
          <a-tag :color="categoryColor" size="small">{{ activity.category }}</a-tag>
          <span v-if="activity.start_time" class="text-xs text-gray-500">
            {{ activity.start_time }}{{ activity.end_time ? ` - ${activity.end_time}` : '' }}
          </span>
        </div>
        <h4 class="font-medium text-gray-800">{{ activity.name }}</h4>
        <p v-if="activity.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
          {{ activity.description }}
        </p>
        <p v-if="activity.address" class="text-xs text-gray-400 mt-1">{{ activity.address }}</p>
      </div>

      <div class="text-right shrink-0">
        <div class="font-semibold text-green-600">RM{{ Number(activity.estimated_cost).toFixed(2) }}</div>
        <p class="text-xs text-gray-400 mt-1 max-w-[120px]">
          <a
            v-if="activity.cost_source === 'catalog' && activity.price_source_url"
            :href="activity.price_source_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-blue-600 hover:underline"
          >
            From {{ activity.price_source_name || 'catalog' }}
          </a>
          <span v-else-if="activity.cost_source === 'catalog'">
            From {{ activity.price_source_name || 'price catalog' }}
          </span>
          <span v-else-if="activity.cost_source === 'places'">Google price level estimate</span>
          <span v-else-if="activity.cost_source === 'manual'">Custom price</span>
          <span v-else>AI estimate</span>
        </p>
        <div class="flex gap-1 mt-2">
          <a-button size="mini" @click="$emit('edit', activity)">Edit</a-button>
          <a-button size="mini" @click="$emit('suggest', activity)">Suggest</a-button>
        </div>
      </div>
    </div>
  </a-card>
</template>

<script setup>
const props = defineProps({
  activity: { type: Object, required: true },
})

defineEmits(['edit', 'suggest'])

const categoryColor = computed(() => {
  const colors = {
    food: 'orangered',
    sightseeing: 'blue',
    transport: 'gray',
    accommodation: 'purple',
    other: 'green',
  }
  return colors[props.activity.category] || 'gray'
})
</script>

<style scoped>
.activity-card {
  margin-bottom: 8px;
  cursor: grab;
}
</style>
