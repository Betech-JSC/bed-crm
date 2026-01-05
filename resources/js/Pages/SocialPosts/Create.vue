<template>
  <div>
    <Head title="Create Social Post" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Social Post</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Content Item <span class="text-red-500">*</span></label>
              <Select
                v-model="form.content_item_id"
                :options="contentItems"
                optionLabel="title"
                optionValue="id"
                placeholder="Select content to post"
                :class="{ 'p-invalid': form.errors.content_item_id }"
              >
                <template #option="{ option }">
                  <div>
                    <div class="font-medium">{{ option.title || 'Untitled' }}</div>
                    <div class="text-sm text-gray-500">{{ option.content }}</div>
                  </div>
                </template>
              </Select>
              <small v-if="form.errors.content_item_id" class="p-error">{{ form.errors.content_item_id }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Social Accounts <span class="text-red-500">*</span></label>
              <MultiSelect
                v-model="form.social_account_ids"
                :options="socialAccounts"
                optionLabel="name"
                optionValue="id"
                placeholder="Select accounts to post to"
                :class="{ 'p-invalid': form.errors.social_account_ids }"
                display="chip"
              >
                <template #option="{ option }">
                  <div class="flex items-center gap-2">
                    <Tag :value="option.platform.toUpperCase()" severity="info" />
                    <span>{{ option.name }}</span>
                  </div>
                </template>
              </MultiSelect>
              <small v-if="form.errors.social_account_ids" class="p-error">{{ form.errors.social_account_ids }}</small>
              <small class="text-gray-500 mt-1">Select one or more accounts to post to simultaneously</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Content Override (Optional)</label>
              <Textarea
                v-model="form.content"
                rows="4"
                placeholder="Leave empty to use content from selected content item"
                :class="{ 'p-invalid': form.errors.content }"
              />
              <small v-if="form.errors.content" class="p-error">{{ form.errors.content }}</small>
              <small class="text-gray-500 mt-1">Override the content if you want to customize it for this post</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Schedule Post (Optional)</label>
              <Calendar
                v-model="scheduledDate"
                showTime
                hourFormat="24"
                :minDate="new Date()"
                placeholder="Select date & time"
                class="w-full"
              />
              <small class="text-gray-500 mt-1">Leave empty to post immediately</small>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/social-posts">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Post" icon="pi pi-send" :loading="form.processing" type="submit" />
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
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Textarea,
    Select,
    MultiSelect,
    Calendar,
    Tag,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    contentItems: Array,
    socialAccounts: Array,
  },
  data() {
    return {
      form: useForm({
        content_item_id: null,
        social_account_ids: [],
        content: '',
        scheduled_at: null,
      }),
      scheduledDate: null,
      breadcrumbItems: [
        { label: 'Social Posts', route: '/social-posts' },
        { label: 'Create' },
      ],
    }
  },
  watch: {
    scheduledDate(newVal) {
      this.form.scheduled_at = newVal ? newVal.toISOString() : null
    },
  },
  methods: {
    store() {
      this.form.post('/social-posts')
    },
  },
}
</script>



