<template>
  <div>
    <Head title="Create Sales Playbook" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Sales Playbook</template>
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
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Industries (comma-separated)</label>
              <InputText v-model="industriesInput" placeholder="e.g., Technology, SaaS, Healthcare" />
              <small class="text-gray-500 mt-1">Industries this playbook applies to</small>
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
              <InputText v-model="painPointsInput" placeholder="e.g., cost, scalability, security" />
              <small class="text-gray-500 mt-1">Customer pain points this playbook addresses</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Talking Points</label>
              <Textarea v-model="form.talking_points" rows="6" placeholder="Enter suggested talking points..." />
              <small class="text-gray-500 mt-1">Key points to discuss with the customer</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Email Template - Subject</label>
              <InputText v-model="form.email_template_subject" placeholder="e.g., Follow-up: {{deal_title}}" />
              <small class="text-gray-500 mt-1">Available placeholders: {{customer_name}}, {{company_name}}, {{deal_title}}, {{date}}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Email Template - Body</label>
              <Textarea v-model="form.email_template_body" rows="8" placeholder="Enter email template..." />
              <small class="text-gray-500 mt-1">Available placeholders: {{customer_name}}, {{company_name}}, {{deal_title}}, {{date}}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Recommended Documents (comma-separated)</label>
              <InputText v-model="documentsInput" placeholder="e.g., Case Study - SaaS.pdf, Pricing Sheet.pdf" />
              <small class="text-gray-500 mt-1">Documents to send to the customer</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Objections Handling</label>
              <Textarea v-model="form.objections_handling" rows="4" placeholder="Common objections and how to handle them..." />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Next Steps</label>
              <Textarea v-model="form.next_steps" rows="3" placeholder="Suggested next steps..." />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Priority</label>
              <InputNumber v-model="form.priority" :min="0" :max="100" />
              <small class="text-gray-500 mt-1">Higher priority = shown first (0-100)</small>
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
            <Link href="/sales-playbooks">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Playbook" icon="pi pi-check" :loading="form.processing" type="submit" />
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
    dealStages: Object,
  },
  data() {
    return {
      industriesInput: '',
      painPointsInput: '',
      documentsInput: '',
      form: this.$inertia.form({
        name: '',
        description: '',
        industries: [],
        deal_stages: [],
        pain_points: [],
        talking_points: '',
        email_template_subject: '',
        email_template_body: '',
        recommended_documents: [],
        objections_handling: '',
        next_steps: '',
        tags: [],
        priority: 0,
        is_active: true,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      breadcrumbItems: [
        { label: 'Sales Playbooks', route: '/sales-playbooks' },
        { label: 'Create' },
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
    store() {
      this.form.post('/sales-playbooks')
    },
  },
}
</script>

