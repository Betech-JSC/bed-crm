<template>
  <div>
    <Head title="Create Email Template" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create Email Template</template>
      <template #content>
        <form @submit.prevent="submit">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Name *</label>
              <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Type *</label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                :class="{ 'p-invalid': form.errors.type }"
              />
              <small v-if="form.errors.type" class="p-error">{{ form.errors.type }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Subject *</label>
              <InputText v-model="form.subject" class="w-full" :class="{ 'p-invalid': form.errors.subject }" />
              <small v-if="form.errors.subject" class="p-error">{{ form.errors.subject }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">HTML Body</label>
              <Textarea v-model="form.body_html" rows="10" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Text Body</label>
              <Textarea v-model="form.body_text" rows="10" class="w-full" />
            </div>

            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
              <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="flex items-center justify-end gap-2">
              <Link href="/email-templates">
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
    types: Array,
    variables: Array,
  },
  data() {
    return {
      form: useForm({
        name: '',
        subject: '',
        body_html: '',
        body_text: '',
        type: 'campaign',
        is_active: true,
      }),
      breadcrumbItems: [
        { label: 'Email Templates', route: '/email-templates' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    typeOptions() {
      return (this.types || []).map(type => ({
        label: type.charAt(0).toUpperCase() + type.slice(1),
        value: type,
      }))
    },
  },
  methods: {
    submit() {
      this.form.post('/email-templates')
    },
  },
}
</script>

