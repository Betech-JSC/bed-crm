<template>
  <div>
    <Head title="Upload File" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Upload New File</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">File <span class="text-red-500">*</span></label>
              <div class="relative">
                <input
                  type="file"
                  ref="fileInput"
                  @change="onFileChange"
                  class="hidden"
                  :class="{ 'p-invalid': form.errors.file }"
                />
                <Button
                  type="button"
                  :label="selectedFile ? 'Change File' : 'Choose File'"
                  icon="pi pi-upload"
                  @click="$refs.fileInput.click()"
                  :class="{ 'p-invalid': form.errors.file }"
                />
              </div>
              <small v-if="form.errors.file" class="p-error">{{ form.errors.file }}</small>
              <small class="text-gray-500 mt-1">Maximum file size: 10MB. All file types allowed (validation on server)</small>
              <div v-if="selectedFile" class="mt-2 p-2 bg-gray-50 rounded">
                <div class="flex items-center gap-2">
                  <i class="pi pi-file text-primary-600"></i>
                  <span class="text-sm">{{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})</span>
                  <Button
                    icon="pi pi-times"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    @click="removeFile"
                    class="ml-auto"
                  />
                </div>
              </div>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Type</label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select type"
              />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Access Level</label>
              <Select
                v-model="form.access_level"
                :options="accessLevelOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select access level"
              />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea
                v-model="form.description"
                rows="3"
                placeholder="Optional file description"
              />
            </div>

            <div class="flex items-center">
              <Checkbox v-model="form.is_public" inputId="is_public" :binary="true" />
              <label for="is_public" class="ml-2">Make file publicly accessible</label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/files">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Upload File" icon="pi pi-upload" :loading="form.processing" type="submit" :disabled="!selectedFile" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Textarea,
    Select,
    Checkbox,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    categories: Object,
    types: Object,
    accessLevels: Object,
  },
  data() {
    return {
      form: useForm({
        file: null,
        type: null,
        description: '',
        is_public: false,
        access_level: 'private',
      }),
      selectedFile: null,
      typeOptions: [
        { label: 'Select type', value: null },
        ...Object.entries(this.types).map(([value, label]) => ({ value, label })),
      ],
      accessLevelOptions: Object.entries(this.accessLevels).map(([value, label]) => ({ value, label })),
      breadcrumbItems: [
        { label: 'Files', route: '/files' },
        { label: 'Upload' },
      ],
    }
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files[0]
      if (file) {
        // Check file size (10MB = 10485760 bytes)
        if (file.size > 10485760) {
          alert('File size exceeds 10MB limit')
          event.target.value = ''
          return
        }
        this.selectedFile = file
        this.form.file = file
      }
    },
    removeFile() {
      this.selectedFile = null
      this.form.file = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    store() {
      if (!this.selectedFile) {
        return
      }

      this.form.post('/files', {
        forceFormData: true,
      })
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
  },
}
</script>

