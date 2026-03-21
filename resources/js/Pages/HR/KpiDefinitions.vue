<template>
  <div>
    <Head title="KPI Definitions" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-chart-bar" /></div>
        <div>
          <h1 class="page-title">{{ t('common.kpi_definitions') }}</h1>
          <p class="page-subtitle">{{ kpis.length }} KPIs {{ isVi ? 'đã định nghĩa' : 'defined' }}</p>
        </div>
      </div>
      <div class="header-actions">
        <a href="/hr" class="btn-secondary"><i class="pi pi-chart-line" /> Dashboard</a>
        <button class="btn-primary" @click="openCreate"><i class="pi pi-plus" /> {{ isVi ? 'Thêm KPI' : 'Add KPI' }}</button>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div v-for="cat in categoryStats" :key="cat.key" class="stat-card" :class="{ active: filterCat === cat.key }" @click="toggleCat(cat.key)">
        <div class="stat-dot" :style="{ background: cat.color }" />
        <span class="stat-value">{{ cat.count }}</span>
        <span class="stat-label">{{ cat.label }}</span>
      </div>
      <div class="stat-card">
        <div class="stat-dot" style="background: #10b981" />
        <span class="stat-value">{{ activeCount }}</span>
        <span class="stat-label">Active</span>
      </div>
      <div class="stat-card" v-if="inactiveCount > 0">
        <div class="stat-dot" style="background: #ef4444" />
        <span class="stat-value">{{ inactiveCount }}</span>
        <span class="stat-label">Inactive</span>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="searchQuery" :placeholder="isVi ? 'Tìm KPI...' : 'Search KPIs...'" class="search-input" />
      </div>
      <div class="filter-chips">
        <button v-for="cat in categoryStats" :key="cat.key" class="chip" :class="{ active: filterCat === cat.key }" @click="toggleCat(cat.key)">
          <span class="chip-dot" :style="{ background: cat.color }" />
          {{ cat.label }}
        </button>
        <button v-if="filterCat" class="chip clear" @click="filterCat = null"><i class="pi pi-times" /> Clear</button>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="filteredKpis.length === 0 && kpis.length === 0" class="empty-state">
      <div class="empty-icon"><i class="pi pi-chart-bar" /></div>
      <h3>{{ isVi ? 'Chưa có KPI nào' : 'No KPIs yet' }}</h3>
      <p>{{ isVi ? 'Thêm KPI đầu tiên để bắt đầu theo dõi hiệu suất' : 'Add your first KPI to start tracking performance' }}</p>
      <button class="btn-primary" @click="openCreate"><i class="pi pi-plus" /> {{ isVi ? 'Thêm KPI' : 'Add KPI' }}</button>
    </div>

    <div v-else-if="filteredKpis.length === 0" class="empty-state sm">
      <p>{{ isVi ? 'Không tìm thấy KPI' : 'No matching KPIs' }}</p>
    </div>

    <!-- KPI Grid -->
    <div v-else class="kpi-grid">
      <div v-for="kpi in filteredKpis" :key="kpi.id" class="kpi-card" :class="{ inactive: !kpi.is_active }">
        <div class="kpi-accent" :style="{ background: catColor(kpi.category) }" />
        <div class="kpi-body">
          <div class="kpi-top">
            <span class="cat-badge" :style="{ background: catColor(kpi.category) + '15', color: catColor(kpi.category) }">{{ categories[kpi.category] || kpi.category }}</span>
            <div class="kpi-btns">
              <button class="action-btn sm" @click="editKpi(kpi)"><i class="pi pi-pencil" /></button>
              <button class="action-btn sm danger" @click="deleteKpi(kpi)"><i class="pi pi-trash" /></button>
            </div>
          </div>

          <h3 class="kpi-name">{{ kpi.name }}</h3>
          <p v-if="kpi.description" class="kpi-desc">{{ kpi.description }}</p>

          <!-- Meta Tags -->
          <div class="kpi-meta">
            <span class="meta-tag"><i class="pi pi-chart-bar" /> {{ units[kpi.unit] || kpi.unit }}</span>
            <span class="meta-tag"><i class="pi pi-calendar" /> {{ periods[kpi.period] || kpi.period }}</span>
          </div>

          <!-- Target -->
          <div class="kpi-target">
            <div class="target-icon"><i class="pi pi-flag" /></div>
            <div class="target-info">
              <span class="target-label">Target</span>
              <span class="target-value">{{ formatMetaValue(kpi.target_value, kpi.unit) }}</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="kpi-footer">
            <span class="direction-badge" :class="kpi.higher_is_better ? 'dir-up' : 'dir-down'">
              <i :class="kpi.higher_is_better ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
              {{ kpi.higher_is_better ? (isVi ? 'Cao hơn = Tốt hơn' : 'Higher is better') : (isVi ? 'Thấp hơn = Tốt hơn' : 'Lower is better') }}
            </span>
            <span v-if="!kpi.is_active" class="inactive-badge">{{ isVi ? 'Tạm dừng' : 'Inactive' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showDialog" class="modal-overlay" @click.self="showDialog = false">
      <div class="modal-panel">
        <div class="modal-header">
          <h3><i class="pi pi-chart-bar" /> {{ editing ? (isVi ? 'Chỉnh sửa KPI' : 'Edit KPI') : (isVi ? 'Thêm KPI mới' : 'Create KPI') }}</h3>
          <button class="modal-close" @click="showDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ t('common.name') }} <span class="req">*</span></label>
            <input v-model="kpiForm.name" class="form-input" :placeholder="isVi ? 'VD: Doanh thu tháng' : 'e.g. Monthly Revenue'" />
          </div>
          <div class="form-group">
            <label>{{ t('common.description') }}</label>
            <textarea v-model="kpiForm.description" class="form-input" rows="2" :placeholder="isVi ? 'Mô tả ngắn...' : 'Brief description...'" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ isVi ? 'Danh mục' : 'Category' }} <span class="req">*</span></label>
              <select v-model="kpiForm.category" class="form-input">
                <option v-for="(label, val) in categories" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Đơn vị' : 'Unit' }} <span class="req">*</span></label>
              <select v-model="kpiForm.unit" class="form-input">
                <option v-for="(label, val) in units" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ isVi ? 'Chu kỳ' : 'Period' }} <span class="req">*</span></label>
              <select v-model="kpiForm.period" class="form-input">
                <option v-for="(label, val) in periods" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Giá trị mục tiêu' : 'Target Value' }}</label>
              <input v-model="kpiForm.target_value" type="number" class="form-input" />
            </div>
          </div>
          <div class="form-row checkbox-row">
            <label class="checkbox-label"><input type="checkbox" v-model="kpiForm.higher_is_better" /> {{ isVi ? 'Cao hơn = Tốt hơn' : 'Higher is better' }}</label>
            <label class="checkbox-label"><input type="checkbox" v-model="kpiForm.is_active" /> {{ isVi ? 'Đang hoạt động' : 'Active' }}</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showDialog = false">{{ t('common.cancel') }}</button>
          <button class="btn-primary" @click="submitKpi" :disabled="submitting">
            <i :class="submitting ? 'pi pi-spin pi-spinner' : 'pi pi-check'" />
            {{ editing ? t('common.save') : t('common.create') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head },
  layout: Layout,
  props: { kpis: Array, units: Object, periods: Object, categories: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      showDialog: false, editing: null, submitting: false, searchQuery: '', filterCat: null,
      kpiForm: this.freshForm(),
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    activeCount() { return this.kpis.filter(k => k.is_active).length },
    inactiveCount() { return this.kpis.filter(k => !k.is_active).length },
    categoryStats() {
      const catColors = { sales: '#3b82f6', support: '#8b5cf6', productivity: '#10b981', quality: '#f59e0b', custom: '#64748b' }
      return Object.entries(this.categories).map(([key, label]) => ({
        key, label, color: catColors[key] || '#94a3b8',
        count: this.kpis.filter(k => k.category === key).length,
      })).filter(c => c.count > 0)
    },
    filteredKpis() {
      let list = this.kpis
      if (this.filterCat) list = list.filter(k => k.category === this.filterCat)
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        list = list.filter(k => k.name?.toLowerCase().includes(q) || k.description?.toLowerCase().includes(q))
      }
      return list
    },
  },
  methods: {
    freshForm() { return { name: '', description: '', unit: 'number', period: 'monthly', category: 'sales', target_value: 0, higher_is_better: true, is_active: true } },
    catColor(cat) { return { sales: '#3b82f6', support: '#8b5cf6', productivity: '#10b981', quality: '#f59e0b', custom: '#64748b' }[cat] || '#94a3b8' },
    toggleCat(key) { this.filterCat = this.filterCat === key ? null : key },
    openCreate() { this.editing = null; this.kpiForm = this.freshForm(); this.showDialog = true },
    editKpi(kpi) { this.editing = kpi; this.kpiForm = { ...kpi }; this.showDialog = true },
    deleteKpi(kpi) { if (confirm(`${this.isVi ? 'Xóa KPI' : 'Delete KPI'} "${kpi.name}"?`)) router.delete(`/hr/kpi-definitions/${kpi.id}`) },
    submitKpi() {
      this.submitting = true
      const opts = { onSuccess: () => { this.showDialog = false; this.editing = null }, onFinish: () => this.submitting = false }
      this.editing ? router.put(`/hr/kpi-definitions/${this.editing.id}`, this.kpiForm, opts) : router.post('/hr/kpi-definitions', this.kpiForm, opts)
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
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.header-actions { display: flex; gap: 0.4rem; align-items: center; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-secondary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; border-radius: 10px; background: white; color: #475569; font-size: 0.82rem; font-weight: 600; border: 1.5px solid #e2e8f0; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.btn-secondary:hover { border-color: #3b82f6; color: #3b82f6; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; }

/* Stats */
.stats-row { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; background: white; border-radius: 10px; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.stat-card:hover { border-color: #cbd5e1; }
.stat-card.active { border-color: #3b82f6; background: #eff6ff; }
.stat-dot { width: 8px; height: 8px; border-radius: 50%; }
.stat-value { font-size: 1rem; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 0.68rem; color: #94a3b8; }

/* Toolbar */
.toolbar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; min-width: 220px; }
.search-box:focus-within { border-color: #3b82f6; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-chips { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.chip { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.65rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.72rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s; }
.chip:hover { border-color: #cbd5e1; }
.chip.active { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }
.chip.clear { color: #94a3b8; font-size: 0.65rem; }
.chip-dot { width: 6px; height: 6px; border-radius: 50%; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }
.kpi-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.3s; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); border-color: #cbd5e1; }
.kpi-card.inactive { opacity: 0.55; }
.kpi-accent { height: 3px; }
.kpi-body { padding: 1.15rem 1.25rem; }
.kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.cat-badge { font-size: 0.6rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
.kpi-btns { display: flex; gap: 0.2rem; opacity: 0; transition: opacity 0.2s; }
.kpi-card:hover .kpi-btns { opacity: 1; }
.action-btn { border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn.sm { width: 26px; height: 26px; font-size: 0.6rem; border-radius: 6px; }
.action-btn:hover { border-color: #3b82f6; color: #3b82f6; }
.action-btn.danger:hover { border-color: #ef4444; color: #ef4444; }
.kpi-name { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.2rem; }
.kpi-desc { font-size: 0.75rem; color: #64748b; margin: 0 0 0.5rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Meta Tags */
.kpi-meta { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.65rem; }
.meta-tag { font-size: 0.68rem; color: #64748b; display: flex; align-items: center; gap: 0.2rem; background: #f8fafc; padding: 0.15rem 0.45rem; border-radius: 6px; }
.meta-tag i { font-size: 0.6rem; color: #94a3b8; }

/* Target */
.kpi-target { display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.75rem; background: #f8fafc; border-radius: 10px; margin-bottom: 0.65rem; }
.target-icon { width: 30px; height: 30px; border-radius: 8px; background: #fef3c7; color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; flex-shrink: 0; }
.target-info { display: flex; flex-direction: column; }
.target-label { font-size: 0.62rem; color: #94a3b8; }
.target-value { font-size: 0.95rem; font-weight: 800; color: #1e293b; }

/* Footer */
.kpi-footer { display: flex; justify-content: space-between; align-items: center; }
.direction-badge { font-size: 0.65rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.5rem; border-radius: 6px; }
.dir-up { background: #ecfdf5; color: #059669; }
.dir-down { background: #fef2f2; color: #dc2626; }
.inactive-badge { font-size: 0.6rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 0.15rem 0.45rem; border-radius: 5px; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; background: white; border: 2px dashed #e2e8f0; border-radius: 20px; }
.empty-state.sm { padding: 2rem; }
.empty-icon { width: 72px; height: 72px; border-radius: 20px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s; }
@keyframes fadeIn { from { opacity: 0; } }
.modal-panel { background: white; border-radius: 20px; width: 90%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.modal-header h3 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.modal-header h3 i { color: #3b82f6; }
.modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; padding: 0.3rem; border-radius: 6px; }
.modal-close:hover { color: #1e293b; background: #f1f5f9; }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; max-height: 60vh; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

/* Form */
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.req { color: #ef4444; }
.form-input { width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; color: #334155; background: #f8fafc; outline: none; transition: all 0.2s; font-family: inherit; box-sizing: border-box; resize: vertical; }
.form-input:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.checkbox-row { display: flex; gap: 1.5rem; align-items: center; }
.checkbox-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; color: #475569; cursor: pointer; font-weight: 500; }
.checkbox-label input { accent-color: #3b82f6; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .kpi-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .toolbar { flex-direction: column; align-items: stretch; }
}
</style>
