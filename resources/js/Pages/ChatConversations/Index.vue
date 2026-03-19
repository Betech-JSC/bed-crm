<template>
  <div>
    <Head title="Chat Conversations" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.conversations') }}</h1>
        <p class="mt-1 text-gray-600">View and manage all chat conversations</p>
      </div>
    </div>

    <Card>
      <template #content>
        <div class="mb-4 flex items-center gap-4">
          <div class="flex-1">
            <InputText
              v-model="filters.search"
              placeholder="Search by name, email, or phone..."
              class="w-full"
              @input="search"
            />
          </div>
          <Select
            v-model="filters.widget_id"
            :options="widgetOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Widgets"
            class="w-48"
            @change="search"
          />
          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            class="w-48"
            @change="search"
          />
        </div>

        <DataTable
          :value="conversations.data"
          :paginator="true"
          :rows="conversations.per_page"
          :totalRecords="conversations.total"
          @page="onPage"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No conversations found.</div>
          </template>

          <Column field="visitor_name" header="Visitor" sortable>
            <template #body="{ data }">
              <div>
                <div class="font-medium">{{ data.visitor_name || 'Anonymous' }}</div>
                <div class="text-xs text-gray-500">{{ data.visitor_email || data.visitor_phone || '-' }}</div>
              </div>
            </template>
          </Column>

          <Column field="widget" header="Widget">
            <template #body="{ data }">
              <span class="text-sm">{{ data.widget?.name || '-' }}</span>
            </template>
          </Column>

          <Column header="Linked">
            <template #body="{ data }">
              <div class="flex flex-col gap-1">
                <Link
                  v-if="data.lead"
                  :href="`/leads/${data.lead.id}/edit`"
                  class="text-xs text-primary-600 hover:text-primary-800"
                >
                  Lead: {{ data.lead.name }}
                </Link>
                <Link
                  v-if="data.contact"
                  :href="`/contacts/${data.contact.id}/edit`"
                  class="text-xs text-primary-600 hover:text-primary-800"
                >
                  Contact: {{ data.contact.name }}
                </Link>
                <span v-if="!data.lead && !data.contact" class="text-xs text-gray-400">-</span>
              </div>
            </template>
          </Column>

          <Column field="message_count" header="Messages" sortable>
            <template #body="{ data }">
              <Badge :value="data.message_count" />
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag
                :value="data.status"
                :severity="data.status === 'active' ? 'success' : data.status === 'closed' ? 'secondary' : 'info'"
              />
            </template>
          </Column>

          <Column field="last_message_at" header="Last Message" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.last_message_at || '-' }}</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/chat-conversations/${data.id}`">
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
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    InputText,
    Select,
    Button,
    Tag,
    Badge,
  },
  layout: Layout,
  props: {
    filters: Object,
    conversations: Object,
    widgets: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      widgetOptions: [
        { label: 'All Widgets', value: null },
        ...this.widgets.map(w => ({ label: w.name, value: w.id })),
      ],
      statusOptions: [
        { label: 'All Statuses', value: null },
        { label: 'Active', value: 'active' },
        { label: 'Closed', value: 'closed' },
        { label: 'Archived', value: 'archived' },
      ],
    }
  },
  methods: {
    search() {
      router.get('/chat-conversations', this.filters, {
        preserveState: true,
        preserveScroll: true,
      })
    },
    onPage(event) {
      router.get('/chat-conversations', {
        ...this.filters,
        page: event.page + 1,
      }, {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
}
</script>

