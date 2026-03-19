<template>
  <div>
    <Head title="Proposals" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.proposals') }}</h1>
        <p class="mt-1 text-gray-600">Manage your quotations and proposals</p>
      </div>
      <Link href="/proposals/create">
        <Button label="Create Proposal" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">{{ t('common.status') }}</label>
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Statuses"
              @change="handleFilter"
            />
          </div>
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Deal</label>
            <Select
              v-model="filters.deal_id"
              :options="dealOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Deals"
              @change="handleFilter"
            />
          </div>
          <div class="flex items-end">
            <Button label="Reset" icon="pi pi-refresh" severity="secondary" text @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>

    <!-- Proposals Table -->
    <Card>
      <template #content>
        <DataTable
          :value="proposals.data"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No proposals found.</div>
          </template>

          <Column field="title" header="Title" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/proposals/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                  {{ data.title }}
                </Link>
                <Badge v-if="data.version > 1" :value="`v${data.version}`" severity="info" />
              </div>
            </template>
          </Column>

          <Column header="Deal">
            <template #body="{ data }">
              <Link v-if="data.deal" :href="`/deals/${data.deal.id}/edit`" class="text-primary-600 hover:text-primary-800">
                {{ data.deal.title }}
              </Link>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="amount" header="Amount" sortable>
            <template #body="{ data }">
              <span v-if="data.amount">{{ formatCurrency(data.amount) }}</span>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="data.status_label" :severity="data.status_severity" />
            </template>
          </Column>

          <Column header="Tracking">
            <template #body="{ data }">
              <div class="text-xs text-gray-600 space-y-1">
                <div v-if="data.sent_at">
                  <i class="pi pi-send mr-1" /> Sent: {{ formatDate(data.sent_at) }}
                </div>
                <div v-if="data.viewed_at">
                  <i class="pi pi-eye mr-1" /> Viewed: {{ formatDate(data.viewed_at) }} ({{ data.view_count }}x)
                </div>
                <div v-if="data.accepted_at">
                  <i class="pi pi-check mr-1" /> Accepted: {{ formatDate(data.accepted_at) }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Created">
            <template #body="{ data }">
              <div class="text-xs text-gray-600">
                <div>{{ formatDate(data.created_at) }}</div>
                <div v-if="data.creator" class="text-gray-500">by {{ data.creator.name }}</div>
              </div>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/proposals/${data.id}`">
                <Button icon="pi pi-arrow-right" text rounded />
              </Link>
            </template>
          </Column>
        </DataTable>

        <div class="mt-4">
          <Paginator
            v-if="proposals.total > 0"
            :first="(proposals.current_page - 1) * proposals.per_page"
            :rows="proposals.per_page"
            :totalRecords="proposals.total"
            @page="onPageChange"
          />
        </div>
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
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import { useTranslation } from '@/composables/useTranslation'

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
    Select,
    Paginator,
  },
  layout: Layout,
  props: {
    filters: Object,
    proposals: Object,
    statuses: Object,
    deals: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      filters: {
        status: this.filters?.status || null,
        deal_id: this.filters?.deal_id || null,
      },
    }
  },
  computed: {
    statusOptions() {
      return [
        { label: 'All Statuses', value: null },
        ...Object.entries(this.statuses).map(([value, label]) => ({ label, value })),
      ]
    },
    dealOptions() {
      return [
        { label: 'All Deals', value: null },
        ...this.deals.map(deal => ({ label: deal.title, value: deal.id })),
      ]
    },
  },
  methods: {
    handleFilter() {
      router.get('/proposals', this.filters, { preserveState: true })
    },
    resetFilters() {
      this.filters = { status: null, deal_id: null }
      router.get('/proposals', {}, { preserveState: true })
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(value)
    },
    formatDate(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    },
    onPageChange(event) {
      const page = event.page + 1
      router.get('/proposals', { ...this.filters, page }, { preserveState: true })
    },
  },
}
</script>

