<template>
  <div>
    <Head title="Create Permission" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>{{ t('common.create_permission') }}</template>
      <template #content>
        <form @submit.prevent="submit">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Name *</label>
              <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Slug *</label>
              <InputText v-model="form.slug" class="w-full" :class="{ 'p-invalid': form.errors.slug }" />
              <small v-if="form.errors.slug" class="p-error">{{ form.errors.slug }}</small>
              <small class="text-gray-500">Auto-generated from name if left empty (e.g., "view-leads" → "view-leads")</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Group</label>
              <Select
                v-model="form.group"
                :options="groupOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select group"
                class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Description</label>
              <Textarea v-model="form.description" rows="3" class="w-full" />
            </div>

            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
              <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="flex items-center justify-end gap-2">
              <Link href="/permissions">
                <Button label="Cancel" severity="secondary" outlined />
              </Link>
              <Button label="Create" type="submit" :loading="form.processing" />
            </div>
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
import { useTranslation } from '@/composables/useTranslation'

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
    groups: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        name: '',
        slug: '',
        group: null,
        description: '',
        is_active: true,
      }),
      breadcrumbItems: [
        { label: 'Permissions', route: '/permissions' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    groupOptions() {
      return [
        { label: 'Select group', value: null },
        ...(this.groups || []).map(group => ({
          label: group.charAt(0).toUpperCase() + group.slice(1).replace('-', ' '),
          value: group,
        })),
      ]
    },
  },
  watch: {
    'form.name'(newVal) {
      if (!this.form.slug && newVal) {
        this.form.slug = newVal.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')
      }
    },
  },
  methods: {
    submit() {
      this.form.post('/permissions')
    },
  },
}
</script>

