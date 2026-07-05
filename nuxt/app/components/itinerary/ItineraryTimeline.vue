<template>
  <div class="space-y-6">
    <div
      v-for="day in days"
      :key="day.id"
      class="border rounded-lg p-4 bg-white"
    >
      <div class="mb-3 flex items-center justify-between">
        <div>
          <h3 class="font-semibold">Day {{ day.day_number }}: {{ day.title }}</h3>
          <p v-if="day.notes" class="text-sm text-gray-500">{{ day.notes }}</p>
        </div>
        <a-button
          v-if="editable"
          size="small"
          :loading="regenerating"
          @click="$emit('regenerate-day', day.day_number)"
        >
          Regenerate Day
        </a-button>
      </div>

      <draggable
        v-if="editable"
        :list="day.activities"
        item-key="id"
        group="activities"
        handle=".cursor-grab"
        class="space-y-2 min-h-12"
        @end="onDragEnd"
      >
        <template #item="{ element }">
          <ActivityCard
            :activity="element"
            @edit="$emit('edit-activity', element)"
            @suggest="$emit('suggest-activity', element)"
          />
        </template>
      </draggable>

      <div v-else class="space-y-2">
        <ActivityCard
          v-for="activity in day.activities"
          :key="activity.id"
          :activity="activity"
        />
      </div>

      <p v-if="!day.activities.length" class="text-sm text-gray-400 text-center py-4">
        Drop activities here
      </p>
    </div>
  </div>
</template>

<script setup>
import draggable from 'vuedraggable'
import ActivityCard from './ActivityCard.vue'

const props = defineProps({
  days: { type: Array, default: () => [] },
  editable: { type: Boolean, default: true },
  regenerating: { type: Boolean, default: false },
})

const emit = defineEmits(['reorder', 'edit-activity', 'suggest-activity', 'regenerate-day'])

const onDragEnd = () => {
  const payload = []

  props.days.forEach((day) => {
    day.activities.forEach((activity, index) => {
      payload.push({
        id: activity.id,
        day_id: day.id,
        sort_order: index,
      })
    })
  })

  emit('reorder', payload)
}
</script>
