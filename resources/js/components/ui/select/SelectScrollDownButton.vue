<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import { SelectScrollDownButton, type SelectScrollDownButtonProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<SelectScrollDownButtonProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <SelectScrollDownButton
    :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
    v-bind="props"
  >
    <slot>
      <ChevronDown class="size-4" />
    </slot>
  </SelectScrollDownButton>
</template>
