<template>
  <div>
    <Head title="Generate Content" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Generate New Content</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Template <span class="text-red-500">*</span></label>
              <Select
                v-model="form.template_id"
                :options="templates"
                optionLabel="name"
                optionValue="id"
                placeholder="Select a template"
                :class="{ 'p-invalid': form.errors.template_id }"
              />
              <small v-if="form.errors.template_id" class="p-error">{{ form.errors.template_id }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Content Type <span class="text-red-500">*</span></label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.type }"
              />
              <small v-if="form.errors.type" class="p-error">{{ form.errors.type }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Title</label>
              <InputText v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
              <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Template Variables (JSON)</label>
              <Textarea
                v-model="variablesJson"
                rows="6"
                placeholder='{"company_name": "Acme Corp", "industry": "Technology", "target_audience": "SMEs"}'
                :class="{ 'p-invalid': form.errors.variables }"
              />
              <small v-if="form.errors.variables" class="p-error">{{ form.errors.variables }}</small>
              <small class="text-gray-500 mt-1">Enter variables as JSON object to replace placeholders in template</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Generation Options (JSON - Optional)</label>
              <Textarea
                v-model="optionsJson"
                rows="4"
                placeholder='{"temperature": 0.8, "max_tokens": 1500}'
              />
              <small class="text-gray-500 mt-1">Optional: Override AI generation parameters</small>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/content-items">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Generate Content" icon="pi pi-check" :loading="form.processing" type="submit" />
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
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    templates: Array,
    types: Object,
  },
  data() {
    return {
      form: useForm({
        template_id: null,
        type: 'text',
        title: '',
        variables: {},
        options: {},
      }),
      variablesJson: '{}',
      optionsJson: '{}',
      typeOptions: Object.entries(this.types).map(([value, label]) => ({ value, label })),
      breadcrumbItems: [
        { label: 'Content Items', route: '/content-items' },
        { label: 'Generate' },
      ],
    }
  },
  methods: {
    store() {
      // Parse JSON inputs
      try {
        this.form.variables = JSON.parse(this.variablesJson || '{}')
      } catch (e) {
        this.form.errors.variables = 'Invalid JSON format for variables'
        return
      }

      try {
        this.form.options = JSON.parse(this.optionsJson || '{}')
      } catch (e) {
        // Options are optional
      }

      this.form.post('/content-items')
    },
  },
}
</script>

