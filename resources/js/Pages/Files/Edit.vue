<template>
  <div>
    <Head :title="`Edit: ${file.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit File</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">File Name</label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Type</label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.type }"
              />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Access Level</label>
              <Select
                v-model="form.access_level"
                :options="accessLevelOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.access_level }"
              />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea
                v-model="form.description"
                rows="3"
                :class="{ 'p-invalid': form.errors.description }"
              />
              <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
            </div>

            <div class="flex items-center">
              <Checkbox v-model="form.is_public" inputId="is_public" :binary="true" />
              <label for="is_public" class="ml-2">Make file publicly accessible</label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link :href="`/files/${file.id}`">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Update File" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import InputText from 'primevue/inputtext'
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
    InputText,
    Textarea,
    Select,
    Checkbox,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    file: Object,
    types: Object,
    accessLevels: Object,
  },
  data() {
    return {
      form: useForm({
        name: this.file.name,
        description: this.file.description || '',
        type: this.file.type,
        is_public: this.file.is_public,
        access_level: this.file.access_level,
      }),
      typeOptions: [
        { label: 'Select type', value: null },
        ...Object.entries(this.types).map(([value, label]) => ({ value, label })),
      ],
      accessLevelOptions: Object.entries(this.accessLevels).map(([value, label]) => ({ value, label })),
      breadcrumbItems: [
        { label: 'Files', route: '/files' },
        { label: this.file.name },
        { label: 'Edit' },
      ],
    }
  },
  methods: {
    update() {
      this.form.put(`/files/${this.file.id}`)
    },
  },
}
</script>



