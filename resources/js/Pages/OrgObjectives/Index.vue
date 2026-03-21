<template>
  <div>
    <Head :title="t('common.objectives')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-bullseye" /></div>
        <div>
          <h1 class="page-title">{{ t('common.objectives') }} (OKRs)</h1>
          <p class="page-subtitle">{{ isVi ? 'Cascading: Công ty → Phòng ban → Nhóm → Cá nhân' : 'Company → Department → Team → Individual' }}</p>
        </div>
      </div>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus" /> {{ isVi ? 'Tạo mục tiêu' : 'New Objective' }}</button>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="stats">
      <!-- Progress Ring -->
      <div class="stat-card ring-card">
        <div class="progress-ring" :style="ringStyle(stats.avg_progress || 0)">
          <span class="ring-val">{{ stats.avg_progress || 0 }}%</span>
        </div>
        <div><span class="stat-label-lg">{{ isVi ? 'Tiến độ TB' : 'Avg Progress' }}</span></div>
      </div>
      <!-- Level filters -->
      <div v-for="lv in levelStats" :key="lv.key" class="stat-card clickable" :class="{ active: activeFilter === lv.key }" @click="toggleFilter(lv.key)">
        <div class="stat-icon" :style="{ background: lv.bg, color: lv.color }"><i :class="lv.icon" /></div>
        <div><span class="stat-value">{{ lv.count }}</span><span class="stat-label">{{ lv.label }}</span></div>
      </div>
      <!-- At risk -->
      <div v-if="stats.at_risk > 0" class="stat-card risk-card">
        <div class="stat-icon" style="background:#fef2f2; color:#ef4444"><i class="pi pi-exclamation-triangle" /></div>
        <div><span class="stat-value risk">{{ stats.at_risk }}</span><span class="stat-label">{{ isVi ? 'Rủi ro' : 'At Risk' }}</span></div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="searchQuery" :placeholder="isVi ? 'Tìm mục tiêu...' : 'Search objectives...'" class="search-input" />
      </div>
      <div class="filter-pills">
        <button v-for="lv in levelStats" :key="lv.key" class="pill" :class="{ active: activeFilter === lv.key }" @click="toggleFilter(lv.key)">
          <i :class="lv.icon" /> {{ lv.label }}
        </button>
        <button v-if="activeFilter" class="pill clear" @click="activeFilter = null"><i class="pi pi-times" /></button>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="filteredObjectives.length === 0 && !(objectives || []).length" class="empty-state">
      <div class="empty-icon"><i class="pi pi-bullseye" /></div>
      <h3>{{ isVi ? 'Chưa có mục tiêu nào' : 'No objectives yet' }}</h3>
      <p>{{ isVi ? 'Thiết lập OKR để đồng bộ mục tiêu toàn tổ chức' : 'Set up OKRs to align your organization' }}</p>
      <button class="btn-primary" @click="showDialog = true"><i class="pi pi-plus" /> {{ isVi ? 'Tạo mục tiêu' : 'Create Objective' }}</button>
    </div>

    <div v-else-if="filteredObjectives.length === 0" class="empty-state sm">
      <p>{{ isVi ? 'Không tìm thấy mục tiêu' : 'No matching objectives' }}</p>
    </div>

    <!-- OKR List -->
    <div v-else class="okr-list">
      <div v-for="obj in filteredObjectives" :key="obj.id" class="okr-card">
        <div class="okr-accent" :style="{ background: levelColor(obj.level) }" />
        <div class="okr-body">
          <!-- Header -->
          <div class="okr-header">
            <span class="level-badge" :style="{ background: levelColor(obj.level) + '15', color: levelColor(obj.level) }">
              <i :class="levelIcon(obj.level)" /> {{ levelLabel(obj.level) }}
            </span>
            <h3 class="okr-title">{{ obj.title }}</h3>
            <span class="status-badge" :class="`st-${obj.status}`">{{ statusLabel(obj.status) }}</span>
            <span v-if="obj.period_label" class="period-tag">{{ obj.period_label }}</span>
          </div>

          <p v-if="obj.description" class="okr-desc">{{ obj.description }}</p>

          <!-- Progress -->
          <div class="progress-section">
            <div class="progress-top">
              <span class="progress-pct">{{ obj.progress }}%</span>
              <span class="health-chip" :class="`h-${obj.health}`">
                <i :class="healthIcon(obj.health)" /> {{ healthLabel(obj.health) }}
              </span>
            </div>
            <div class="progress-bar"><div class="progress-fill" :style="{ width: obj.progress + '%', background: healthGradient(obj.health) }" /></div>
          </div>

          <!-- Key Results -->
          <div v-if="obj.key_results && obj.key_results.length" class="kr-section">
            <div class="kr-header"><i class="pi pi-key" /> {{ isVi ? 'Kết quả then chốt' : 'Key Results' }} ({{ obj.key_results.length }})</div>
            <div v-for="kr in obj.key_results" :key="kr.id" class="kr-item">
              <div class="kr-left">
                <span class="kr-dot" :class="`dot-${kr.status}`" />
                <span class="kr-title">{{ kr.title }}</span>
              </div>
              <div class="kr-right">
                <div class="kr-mini-bar"><div class="kr-mini-fill" :style="{ width: kr.progress + '%' }" /></div>
                <span class="kr-values">{{ kr.current_value }}<small>/{{ kr.target_value }}</small> <em v-if="kr.unit">{{ kr.unit }}</em></span>
              </div>
            </div>
          </div>

          <!-- Date Info -->
          <div v-if="obj.start_date || obj.end_date" class="okr-dates">
            <span v-if="obj.start_date" class="date-tag"><i class="pi pi-calendar" /> {{ formatDate(obj.start_date) }}</span>
            <span v-if="obj.start_date && obj.end_date" class="date-arrow">→</span>
            <span v-if="obj.end_date" class="date-tag"><i class="pi pi-flag" /> {{ formatDate(obj.end_date) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showDialog" class="modal-overlay" @click.self="showDialog = false">
      <div class="modal-panel">
        <div class="modal-header">
          <h3><i class="pi pi-bullseye" /> {{ isVi ? 'Tạo mục tiêu mới' : 'New Objective' }}</h3>
          <button class="modal-close" @click="showDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ t('common.title') }} <span class="req">*</span></label>
            <input v-model="form.title" class="form-input" :placeholder="isVi ? 'VD: Tăng doanh thu Q2 2026' : 'e.g. Increase Q2 Revenue'" />
          </div>
          <div class="form-group">
            <label>{{ t('common.description') }}</label>
            <textarea v-model="form.description" class="form-input" rows="2" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ isVi ? 'Cấp độ' : 'Level' }} <span class="req">*</span></label>
              <select v-model="form.level" class="form-input">
                <option v-for="lo in levelOptions" :key="lo.value" :value="lo.value">{{ lo.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Kỳ' : 'Period' }}</label>
              <input v-model="form.period_label" class="form-input" placeholder="Q1-2026" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>{{ isVi ? 'Bắt đầu' : 'Start' }}</label>
              <input v-model="form.start_date" type="date" class="form-input" />
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Kết thúc' : 'End' }}</label>
              <input v-model="form.end_date" type="date" class="form-input" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showDialog = false">{{ t('common.cancel') }}</button>
          <button class="btn-primary" @click="store" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> {{ t('common.create') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head },
  layout: Layout,
  props: { objectives: Array, departments: Array, teams: Array, stats: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      showDialog: false, saving: false, activeFilter: null, searchQuery: '',
      form: { title: '', description: '', level: 'company', period_label: '', start_date: null, end_date: null },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    levelOptions() {
      return [
        { value: 'company', label: this.isVi ? '🏢 Công ty' : '🏢 Company' },
        { value: 'department', label: this.isVi ? '📁 Phòng ban' : '📁 Department' },
        { value: 'team', label: this.isVi ? '👥 Nhóm' : '👥 Team' },
        { value: 'individual', label: this.isVi ? '👤 Cá nhân' : '👤 Individual' },
      ]
    },
    levelStats() {
      const obj = this.objectives || []
      return [
        { key: 'company', label: this.isVi ? 'Công ty' : 'Company', count: obj.filter(o => o.level === 'company').length, icon: 'pi pi-building', color: '#ef4444', bg: '#fef2f2' },
        { key: 'department', label: this.isVi ? 'Phòng ban' : 'Department', count: obj.filter(o => o.level === 'department').length, icon: 'pi pi-folder', color: '#f59e0b', bg: '#fffbeb' },
        { key: 'team', label: this.isVi ? 'Nhóm' : 'Team', count: obj.filter(o => o.level === 'team').length, icon: 'pi pi-users', color: '#3b82f6', bg: '#eff6ff' },
        { key: 'individual', label: this.isVi ? 'Cá nhân' : 'Individual', count: obj.filter(o => o.level === 'individual').length, icon: 'pi pi-user', color: '#8b5cf6', bg: '#f5f3ff' },
      ]
    },
    filteredObjectives() {
      let list = this.objectives || []
      if (this.activeFilter) list = list.filter(o => o.level === this.activeFilter)
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        list = list.filter(o => o.title?.toLowerCase().includes(q) || o.description?.toLowerCase().includes(q))
      }
      return list
    },
  },
  methods: {
    toggleFilter(key) { this.activeFilter = this.activeFilter === key ? null : key },
    levelColor(l) { return { company: '#ef4444', department: '#f59e0b', team: '#3b82f6', individual: '#8b5cf6' }[l] || '#94a3b8' },
    levelIcon(l) { return { company: 'pi pi-building', department: 'pi pi-folder', team: 'pi pi-users', individual: 'pi pi-user' }[l] || 'pi pi-circle' },
    levelLabel(l) { return { company: this.isVi ? 'Công ty' : 'Company', department: this.isVi ? 'Phòng ban' : 'Dept', team: this.isVi ? 'Nhóm' : 'Team', individual: this.isVi ? 'Cá nhân' : 'Individual' }[l] || l },
    statusLabel(s) { return { active: this.isVi ? 'Đang thực hiện' : 'Active', draft: this.isVi ? 'Nháp' : 'Draft', completed: this.isVi ? 'Hoàn thành' : 'Completed', cancelled: this.isVi ? 'Đã hủy' : 'Cancelled' }[s] || s },
    healthIcon(h) { return { on_track: 'pi pi-check-circle', at_risk: 'pi pi-exclamation-triangle', behind: 'pi pi-times-circle', completed: 'pi pi-verified' }[h] || 'pi pi-circle' },
    healthLabel(h) { return { on_track: this.isVi ? 'Đúng tiến độ' : 'On Track', at_risk: this.isVi ? 'Rủi ro' : 'At Risk', behind: this.isVi ? 'Chậm' : 'Behind', completed: this.isVi ? 'Hoàn thành' : 'Done' }[h] || h },
    healthGradient(h) { return { on_track: 'linear-gradient(90deg, #10b981, #34d399)', at_risk: 'linear-gradient(90deg, #f59e0b, #fbbf24)', behind: 'linear-gradient(90deg, #ef4444, #f87171)', completed: 'linear-gradient(90deg, #3b82f6, #60a5fa)' }[h] || '#94a3b8' },
    ringStyle(pct) { const c = pct >= 70 ? '#10b981' : pct >= 40 ? '#f59e0b' : '#ef4444'; return { background: `conic-gradient(${c} ${pct * 3.6}deg, #f1f5f9 0deg)` } },
    formatDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString(this.isVi ? 'vi-VN' : 'en-US', { month: 'short', day: 'numeric' }) },
    store() { this.saving = true; router.post('/org-objectives', this.form, { onFinish: () => { this.saving = false; this.showDialog = false } }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #ea580c, #c2410c); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(234,88,12,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #ea580c, #c2410c); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(234,88,12,0.3); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; }

/* Stats */
.stats-row { display: flex; gap: 0.65rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.stat-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; transition: all 0.2s; }
.stat-card.clickable { cursor: pointer; }
.stat-card.clickable:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.stat-card.active { border-color: #ea580c; background: #fff7ed; }
.stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-value.risk { color: #ef4444; }
.stat-label { font-size: 0.65rem; color: #94a3b8; }
.stat-label-lg { font-size: 0.72rem; color: #64748b; font-weight: 600; }

/* Ring */
.ring-card { flex-shrink: 0; }
.progress-ring { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ring-val { background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; color: #1e293b; }

/* Toolbar */
.toolbar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; min-width: 220px; }
.search-box:focus-within { border-color: #ea580c; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-pills { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.pill { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.65rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.72rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s; }
.pill i { font-size: 0.65rem; }
.pill:hover { border-color: #cbd5e1; }
.pill.active { background: #fff7ed; border-color: #ea580c; color: #c2410c; }
.pill.clear { color: #94a3b8; }

/* OKR List */
.okr-list { display: flex; flex-direction: column; gap: 0.85rem; }
.okr-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.3s; }
.okr-card:hover { border-color: #cbd5e1; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
.okr-accent { height: 3px; }
.okr-body { padding: 1.25rem 1.5rem; }
.okr-header { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.level-badge { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; display: flex; align-items: center; gap: 0.3rem; flex-shrink: 0; }
.level-badge i { font-size: 0.58rem; }
.okr-title { flex: 1; font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; min-width: 0; }
.status-badge { font-size: 0.6rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 20px; text-transform: capitalize; flex-shrink: 0; }
.st-active { background: #dcfce7; color: #16a34a; }
.st-draft { background: #f1f5f9; color: #64748b; }
.st-completed { background: #dbeafe; color: #2563eb; }
.st-cancelled { background: #fee2e2; color: #dc2626; }
.period-tag { font-size: 0.62rem; color: #94a3b8; background: #f8fafc; padding: 0.12rem 0.45rem; border-radius: 5px; font-weight: 600; flex-shrink: 0; }
.okr-desc { font-size: 0.78rem; color: #64748b; margin: 0.5rem 0 0; line-height: 1.5; }

/* Progress */
.progress-section { margin-top: 0.85rem; }
.progress-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem; }
.progress-pct { font-size: 0.85rem; font-weight: 800; color: #1e293b; }
.health-chip { font-size: 0.62rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 20px; display: flex; align-items: center; gap: 0.2rem; }
.health-chip i { font-size: 0.55rem; }
.h-on_track { background: #dcfce7; color: #16a34a; }
.h-at_risk { background: #fef3c7; color: #d97706; }
.h-behind { background: #fee2e2; color: #dc2626; }
.h-completed { background: #dbeafe; color: #2563eb; }
.progress-bar { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; transition: width 0.6s ease; }

/* Key Results */
.kr-section { margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid #f1f5f9; }
.kr-header { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.kr-header i { font-size: 0.6rem; }
.kr-item { display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0; gap: 1rem; }
.kr-left { display: flex; align-items: center; gap: 0.4rem; flex: 1; min-width: 0; }
.kr-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.dot-not_started { background: #cbd5e1; } .dot-in_progress { background: #f59e0b; } .dot-completed { background: #10b981; } .dot-cancelled { background: #ef4444; }
.kr-title { font-size: 0.82rem; color: #334155; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kr-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.kr-mini-bar { width: 60px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.kr-mini-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #818cf8); border-radius: 2px; }
.kr-values { font-size: 0.72rem; font-weight: 700; color: #334155; min-width: 70px; text-align: right; }
.kr-values small { color: #94a3b8; font-weight: 500; }
.kr-values em { font-size: 0.6rem; color: #94a3b8; font-style: normal; }

/* Dates */
.okr-dates { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid #f8fafc; }
.date-tag { font-size: 0.68rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.date-tag i { font-size: 0.6rem; }
.date-arrow { color: #cbd5e1; font-size: 0.72rem; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; background: white; border: 2px dashed #e2e8f0; border-radius: 20px; }
.empty-state.sm { padding: 2rem; }
.empty-icon { width: 72px; height: 72px; border-radius: 20px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s; }
@keyframes fadeIn { from { opacity: 0; } }
.modal-panel { background: white; border-radius: 20px; width: 90%; max-width: 540px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.modal-header h3 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.modal-header h3 i { color: #ea580c; }
.modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; padding: 0.3rem; border-radius: 6px; }
.modal-close:hover { color: #1e293b; background: #f1f5f9; }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; max-height: 60vh; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

/* Form */
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.req { color: #ef4444; }
.form-input { width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; color: #334155; background: #f8fafc; outline: none; transition: all 0.2s; font-family: inherit; box-sizing: border-box; resize: vertical; }
.form-input:focus { border-color: #ea580c; background: white; box-shadow: 0 0 0 3px rgba(234,88,12,0.08); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .stats-row { flex-direction: column; }
  .form-row { grid-template-columns: 1fr; }
  .toolbar { flex-direction: column; align-items: stretch; }
}
</style>
