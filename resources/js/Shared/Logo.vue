<template>
  <img src="/images/logo.png" alt="Betech CRM" class="betech-logo" :style="logoStyle" />
</template>

<script>
export default {
  props: {
    width: { type: [Number, String], default: 120 },
    height: { type: [Number, String], default: 28 },
  },
  computed: {
    logoStyle() {
      return {
        width: typeof this.width === 'number' ? this.width + 'px' : this.width,
        height: typeof this.height === 'number' ? this.height + 'px' : this.height,
      }
    },
  },
}
</script>

<style scoped>
.betech-logo {
  object-fit: contain;
  display: block;
}
</style>
