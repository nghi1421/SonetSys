<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import L from 'leaflet'

const props = defineProps<{ lat: number; lng: number; name: string }>()

const mapContainer = ref<HTMLDivElement | null>(null)
let map: L.Map | null = null

const locationMarkerIcon = L.divIcon({
  className: 'custom-location-marker',
  html: '<div class="custom-location-marker__dot"></div>',
  iconSize: [16, 16],
})

onMounted(() => {
  if (!mapContainer.value) return

  map = L.map(mapContainer.value, {
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    zoomControl: false,
    attributionControl: true,
  }).setView([props.lat, props.lng], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  L.marker([props.lat, props.lng], { icon: locationMarkerIcon, alt: props.name }).addTo(map)
})

onUnmounted(() => {
  map?.remove()
  map = null
})
</script>

<template>
  <div ref="mapContainer" class="h-40 w-full rounded-hud border border-cyber-border overflow-hidden" />
</template>

<style scoped>
:deep(.leaflet-tile-pane) {
  filter: invert(1) hue-rotate(180deg);
}

:deep(.custom-location-marker__dot) {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: var(--color-cyber-neon-cyan);
  border: 2px solid white;
  box-shadow: 0 0 8px 2px var(--color-cyber-neon-cyan);
}
</style>
