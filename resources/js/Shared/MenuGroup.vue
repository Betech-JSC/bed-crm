<template>
  <div class="mb-2">
    <button
      @click="$emit('toggle')"
      class="group flex items-center justify-between w-full py-2.5 px-0 text-left"
    >
      <div class="flex items-center min-w-0 flex-1">
        <icon :name="icon" class="mr-3 w-5 h-5 flex-shrink-0" :class="isOpen ? 'fill-white' : 'fill-primary-400 group-hover:fill-white'" />
        <div class="text-sm font-semibold truncate" :class="isOpen ? 'text-white' : 'text-primary-300 group-hover:text-white'">{{ title }}</div>
      </div>
      <icon
        name="cheveron-down"
        class="w-4 h-4 flex-shrink-0 ml-2 transition-transform duration-200"
        :class="[
          isOpen ? 'fill-white rotate-180' : 'fill-primary-400 group-hover:fill-white',
        ]"
      />
    </button>
    <div
      v-show="isOpen"
      class="ml-8 space-y-0.5 border-l-2 border-primary-700 pl-4 mt-1"
    >
      <slot />
    </div>
  </div>
</template>

<script>
import Icon from '@/Shared/Icon.vue'

export default {
  components: {
    Icon,
  },
  props: {
    title: String,
    icon: String,
    isOpen: Boolean,
  },
  emits: ['toggle'],
}
</script>

