<template>
  <div>
    <Head title="Email Automations" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Email Automations</h1>
        <p class="mt-1 text-gray-600">Automate your email marketing workflows</p>
      </div>
      <Link href="/email-automations/create">
        <Button label="Create Automation" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="automations"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No automations found. Create your first automation.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/email-automations/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="trigger_type" header="Trigger">
            <template #body="{ data }">
              <Tag :value="data.trigger_type" severity="info" />
            </template>
          </Column>

          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="steps_count" header="Steps">
            <template #body="{ data }">
              <span class="text-sm">{{ data.steps_count || 0 }}</span>
            </template>
          </Column>

          <Column field="contacts_processed" header="Processed">
            <template #body="{ data }">
              <span class="text-sm">{{ data.contacts_processed || 0 }}</span>
            </template>
          </Column>

          <Column field="emails_sent" header="Emails Sent">
            <template #body="{ data }">
              <span class="text-sm">{{ data.emails_sent || 0 }}</span>
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
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    DataTable,
    Column,
    Tag,
  },
  layout: Layout,
  props: {
    automations: Array,
  },
  methods: {
    getStatusSeverity(status) {
      const severityMap = {
        draft: 'secondary',
        active: 'success',
        paused: 'warning',
        completed: 'info',
      }
      return severityMap[status] || 'secondary'
    },
  },
}
</script>

