<template>
  <Link
    :href="href"
    class="group flex items-center py-2 transition-colors min-w-0"
    :class="active ? 'text-white' : 'text-primary-300 hover:text-white'"
  >
    <icon
      :name="icon"
      class="mr-3 w-4 h-4 flex-shrink-0"
      :class="active ? 'fill-white' : 'fill-primary-400 group-hover:fill-white'"
    />
    <span class="text-sm truncate"><slot /></span>
  </Link>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'

export default {
  components: {
    Link,
    Icon,
  },
  props: {
    href: String,
    icon: String,
    active: Boolean,
  },
}
</script>

