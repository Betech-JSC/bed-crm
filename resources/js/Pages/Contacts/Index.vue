<template>
  <div>
    <Head :title="t('common.contacts')" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('common.contacts') }}</h1>
        <p class="page-subtitle">{{ contacts.total || 0 }} {{ t('common.results') }}</p>
      </div>
      <Link href="/contacts/create">
        <Button :label="t('common.create_contact')" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <span class="p-input-icon-left w-full">
          <i class="pi pi-search" />
          <InputText v-model="form.search" :placeholder="t('common.search_contacts')" class="w-full" @input="handleSearch" />
        </span>
      </div>
      <Select
        v-model="form.trashed"
        :options="trashedOptions"
        optionLabel="label"
        optionValue="value"
        class="filter-select"
        @change="handleFilter"
      />
      <Button :label="t('common.reset_filters')" icon="pi pi-refresh" severity="secondary" text size="small" @click="reset" />
    </div>

    <!-- Contacts Table -->
    <div class="data-card">
      <DataTable
        :value="contacts.data"
        :paginator="false"
        :rows="15"
        responsiveLayout="scroll"
        class="p-datatable-sm"
        :rowHover="true"
      >
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-users" />
            <span>{{ t('common.no_contacts') }}</span>
          </div>
        </template>

        <Column field="name" :header="t('common.name')" sortable>
          <template #body="{ data }">
            <Link :href="`/contacts/${data.id}/edit`" class="contact-name-link">
              <div class="contact-avatar">{{ initials(data.name) }}</div>
              <div class="contact-name-info">
                <span class="contact-name-text">{{ data.name }}</span>
                <span v-if="data.email" class="contact-email-sub">{{ data.email }}</span>
              </div>
              <i v-if="data.deleted_at" class="pi pi-trash deleted-icon" />
            </Link>
          </template>
        </Column>

        <Column :header="t('common.organization')">
          <template #body="{ data }">
            <div v-if="data.organization" class="org-cell">
              <i class="pi pi-building" />
              <span>{{ data.organization.name }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="phone" :header="t('common.phone')">
          <template #body="{ data }">
            <span v-if="data.phone" class="phone-cell">
              <i class="pi pi-phone" />{{ data.phone }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="city" :header="t('common.city')">
          <template #body="{ data }">
            <span v-if="data.city" class="city-cell">
              <i class="pi pi-map-marker" />{{ data.city }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column style="width: 60px">
          <template #body="{ data }">
            <Link :href="`/contacts/${data.id}/edit`">
              <Button icon="pi pi-chevron-right" text rounded size="small" />
            </Link>
          </template>
        </Column>
      </DataTable>

      <div v-if="contacts.total > 0" class="pagination-wrapper">
        <span class="pagination-info">
          {{ t('common.showing') }} {{ contacts.from }}–{{ contacts.to }} {{ t('common.of') }} {{ contacts.total }}
        </span>
        <Paginator
          :first="(contacts.current_page - 1) * contacts.per_page"
          :rows="contacts.per_page"
          :totalRecords="contacts.total"
          @page="onPageChange"
          template="PrevPageLink PageLinks NextPageLink"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, DataTable, Column, Button, InputText, Select, Paginator },
  layout: Layout,
  props: {
    filters: Object,
    contacts: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
      trashedOptions: [
        { label: this.t('common.active_only'), value: null },
        { label: this.t('common.with_trashed'), value: 'with' },
        { label: this.t('common.only_trashed'), value: 'only' },
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
    initials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
    },
    onPageChange(event) {
      const page = event.page + 1
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('page', page)
      router.visit(currentUrl.pathname + currentUrl.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

.filter-bar {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.85rem 1rem; background: white; border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem;
}
.filter-search { flex: 1; min-width: 220px; }
.filter-select { min-width: 150px; }

.data-card {
  background: white; border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden;
}

.contact-name-link {
  display: flex; align-items: center; gap: 0.65rem;
  text-decoration: none; color: inherit;
}
.contact-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white; font-size: 0.68rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.contact-name-info { display: flex; flex-direction: column; }
.contact-name-text { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.contact-name-link:hover .contact-name-text { color: #10b981; }
.contact-email-sub { font-size: 0.72rem; color: #94a3b8; }
.deleted-icon { font-size: 0.65rem; color: #cbd5e1; margin-left: auto; }

.org-cell { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; color: #334155; }
.org-cell i { font-size: 0.72rem; color: #94a3b8; }

.phone-cell, .city-cell {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.82rem; color: #475569;
}
.phone-cell i, .city-cell i { font-size: 0.68rem; color: #94a3b8; }

.pagination-wrapper {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1.25rem; border-top: 1px solid #f1f5f9;
}
.pagination-info { font-size: 0.78rem; color: #94a3b8; }

.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 3rem; color: #94a3b8;
}
.empty-state i { font-size: 2rem; }
.text-muted { color: #cbd5e1; font-size: 0.82rem; }
</style>
