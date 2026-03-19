<template>
  <div>
    <Head title="Create Proposal" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Proposal</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Title <span class="text-red-500">*</span></label>
              <InputText v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
              <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Deal</label>
              <Select
                v-model="form.deal_id"
                :options="dealOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select a deal (optional)"
                :class="{ 'p-invalid': form.errors.deal_id }"
              />
              <small v-if="form.errors.deal_id" class="p-error">{{ form.errors.deal_id }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Amount</label>
              <InputNumber
                v-model="form.amount"
                mode="currency"
                currency="USD"
                locale="en-US"
                :class="{ 'p-invalid': form.errors.amount }"
              />
              <small v-if="form.errors.amount" class="p-error">{{ form.errors.amount }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Valid Until</label>
              <Calendar
                v-model="form.valid_until"
                dateFormat="yy-mm-dd"
                :minDate="minDate"
                :class="{ 'p-invalid': form.errors.valid_until }"
              />
              <small v-if="form.errors.valid_until" class="p-error">{{ form.errors.valid_until }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea v-model="form.description" rows="4" :class="{ 'p-invalid': form.errors.description }" />
              <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Proposal File <span class="text-red-500">*</span></label>
              <FileUpload
                mode="basic"
                accept=".pdf,.doc,.docx"
                :maxFileSize="10000000"
                chooseLabel="Choose File"
                :class="{ 'p-invalid': form.errors.file }"
                @select="onFileSelect"
              />
              <small v-if="form.errors.file" class="p-error">{{ form.errors.file }}</small>
              <small class="text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX (Max 10MB)</small>
              <div v-if="selectedFile" class="mt-2 p-2 bg-gray-50 rounded">
                <div class="flex items-center justify-between">
                  <span class="text-sm">{{ selectedFile.name }}</span>
                  <span class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/proposals">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Proposal" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import FileUpload from 'primevue/fileupload'
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
    FileUpload,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    deal_id: Number,
    deals: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      selectedFile: null,
      form: this.$inertia.form({
        deal_id: this.deal_id || null,
        title: '',
        description: '',
        amount: null,
        valid_until: null,
        file: null,
      }),
      breadcrumbItems: [
        { label: 'Proposals', route: '/proposals' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    dealOptions() {
      return [
        { label: 'Select a deal (optional)', value: null },
        ...this.deals.map(deal => ({ label: deal.title, value: deal.id })),
      ]
    },
    minDate() {
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      return tomorrow
    },
  },
  methods: {
    onFileSelect(event) {
      this.selectedFile = event.files[0]
      this.form.file = event.files[0]
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
    store() {
      const formData = new FormData()
      formData.append('deal_id', this.form.deal_id || '')
      formData.append('title', this.form.title)
      formData.append('description', this.form.description || '')
      formData.append('amount', this.form.amount || '')
      formData.append('valid_until', this.form.valid_until || '')
      if (this.form.file) {
        formData.append('file', this.form.file)
      }

      this.$inertia.post('/proposals', formData, {
        forceFormData: true,
      })
    },
  },
}
</script>

