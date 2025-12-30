<script setup lang="ts">
import { SelectGroup, type SelectGroupProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'

const props = defineProps<SelectGroupProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <SelectGroup v-bind="props">
    <slot />
  </SelectGroup>
</template>
