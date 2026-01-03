<template>
  <div>
    <Head :title="`Edit: ${contentItem.title || 'Content'}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit Content</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Title</label>
              <InputText v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
              <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Type</label>
              <Select
                v-model="form.type"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.type }"
              />
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Content <span class="text-red-500">*</span></label>
              <Textarea
                v-model="form.content"
                rows="12"
                :class="{ 'p-invalid': form.errors.content }"
              />
              <small v-if="form.errors.content" class="p-error">{{ form.errors.content }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Status</label>
              <Select
                v-model="form.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.status }"
              />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Tags (comma-separated)</label>
              <InputText v-model="tagsInput" placeholder="tag1, tag2, tag3" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link :href="`/content-items/${contentItem.id}`">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Update Content" icon="pi pi-check" :loading="form.processing" type="submit" />
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
    contentItem: Object,
    types: Object,
    statuses: Object,
  },
  data() {
    return {
      form: useForm({
        title: this.contentItem.title || '',
        type: this.contentItem.type,
        content: this.contentItem.content,
        metadata: this.contentItem.metadata || {},
        status: this.contentItem.status,
        tags: this.contentItem.tags || [],
      }),
      tagsInput: (this.contentItem.tags || []).join(', '),
      typeOptions: Object.entries(this.types).map(([value, label]) => ({ value, label })),
      statusOptions: Object.entries(this.statuses).map(([value, label]) => ({ value, label })),
      breadcrumbItems: [
        { label: 'Content Items', route: '/content-items' },
        { label: this.contentItem.title || 'Content' },
        { label: 'Edit' },
      ],
    }
  },
  methods: {
    update() {
      // Parse tags
      if (this.tagsInput.trim()) {
        this.form.tags = this.tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag)
      } else {
        this.form.tags = []
      }

      this.form.put(`/content-items/${this.contentItem.id}`)
    },
  },
}
</script>

