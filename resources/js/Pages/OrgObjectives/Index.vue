<template>
  <div>
    <Head :title="t('common.objectives')" />

    <!-- Hero Banner -->
    <div class="hero-banner">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon-wrap"><i class="pi pi-bullseye" /></div>
          <div>
            <h1 class="hero-title">{{ t('common.objectives') }} (OKRs)</h1>
            <p class="hero-subtitle">{{ isVi ? 'Mục tiêu cascading: Công ty → Phòng ban → Nhóm → Cá nhân' : 'Cascading goals: Company → Department → Team → Individual' }}</p>
          </div>
        </div>
        <div class="hero-actions">
          <Button :label="isVi ? 'Tạo mục tiêu' : 'New Objective'" icon="pi pi-plus" class="hero-btn" @click="showDialog = true" />
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="stats">
      <div class="stat-card"><div class="stat-ring" :style="ringStyle(stats.avg_progress || 0)"><span class="ring-val">{{ stats.avg_progress || 0 }}%</span></div><div class="stat-body"><span class="stat-label">{{ isVi ? 'Tiến độ TB' : 'Avg Progress' }}</span></div></div>
      <div class="stat-card stat-interactive" v-for="lv in levelStats" :key="lv.key" :class="{ active: activeFilter === lv.key }" @click="toggleFilter(lv.key)">
        <div class="stat-icon-wrap" :style="{ background: lv.bg }"><i :class="lv.icon" :style="{ color: lv.color }" /></div>
        <div class="stat-body"><span class="stat-number">{{ lv.count }}</span><span class="stat-label">{{ lv.label }}</span></div>
      </div>
      <div class="stat-card stat-alert" v-if="stats.at_risk > 0"><div class="stat-icon-wrap" style="background:#fef2f2"><i class="pi pi-exclamation-triangle" style="color:#ef4444" /></div><div class="stat-body"><span class="stat-number stat-danger">{{ stats.at_risk }}</span><span class="stat-label">{{ isVi ? 'Có rủi ro' : 'At Risk' }}</span></div></div>
    </div>

    <!-- OKR List -->
    <div v-if="filteredObjectives.length === 0" class="empty-state">
      <div class="empty-illustration"><i class="pi pi-bullseye" /></div>
      <h3>{{ isVi ? 'Chưa có mục tiêu nào' : 'No objectives yet' }}</h3>
      <p>{{ isVi ? 'Bắt đầu thiết lập OKR cho tổ chức' : 'Start setting up OKRs for your organization' }}</p>
      <Button :label="isVi ? 'Tạo mục tiêu đầu tiên' : 'Create First Objective'" icon="pi pi-plus" @click="showDialog = true" />
    </div>

    <TransitionGroup name="list" tag="div" class="okr-list" v-else>
      <div v-for="obj in filteredObjectives" :key="obj.id" class="okr-card">
        <div class="okr-level-bar" :style="{ background: levelColor(obj.level) }" />
        <div class="okr-body">
          <div class="okr-header">
            <span class="okr-level-badge" :style="{ background: levelColor(obj.level) + '18', color: levelColor(obj.level) }">
              <i :class="levelIcon(obj.level)" /> {{ levelLabel(obj.level) }}
            </span>
            <h3 class="okr-title">{{ obj.title }}</h3>
            <span class="okr-status-badge" :class="`status-${obj.status}`">{{ obj.status }}</span>
            <span class="okr-period" v-if="obj.period_label">{{ obj.period_label }}</span>
          </div>
          <p class="okr-desc" v-if="obj.description">{{ obj.description }}</p>

          <!-- Progress Bar -->
          <div class="okr-progress-section">
            <div class="progress-info">
              <span class="progress-pct">{{ obj.progress }}%</span>
              <span class="health-badge" :class="`health-${obj.health}`">
                <i :class="healthIcon(obj.health)" /> {{ healthLabel(obj.health) }}
              </span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" :style="{ width: obj.progress + '%', background: healthGradient(obj.health) }" />
            </div>
          </div>

          <!-- Key Results -->
          <div v-if="obj.key_results && obj.key_results.length" class="kr-section">
            <div class="kr-header"><i class="pi pi-key" /> {{ isVi ? 'Kết quả then chốt' : 'Key Results' }} ({{ obj.key_results.length }})</div>
            <div v-for="kr in obj.key_results" :key="kr.id" class="kr-item">
              <div class="kr-main">
                <span class="kr-status-dot" :class="`dot-${kr.status}`" />
                <span class="kr-title">{{ kr.title }}</span>
              </div>
              <div class="kr-metrics">
                <div class="kr-bar"><div class="kr-bar-fill" :style="{ width: kr.progress + '%' }" /></div>
                <span class="kr-values">{{ kr.current_value }}<small>/{{ kr.target_value }}</small> <em v-if="kr.unit">{{ kr.unit }}</em></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <!-- Create Dialog -->
    <Dialog v-model:visible="showDialog" :header="isVi ? 'Tạo mục tiêu mới' : 'Create New Objective'" modal :style="{ width: '560px' }">
      <div class="dialog-form">
        <div class="form-group"><label>{{ t('common.title') }} <span class="req">*</span></label><InputText v-model="form.title" class="w-full" :placeholder="isVi ? 'VD: Tăng doanh thu Q2' : 'e.g. Increase Q2 Revenue'" /></div>
        <div class="form-group"><label>{{ t('common.description') }}</label><Textarea v-model="form.description" rows="2" class="w-full" autoResize /></div>
        <div class="form-row">
          <div class="form-group"><label>{{ isVi ? 'Cấp độ' : 'Level' }} <span class="req">*</span></label>
            <Dropdown v-model="form.level" :options="levelOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-group"><label>{{ isVi ? 'Kỳ' : 'Period' }}</label>
            <InputText v-model="form.period_label" class="w-full" placeholder="2026-Q1" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>{{ isVi ? 'Ngày bắt đầu' : 'Start Date' }}</label><InputText v-model="form.start_date" type="date" class="w-full" /></div>
          <div class="form-group"><label>{{ isVi ? 'Ngày kết thúc' : 'End Date' }}</label><InputText v-model="form.end_date" type="date" class="w-full" /></div>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer"><Button :label="t('common.cancel')" severity="secondary" text @click="showDialog = false" /><Button :label="t('common.create')" icon="pi pi-check" @click="store" :loading="saving" /></div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, Dialog, InputText, Textarea, Dropdown },
  layout: Layout,
  props: { objectives: Array, departments: Array, teams: Array, stats: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      showDialog: false, saving: false, activeFilter: null,
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
      if (!this.activeFilter) return this.objectives || []
      return (this.objectives || []).filter(o => o.level === this.activeFilter)
    },
  },
  methods: {
    toggleFilter(key) { this.activeFilter = this.activeFilter === key ? null : key },
    levelColor(l) { return { company: '#ef4444', department: '#f59e0b', team: '#3b82f6', individual: '#8b5cf6' }[l] || '#94a3b8' },
    levelIcon(l) { return { company: 'pi pi-building', department: 'pi pi-folder', team: 'pi pi-users', individual: 'pi pi-user' }[l] || 'pi pi-circle' },
    levelLabel(l) { const m = { company: this.isVi ? 'Công ty' : 'Company', department: this.isVi ? 'Phòng ban' : 'Dept', team: this.isVi ? 'Nhóm' : 'Team', individual: this.isVi ? 'Cá nhân' : 'Individual' }; return m[l] || l },
    healthIcon(h) { return { on_track: 'pi pi-check-circle', at_risk: 'pi pi-exclamation-triangle', behind: 'pi pi-times-circle', completed: 'pi pi-verified' }[h] || 'pi pi-circle' },
    healthLabel(h) { const m = { on_track: this.isVi ? 'Đúng tiến độ' : 'On Track', at_risk: this.isVi ? 'Có rủi ro' : 'At Risk', behind: this.isVi ? 'Chậm tiến độ' : 'Behind', completed: this.isVi ? 'Hoàn thành' : 'Completed' }; return m[h] || h },
    healthGradient(h) { return { on_track: 'linear-gradient(90deg, #10b981, #34d399)', at_risk: 'linear-gradient(90deg, #f59e0b, #fbbf24)', behind: 'linear-gradient(90deg, #ef4444, #f87171)', completed: 'linear-gradient(90deg, #3b82f6, #60a5fa)' }[h] || '#94a3b8' },
    ringStyle(pct) { const c = pct >= 70 ? '#10b981' : pct >= 40 ? '#f59e0b' : '#ef4444'; return { background: `conic-gradient(${c} ${pct * 3.6}deg, #f1f5f9 0deg)` } },
    store() { this.saving = true; router.post('/org-objectives', this.form, { onFinish: () => { this.saving = false; this.showDialog = false } }) },
  },
}
</script>

