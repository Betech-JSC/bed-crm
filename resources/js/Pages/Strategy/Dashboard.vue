<template>
  <div>
    <Head :title="isVi ? 'Chiến lược' : 'Strategy'" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isVi ? 'Bảng điều khiển chiến lược' : 'Strategy Cockpit' }}</h1>
        <p class="page-subtitle" v-if="health.plan">{{ health.plan.title }}</p>
        <p class="page-subtitle" v-else>{{ isVi ? 'Chưa có kế hoạch chiến lược' : 'No strategic plan yet' }}</p>
      </div>
      <div class="header-actions">
        <Button :label="isVi ? 'Đồng bộ CRM' : 'Sync CRM'" icon="pi pi-sync" severity="secondary" outlined @click="refreshKRs" :loading="syncing" />
        <Button :label="isVi ? 'OKR Tree' : 'OKR Tree'" icon="pi pi-sitemap" @click="$inertia.visit('/okrs')" />
      </div>
    </div>

    <!-- Vision & Mission -->
    <div v-if="health.plan" class="vision-banner">
      <div class="vision-item" v-if="health.plan.vision">
        <span class="vision-label">{{ isVi ? 'Tầm nhìn' : 'Vision' }}</span>
        <p class="vision-text">{{ health.plan.vision }}</p>
      </div>
      <div class="vision-divider" v-if="health.plan.vision && health.plan.mission" />
      <div class="vision-item" v-if="health.plan.mission">
        <span class="vision-label">{{ isVi ? 'Sứ mệnh' : 'Mission' }}</span>
        <p class="vision-text">{{ health.plan.mission }}</p>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card stat-health">
        <div class="stat-ring" :style="ringStyle(health.overall_health)">
          <span class="stat-ring-value">{{ health.overall_health }}%</span>
        </div>
        <div class="stat-info">
          <span class="stat-label">{{ isVi ? 'Sức khoẻ chiến lược' : 'Strategy Health' }}</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-number">{{ stats.total_objectives }}</span>
        <span class="stat-label">{{ isVi ? 'Mục tiêu' : 'Objectives' }}</span>
        <div class="stat-breakdown">
          <span class="stat-mini">🏢 {{ stats.company_level }}</span>
          <span class="stat-mini">👥 {{ stats.team_level }}</span>
          <span class="stat-mini">👤 {{ stats.individual_level }}</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-number">{{ stats.avg_progress }}<small>%</small></span>
        <span class="stat-label">{{ isVi ? 'Tiến độ TB' : 'Avg Progress' }}</span>
        <div class="stat-bar"><div class="stat-bar-fill" :style="{ width: stats.avg_progress + '%' }" /></div>
      </div>
      <div class="stat-card">
        <span class="stat-number">{{ alignment.overall_alignment }}<small>%</small></span>
        <span class="stat-label">{{ isVi ? 'Độ liên kết CRM' : 'CRM Alignment' }}</span>
        <div class="stat-detail">{{ alignment.auto_tracked_krs }}/{{ alignment.total_krs }} KRs</div>
      </div>
      <div class="stat-card">
        <span class="stat-number at-risk">{{ stats.at_risk }}</span>
        <span class="stat-label">{{ isVi ? 'Có rủi ro' : 'At Risk' }}</span>
      </div>
    </div>

    <!-- Strategic Themes -->
    <h2 class="section-title">{{ isVi ? 'Trụ cột chiến lược' : 'Strategic Themes' }}</h2>
    <div class="themes-grid" v-if="health.themes.length">
      <div v-for="theme in health.themes" :key="theme.id" class="theme-card" :style="{ borderTopColor: theme.color }">
        <div class="theme-header">
          <i :class="theme.icon" class="theme-icon" :style="{ color: theme.color }" />
          <h3 class="theme-name">{{ theme.name }}</h3>
        </div>
        <div class="theme-health">
          <div class="theme-health-bar">
            <div class="theme-health-fill" :style="{ width: theme.health + '%', background: theme.color }" />
          </div>
          <span class="theme-health-pct">{{ theme.health }}%</span>
        </div>
        <div class="theme-stats">
          <span>{{ theme.objectives_count }} {{ isVi ? 'mục tiêu' : 'objectives' }}</span>
          <span class="dot">·</span>
          <span class="text-green">{{ theme.completed_count }} ✓</span>
          <span class="dot" v-if="theme.at_risk_count">·</span>
          <span v-if="theme.at_risk_count" class="text-amber">{{ theme.at_risk_count }} ⚠</span>
        </div>
      </div>
    </div>
    <div v-else class="empty-themes">
      <Button :label="isVi ? 'Tạo kế hoạch chiến lược' : 'Create Strategic Plan'" icon="pi pi-plus" @click="showCreatePlan = true" />
    </div>

    <!-- Risk Alerts -->
    <div v-if="health.risk_alerts.length" class="risk-section">
      <h2 class="section-title">{{ isVi ? 'Cảnh báo rủi ro' : 'Risk Alerts' }}</h2>
      <div class="risk-list">
        <div v-for="(alert, i) in health.risk_alerts" :key="i" class="risk-card" :class="`risk-${alert.severity}`">
          <i :class="alert.severity === 'warning' ? 'pi pi-exclamation-triangle' : 'pi pi-info-circle'" />
          <span>{{ isVi ? alert.message_vi : alert.message_en }}</span>
        </div>
      </div>
    </div>

    <!-- Create Plan Dialog -->
    <Dialog v-model:visible="showCreatePlan" :header="isVi ? 'Tạo kế hoạch chiến lược' : 'Create Strategic Plan'" modal :style="{ width: '560px' }">
      <div class="form-group">
        <label>{{ isVi ? 'Tiêu đề' : 'Title' }}</label>
        <InputText v-model="planForm.title" :placeholder="isVi ? 'VD: Kế hoạch 2026-2028' : 'e.g. Strategic Plan 2026-2028'" class="w-full" />
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Tầm nhìn' : 'Vision' }}</label>
        <Textarea v-model="planForm.vision" rows="2" class="w-full" />
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Sứ mệnh' : 'Mission' }}</label>
        <Textarea v-model="planForm.mission" rows="2" class="w-full" />
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showCreatePlan = false" />
        <Button :label="isVi ? 'Tạo' : 'Create'" icon="pi pi-check" @click="createPlan" :loading="creating" />
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
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, Dialog, InputText, Textarea },
  layout: Layout,
  props: { health: Object, stats: Object, alignment: Object, periods: Array, data_sources: Object },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      syncing: false,
      showCreatePlan: false,
      creating: false,
      planForm: { title: '', vision: '', mission: '' },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
  },
  methods: {
    ringStyle(pct) {
      const color = pct >= 70 ? '#10b981' : pct >= 40 ? '#f59e0b' : '#ef4444'
      return { background: `conic-gradient(${color} ${pct * 3.6}deg, #f1f5f9 0deg)` }
    },
    async refreshKRs() {
      this.syncing = true
      await axios.post('/strategy/api/refresh')
      this.syncing = false
      router.reload()
    },
    async createPlan() {
      this.creating = true
      await axios.post('/strategy/plans', { ...this.planForm, status: 'active' })
      this.creating = false
      this.showCreatePlan = false
      router.reload()
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* Vision Banner */
.vision-banner { display: flex; gap: 1.5rem; background: linear-gradient(135deg, #312e81, #4338ca); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; }
.vision-item { flex: 1; }
.vision-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #c7d2fe; }
.vision-text { font-size: 0.82rem; color: white; margin: 0.25rem 0 0; line-height: 1.5; font-weight: 500; }
.vision-divider { width: 1px; background: rgba(255,255,255,0.15); }

/* Stats Row */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.stat-card { background: white; border: 1px solid #f1f5f9; border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 0.15rem; }
.stat-health { flex-direction: row; gap: 0.75rem; justify-content: center; }
.stat-ring { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-ring-value { background: white; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; color: #1e293b; }
.stat-number { font-size: 1.5rem; font-weight: 700; color: #1e293b; line-height: 1; }
.stat-number small { font-size: 0.7rem; color: #94a3b8; }
.stat-number.at-risk { color: #f59e0b; }
.stat-label { font-size: 0.65rem; color: #94a3b8; font-weight: 500; }
.stat-breakdown { display: flex; gap: 0.5rem; margin-top: 0.25rem; }
.stat-mini { font-size: 0.6rem; color: #64748b; }
.stat-bar { height: 4px; background: #f1f5f9; border-radius: 2px; width: 100%; margin-top: 0.35rem; }
.stat-bar-fill { height: 100%; background: #6366f1; border-radius: 2px; transition: width 0.5s; }
.stat-detail { font-size: 0.6rem; color: #94a3b8; margin-top: 0.15rem; }

/* Section Title */
.section-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0 0 0.75rem; }

/* Themes Grid */
.themes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.theme-card { background: white; border: 1px solid #f1f5f9; border-top: 3px solid; border-radius: 12px; padding: 1rem; transition: all 0.2s; }
.theme-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
.theme-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.theme-icon { font-size: 1rem; }
.theme-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0; }
.theme-health { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; }
.theme-health-bar { flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.theme-health-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
.theme-health-pct { font-size: 0.7rem; font-weight: 700; color: #334155; min-width: 32px; }
.theme-stats { font-size: 0.65rem; color: #94a3b8; display: flex; gap: 0.35rem; }
.dot { color: #e2e8f0; }
.text-green { color: #10b981; }
.text-amber { color: #f59e0b; }

.empty-themes { text-align: center; padding: 2rem; background: white; border-radius: 12px; border: 1px dashed #e2e8f0; }

/* Risk Alerts */
.risk-section { margin-bottom: 1rem; }
.risk-list { display: flex; flex-direction: column; gap: 0.35rem; }
.risk-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.85rem; border-radius: 8px; font-size: 0.78rem; }
.risk-warning { background: #fffbeb; color: #92400e; }
.risk-info { background: #eef2ff; color: #3730a3; }

/* Form */
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.25rem; }
.w-full { width: 100%; }
</style>
