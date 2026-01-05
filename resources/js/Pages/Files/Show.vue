<template>
  <div>
    <Head :title="file.name" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #title>File Details</template>
          <template #content>
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <i :class="file.icon" class="text-5xl text-primary-600"></i>
                <div class="flex-1">
                  <h3 class="text-xl font-semibold">{{ file.name }}</h3>
                  <div class="flex items-center gap-2 mt-2">
                    <Tag :value="file.category" severity="info" />
                    <Tag v-if="file.type" :value="file.type" severity="secondary" />
                    <Tag :value="file.size" severity="success" />
                  </div>
                </div>
              </div>

              <Divider />

              <div v-if="file.description" class="p-4 bg-gray-50 rounded">
                <h4 class="font-semibold mb-2">Description</h4>
                <p class="text-gray-700">{{ file.description }}</p>
              </div>

              <div v-if="file.metadata && Object.keys(file.metadata).length" class="p-4 bg-gray-50 rounded">
                <h4 class="font-semibold mb-2">Metadata</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <div v-for="(value, key) in file.metadata" :key="key">
                    <span class="text-gray-600">{{ key }}:</span>
                    <span class="ml-2 font-medium">{{ value }}</span>
                  </div>
                </div>
              </div>

              <!-- Image Preview -->
              <div v-if="file.category === 'image'" class="mt-4">
                <img :src="`/files/${file.id}/preview`" :alt="file.name" class="max-w-full rounded-lg border" />
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
              <Link :href="`/files/${file.id}/download`" target="_blank">
                <Button label="Download" icon="pi pi-download" class="w-full" />
              </Link>

              <Link v-if="file.category === 'image' || file.mime_type === 'application/pdf'" :href="`/files/${file.id}/preview`" target="_blank">
                <Button label="Preview" icon="pi pi-eye" class="w-full" severity="secondary" outlined />
              </Link>

              <Link :href="`/files/${file.id}/edit`">
                <Button label="Edit" icon="pi pi-pencil" class="w-full" severity="secondary" outlined />
              </Link>

              <Button
                label="Delete"
                icon="pi pi-trash"
                class="w-full"
                severity="danger"
                outlined
                @click="deleteFile"
              />
            </div>
          </template>
        </Card>

        <Card>
          <template #title>Information</template>
          <template #content>
            <div class="space-y-2 text-sm">
              <div>
                <span class="text-gray-600">MIME Type:</span>
                <span class="ml-2 font-medium">{{ file.mime_type }}</span>
              </div>
              <div>
                <span class="text-gray-600">Extension:</span>
                <span class="ml-2 font-medium">{{ file.extension.toUpperCase() }}</span>
              </div>
              <div>
                <span class="text-gray-600">Size:</span>
                <span class="ml-2 font-medium">{{ file.size }}</span>
              </div>
              <div>
                <span class="text-gray-600">Downloads:</span>
                <span class="ml-2 font-medium">{{ file.download_count }}</span>
              </div>
              <div v-if="file.last_downloaded_at">
                <span class="text-gray-600">Last Downloaded:</span>
                <span class="ml-2 font-medium">{{ file.last_downloaded_at }}</span>
              </div>
              <div>
                <span class="text-gray-600">Access Level:</span>
                <Tag :value="file.access_level" :severity="file.is_public ? 'success' : 'secondary'" class="ml-2" />
              </div>
              <div>
                <span class="text-gray-600">Uploaded by:</span>
                <span class="ml-2 font-medium">{{ file.uploader?.name || '-' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Uploaded:</span>
                <span class="ml-2 font-medium">{{ file.created_at }}</span>
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
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Button,
    Divider,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    file: Object,
  },
  data() {
    return {
      breadcrumbItems: [
        { label: 'Files', route: '/files' },
        { label: this.file.name },
      ],
    }
  },
  methods: {
    deleteFile() {
      if (confirm('Are you sure you want to delete this file?')) {
        router.delete(`/files/${this.file.id}`)
      }
    },
  },
}
</script>



