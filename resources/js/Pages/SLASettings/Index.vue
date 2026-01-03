<template>
  <div>
    <Head title="SLA Settings" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">SLA Settings</h1>
        <p class="mt-1 text-gray-600">Manage your Service Level Agreement settings</p>
      </div>
      <Link href="/sla-settings/create">
        <Button label="Create SLA Setting" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="slaSettings"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No SLA settings found. Create your first SLA setting to start tracking response times.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/sla-settings/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                  {{ data.name }}
                </Link>
                <Tag v-if="data.is_default" value="Default" severity="success" />
              </div>
            </template>
          </Column>

          <Column field="description" header="Description">
            <template #body="{ data }">
              <p class="text-sm text-gray-600 line-clamp-2">{{ data.description || '-' }}</p>
            </template>
          </Column>

          <Column header="Thresholds">
            <template #body="{ data }">
              <div class="text-sm">
                <div>Response: {{ formatMinutes(data.first_response_threshold) }}</div>
                <div class="text-gray-500">Warning: {{ formatMinutes(data.warning_threshold) }}</div>
              </div>
            </template>
          </Column>

          <Column header="Leads">
            <template #body="{ data }">
              <div class="text-sm">
                <div>Pending: <Badge :value="data.pending_leads_count" severity="warning" /></div>
                <div>Breached: <Badge :value="data.breached_leads_count" severity="danger" /></div>
              </div>
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/sla-settings/${data.id}/edit`">
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
    slaSettings: Array,
  },
  methods: {
    formatMinutes(minutes) {
      if (minutes < 60) {
        return `${minutes} min`
      }
      const hours = Math.floor(minutes / 60)
      const mins = minutes % 60
      return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
    },
  },
}
</script>

