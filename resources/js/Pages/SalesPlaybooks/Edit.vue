<template>
  <div>
    <Head :title="`Edit: ${playbook.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit Sales Playbook</template>
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
              <Textarea v-model="form.description" rows="3" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industries (comma-separated)</label>
              <InputText v-model="industriesInput" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Deal Stages</label>
              <MultiSelect
                v-model="form.deal_stages"
                :options="dealStageOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select stages"
                display="chip"
              />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Pain Points (comma-separated)</label>
              <InputText v-model="painPointsInput" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Talking Points</label>
              <Textarea v-model="form.talking_points" rows="6" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Email Template - Subject</label>
              <InputText v-model="form.email_template_subject" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Email Template - Body</label>
              <Textarea v-model="form.email_template_body" rows="8" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Recommended Documents (comma-separated)</label>
              <InputText v-model="documentsInput" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Objections Handling</label>
              <Textarea v-model="form.objections_handling" rows="4" />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Next Steps</label>
              <Textarea v-model="form.next_steps" rows="3" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Priority</label>
              <InputNumber v-model="form.priority" :min="0" :max="100" />
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

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link :href="`/sales-playbooks/${playbook.id}`">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Update Playbook" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Select,
    MultiSelect,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    playbook: Object,
    dealStages: Object,
  },
  data() {
    return {
      industriesInput: (this.playbook.industries || []).join(', '),
      painPointsInput: (this.playbook.pain_points || []).join(', '),
      documentsInput: (this.playbook.recommended_documents || []).join(', '),
      form: this.$inertia.form({
        name: this.playbook.name,
        description: this.playbook.description || '',
        industries: this.playbook.industries || [],
        deal_stages: this.playbook.deal_stages || [],
        pain_points: this.playbook.pain_points || [],
        talking_points: this.playbook.talking_points || '',
        email_template_subject: this.playbook.email_template_subject || '',
        email_template_body: this.playbook.email_template_body || '',
        recommended_documents: this.playbook.recommended_documents || [],
        objections_handling: this.playbook.objections_handling || '',
        next_steps: this.playbook.next_steps || '',
        tags: this.playbook.tags || [],
        priority: this.playbook.priority || 0,
        is_active: this.playbook.is_active !== undefined ? this.playbook.is_active : true,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      breadcrumbItems: [
        { label: 'Sales Playbooks', route: '/sales-playbooks' },
        { label: this.playbook.name },
        { label: 'Edit' },
      ],
    }
  },
  computed: {
    dealStageOptions() {
      return Object.entries(this.dealStages).map(([value, label]) => ({ label, value }))
    },
  },
  watch: {
    industriesInput(newVal) {
      this.form.industries = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    painPointsInput(newVal) {
      this.form.pain_points = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
    documentsInput(newVal) {
      this.form.recommended_documents = newVal.split(',').map(s => s.trim()).filter(s => s.length > 0)
    },
  },
  methods: {
    update() {
      this.form.put(`/sales-playbooks/${this.playbook.id}`)
    },
  },
}
</script>

