<template>
  <div>
    <Head title="KPI Definitions" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('common.kpi_definitions') }}</h1>
        <p class="page-subtitle">{{ kpis.length }} KPIs defined</p>
      </div>
      <div class="header-actions">
        <Link href="/hr"><Button label="Dashboard" icon="pi pi-chart-line" severity="secondary" text /></Link>
        <Button label="Add KPI" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <!-- KPI Cards Grid -->
    <div class="kpi-grid">
      <div v-for="kpi in kpis" :key="kpi.id" class="kpi-card" :class="{ 'kpi-inactive': !kpi.is_active }">
        <div class="kpi-top">
          <span class="cat-badge" :class="`cat-${kpi.category}`">{{ categories[kpi.category] || kpi.category }}</span>
          <div class="kpi-actions">
            <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="editKpi(kpi)" />
            <Button icon="pi pi-trash" severity="danger" text size="small" @click="deleteKpi(kpi)" />
          </div>
        </div>
        <h3 class="kpi-name">{{ kpi.name }}</h3>
        <p v-if="kpi.description" class="kpi-desc">{{ kpi.description }}</p>
        <div class="kpi-meta">
          <span class="meta-tag"><i class="pi pi-chart-bar" /> {{ units[kpi.unit] || kpi.unit }}</span>
          <span class="meta-tag"><i class="pi pi-calendar" /> {{ periods[kpi.period] || kpi.period }}</span>
          <span class="meta-tag"><i class="pi pi-flag" /> Target: {{ formatMetaValue(kpi.target_value, kpi.unit) }}</span>
        </div>
        <div class="kpi-footer">
          <span class="direction-tag" :class="kpi.higher_is_better ? 'dir-up' : 'dir-down'">
            <i :class="kpi.higher_is_better ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
            {{ kpi.higher_is_better ? 'Higher is better' : 'Lower is better' }}
          </span>
          <span v-if="!kpi.is_active" class="inactive-badge">{{ t('common.inactive') }}</span>
        </div>
      </div>
    </div>

    <div v-if="kpis.length === 0" class="empty-state">
      <i class="pi pi-chart-bar" />
      <span>No KPI definitions yet. Add your first KPI to start tracking performance.</span>
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit KPI' : 'Create KPI'" :modal="true" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="form-group">
          <label>Name *</label>
          <InputText v-model="kpiForm.name" placeholder="e.g. Monthly Revenue Target" class="w-full" />
        </div>
        <div class="form-group">
          <label>{{ t('common.description') }}</label>
          <Textarea v-model="kpiForm.description" placeholder="Brief description of this KPI..." rows="2" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category *</label>
            <Select v-model="kpiForm.category" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Select category" class="w-full" />
          </div>
          <div class="form-group">
            <label>Unit *</label>
            <Select v-model="kpiForm.unit" :options="unitOptions" optionLabel="label" optionValue="value" placeholder="Select unit" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Period *</label>
            <Select v-model="kpiForm.period" :options="periodOptions" optionLabel="label" optionValue="value" placeholder="Select period" class="w-full" />
          </div>
          <div class="form-group">
            <label>Target Value</label>
            <InputText v-model="kpiForm.target_value" type="number" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group form-check">
            <label><input type="checkbox" v-model="kpiForm.higher_is_better" /> Higher is better</label>
          </div>
          <div class="form-group form-check">
            <label><input type="checkbox" v-model="kpiForm.is_active" /> Active</label>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showDialog = false" />
        <Button :label="editing ? 'Update' : 'Create'" icon="pi pi-check" @click="submitKpi" :loading="submitting" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, InputText, Textarea, Select, Dialog },
  layout: Layout,
  props: {
    kpis: Array,
    units: Object,
    periods: Object,
    categories: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      showDialog: false,
      editing: null,
      submitting: false,
      kpiForm: this.freshForm(),
    }
  },
  computed: {
    categoryOptions() {
      return Object.entries(this.categories).map(([v, l]) => ({ label: l, value: v }))
    },
    unitOptions() {
      return Object.entries(this.units).map(([v, l]) => ({ label: l, value: v }))
    },
    periodOptions() {
      return Object.entries(this.periods).map(([v, l]) => ({ label: l, value: v }))
    },
  },
  methods: {
    freshForm() {
      return {
        name: '',
        description: '',
        unit: 'number',
        period: 'monthly',
        category: 'sales',
        target_value: 0,
        higher_is_better: true,
        is_active: true,
      }
    },
    openCreate() {
      this.editing = null
      this.kpiForm = this.freshForm()
      this.showDialog = true
    },
    editKpi(kpi) {
      this.editing = kpi
      this.kpiForm = { ...kpi }
      this.showDialog = true
    },
    deleteKpi(kpi) {
      if (confirm(`Delete KPI "${kpi.name}"? This will also remove all recorded values.`)) {
        router.delete(`/hr/kpi-definitions/${kpi.id}`)
      }
    },
    submitKpi() {
      this.submitting = true
      if (this.editing) {
        router.put(`/hr/kpi-definitions/${this.editing.id}`, this.kpiForm, {
          onSuccess: () => { this.showDialog = false; this.editing = null },
          onFinish: () => { this.submitting = false },
        })
      } else {
        router.post('/hr/kpi-definitions', this.kpiForm, {
          onSuccess: () => { this.showDialog = false; this.kpiForm = this.freshForm() },
          onFinish: () => { this.submitting = false },
        })
      }
    },
    formatMetaValue(val, unit) {
      if (unit === 'currency') return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(val)
      if (unit === 'percentage') return val + '%'
      if (unit === 'hours') return val + 'h'
      return val?.toLocaleString() ?? '0'
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

.kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.85rem; }
.kpi-card { background: white; border-radius: 14px; padding: 1.1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; transition: all 0.2s; }
.kpi-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
.kpi-inactive { opacity: 0.6; }
.kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.kpi-actions { display: flex; gap: 0.1rem; }
.kpi-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.kpi-desc { font-size: 0.75rem; color: #64748b; margin: 0 0 0.5rem; line-height: 1.4; }
.kpi-meta { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.5rem; }
.meta-tag { font-size: 0.68rem; color: #64748b; display: flex; align-items: center; gap: 0.2rem; background: #f8fafc; padding: 0.15rem 0.4rem; border-radius: 5px; }
.meta-tag i { font-size: 0.6rem; }
.kpi-footer { display: flex; justify-content: space-between; align-items: center; }
.direction-tag { font-size: 0.65rem; font-weight: 600; display: flex; align-items: center; gap: 0.2rem; }
.dir-up { color: #059669; } .dir-down { color: #dc2626; }
.inactive-badge { font-size: 0.6rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 0.1rem 0.35rem; border-radius: 4px; }

.cat-badge { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.cat-sales { background: #dbeafe; color: #2563eb; }
.cat-support { background: #e0e7ff; color: #4f46e5; }
.cat-productivity { background: #d1fae5; color: #059669; }
.cat-quality { background: #fef3c7; color: #d97706; }
.cat-custom { background: #f1f5f9; color: #475569; }

.form-grid { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.form-check label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; }

.empty-state { text-align: center; padding: 3rem; color: #94a3b8; } .empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
</style>
