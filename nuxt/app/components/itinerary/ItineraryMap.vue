<template>
  <a-card title="Map">
    <div ref="mapContainer" class="w-full h-80 rounded-lg bg-gray-100" />
    <p v-if="!hasMarkers" class="text-center text-gray-400 text-sm mt-2">
      Map markers appear when activities have location data.
    </p>
  </a-card>
</template>

<script setup>
const props = defineProps({
  days: { type: Array, default: () => [] },
  activeDay: { type: Number, default: 1 },
})

const emit = defineEmits(['marker-click'])

const mapContainer = ref(null)
let map = null
let markers = []

const dayColors = ['#165DFF', '#00B42A', '#FF7D00', '#F53F3F', '#722ED1', '#14C9C9']

const allActivities = computed(() => {
  const result = []
  props.days.forEach((day) => {
    day.activities.forEach((activity) => {
      if (activity.lat && activity.lng) {
        result.push({ ...activity, day_number: day.day_number })
      }
    })
  })
  return result
})

const hasMarkers = computed(() => allActivities.value.length > 0)

const initMap = () => {
  if (!mapContainer.value || !window.google?.maps) return

  const center = allActivities.value[0]
    ? { lat: Number(allActivities.value[0].lat), lng: Number(allActivities.value[0].lng) }
    : { lat: 3.139, lng: 101.6869 }

  map = new window.google.maps.Map(mapContainer.value, {
    zoom: 12,
    center,
    mapTypeControl: false,
  })

  renderMarkers()
}

const renderMarkers = () => {
  markers.forEach((m) => m.setMap(null))
  markers = []

  if (!map) return

  const bounds = new window.google.maps.LatLngBounds()
  let hasBounds = false

  const filtered = props.activeDay
    ? allActivities.value.filter((a) => a.day_number === props.activeDay)
    : allActivities.value

  filtered.forEach((activity, index) => {
    const position = { lat: Number(activity.lat), lng: Number(activity.lng) }
    const color = dayColors[(activity.day_number - 1) % dayColors.length]

    const marker = new window.google.maps.Marker({
      position,
      map,
      label: { text: String(index + 1), color: 'white' },
      icon: {
        path: window.google.maps.SymbolPath.CIRCLE,
        fillColor: color,
        fillOpacity: 1,
        strokeWeight: 1,
        strokeColor: '#fff',
        scale: 14,
      },
      title: activity.name,
    })

    marker.addListener('click', () => emit('marker-click', activity))
    markers.push(marker)
    bounds.extend(position)
    hasBounds = true
  })

  if (hasBounds) {
    map.fitBounds(bounds)
  }
}

watch(() => [props.days, props.activeDay], () => {
  if (map) renderMarkers()
  else initMap()
}, { deep: true })

onMounted(() => {
  const tryInit = () => {
    if (window.google?.maps) initMap()
    else setTimeout(tryInit, 300)
  }
  tryInit()
})
</script>
