<script setup lang="ts">
interface LogoProps {
  size?: number
}

withDefaults(defineProps<LogoProps>(), {
  size: 28,
})
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 96 96"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    class="shrink-0"
  >
    <path d="M66 30 Q46 40 28 46" stroke="#9AA6BB" stroke-width="5" stroke-linecap="round" fill="none" />
    <path d="M28 46 Q42 62 54 74" stroke="#9AA6BB" stroke-width="5" stroke-linecap="round" fill="none" />
    <circle cx="66" cy="30" r="13" fill="#1877F2" />
    <circle cx="28" cy="46" r="11" fill="#0EA5E9" />
    <circle cx="54" cy="74" r="10" fill="#FA383E" />
  </svg>
</template>
