<template>
  <div :class="$attrs.class">
    <span v-if="label" class="p-float-label">
      <InputText
        :id="id"
        ref="input"
        v-bind="{ ...$attrs, class: null }"
        :type="type"
        :value="modelValue"
        :class="{ 'p-invalid': error }"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <label :for="id">{{ label }}</label>
    </span>
    <InputText
      v-else
      :id="id"
      ref="input"
      v-bind="{ ...$attrs, class: null }"
      :type="type"
      :value="modelValue"
      :class="{ 'p-invalid': error }"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script>
import InputText from 'primevue/inputtext'
import { v4 as uuid } from 'uuid'

export default {
  components: {
    InputText,
  },
  inheritAttrs: false,
  props: {
    id: {
      type: String,
      default() {
        return `text-input-${uuid()}`
      },
    },
    type: {
      type: String,
      default: 'text',
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
    setSelectionRange(start, end) {
      this.$refs.input.$el.setSelectionRange(start, end)
    },
  },
}
</script>
