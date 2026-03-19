<template>
  <div>
    <Head title="Create Deal" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Deal</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Title <span class="text-red-500">*</span></label>
              <InputText v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
              <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Lead</label>
              <Select
                v-model="form.lead_id"
                :options="leadOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select a lead (optional)"
                :class="{ 'p-invalid': form.errors.lead_id }"
              />
              <small v-if="form.errors.lead_id" class="p-error">{{ form.errors.lead_id }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Stage <span class="text-red-500">*</span></label>
              <Select
                v-model="form.stage"
                :options="stageOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.stage }"
              />
              <small v-if="form.errors.stage" class="p-error">{{ form.errors.stage }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Value</label>
              <InputNumber
                v-model="form.value"
                mode="currency"
                currency="VND"
                locale="vi-VN"
                :class="{ 'p-invalid': form.errors.value }"
              />
              <small v-if="form.errors.value" class="p-error">{{ form.errors.value }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Expected Close Date</label>
              <Calendar
                v-model="form.expected_close_date"
                dateFormat="yy-mm-dd"
                :class="{ 'p-invalid': form.errors.expected_close_date }"
              />
              <small v-if="form.errors.expected_close_date" class="p-error">{{ form.errors.expected_close_date }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Assign To</label>
              <Select
                v-model="form.assigned_to"
                :options="assignedOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Unassigned"
                :class="{ 'p-invalid': form.errors.assigned_to }"
              />
              <small v-if="form.errors.assigned_to" class="p-error">{{ form.errors.assigned_to }}</small>
            </div>
          </div>

          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Notes</label>
            <Textarea v-model="form.notes" rows="4" :class="{ 'p-invalid': form.errors.notes }" />
            <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/deals">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Deal" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
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
    Calendar,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    leads: Array,
    stages: Object,
    salesUsers: Array,
  },
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: this.$inertia.form({
        lead_id: null,
        title: '',
        stage: 'prospecting',
        value: null,
        expected_close_date: null,
        assigned_to: null,
        notes: '',
      }),
      breadcrumbItems: [
        { label: 'Deals', route: '/deals' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    leadOptions() {
      return [
        { label: 'No lead', value: null },
        ...this.leads.map(lead => ({
          label: `${lead.name}${lead.company ? ` - ${lead.company}` : ''}`,
          value: lead.id,
        })),
      ]
    },
    stageOptions() {
      return Object.entries(this.stages).map(([value, label]) => ({ label, value }))
    },
    assignedOptions() {
      return [
        { label: 'Unassigned', value: null },
        ...this.salesUsers.map(user => ({ label: user.name, value: user.id })),
      ]
    },
  },
  methods: {
    store() {
      this.form.post('/deals')
    },
  },
}
</script>

