<template>
  <div>
    <Head :title="detail.employee.name" />

    <div class="page-header">
      <div class="header-left">
        <Link href="/hr/employees" class="back-link"><i class="pi pi-arrow-left" /> Employees</Link>
        <div class="emp-profile">
          <div class="profile-avatar">{{ initials(detail.employee.name) }}</div>
          <div>
            <h1 class="page-title">{{ detail.employee.name }}</h1>
            <p class="page-subtitle">
              {{ detail.employee.position || 'No position' }}
              <span v-if="detail.employee.department" class="dept-badge" :class="`dept-${detail.employee.department}`">{{ detail.employee.department }}</span>
            </p>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <Button label="Record KPI" icon="pi pi-plus" severity="secondary" @click="showKpiDialog = true" />
        <Button label="Add Review" icon="pi pi-star" @click="openReviewDialog" />
      </div>
    </div>

    <!-- Profile Summary -->
    <div class="profile-stats">
      <div class="stat-card">
        <span class="stat-value">{{ detail.employee.tenure_months }}<small>mo</small></span>
        <span class="stat-label">Tenure</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ formatCurrency(detail.employee.base_salary) }}</span>
        <span class="stat-label">Base Salary</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ formatCurrency(detail.total_revenue) }}</span>
        <span class="stat-label">Total Revenue</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ detail.total_deals }}</span>
        <span class="stat-label">Deals Won</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ revenueRatio }}x</span>
        <span class="stat-label">Revenue / Salary</span>
      </div>
    </div>

    <div class="detail-layout">
      <!-- Left: KPI History -->
      <div class="detail-column main-col">
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-chart-bar" /> KPI History</h2>
          </div>
          <div v-for="period in detail.kpi_history" :key="period.period" class="period-block">
            <div class="period-header">
              <span class="period-label">{{ period.period }}</span>
              <span class="period-avg" :class="achievementColor(period.avg_achievement)">Avg: {{ period.avg_achievement }}%</span>
            </div>
            <div class="kpi-rows">
              <div v-for="kpi in period.kpis" :key="kpi.id" class="kpi-row">
                <div class="kpi-row-info">
                  <span class="kpi-row-name">{{ kpi.kpi_name }}</span>
                  <span class="kpi-row-cat"><span class="cat-mini" :class="`cat-${kpi.category}`">{{ kpi.category }}</span></span>
                </div>
                <div class="kpi-row-meter">
                  <div class="meter-track">
                    <div class="meter-fill" :class="achievementColor(kpi.achievement)" :style="{ width: Math.min(kpi.achievement, 100) + '%' }"></div>
                  </div>
                </div>
                <div class="kpi-row-values">
                  <span class="val-actual">{{ formatKpiVal(kpi.value, kpi.unit) }}</span>
                  <span class="val-sep">/</span>
                  <span class="val-target">{{ formatKpiVal(kpi.target, kpi.unit) }}</span>
                  <span class="val-pct" :class="achievementColor(kpi.achievement)">{{ kpi.achievement }}%</span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="detail.kpi_history.length === 0" class="empty-mini">
            <i class="pi pi-chart-bar" /> No KPI data recorded yet.
          </div>
        </div>

        <!-- Deals Won -->
        <div class="section-card" v-if="detail.deals.length > 0">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-briefcase" /> Deals Won</h2>
            <span class="section-count">{{ detail.total_deals }}</span>
          </div>
          <div class="deals-list">
            <div v-for="deal in detail.deals" :key="deal.id" class="deal-item">
              <span class="deal-title">{{ deal.title }}</span>
              <span class="deal-value">{{ formatCurrency(deal.value) }}</span>
              <span class="deal-date">{{ deal.closed_at }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Reviews -->
      <div class="detail-column side-col">
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-star" /> Performance Reviews</h2>
          </div>
          <div class="reviews-list">
            <div v-for="review in detail.reviews" :key="review.id" class="review-card">
              <div class="review-top">
                <span class="review-period">{{ review.period_label }}</span>
                <span class="rating-chip" :class="`rating-${review.rating}`">{{ review.rating }}</span>
              </div>
              <div class="review-score-ring">
                <svg viewBox="0 0 36 36" class="score-svg">
                  <path class="svg-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  <path class="svg-fg" :class="scoreColor(review.overall_score)" :stroke-dasharray="`${review.overall_score}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span class="score-text">{{ review.overall_score }}</span>
              </div>
              <div class="review-breakdown" v-if="review.score_breakdown">
                <div v-for="(val, key) in review.score_breakdown" :key="key" class="bd-item">
                  <span class="bd-label">{{ formatBreakdownLabel(key) }}</span>
                  <div class="bd-bar"><div class="bd-fill" :style="{ width: Math.min(val, 100) + '%' }"></div></div>
                  <span class="bd-val">{{ val }}%</span>
                </div>
              </div>
              <div class="review-meta">
                <div class="rm-row" v-if="review.revenue_generated"><span class="rm-label">Revenue</span><span class="rm-value">{{ formatCurrency(review.revenue_generated) }}</span></div>
                <div class="rm-row" v-if="review.deals_closed_count"><span class="rm-label">Deals</span><span class="rm-value">{{ review.deals_closed_count }}</span></div>
                <div class="rm-row" v-if="review.hours_logged"><span class="rm-label">Hours</span><span class="rm-value">{{ review.hours_logged }}h</span></div>
              </div>
              <div v-if="review.strengths" class="review-text"><strong>Strengths:</strong> {{ review.strengths }}</div>
              <div v-if="review.improvements" class="review-text"><strong>Improvements:</strong> {{ review.improvements }}</div>
              <div v-if="review.reviewed_by" class="review-by">Reviewed by {{ review.reviewed_by }}</div>
            </div>
          </div>
          <div v-if="detail.reviews.length === 0" class="empty-mini">
            <i class="pi pi-star" /> No reviews yet.
          </div>
        </div>
      </div>
    </div>

    <!-- Record KPI Dialog -->
    <Dialog v-model:visible="showKpiDialog" header="Record KPI Value" :modal="true" :style="{ width: '480px' }">
      <div class="form-grid">
        <div class="form-group">
          <label>KPI *</label>
          <Select v-model="kpiForm.kpi_definition_id" :options="kpiDefinitions" optionLabel="name" optionValue="id" placeholder="Select KPI" class="w-full" filter />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Value *</label>
            <InputText v-model="kpiForm.value" type="number" class="w-full" />
          </div>
          <div class="form-group">
            <label>Target</label>
            <InputText v-model="kpiForm.target" type="number" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Period Label * (e.g. 2026-03)</label>
          <InputText v-model="kpiForm.period_label" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Period Start *</label>
            <InputText v-model="kpiForm.period_start" type="date" class="w-full" />
          </div>
          <div class="form-group">
            <label>Period End *</label>
            <InputText v-model="kpiForm.period_end" type="date" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>{{ t('common.notes') }}</label>
          <Textarea v-model="kpiForm.notes" rows="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showKpiDialog = false" />
        <Button label="Save KPI Value" icon="pi pi-check" @click="submitKpiValue" :loading="saving" />
      </template>
    </Dialog>

    <!-- Review Dialog -->
    <Dialog v-model:visible="showReviewDialog" header="Add Performance Review" :modal="true" :style="{ width: '560px' }">
      <div class="form-grid">
        <div class="form-row">
          <div class="form-group">
            <label>Period Label *</label>
            <InputText v-model="reviewForm.period_label" class="w-full" />
          </div>
          <div class="form-group">
            <label>Rating</label>
            <Select v-model="reviewForm.rating" :options="ratingOptions" optionLabel="label" optionValue="value" placeholder="Select rating" class="w-full" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Period Start *</label>
            <InputText v-model="reviewForm.period_start" type="date" class="w-full" />
          </div>
          <div class="form-group">
            <label>Period End *</label>
            <InputText v-model="reviewForm.period_end" type="date" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Overall Score (0-100)</label>
          <div class="score-input-row">
            <InputText v-model="reviewForm.overall_score" type="number" min="0" max="100" class="w-full" />
            <Button label="Auto Calculate" icon="pi pi-calculator" severity="secondary" size="small" @click="autoCalculate" :loading="calculating" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Revenue Generated</label>
            <InputText v-model="reviewForm.revenue_generated" type="number" class="w-full" />
          </div>
          <div class="form-group">
            <label>Hours Logged</label>
            <InputText v-model="reviewForm.hours_logged" type="number" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Strengths</label>
          <Textarea v-model="reviewForm.strengths" rows="2" class="w-full" />
        </div>
        <div class="form-group">
          <label>Areas for Improvement</label>
          <Textarea v-model="reviewForm.improvements" rows="2" class="w-full" />
        </div>
        <div class="form-group">
          <label>Notes</label>
          <Textarea v-model="reviewForm.notes" rows="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showReviewDialog = false" />
        <Button label="Save Review" icon="pi pi-check" @click="submitReview" :loading="saving" />
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
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, InputText, Textarea, Select, Dialog },
  layout: Layout,
  props: {
    detail: Object,
    kpiDefinitions: Array,
    ratings: Object,
    allUsers: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const periodLabel = `${year}-${month}`
    const startOfMonth = `${year}-${month}-01`
    const endOfMonth = new Date(year, now.getMonth() + 1, 0).toISOString().split('T')[0]

    return {
      showKpiDialog: false,
      showReviewDialog: false,
      saving: false,
      calculating: false,
      kpiForm: {
        kpi_definition_id: null,
        value: 0,
        target: 0,
        period_label: periodLabel,
        period_start: startOfMonth,
        period_end: endOfMonth,
        notes: '',
      },
      reviewForm: {
        period_label: periodLabel,
        period_start: startOfMonth,
        period_end: endOfMonth,
        overall_score: 0,
        rating: null,
        revenue_generated: 0,
        deals_closed_value: 0,
        deals_closed_count: 0,
        hours_logged: 0,
        strengths: '',
        improvements: '',
        notes: '',
      },
    }
  },
  computed: {
    revenueRatio() {
      const salary = this.detail.employee.base_salary
      if (!salary || salary <= 0) return '0'
      return ((this.detail.total_revenue / (salary * 12)) || 0).toFixed(1)
    },
    ratingOptions() {
      return Object.entries(this.ratings).map(([v, l]) => ({ label: l, value: v }))
    },
  },
  methods: {
    openReviewDialog() {
      this.showReviewDialog = true
    },
    async autoCalculate() {
      this.calculating = true
      try {
        const res = await axios.get(`/hr/employees/${this.detail.employee.id}/calculate-score`, {
          params: { period_label: this.reviewForm.period_label },
        })
        const data = res.data
        this.reviewForm.overall_score = data.overall_score
        this.reviewForm.rating = data.rating
        this.reviewForm.revenue_generated = data.revenue_generated
        this.reviewForm.deals_closed_value = data.deals_closed_value
        this.reviewForm.deals_closed_count = data.deals_closed_count
        this.reviewForm.hours_logged = data.hours_logged
      } catch (e) {
        console.error('Failed to calculate:', e)
      }
      this.calculating = false
    },
    submitKpiValue() {
      this.saving = true
      router.post(`/hr/employees/${this.detail.employee.id}/kpi-values`, this.kpiForm, {
        onSuccess: () => { this.showKpiDialog = false },
        onFinish: () => { this.saving = false },
      })
    },
    submitReview() {
      this.saving = true
      router.post(`/hr/employees/${this.detail.employee.id}/reviews`, this.reviewForm, {
        onSuccess: () => { this.showReviewDialog = false },
        onFinish: () => { this.saving = false },
      })
    },
    formatCurrency(v) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0)
    },
    formatKpiVal(val, unit) {
      if (unit === 'currency') return this.formatCurrency(val)
      if (unit === 'percentage') return val + '%'
      if (unit === 'hours') return val + 'h'
      return val?.toLocaleString() ?? '0'
    },
    initials(n) {
      return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?'
    },
    achievementColor(val) {
      if (val >= 90) return 'ach-excellent'
      if (val >= 70) return 'ach-good'
      if (val >= 50) return 'ach-average'
      return 'ach-low'
    },
    scoreColor(score) {
      if (score >= 80) return 'score-green'
      if (score >= 60) return 'score-blue'
      if (score >= 40) return 'score-amber'
      return 'score-red'
    },
    formatBreakdownLabel(key) {
      return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.header-left { display: flex; flex-direction: column; gap: 0.5rem; }
.back-link { font-size: 0.75rem; color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.back-link:hover { text-decoration: underline; }
.emp-profile { display: flex; align-items: center; gap: 0.75rem; }
.profile-avatar { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.page-title { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

.dept-badge { font-size: 0.58rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.dept-sales { background: #dbeafe; color: #2563eb; } .dept-marketing { background: #fce7f3; color: #db2777; }
.dept-engineering { background: #d1fae5; color: #059669; } .dept-design { background: #fef3c7; color: #d97706; }
.dept-support { background: #e0e7ff; color: #4f46e5; } .dept-management { background: #f1f5f9; color: #475569; }
.dept-hr { background: #fae8ff; color: #a855f7; } .dept-finance { background: #ccfbf1; color: #0d9488; }

/* Stats */
.profile-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.stat-card { background: white; border-radius: 12px; padding: 0.85rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.stat-value { font-size: 1.15rem; font-weight: 700; color: #0f172a; display: block; }
.stat-value small { font-size: 0.72rem; font-weight: 500; color: #94a3b8; }
.stat-label { font-size: 0.68rem; color: #94a3b8; }

/* Layout */
.detail-layout { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; }
@media (max-width: 1024px) { .detail-layout { grid-template-columns: 1fr; } }
.detail-column { display: flex; flex-direction: column; gap: 1rem; }

/* Section */
.section-card { background: white; border-radius: 14px; padding: 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.section-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.section-title i { font-size: 0.85rem; color: #6366f1; }
.section-count { font-size: 0.7rem; background: #eef2ff; color: #6366f1; padding: 0.1rem 0.4rem; border-radius: 6px; font-weight: 600; }

/* KPI History */
.period-block { margin-bottom: 1rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9; }
.period-block:last-child { border-bottom: none; margin-bottom: 0; }
.period-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.period-label { font-size: 0.82rem; font-weight: 700; color: #334155; }
.period-avg { font-size: 0.72rem; font-weight: 700; }
.kpi-rows { display: flex; flex-direction: column; gap: 0.45rem; }
.kpi-row { display: grid; grid-template-columns: 1fr 120px auto; align-items: center; gap: 0.5rem; padding: 0.35rem 0; }
.kpi-row-info { min-width: 0; }
.kpi-row-name { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cat-mini { font-size: 0.55rem; font-weight: 700; padding: 0.08rem 0.3rem; border-radius: 3px; text-transform: uppercase; }
.cat-sales { background: #dbeafe; color: #2563eb; } .cat-support { background: #e0e7ff; color: #4f46e5; }
.cat-productivity { background: #d1fae5; color: #059669; } .cat-quality { background: #fef3c7; color: #d97706; }
.cat-custom { background: #f1f5f9; color: #475569; }
.kpi-row-meter { min-width: 0; }
.meter-track { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.meter-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
.kpi-row-values { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; white-space: nowrap; }
.val-actual { font-weight: 600; color: #334155; }
.val-sep { color: #cbd5e1; }
.val-target { color: #94a3b8; }
.val-pct { font-weight: 700; margin-left: 0.3rem; }

.ach-excellent { color: #059669; } .ach-excellent.meter-fill { background: #10b981; }
.ach-good { color: #2563eb; } .ach-good.meter-fill { background: #3b82f6; }
.ach-average { color: #d97706; } .ach-average.meter-fill { background: #f59e0b; }
.ach-low { color: #dc2626; } .ach-low.meter-fill { background: #ef4444; }

/* Deals */
.deals-list { display: flex; flex-direction: column; gap: 0.4rem; }
.deal-item { display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 0.75rem; padding: 0.45rem 0; border-bottom: 1px solid #f8fafc; }
.deal-item:last-child { border-bottom: none; }
.deal-title { font-size: 0.78rem; font-weight: 500; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.deal-value { font-size: 0.78rem; font-weight: 700; color: #059669; }
.deal-date { font-size: 0.68rem; color: #94a3b8; }

/* Reviews */
.reviews-list { display: flex; flex-direction: column; gap: 0.85rem; }
.review-card { padding: 0.85rem; background: #fafbfc; border-radius: 12px; border: 1px solid #f1f5f9; }
.review-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; }
.review-period { font-size: 0.82rem; font-weight: 700; color: #334155; }
.rating-chip { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: capitalize; }
.rating-exceptional { background: #d1fae5; color: #059669; }
.rating-exceeds { background: #dbeafe; color: #2563eb; }
.rating-meets { background: #fef3c7; color: #d97706; }
.rating-below { background: #fee2e2; color: #dc2626; }
.rating-unsatisfactory { background: #fecaca; color: #991b1b; }

.review-score-ring { position: relative; width: 64px; height: 64px; margin: 0.4rem auto; }
.score-svg { transform: rotate(-90deg); width: 64px; height: 64px; }
.svg-bg { fill: none; stroke: #f1f5f9; stroke-width: 3; }
.svg-fg { fill: none; stroke-width: 3; stroke-linecap: round; transition: stroke-dasharray 0.6s; }
.score-green { stroke: #10b981; } .score-blue { stroke: #3b82f6; } .score-amber { stroke: #f59e0b; } .score-red { stroke: #ef4444; }
.score-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1rem; font-weight: 800; color: #0f172a; }

.review-breakdown { margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem; }
.bd-item { display: grid; grid-template-columns: 85px 1fr 35px; align-items: center; gap: 0.4rem; }
.bd-label { font-size: 0.65rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bd-bar { height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.bd-fill { height: 100%; border-radius: 3px; background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.bd-val { font-size: 0.65rem; font-weight: 600; color: #475569; text-align: right; }

.review-meta { margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.2rem; }
.rm-row { display: flex; justify-content: space-between; font-size: 0.72rem; }
.rm-label { color: #94a3b8; }
.rm-value { font-weight: 600; color: #334155; }
.review-text { font-size: 0.72rem; color: #475569; margin-top: 0.35rem; line-height: 1.4; }
.review-by { font-size: 0.65rem; color: #94a3b8; margin-top: 0.35rem; text-align: right; font-style: italic; }

/* Form */
.form-grid { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.score-input-row { display: flex; gap: 0.5rem; }

.empty-mini { text-align: center; padding: 1.5rem; color: #cbd5e1; font-size: 0.78rem; }
.empty-mini i { display: block; font-size: 1.3rem; margin-bottom: 0.3rem; }
</style>
