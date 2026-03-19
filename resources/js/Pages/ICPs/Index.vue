<template>
  <div>
    <Head title="ICP Profiles" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.icp_profiles') }}</h1>
        <p class="mt-1 text-gray-600">Manage your Ideal Customer Profiles</p>
      </div>
      <Link href="/icps/create">
        <Button label="Create ICP" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="icps"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No ICP profiles found. Create your first ICP profile to start scoring leads.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/icps/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="description" header="Description">
            <template #body="{ data }">
              {{ data.description || '-' }}
            </template>
          </Column>

          <Column field="min_score" header="Min Score">
            <template #body="{ data }">
              <Tag :value="`${data.min_score}/100`" severity="info" />
            </template>
          </Column>

          <Column field="leads_count" header="Matched Leads" sortable>
            <template #body="{ data }">
              {{ data.leads_count || 0 }}
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/icps/${data.id}/edit`">
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
import { useTranslation } from '@/composables/useTranslation'

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
    icps: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
}
</script>

