<template>
  <Button
    :disabled="loading"
    :loading="loading"
    v-bind="$attrs"
  >
    <slot />
  </Button>
</template>

<script>
import Button from 'primevue/button'

export default {
  components: {
    Button,
  },
  inheritAttrs: false,
  props: {
    loading: Boolean,
  },
}
</script>
