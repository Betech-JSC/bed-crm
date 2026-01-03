<template>
  <div>
    <Head title="Leads" />
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-3xl font-bold">Leads</h1>
      <Link href="/leads/create">
        <Button label="Create Lead" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Filters Card -->
    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Search</label>
            <InputText v-model="form.search" placeholder="Search leads..." @input="handleSearch" />
          </div>
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Status</label>
            <Select
              v-model="form.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Statuses"
              @change="handleFilter"
            />
          </div>
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Source</label>
            <Select
              v-model="form.source"
              :options="sourceOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Sources"
              @change="handleFilter"
            />
          </div>
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Assigned To</label>
            <Select
              v-model="form.assigned_to"
              :options="assignedOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Users"
              @change="handleFilter"
            />
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
          <Button label="Reset Filters" icon="pi pi-refresh" severity="secondary" text @click="reset" />
          <div class="text-sm text-gray-600">
            Showing {{ leads.data.length }} of {{ leads.total || 0 }} leads
          </div>
        </div>
      </template>
    </Card>

    <!-- Leads Table -->
    <Card>
      <template #content>
        <DataTable
          :value="leads.data"
          :paginator="false"
          :rows="15"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No leads found.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/leads/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
                <i v-if="data.deleted_at" class="pi pi-trash ml-2 text-xs text-gray-400" />
              </Link>
            </template>
          </Column>

          <Column field="company" header="Company">
            <template #body="{ data }">
              {{ data.company || '-' }}
            </template>
          </Column>

          <Column header="Contact">
            <template #body="{ data }">
              <div class="flex flex-col">
                <span v-if="data.email" class="text-sm">{{ data.email }}</span>
                <span v-if="data.phone" class="text-xs text-gray-500">{{ data.phone }}</span>
              </div>
            </template>
          </Column>

          <Column field="source" header="Source">
            <template #body="{ data }">
              <Tag v-if="data.source" :value="sources[data.source] || data.source" severity="info" />
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="statuses[data.status] || data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="score" header="Score" sortable>
            <template #body="{ data }">
              <div v-if="data.score !== null && data.score !== undefined" class="flex items-center gap-2">
                <Tag
                  :value="data.score"
                  :severity="getScoreSeverity(data.score)"
                />
                <span v-if="data.icp" class="text-xs text-gray-500">{{ data.icp.name }}</span>
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column header="Assigned To">
            <template #body="{ data }">
              <span v-if="data.assigned_user">{{ data.assigned_user.name }}</span>
              <span v-else class="text-gray-400">Unassigned</span>
            </template>
          </Column>

          <Column header="Tags">
            <template #body="{ data }">
              <div v-if="data.tags && data.tags.length > 0" class="flex flex-wrap gap-1">
                <Tag v-for="tag in data.tags" :key="tag" :value="tag" severity="secondary" />
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/leads/${data.id}/edit`">
                <Button icon="pi pi-arrow-right" text rounded />
              </Link>
            </template>
          </Column>
        </DataTable>

        <div class="mt-4">
          <Paginator
            v-if="leads.total > 0"
            :first="(leads.current_page - 1) * leads.per_page"
            :rows="leads.per_page"
            :totalRecords="leads.total"
            @page="onPageChange"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
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
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Tag,
    Button,
    InputText,
    Select,
    Paginator,
  },
  layout: Layout,
  props: {
    filters: Object,
    leads: Object,
    statuses: Object,
    sources: Object,
    salesUsers: Array,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        status: this.filters.status,
        source: this.filters.source,
        assigned_to: this.filters.assigned_to,
        trashed: this.filters.trashed,
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
    sourceOptions() {
      return [
        { label: 'All Sources', value: null },
        ...Object.entries(this.sources).map(([value, label]) => ({ label, value })),
      ]
    },
    assignedOptions() {
      return [
        { label: 'All Users', value: null },
        ...this.salesUsers.map(user => ({ label: user.name, value: user.id })),
      ]
    },
  },
  methods: {
    handleSearch: throttle(function () {
      this.$inertia.get('/leads', pickBy(this.form), { preserveState: true })
    }, 300),
    handleFilter() {
      this.$inertia.get('/leads', pickBy(this.form), { preserveState: true })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
      this.$inertia.get('/leads', {}, { preserveState: true })
    },
    getStatusSeverity(status) {
      const severityMap = {
        new: 'info',
        contacted: 'warning',
        qualified: 'success',
        won: 'success',
        lost: 'danger',
      }
      return severityMap[status] || 'secondary'
    },
    getScoreSeverity(score) {
      if (score >= 80) return 'success'
      if (score >= 60) return 'warning'
      if (score >= 40) return 'info'
      return 'danger'
    },
    onPageChange(event) {
      const page = event.page + 1
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('page', page)
      router.visit(currentUrl.pathname + currentUrl.search, {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
}
</script>