<style scoped>
.hero-banner { background: linear-gradient(135deg, #7c2d12 0%, #ea580c 50%, #fb923c 100%); border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.25rem; position: relative; overflow: hidden; }
.hero-banner::before { content: ''; position: absolute; top: -60%; right: -15%; width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%; }
.hero-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
.hero-left { display: flex; align-items: center; gap: 1rem; }
.hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; }
.hero-title { font-size: 1.35rem; font-weight: 700; color: white; margin: 0; }
.hero-subtitle { font-size: 0.78rem; color: rgba(255,255,255,0.75); margin: 0.15rem 0 0; }
.hero-actions { display: flex; gap: 0.5rem; }
.hero-btn { background: white !important; color: #ea580c !important; border: none !important; font-weight: 600; }

.stats-row { display: grid; grid-template-columns: auto repeat(4, 1fr) auto; gap: 0.75rem; margin-bottom: 1.25rem; }
@media(max-width:900px) { .stats-row { grid-template-columns: repeat(3, 1fr); } }
.stat-card { background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.25s; }
.stat-interactive { cursor: pointer; } .stat-interactive:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); } .stat-interactive.active { border-color: #6366f1; background: #fafafe; box-shadow: 0 0 0 2px #6366f120; }
.stat-ring { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ring-val { background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; color: #1e293b; }
.stat-icon-wrap { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon-wrap i { font-size: 1rem; }
.stat-body { display: flex; flex-direction: column; }
.stat-number { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1; }
.stat-danger { color: #ef4444; }
.stat-label { font-size: 0.65rem; color: #94a3b8; font-weight: 500; }

.okr-list { display: flex; flex-direction: column; gap: 0.75rem; }
.okr-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden; transition: all 0.3s; }
.okr-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.06); }
.okr-level-bar { height: 3px; }
.okr-body { padding: 1.15rem 1.5rem; }
.okr-header { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.okr-level-badge { font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px; display: flex; align-items: center; gap: 0.3rem; }
.okr-level-badge i { font-size: 0.62rem; }
.okr-title { flex: 1; font-size: 0.98rem; font-weight: 700; color: #0f172a; margin: 0; min-width: 0; }
.okr-status-badge { font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 20px; text-transform: capitalize; }
.status-active { background: #dcfce7; color: #16a34a; }
.status-draft { background: #f1f5f9; color: #64748b; }
.status-completed { background: #dbeafe; color: #2563eb; }
.status-cancelled { background: #fee2e2; color: #dc2626; }
.okr-period { font-size: 0.65rem; color: #94a3b8; background: #f8fafc; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; }
.okr-desc { font-size: 0.78rem; color: #64748b; margin: 0.5rem 0 0; line-height: 1.5; }

.okr-progress-section { margin-top: 0.85rem; }
.progress-info { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem; }
.progress-pct { font-size: 0.82rem; font-weight: 800; color: #1e293b; }
.health-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 20px; display: flex; align-items: center; gap: 0.25rem; }
.health-badge i { font-size: 0.58rem; }
.health-on_track { background: #dcfce7; color: #16a34a; }
.health-at_risk { background: #fef3c7; color: #d97706; }
.health-behind { background: #fee2e2; color: #dc2626; }
.health-completed { background: #dbeafe; color: #2563eb; }
.progress-track { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; transition: width 0.6s ease; }

.kr-section { margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid #f8fafc; }
.kr-header { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.kr-header i { font-size: 0.62rem; }
.kr-item { display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0; gap: 1rem; }
.kr-main { display: flex; align-items: center; gap: 0.4rem; flex: 1; min-width: 0; }
.kr-status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.dot-not_started { background: #cbd5e1; } .dot-in_progress { background: #f59e0b; } .dot-completed { background: #10b981; } .dot-cancelled { background: #ef4444; }
.kr-title { font-size: 0.82rem; color: #334155; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kr-metrics { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.kr-bar { width: 60px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.kr-bar-fill { height: 100%; background: #6366f1; border-radius: 2px; }
.kr-values { font-size: 0.72rem; font-weight: 700; color: #334155; min-width: 70px; text-align: right; }
.kr-values small { color: #94a3b8; font-weight: 500; }
.kr-values em { font-size: 0.6rem; color: #94a3b8; font-style: normal; }

.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 2px dashed #e2e8f0; }
.empty-illustration { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fff7ed, #ffedd5); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-illustration i { font-size: 2rem; color: #ea580c; }
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

.dialog-form { display: flex; flex-direction: column; }
.form-group { margin-bottom: 0.85rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.3rem; }
.req { color: #ef4444; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.w-full { width: 100%; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.5rem; }

.list-enter-active { animation: slideIn 0.3s ease; }
.list-leave-active { animation: slideIn 0.2s ease reverse; }
@keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
