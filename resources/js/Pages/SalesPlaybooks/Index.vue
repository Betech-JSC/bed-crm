<template>
  <div>
    <Head title="Sales Playbooks" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Sales Playbooks</h1>
        <p class="mt-1 text-gray-600">Manage your sales strategies and talking points</p>
      </div>
      <Link href="/sales-playbooks/create">
        <Button label="Create Playbook" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="playbooks"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No playbooks found. Create your first sales playbook to get started.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/sales-playbooks/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="description" header="Description">
            <template #body="{ data }">
              <p class="text-sm text-gray-600 line-clamp-2">{{ data.description || '-' }}</p>
            </template>
          </Column>

          <Column header="Industries">
            <template #body="{ data }">
              <div v-if="data.industries && data.industries.length > 0" class="flex flex-wrap gap-1">
                <Tag v-for="industry in data.industries.slice(0, 3)" :key="industry" :value="industry" severity="info" />
                <Badge v-if="data.industries.length > 3" :value="`+${data.industries.length - 3}`" />
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column header="Deal Stages">
            <template #body="{ data }">
              <div v-if="data.deal_stages && data.deal_stages.length > 0" class="flex flex-wrap gap-1">
                <Tag v-for="stage in data.deal_stages" :key="stage" :value="stage" severity="secondary" />
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="priority" header="Priority" sortable>
            <template #body="{ data }">
              <Badge :value="data.priority" :severity="data.priority >= 50 ? 'success' : 'info'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/sales-playbooks/${data.id}`">
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
    playbooks: Array,
  },
}
</script>

