<template>
  <div>
    <Head title="Content Items" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Content Items</h1>
        <p class="mt-1 text-gray-600">Manage your AI-generated content</p>
      </div>
      <Link href="/content-items/create">
        <Button label="Generate Content" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="contentItems.data"
          :paginator="true"
          :rows="contentItems.per_page"
          :totalRecords="contentItems.total"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No content items found. Generate your first content using a template.</div>
          </template>

          <Column field="title" header="Title" sortable>
            <template #body="{ data }">
              <Link :href="`/content-items/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.title || 'Untitled' }}
              </Link>
            </template>
          </Column>

          <Column field="type" header="Type">
            <template #body="{ data }">
              <Tag :value="data.type" severity="info" />
            </template>
          </Column>

          <Column field="content" header="Content">
            <template #body="{ data }">
              <p class="text-sm text-gray-600">{{ data.content }}</p>
            </template>
          </Column>

          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="ai_model" header="AI Model">
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.ai_model || '-' }}</span>
            </template>
          </Column>

          <Column field="usage_count" header="Used" sortable>
            <template #body="{ data }">
              <Badge :value="data.usage_count" severity="info" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/content-items/${data.id}`">
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
import Badge from 'primevue/badge'
import Button from 'primevue/button'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Tag,
    Badge,
    Button,
  },
  layout: Layout,
  props: {
    contentItems: Object,
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
  },
}
</script>



