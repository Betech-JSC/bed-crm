<template>
  <div>
    <Head title="Create ICP Profile" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New ICP Profile</template>
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
              <label class="mb-2 text-sm font-medium">Min Score <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.min_score" :min="0" :max="100" :class="{ 'p-invalid': form.errors.min_score }" />
              <small v-if="form.errors.min_score" class="p-error">{{ form.errors.min_score }}</small>
              <small class="text-gray-500 mt-1">Minimum score (0-100) to be considered a match</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Status</label>
              <Select
                v-model="form.is_active"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.is_active }"
              />
            </div>
          </div>

          <Divider />

          <h3 class="text-lg font-semibold mb-4">Scoring Weights (Total should be 100%)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Company Size Weight</label>
              <InputNumber v-model="form.weight_company_size" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Weight for company size matching</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industry Weight</label>
              <InputNumber v-model="form.weight_industry" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Weight for industry matching</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Location Weight</label>
              <InputNumber v-model="form.weight_location" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Weight for location matching</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Job Title Weight</label>
              <InputNumber v-model="form.weight_job_title" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Weight for job title matching</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Behavioral Weight</label>
              <InputNumber v-model="form.weight_behavioral" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Weight for technologies/keywords matching</small>
            </div>
          </div>

          <Divider />

          <h3 class="text-lg font-semibold mb-4">Criteria (Optional - leave empty to skip)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industries (comma-separated)</label>
              <InputText v-model="industriesInput" placeholder="e.g., Technology, SaaS, E-commerce" />
              <small class="text-gray-500 mt-1">Industries to match</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Locations (comma-separated)</label>
              <InputText v-model="locationsInput" placeholder="e.g., United States, Canada" />
              <small class="text-gray-500 mt-1">Countries/regions to match</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Job Titles (comma-separated)</label>
              <InputText v-model="jobTitlesInput" placeholder="e.g., CEO, CTO, Marketing Director" />
              <small class="text-gray-500 mt-1">Job titles/keywords to match</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Technologies (comma-separated)</label>
              <InputText v-model="technologiesInput" placeholder="e.g., React, Laravel, AWS" />
              <small class="text-gray-500 mt-1">Technologies they use</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Keywords (comma-separated)</label>
              <InputText v-model="keywordsInput" placeholder="e.g., B2B, enterprise, startup" />
              <small class="text-gray-500 mt-1">Keywords to match in descriptions</small>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/icps">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create ICP" icon="pi pi-check" :loading="form.processing" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Select,
    Button,
    Breadcrumb,
    Divider,
  },
  layout: Layout,
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      industriesInput: '',
      locationsInput: '',
      jobTitlesInput: '',
      technologiesInput: '',
      keywordsInput: '',
      form: this.$inertia.form({
        name: '',
        description: '',
        company_size_min: null,
        company_size_max: null,
        industries: [],
        locations: [],
        job_titles: [],
        departments: [],
        technologies: [],
        keywords: [],
        weight_company_size: 20,
        weight_industry: 25,
        weight_location: 15,
        weight_job_title: 20,
        weight_behavioral: 20,
        min_score: 60,
        is_active: true,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      breadcrumbItems: [
        { label: 'ICP Profiles', route: '/icps' },
        { label: 'Create' },
      ],
    }
  },
  watch: {
    industriesInput(newVal) {
      this.form.industries = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    locationsInput(newVal) {
      this.form.locations = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    jobTitlesInput(newVal) {
      this.form.job_titles = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    technologiesInput(newVal) {
      this.form.technologies = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    keywordsInput(newVal) {
      this.form.keywords = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
  },
  methods: {
    store() {
      this.form.post('/icps')
    },
  },
}
</script>

