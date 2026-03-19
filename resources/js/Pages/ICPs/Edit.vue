<template>
  <div>
    <Head :title="icp.name" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit ICP Profile</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
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
          </div>

          <Divider />

          <h3 class="text-lg font-semibold mb-4">Scoring Weights</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Company Size Weight</label>
              <InputNumber v-model="form.weight_company_size" :min="0" :max="100" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industry Weight</label>
              <InputNumber v-model="form.weight_industry" :min="0" :max="100" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Location Weight</label>
              <InputNumber v-model="form.weight_location" :min="0" :max="100" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Job Title Weight</label>
              <InputNumber v-model="form.weight_job_title" :min="0" :max="100" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Behavioral Weight</label>
              <InputNumber v-model="form.weight_behavioral" :min="0" :max="100" />
            </div>
          </div>

          <Divider />

          <h3 class="text-lg font-semibold mb-4">Criteria</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industries (comma-separated)</label>
              <InputText v-model="industriesInput" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Locations (comma-separated)</label>
              <InputText v-model="locationsInput" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Job Titles (comma-separated)</label>
              <InputText v-model="jobTitlesInput" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Technologies (comma-separated)</label>
              <InputText v-model="technologiesInput" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Keywords (comma-separated)</label>
              <InputText v-model="keywordsInput" />
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t">
            <Button
              label="Delete ICP"
              icon="pi pi-trash"
              severity="danger"
              outlined
              @click="destroy"
            />
            <div class="flex gap-2">
              <Link href="/icps">
                <Button label="Cancel" severity="secondary" outlined />
              </Link>
              <Button label="Update ICP" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
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
  props: {
    icp: Object,
  },
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      industriesInput: (this.icp.industries || []).join(', '),
      locationsInput: (this.icp.locations || []).join(', '),
      jobTitlesInput: (this.icp.job_titles || []).join(', '),
      technologiesInput: (this.icp.technologies || []).join(', '),
      keywordsInput: (this.icp.keywords || []).join(', '),
      form: this.$inertia.form({
        name: this.icp.name,
        description: this.icp.description || '',
        industries: this.icp.industries || [],
        locations: this.icp.locations || [],
        job_titles: this.icp.job_titles || [],
        technologies: this.icp.technologies || [],
        keywords: this.icp.keywords || [],
        weight_company_size: this.icp.weight_company_size || 20,
        weight_industry: this.icp.weight_industry || 25,
        weight_location: this.icp.weight_location || 15,
        weight_job_title: this.icp.weight_job_title || 20,
        weight_behavioral: this.icp.weight_behavioral || 20,
        min_score: this.icp.min_score || 60,
        is_active: this.icp.is_active !== undefined ? this.icp.is_active : true,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      breadcrumbItems: [
        { label: 'ICP Profiles', route: '/icps' },
        { label: this.icp.name },
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
    update() {
      this.form.put(`/icps/${this.icp.id}`)
    },
    destroy() {
      if (confirm('Are you sure you want to delete this ICP profile?')) {
        this.$inertia.delete(`/icps/${this.icp.id}`)
      }
    },
  },
}
</script>

