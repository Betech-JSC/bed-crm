<template>
  <div>
    <button type="button" ref="trigger" @click="toggle">
      <slot />
    </button>
    <OverlayPanel
      ref="overlay"
      :dismissable="autoClose"
      :appendTo="appendTo"
    >
      <div @click.stop>
        <slot name="dropdown" />
      </div>
    </OverlayPanel>
  </div>
</template>

<script>
import OverlayPanel from 'primevue/overlaypanel'

export default {
  components: {
    OverlayPanel,
  },
  props: {
    placement: {
      type: String,
      default: 'bottom-end',
    },
    autoClose: {
      type: Boolean,
      default: true,
    },
    appendTo: {
      type: String,
      default: 'body',
    },
  },
  methods: {
    toggle(event) {
      this.$refs.overlay.toggle(event, this.$refs.trigger)
    },
    show(event) {
      this.$refs.overlay.show(event, this.$refs.trigger)
    },
    hide() {
      this.$refs.overlay.hide()
    },
  },
}
</script>
