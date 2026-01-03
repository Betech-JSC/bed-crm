<template>
  <div :class="$attrs.class">
    <span v-if="label" class="p-float-label">
      <Textarea
        :id="id"
        ref="input"
        v-bind="{ ...$attrs, class: null }"
        :value="modelValue"
        :class="{ 'p-invalid': error }"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <label :for="id">{{ label }}</label>
    </span>
    <Textarea
      v-else
      :id="id"
      ref="input"
      v-bind="{ ...$attrs, class: null }"
      :value="modelValue"
      :class="{ 'p-invalid': error }"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script>
import Textarea from 'primevue/textarea'
import { v4 as uuid } from 'uuid'

export default {
  components: {
    Textarea,
  },
  inheritAttrs: false,
  props: {
    id: {
      type: String,
      default() {
        return `textarea-input-${uuid()}`
      },
    },
    error: String,
    label: String,
    modelValue: String,
  },
  emits: ['update:modelValue'],
  methods: {
    focus() {
      this.$refs.input.$el.focus()
    },
    select() {
      this.$refs.input.$el.select()
    },
  },
}
</script>
