<template>
  <div>
    <Head :title="t('common.create_lead')" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>{{ t('common.create_lead') }}</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <Message v-if="form.errors.duplicate" severity="error" :closable="false" class="mb-4">
            <div class="mb-2">{{ form.errors.duplicate }}</div>
            <Link v-if="form.errors.duplicate_id" :href="`/leads/${form.errors.duplicate_id}/edit`" class="text-sm underline">
              View existing lead
            </Link>
          </Message>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.name') }} <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.company') }}</label>
              <InputText v-model="form.company" :class="{ 'p-invalid': form.errors.company }" />
              <small v-if="form.errors.company" class="p-error">{{ form.errors.company }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.phone') }}</label>
              <InputText v-model="form.phone" :class="{ 'p-invalid': form.errors.phone }" />
              <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.email') }}</label>
              <InputText v-model="form.email" type="email" :class="{ 'p-invalid': form.errors.email }" />
              <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.source') }}</label>
              <Select
                v-model="form.source"
                :options="sourceOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select source"
                :class="{ 'p-invalid': form.errors.source }"
              />
              <small v-if="form.errors.source" class="p-error">{{ form.errors.source }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.status') }} <span class="text-red-500">*</span></label>
              <Select
                v-model="form.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.status }"
              />
              <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.assigned_to') }}</label>
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

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.tags') }}</label>
              <InputText v-model="tagsInput" placeholder="e.g. hot, vip, follow-up" />
              <small class="text-gray-500 text-xs mt-1">Separate tags with commas</small>
            </div>
          </div>

          <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">{{ t('common.notes') }}</label>
            <Textarea v-model="form.notes" rows="4" :class="{ 'p-invalid': form.errors.notes }" />
            <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/leads">
              <Button :label="t('common.cancel')" severity="secondary" outlined />
            </Link>
            <Button :label="t('common.create_lead')" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Breadcrumb from 'primevue/breadcrumb'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head, Link, Card, InputText, Textarea, Select, Button, Message, Breadcrumb,
  },
  layout: Layout,
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  props: {
    statuses: Object,
    sources: Object,
    salesUsers: Array,
  },
  remember: 'form',
  data() {
    return {
      tagsInput: '',
      form: this.$inertia.form({
        name: '',
        phone: '',
        email: '',
        company: '',
        source: null,
        status: 'new',
        assigned_to: null,
        notes: '',
        tags: [],
      }),
      breadcrumbItems: [
        { label: 'Leads', route: '/leads' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    statusOptions() {
      return Object.entries(this.statuses).map(([value, label]) => ({ label, value }))
    },
    sourceOptions() {
      return [{ label: 'Select source', value: null }, ...Object.entries(this.sources).map(([value, label]) => ({ label, value }))]
    },
    assignedOptions() {
      return [{ label: 'Unassigned', value: null }, ...this.salesUsers.map(user => ({ label: user.name, value: user.id }))]
    },
  },
  watch: {
    tagsInput(newVal) {
      this.form.tags = newVal.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0)
    },
  },
  methods: {
    store() {
      this.form.post('/leads')
    },
  },
}
</script>
