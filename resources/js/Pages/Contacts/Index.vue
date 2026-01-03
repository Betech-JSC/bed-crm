<template>
  <div>
    <Head title="Contacts" />
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-3xl font-bold">Contacts</h1>
      <Link href="/contacts/create">
        <Button label="Create Contact" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Filters Card -->
    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Search</label>
            <InputText v-model="form.search" placeholder="Search contacts..." @input="handleSearch" />
          </div>
          <div class="flex flex-col">
            <label class="mb-2 text-sm font-medium">Trashed</label>
            <Select
              v-model="form.trashed"
              :options="trashedOptions"
              optionLabel="label"
              optionValue="value"
              @change="handleFilter"
            />
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
          <Button label="Reset Filters" icon="pi pi-refresh" severity="secondary" text @click="reset" />
          <div class="text-sm text-gray-600">
            Showing {{ contacts.data.length }} of {{ contacts.total || 0 }} contacts
          </div>
        </div>
      </template>
    </Card>

    <!-- Contacts Table -->
    <Card>
      <template #content>
        <DataTable
          :value="contacts.data"
          :paginator="false"
          :rows="10"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No contacts found.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/contacts/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
                <i v-if="data.deleted_at" class="pi pi-trash ml-2 text-xs text-gray-400" />
              </Link>
            </template>
          </Column>

          <Column header="Organization">
            <template #body="{ data }">
              <span v-if="data.organization">{{ data.organization.name }}</span>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="city" header="City">
            <template #body="{ data }">
              {{ data.city || '-' }}
            </template>
          </Column>

          <Column field="phone" header="Phone">
            <template #body="{ data }">
              {{ data.phone || '-' }}
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/contacts/${data.id}/edit`">
                <Button icon="pi pi-arrow-right" text rounded />
              </Link>
            </template>
          </Column>
        </DataTable>

        <div class="mt-4">
          <Paginator
            v-if="contacts.total > 0"
            :first="(contacts.current_page - 1) * contacts.per_page"
            :rows="contacts.per_page"
            :totalRecords="contacts.total"
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
    Button,
    InputText,
    Select,
    Paginator,
  },
  layout: Layout,
  props: {
    filters: Object,
    contacts: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
      trashedOptions: [
        { label: 'Active Only', value: null },
        { label: 'With Trashed', value: 'with' },
        { label: 'Only Trashed', value: 'only' },
      ],
    }
  },
  methods: {
    handleSearch: throttle(function () {
      this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true })
    }, 300),
    handleFilter() {
      this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
      this.$inertia.get('/contacts', {}, { preserveState: true })
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
