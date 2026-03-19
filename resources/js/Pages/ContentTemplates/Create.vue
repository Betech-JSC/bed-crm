<template>
  <div>
    <Head title="Create Content Template" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Content Template</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea v-model="form.description" rows="3" :class="{ 'p-invalid': form.errors.description }" />
              <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Category</label>
              <Select
                v-model="form.category"
                :options="categories"
                placeholder="Select category"
                :class="{ 'p-invalid': form.errors.category }"
              />
              <small v-if="form.errors.category" class="p-error">{{ form.errors.category }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Status</label>
              <Select
                v-model="form.is_active"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
              />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Prompt Template <span class="text-red-500">*</span></label>
              <Textarea
                v-model="form.prompt_template"
                rows="8"
                placeholder="Enter your prompt template. Use {{variable}} or {variable} for placeholders."
                :class="{ 'p-invalid': form.errors.prompt_template }"
              />
              <small v-if="form.errors.prompt_template" class="p-error">{{ form.errors.prompt_template }}</small>
              <small class="text-gray-500 mt-1">Use {{variable}} or {variable} syntax for placeholders (e.g., {{company_name}}, {{industry}})</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Available Variables (JSON)</label>
              <Textarea
                v-model="variablesJson"
                rows="4"
                placeholder='["company_name", "industry", "target_audience"]'
                :class="{ 'p-invalid': form.errors.variables }"
              />
              <small v-if="form.errors.variables" class="p-error">{{ form.errors.variables }}</small>
              <small class="text-gray-500 mt-1">Enter as JSON array of variable names</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">AI Settings (JSON - Optional)</label>
              <Textarea
                v-model="settingsJson"
                rows="4"
                placeholder='{"temperature": 0.7, "max_tokens": 1000, "system_prompt": "You are a professional content writer"}'
              />
              <small class="text-gray-500 mt-1">Optional: Customize AI generation parameters</small>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/content-templates">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Template" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import { useTranslation } from '@/composables/useTranslation'

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
    categories: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        name: '',
        description: '',
        category: null,
        prompt_template: '',
        variables: [],
        settings: {},
        is_active: true,
      }),
      variablesJson: '',
      settingsJson: '',
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      breadcrumbItems: [
        { label: 'Content Templates', route: '/content-templates' },
        { label: 'Create' },
      ],
    }
  },
  methods: {
    store() {
      // Parse JSON inputs
      try {
        if (this.variablesJson.trim()) {
          this.form.variables = JSON.parse(this.variablesJson)
        }
      } catch (e) {
        this.form.errors.variables = 'Invalid JSON format for variables'
        return
      }

      try {
        if (this.settingsJson.trim()) {
          this.form.settings = JSON.parse(this.settingsJson)
        }
      } catch (e) {
        // Settings are optional, ignore parse errors
      }

      this.form.post('/content-templates')
    },
  },
}
</script>



