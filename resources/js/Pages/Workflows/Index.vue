<template>
  <div>
    <Head title="Workflows" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Marketing Automation</h1>
        <p class="mt-1 text-gray-600">Automate your sales and marketing processes</p>
      </div>
      <Link href="/workflows/create">
        <Button label="Create Workflow" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="workflows"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No workflows found. Create your first workflow to automate processes.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/workflows/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="trigger_event" header="Trigger">
            <template #body="{ data }">
              <Tag :value="triggerEvents[data.trigger_event] || data.trigger_event" severity="info" />
            </template>
          </Column>

          <Column field="actions_count" header="Actions">
            <template #body="{ data }">
              {{ data.actions_count || 0 }}
            </template>
          </Column>

          <Column field="execution_count" header="Executions" sortable>
            <template #body="{ data }">
              {{ data.execution_count || 0 }}
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/workflows/${data.id}/edit`">
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
    workflows: Array,
    triggerEvents: Object,
    actionTypes: Object,
  },
}
</script>

