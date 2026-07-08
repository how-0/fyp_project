<template>
  <section>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-xl font-bold">Featured Destinations</h2>
        <p class="text-gray-500 text-sm mt-1">Popular spots in Malaysia — pick one to start planning</p>
      </div>
    </div>

    <a-spin :loading="loading" class="w-full">
      <div v-if="destinations.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a-card
          v-for="destination in destinations"
          :key="destination.id"
          hoverable
          class="cursor-pointer overflow-hidden featured-card"
          @click="emit('select', destination)"
        >
          <div
            class="h-36 -mx-4 -mt-4 mb-4 bg-cover bg-center flex items-end"
            :class="destination.image_url ? '' : categoryClass(destination.category)"
            :style="destination.image_url ? { backgroundImage: `url(${destination.image_url})` } : undefined"
          >
            <div class="w-full bg-gradient-to-t from-black/60 to-transparent p-4">
              <a-tag :color="categoryColor(destination.category)" size="small" class="mb-2">
                {{ categoryLabel(destination.category) }}
              </a-tag>
            </div>
          </div>

          <h3 class="font-semibold text-lg">{{ destination.name }}</h3>
          <p class="text-gray-500 text-sm mb-2">{{ destination.state }}</p>
          <p v-if="destination.description" class="text-gray-600 text-sm line-clamp-2">
            {{ destination.description }}
          </p>
        </a-card>
      </div>

      <a-empty v-else-if="!loading" description="No featured destinations yet." />
    </a-spin>
  </section>
</template>

<script setup>
const emit = defineEmits(['select'])

defineProps({
  destinations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const categoryLabel = (category) => {
  const labels = {
    food: 'Food',
    heritage: 'Heritage',
    nature: 'Nature',
    sightseeing: 'Sightseeing',
    beaches: 'Beaches',
    shopping: 'Shopping',
    nightlife: 'Nightlife',
  }
  return labels[category] || 'Explore'
}

const categoryColor = (category) => {
  const colors = {
    food: 'orangered',
    heritage: 'gold',
    nature: 'green',
    sightseeing: 'blue',
    beaches: 'cyan',
    shopping: 'purple',
    nightlife: 'magenta',
  }
  return colors[category] || 'arcoblue'
}

const categoryClass = (category) => {
  const classes = {
    food: 'bg-gradient-to-br from-orange-400 to-red-500',
    heritage: 'bg-gradient-to-br from-amber-500 to-orange-600',
    nature: 'bg-gradient-to-br from-green-500 to-emerald-700',
    sightseeing: 'bg-gradient-to-br from-blue-500 to-indigo-600',
    beaches: 'bg-gradient-to-br from-cyan-400 to-blue-500',
    shopping: 'bg-gradient-to-br from-purple-500 to-pink-500',
    nightlife: 'bg-gradient-to-br from-indigo-600 to-purple-800',
  }
  return classes[category] || 'bg-gradient-to-br from-blue-500 to-indigo-600'
}
</script>

<style scoped>
.featured-card :deep(.arco-card-body) {
  padding-top: 0;
}
</style>
