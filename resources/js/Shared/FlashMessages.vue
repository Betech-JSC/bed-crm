<template>
  <div>
    <Message
      v-if="$page.props.flash.success && show"
      severity="success"
      :closable="true"
      class="mb-4"
      @close="show = false"
    >
      {{ $page.props.flash.success }}
    </Message>
    <Message
      v-if="($page.props.flash.error || Object.keys($page.props.errors).length > 0) && show"
      severity="error"
      :closable="true"
      class="mb-4"
      @close="show = false"
    >
      <div v-if="$page.props.flash.error">{{ $page.props.flash.error }}</div>
      <div v-else>
        <span v-if="Object.keys($page.props.errors).length === 1">There is one form error.</span>
        <span v-else>There are {{ Object.keys($page.props.errors).length }} form errors.</span>
      </div>
    </Message>
  </div>
</template>

<script>
import Message from 'primevue/message'

export default {
  components: {
    Message,
  },
  data() {
    return {
      show: true,
    }
  },
  watch: {
    '$page.props.flash': {
      handler() {
        this.show = true
      },
      deep: true,
    },
  },
}
</script>
