<template>
  <div>
    <FileUpload
      mode="basic"
      :accept="accept"
      :maxFileSize="maxFileSize"
      :chooseLabel="label || 'Choose'"
      :class="{ 'p-invalid': errors.length }"
      @select="onSelect"
      @remove="onRemove"
    />
    <div v-if="modelValue" class="mt-2">
      <span class="text-sm">{{ modelValue.name }}</span>
      <span class="text-gray-500 text-xs ml-2">({{ filesize(modelValue.size) }})</span>
    </div>
    <small v-if="errors.length" class="p-error">{{ errors[0] }}</small>
  </div>
</template>

<script>
import FileUpload from 'primevue/fileupload'

export default {
  components: {
    FileUpload,
  },
  props: {
    modelValue: File,
    label: String,
    accept: String,
    maxFileSize: {
      type: Number,
      default: 10000000, // 10MB
    },
    errors: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
  methods: {
    filesize(size) {
      var i = Math.floor(Math.log(size) / Math.log(1024))
      return (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i]
    },
    onSelect(event) {
      this.$emit('update:modelValue', event.files[0])
    },
    onRemove() {
      this.$emit('update:modelValue', null)
    },
  },
}
</script>
