<template>
  <div>
    <Head :title="contentItem.title || 'Content Item'" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #title>Content</template>
          <template #content>
            <div class="space-y-4">
              <div>
                <h3 class="text-lg font-semibold mb-2">{{ contentItem.title || 'Untitled' }}</h3>
                <div class="flex items-center gap-2 mb-4">
                  <Tag :value="contentItem.type" severity="info" />
                  <Tag :value="contentItem.status" :severity="getStatusSeverity(contentItem.status)" />
                  <Badge v-if="contentItem.ai_model" :value="contentItem.ai_model" severity="secondary" />
                </div>
              </div>

              <div class="prose max-w-none">
                <p class="whitespace-pre-wrap text-gray-700">{{ contentItem.content }}</p>
              </div>

              <div v-if="contentItem.tags && contentItem.tags.length" class="flex flex-wrap gap-2">
                <Tag v-for="tag in contentItem.tags" :key="tag" :value="tag" severity="info" />
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="contentItem.ai_metadata">
          <template #title>AI Generation Details</template>
          <template #content>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Model:</span>
                <span class="font-medium">{{ contentItem.ai_metadata.model || '-' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Tokens Used:</span>
                <span class="font-medium">{{ contentItem.ai_metadata.tokens_used || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Service:</span>
                <span class="font-medium">{{ contentItem.ai_metadata.service || '-' }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <template #title>Actions</template>
          <template #content>
            <div class="space-y-3">
              <Link :href="`/content-items/${contentItem.id}/edit`">
                <Button label="Edit" icon="pi pi-pencil" class="w-full" outlined />
              </Link>

              <Button
                label="Generate Variations"
                icon="pi pi-copy"
                class="w-full"
                severity="secondary"
                outlined
                @click="generateVariations"
              />

              <Divider />

              <h4 class="font-semibold mb-2">Optimize for Platform</h4>
              <Select
                v-model="selectedPlatform"
                :options="platformOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select platform"
                class="mb-2"
              />
              <Button
                label="Optimize"
                icon="pi pi-refresh"
                class="w-full"
                :disabled="!selectedPlatform"
                @click="optimize"
              />

              <Divider />

              <h4 class="font-semibold mb-2">Post to Social Media</h4>
              <MultiSelect
                v-model="selectedAccounts"
                :options="socialAccounts"
                optionLabel="name"
                optionValue="id"
                placeholder="Select accounts"
                class="mb-2"
              />
              <Button
                label="Create Post"
                icon="pi pi-send"
                class="w-full"
                :disabled="!selectedAccounts || selectedAccounts.length === 0"
                @click="createPost"
              />
            </div>
          </template>
        </Card>

        <Card>
          <template #title>Information</template>
          <template #content>
            <div class="space-y-2 text-sm">
              <div>
                <span class="text-gray-600">Created by:</span>
                <span class="ml-2 font-medium">{{ contentItem.creator?.name || '-' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Template:</span>
                <span class="ml-2 font-medium">{{ contentItem.template?.name || '-' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Usage count:</span>
                <span class="ml-2 font-medium">{{ contentItem.usage_count }}</span>
              </div>
              <div>
                <span class="text-gray-600">Created:</span>
                <span class="ml-2 font-medium">{{ contentItem.created_at }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Divider from 'primevue/divider'
import Breadcrumb from 'primevue/breadcrumb'
import { useForm } from '@inertiajs/vue3'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Badge,
    Button,
    Select,
    MultiSelect,
    Divider,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    contentItem: Object,
    socialAccounts: Array,
  },
  data() {
    return {
      selectedPlatform: null,
      selectedAccounts: [],
      platformOptions: [
        { label: 'LinkedIn', value: 'linkedin' },
        { label: 'Twitter', value: 'twitter' },
        { label: 'Facebook', value: 'facebook' },
        { label: 'Instagram', value: 'instagram' },
      ],
      breadcrumbItems: [
        { label: 'Content Items', route: '/content-items' },
        { label: this.contentItem.title || 'Content' },
      ],
      variationsForm: useForm({
        count: 3,
      }),
    }
  },
  methods: {
    getStatusSeverity(status) {
      const severities = {
        draft: 'secondary',
        approved: 'success',
        published: 'info',
        archived: 'warning',
      }
      return severities[status] || 'secondary'
    },
    generateVariations() {
      if (confirm('Generate 3 variations of this content?')) {
        this.variationsForm.post(`/content-items/${this.contentItem.id}/generate-variations`)
      }
    },
    optimize() {
      router.post(`/content-items/${this.contentItem.id}/optimize`, {
        platform: this.selectedPlatform,
      })
    },
    createPost() {
      router.visit('/social-posts/create', {
        data: {
          content_item_id: this.contentItem.id,
          social_account_ids: this.selectedAccounts,
        },
      })
    },
  },
}
</script>

