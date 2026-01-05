<template>
  <div>
    <Head title="Content Templates" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Content Templates</h1>
        <p class="mt-1 text-gray-600">Manage AI content generation templates</p>
      </div>
      <Link href="/content-templates/create">
        <Button label="Create Template" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="templates"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No templates found. Create your first template to start generating content.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/content-templates/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="category" header="Category">
            <template #body="{ data }">
              <Tag v-if="data.category" :value="data.category" severity="info" />
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="description" header="Description">
            <template #body="{ data }">
              <p class="text-sm text-gray-600 line-clamp-2">{{ data.description || '-' }}</p>
            </template>
          </Column>

          <Column field="usage_count" header="Usage" sortable>
            <template #body="{ data }">
              <Badge :value="data.usage_count" severity="info" />
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/content-templates/${data.id}/edit`">
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
    templates: Array,
  },
}
</script>



