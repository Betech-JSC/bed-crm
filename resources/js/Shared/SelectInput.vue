<template>
  <div :class="$attrs.class">
    <span v-if="label" class="p-float-label">
      <Select
        :id="id"
        ref="input"
        v-model="selected"
        v-bind="{ ...$attrs, class: null }"
        :class="{ 'p-invalid': error }"
        :options="computedOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="label"
        @update:modelValue="handleUpdate"
      />
      <label :for="id">{{ label }}</label>
    </span>
    <Select
      v-else
      :id="id"
      ref="input"
      v-model="selected"
      v-bind="{ ...$attrs, class: null }"
      :class="{ 'p-invalid': error }"
      :options="computedOptions"
      optionLabel="label"
      optionValue="value"
      @update:modelValue="handleUpdate"
    />
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script>
import Select from 'primevue/select'
import { v4 as uuid } from 'uuid'

export default {
  components: {
    Select,
  },
  inheritAttrs: false,
  props: {
    id: {
      type: String,
      default() {
        return `select-input-${uuid()}`
      },
    },
    error: String,
    label: String,
    modelValue: [String, Number, Boolean, null],
    options: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      selected: this.modelValue,
      slotOptions: [],
    }
  },
  computed: {
    computedOptions() {
      if (this.options.length > 0) {
        return this.options
      }
      return this.slotOptions
    },
  },
  watch: {
    modelValue(newValue) {
      this.selected = newValue
    },
  },
  mounted() {
    // Convert slot options to options array
    if (this.$slots.default) {
      this.slotOptions = []
      this.$slots.default().forEach((vnode) => {
        if (vnode.type === 'option' || (vnode.type && vnode.type.name === 'option')) {
          const value = vnode.props?.value ?? null
          const label = typeof vnode.children === 'string' 
            ? vnode.children 
            : (vnode.children?.default?.()?.[0]?.children || '')
          this.slotOptions.push({
            label: label || (value === null ? '' : String(value)),
            value: value,
          })
        }
      })
    }
  },
  methods: {
    handleUpdate(value) {
      this.selected = value
      this.$emit('update:modelValue', value)
    },
    focus() {
      this.$refs.input.focus()
    },
    select() {
      this.$refs.input.focus()
    },
  },
}
</script>
