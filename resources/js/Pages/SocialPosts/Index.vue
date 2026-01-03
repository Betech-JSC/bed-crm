<template>
  <div>
    <Head title="Social Posts" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Social Posts</h1>
        <p class="mt-1 text-gray-600">Manage your scheduled and published posts</p>
      </div>
      <Link href="/social-posts/create">
        <Button label="Create Post" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="posts.data"
          :paginator="true"
          :rows="posts.per_page"
          :totalRecords="posts.total"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No posts found. Create your first post to start sharing content.</div>
          </template>

          <Column field="platform" header="Platform" sortable>
            <template #body="{ data }">
              <Tag :value="data.platform.toUpperCase()" :severity="getPlatformSeverity(data.platform)" />
            </template>
          </Column>

          <Column field="content" header="Content">
            <template #body="{ data }">
              <p class="text-sm text-gray-600">{{ data.content }}</p>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="scheduled_at" header="Scheduled" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.scheduled_at || '-' }}</span>
            </template>
          </Column>

          <Column field="posted_at" header="Posted" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.posted_at || '-' }}</span>
            </template>
          </Column>

          <Column field="analytics" header="Engagement">
            <template #body="{ data }">
              <div v-if="data.analytics" class="text-sm">
                <div v-if="data.analytics.likes !== undefined">👍 {{ data.analytics.likes }}</div>
                <div v-if="data.analytics.comments !== undefined">💬 {{ data.analytics.comments }}</div>
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/social-posts/${data.id}`">
                <Button icon="pi pi-arrow-right" text rounded />
              </Link>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Tag,
    Button,
  },
  layout: Layout,
  props: {
    posts: Object,
  },
  methods: {
    getPlatformSeverity(platform) {
      const severities = {
        linkedin: 'info',
        twitter: 'info',
        facebook: 'primary',
        instagram: 'warning',
      }
      return severities[platform] || 'secondary'
    },
    getStatusSeverity(status) {
      const severities = {
        draft: 'secondary',
        scheduled: 'warning',
        posting: 'info',
        published: 'success',
        failed: 'danger',
      }
      return severities[status] || 'secondary'
    },
  },
}
</script>

