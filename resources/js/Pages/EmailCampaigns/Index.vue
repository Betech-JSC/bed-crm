<template>
  <div>
    <Head title="Email Campaigns" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.campaigns') }}</h1>
        <p class="mt-1 text-gray-600">Create and manage email marketing campaigns</p>
      </div>
      <Link href="/email-campaigns/create">
        <Button label="Create Campaign" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="campaigns"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No campaigns found. Create your first campaign.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/email-campaigns/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="total_recipients" header="Recipients">
            <template #body="{ data }">
              <span class="text-sm">{{ data.total_recipients || 0 }}</span>
            </template>
          </Column>

          <Column field="open_rate" header="Open Rate">
            <template #body="{ data }">
              <span class="text-sm">{{ data.open_rate ? data.open_rate.toFixed(1) + '%' : '0%' }}</span>
            </template>
          </Column>

          <Column field="click_rate" header="Click Rate">
            <template #body="{ data }">
              <span class="text-sm">{{ data.click_rate ? data.click_rate.toFixed(1) + '%' : '0%' }}</span>
            </template>
          </Column>

          <Column field="sent_at" header="Sent" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.sent_at || '-' }}</span>
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
import { useTranslation } from '@/composables/useTranslation'

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
    campaigns: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  methods: {
    getStatusSeverity(status) {
      const severityMap = {
        draft: 'secondary',
        scheduled: 'info',
        sending: 'warning',
        sent: 'success',
        paused: 'warning',
        cancelled: 'danger',
      }
      return severityMap[status] || 'secondary'
    },
  },
}
</script>

