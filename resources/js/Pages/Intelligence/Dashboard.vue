<template>
  <div>
    <Head title="AI Business Intelligence" />

    <div class="page-header">
      <div>
        <div class="ai-badge"><i class="pi pi-sparkles" /> AI Business Analyst</div>
        <h1 class="page-title">Executive Intelligence</h1>
        <p class="page-subtitle">Analysis generated {{ formattedTime }}</p>
      </div>
      <div class="header-actions">
        <Button label="Refresh Analysis" icon="pi pi-refresh" severity="secondary" @click="refreshAnalysis" :loading="refreshing" />
      </div>
    </div>

    <!-- Revenue Prediction Hero -->
    <div class="prediction-hero">
      <div class="pred-left">
        <span class="pred-label">Predicted Revenue · Next Month</span>
        <div class="pred-value-row">
          <span class="pred-value">{{ fmt(analysis.prediction.predicted_revenue) }}</span>
          <span class="pred-trend" :class="analysis.prediction.trend === 'growing' ? 'trend-up' : analysis.prediction.trend === 'declining' ? 'trend-down' : 'trend-stable'">
            <i :class="analysis.prediction.trend === 'growing' ? 'pi pi-arrow-up' : analysis.prediction.trend === 'declining' ? 'pi pi-arrow-down' : 'pi pi-minus'" />
            {{ Math.abs(analysis.prediction.trend_percent) }}% {{ analysis.prediction.trend }}
          </span>
        </div>
        <div class="pred-range">
          <span>{{ fmt(analysis.prediction.lower_bound) }}</span>
          <div class="range-bar">
            <div class="range-indicator" :style="{ left: rangePosition + '%' }"></div>
          </div>
          <span>{{ fmt(analysis.prediction.upper_bound) }}</span>
        </div>
        <div class="pred-meta">
          <span class="confidence-badge" :class="`conf-${analysis.prediction.confidence}`">
            <i class="pi pi-shield" /> {{ analysis.prediction.confidence_percent }}% confidence
          </span>
          <span class="pred-pipeline">+ {{ fmtCompact(analysis.prediction.pipeline_boost) }} pipeline forecast</span>
        </div>
      </div>
      <div class="pred-chart">
        <div class="mini-bars">
          <div v-for="item in analysis.prediction.historical_data" :key="item.month" class="mini-bar-col">
            <div class="mini-bar" :style="{ height: histBarHeight(item.revenue) + 'px' }"></div>
            <span class="mini-label">{{ item.month.split('-')[1] }}</span>
          </div>
          <div class="mini-bar-col pred-col">
            <div class="mini-bar mini-bar-pred" :style="{ height: histBarHeight(analysis.prediction.predicted_revenue) + 'px' }"></div>
            <span class="mini-label">Next</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Risk Alerts -->
    <div class="section-card risks-section" v-if="analysis.risks.length > 0">
      <div class="section-header">
        <h2 class="section-title"><i class="pi pi-exclamation-triangle" /> Risk Alerts</h2>
        <span class="risk-count" :class="`risk-${worstRiskLevel}`">{{ analysis.risks.length }} detected</span>
      </div>
      <div class="risk-grid">
        <div v-for="(risk, idx) in analysis.risks" :key="idx" class="risk-card" :class="`risk-${risk.level}`">
          <div class="risk-top">
            <span class="risk-level-badge" :class="`rlb-${risk.level}`">
              <i :class="riskIcon(risk.level)" /> {{ risk.level }}
            </span>
            <span class="risk-cat">{{ risk.category }}</span>
          </div>
          <h3 class="risk-title">{{ risk.title }}</h3>
          <p class="risk-desc">{{ risk.description }}</p>
          <span class="risk-metric">{{ risk.metric }}</span>
        </div>
      </div>
    </div>
    <div v-else class="section-card no-risks">
      <i class="pi pi-check-circle" />
      <span>No critical risks detected. Business is operating within healthy parameters.</span>
    </div>

    <!-- Recommended Actions -->
    <div class="section-card actions-section" v-if="analysis.actions.length > 0">
      <div class="section-header">
        <h2 class="section-title"><i class="pi pi-lightbulb" /> Recommended Actions</h2>
      </div>
      <div class="actions-list">
        <div v-for="(action, idx) in analysis.actions" :key="idx" class="action-card">
          <div class="action-priority" :class="`ap-${action.priority}`">{{ action.priority }}</div>
          <div class="action-body">
            <div class="action-top">
              <h3 class="action-title">{{ action.title }}</h3>
              <div class="action-tags">
                <span class="action-cat">{{ action.category }}</span>
                <span class="action-impact" :class="`impact-${action.impact}`">{{ action.impact }} impact</span>
                <span class="action-time" v-if="action.timeframe">{{ action.timeframe }}</span>
              </div>
            </div>
            <p class="action-desc">{{ action.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Analysis Dashboard Grid -->
    <div class="analysis-grid">
      <!-- Revenue Analysis -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-chart-line" /> Revenue Analysis</h2>
        </div>
        <div class="rev-metrics">
          <div class="rev-metric">
            <span class="rm-value">{{ fmt(analysis.revenue.current_month) }}</span>
            <span class="rm-label">This Month</span>
          </div>
          <div class="rev-metric">
            <span class="rm-value growth-val" :class="analysis.revenue.mom_growth >= 0 ? 'text-green' : 'text-red'">
              <i :class="analysis.revenue.mom_growth >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
              {{ Math.abs(analysis.revenue.mom_growth) }}%
            </span>
            <span class="rm-label">MoM Growth</span>
          </div>
          <div class="rev-metric">
            <span class="rm-value">{{ fmt(analysis.revenue.ytd_revenue) }}</span>
            <span class="rm-label">YTD Revenue</span>
          </div>
        </div>
        <div class="rev-chart">
          <div v-for="item in analysis.revenue.monthly_trend" :key="item.month" class="rev-bar-col">
            <div class="rev-bar" :class="item.revenue >= analysis.revenue.avg_monthly ? 'bar-above' : 'bar-below'" :style="{ height: revBarHeight(item.revenue) + 'px' }"></div>
            <span class="rev-bar-label">{{ item.label }}</span>
          </div>
        </div>
        <div class="avg-line-label">Avg: {{ fmtCompact(analysis.revenue.avg_monthly) }}/mo</div>
      </div>

      <!-- Pipeline Health -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-filter" /> Pipeline Health</h2>
        </div>
        <div class="pipeline-metrics">
          <div class="pm-row"><span class="pm-label">Pipeline Value</span><span class="pm-value">{{ fmtCompact(analysis.pipeline.pipeline_value) }}</span></div>
          <div class="pm-row"><span class="pm-label">Open Deals</span><span class="pm-value">{{ analysis.pipeline.deal_count }}</span></div>
          <div class="pm-row"><span class="pm-label">Win Rate (90d)</span><span class="pm-value" :class="analysis.pipeline.win_rate >= 25 ? 'text-green' : 'text-red'">{{ analysis.pipeline.win_rate }}%</span></div>
          <div class="pm-row">
            <span class="pm-label">Pipeline Coverage</span>
            <span class="pm-value" :class="analysis.pipeline.pipeline_coverage >= 3 ? 'text-green' : analysis.pipeline.pipeline_coverage >= 2 ? 'text-amber' : 'text-red'">
              {{ analysis.pipeline.pipeline_coverage }}x
            </span>
          </div>
          <div class="pm-row"><span class="pm-label">Stale Deals</span><span class="pm-value" :class="analysis.pipeline.deals_stale_count > 3 ? 'text-red' : ''">{{ analysis.pipeline.deals_stale_count }}</span></div>
        </div>
        <div class="stage-bars">
          <div v-for="s in analysis.pipeline.stage_distribution" :key="s.stage" class="stage-row">
            <span class="stage-label">{{ s.label }}</span>
            <div class="stage-bar-wrap">
              <div class="stage-bar-fill" :style="{ width: stageWidth(s.count) + '%' }"></div>
            </div>
            <span class="stage-count">{{ s.count }}</span>
          </div>
        </div>
      </div>

      <!-- Customer Insights -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-users" /> Customer Insights</h2>
        </div>
        <div class="customer-metrics">
          <div class="cm-card">
            <span class="cm-value">{{ analysis.customers.new_leads_this_month }}</span>
            <span class="cm-label">New Leads</span>
            <span class="cm-change" :class="analysis.customers.lead_velocity >= 0 ? 'text-green' : 'text-red'">
              {{ analysis.customers.lead_velocity >= 0 ? '+' : '' }}{{ analysis.customers.lead_velocity }}%
            </span>
          </div>
          <div class="cm-card">
            <span class="cm-value" :class="analysis.customers.churn_rate > 10 ? 'text-red' : 'text-green'">{{ analysis.customers.churn_rate }}%</span>
            <span class="cm-label">Churn Rate</span>
          </div>
          <div class="cm-card">
            <span class="cm-value">{{ analysis.customers.avg_lead_score || 'N/A' }}</span>
            <span class="cm-label">Avg Lead Score</span>
          </div>
          <div class="cm-card">
            <span class="cm-value">{{ analysis.customers.total_active_leads }}</span>
            <span class="cm-label">Active Leads</span>
          </div>
        </div>
      </div>

      <!-- Marketing Performance -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-megaphone" /> Channel Performance</h2>
        </div>
        <div class="channel-list">
          <div v-for="ch in analysis.marketing.channels" :key="ch.source" class="channel-row">
            <span class="ch-name">{{ ch.label }}</span>
            <div class="ch-metrics">
              <span class="ch-leads">{{ ch.total_leads }} leads</span>
              <span class="ch-conv" :class="ch.conversion_rate >= 20 ? 'text-green' : ''">{{ ch.conversion_rate }}%</span>
              <span class="ch-rev">{{ fmtCompact(ch.revenue) }}</span>
            </div>
            <div class="ch-bar-wrap">
              <div class="ch-bar" :style="{ width: channelBarWidth(ch.roi_score) + '%' }"></div>
            </div>
          </div>
          <div v-if="analysis.marketing.channels.length === 0" class="empty-mini">No channel data</div>
        </div>
      </div>

      <!-- Financial Health -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-dollar" /> Financial Health</h2>
        </div>
        <div class="fin-metrics">
          <div class="fm-row"><span class="fm-label">Monthly Income</span><span class="fm-value text-green">{{ fmtCompact(analysis.financial_health.current_income) }}</span></div>
          <div class="fm-row"><span class="fm-label">Monthly Expenses</span><span class="fm-value text-red">{{ fmtCompact(analysis.financial_health.current_expenses) }}</span></div>
          <div class="fm-divider"></div>
          <div class="fm-row"><span class="fm-label">Profit Margin</span><span class="fm-value" :class="analysis.financial_health.profit_margin >= 10 ? 'text-green' : 'text-red'">{{ analysis.financial_health.profit_margin }}%</span></div>
          <div class="fm-row"><span class="fm-label">Burn Rate</span><span class="fm-value">{{ fmtCompact(analysis.financial_health.burn_rate) }}/mo</span></div>
          <div class="fm-row"><span class="fm-label">Cash Balance</span><span class="fm-value">{{ fmtCompact(analysis.financial_health.cash_balance) }}</span></div>
          <div class="fm-row"><span class="fm-label">Runway</span><span class="fm-value" :class="analysis.financial_health.runway_months >= 6 ? 'text-green' : 'text-red'">{{ analysis.financial_health.runway_months }}mo</span></div>
        </div>
      </div>

      <!-- Team Overview -->
      <div class="section-card">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-briefcase" /> Team Overview</h2>
        </div>
        <div class="team-metrics">
          <div class="tm-row"><span class="tm-label">Employees</span><span class="tm-value">{{ analysis.team.employee_count }}</span></div>
          <div class="tm-row"><span class="tm-label">Utilization</span><span class="tm-value">{{ analysis.team.avg_utilization }}%</span></div>
          <div class="tm-row"><span class="tm-label">Revenue / Employee</span><span class="tm-value">{{ fmtCompact(analysis.team.revenue_per_employee) }}</span></div>
          <div class="tm-row">
            <span class="tm-label">Rev/Emp Trend</span>
            <span class="tm-value" :class="analysis.team.revenue_per_employee_trend >= 0 ? 'text-green' : 'text-red'">
              {{ analysis.team.revenue_per_employee_trend >= 0 ? '+' : '' }}{{ analysis.team.revenue_per_employee_trend }}%
            </span>
          </div>
          <div class="tm-row"><span class="tm-label">Total Salary Cost</span><span class="tm-value">{{ fmtCompact(analysis.team.total_salary_cost) }}/mo</span></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button },
  layout: Layout,
  props: { analysis: Object },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return { refreshing: false }
  },
  computed: {
    formattedTime() {
      if (!this.analysis.generated_at) return ''
      return new Date(this.analysis.generated_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    },
    worstRiskLevel() {
      return this.analysis.risks[0]?.level || 'low'
    },
    maxHistory() {
      const vals = [...(this.analysis.prediction.historical_data || []).map(d => d.revenue), this.analysis.prediction.predicted_revenue]
      return Math.max(...vals, 1)
    },
    maxRevTrend() {
      return Math.max(...(this.analysis.revenue.monthly_trend || []).map(d => d.revenue), 1)
    },
    maxStageCount() {
      return Math.max(...(this.analysis.pipeline.stage_distribution || []).map(s => s.count), 1)
    },
    maxChannelRoi() {
      return Math.max(...(this.analysis.marketing.channels || []).map(c => c.roi_score), 1)
    },
    rangePosition() {
      const { lower_bound, upper_bound, predicted_revenue } = this.analysis.prediction
      if (upper_bound === lower_bound) return 50
      return Math.round(((predicted_revenue - lower_bound) / (upper_bound - lower_bound)) * 100)
    },
  },
  methods: {
    async refreshAnalysis() {
      this.refreshing = true
      router.reload({ onFinish: () => { this.refreshing = false } })
    },
    fmt(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0) },
    fmtCompact(v) {
      if (!v) return '0₫'
      const abs = Math.abs(v)
      const sign = v < 0 ? '-' : ''
      if (abs >= 1e9) return sign + (abs / 1e9).toFixed(1) + 'B₫'
      if (abs >= 1e6) return sign + (abs / 1e6).toFixed(1) + 'M₫'
      if (abs >= 1e3) return sign + (abs / 1e3).toFixed(0) + 'K₫'
      return sign + abs.toLocaleString() + '₫'
    },
    histBarHeight(val) { return Math.round((val / this.maxHistory) * 80) || 2 },
    revBarHeight(val) { return Math.round((val / this.maxRevTrend) * 80) || 2 },
    stageWidth(count) { return Math.round((count / this.maxStageCount) * 100) },
    channelBarWidth(roi) { return Math.round((roi / this.maxChannelRoi) * 100) || 2 },
    riskIcon(level) {
      if (level === 'critical') return 'pi pi-ban'
      if (level === 'high') return 'pi pi-exclamation-triangle'
      if (level === 'medium') return 'pi pi-info-circle'
      return 'pi pi-check-circle'
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.ai-badge { font-size: 0.65rem; font-weight: 700; color: #7c3aed; background: linear-gradient(135deg, #ede9fe, #f3e8ff); padding: 0.2rem 0.6rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; margin-bottom: 0.3rem; letter-spacing: 0.03em; text-transform: uppercase; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

/* Prediction Hero */
.prediction-hero { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%); border-radius: 18px; padding: 1.5rem 1.75rem; display: flex; justify-content: space-between; align-items: center; gap: 2rem; margin-bottom: 1.25rem; color: white; position: relative; overflow: hidden; }
.prediction-hero::before { content: ''; position: absolute; top: -40%; right: -10%; width: 300px; height: 300px; border-radius: 50%; background: rgba(139, 92, 246, 0.15); }
.pred-left { flex: 1; position: relative; z-index: 1; }
.pred-label { font-size: 0.72rem; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
.pred-value-row { display: flex; align-items: baseline; gap: 0.75rem; margin: 0.3rem 0; }
.pred-value { font-size: 2rem; font-weight: 800; letter-spacing: -0.02em; }
.pred-trend { font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.5rem; border-radius: 6px; }
.pred-trend i { font-size: 0.65rem; }
.trend-up { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
.trend-down { background: rgba(244, 63, 94, 0.2); color: #fda4af; }
.trend-stable { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; }
.pred-range { display: flex; align-items: center; gap: 0.5rem; margin: 0.5rem 0; font-size: 0.68rem; color: rgba(255,255,255,0.5); }
.range-bar { flex: 1; height: 4px; background: rgba(255,255,255,0.15); border-radius: 2px; position: relative; max-width: 200px; }
.range-indicator { position: absolute; top: -4px; width: 12px; height: 12px; background: #8b5cf6; border: 2px solid white; border-radius: 50%; transform: translateX(-50%); }
.pred-meta { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.4rem; }
.confidence-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 5px; display: flex; align-items: center; gap: 0.25rem; }
.confidence-badge i { font-size: 0.55rem; }
.conf-high { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
.conf-medium { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.conf-low { background: rgba(244, 63, 94, 0.2); color: #fda4af; }
.pred-pipeline { font-size: 0.68rem; color: rgba(255,255,255,0.5); }
.pred-chart { flex-shrink: 0; position: relative; z-index: 1; }
.mini-bars { display: flex; align-items: flex-end; gap: 0.35rem; height: 90px; }
.mini-bar-col { display: flex; flex-direction: column; align-items: center; gap: 0.15rem; }
.mini-bar { width: 14px; border-radius: 3px 3px 0 0; background: rgba(255,255,255,0.25); min-height: 2px; transition: height 0.4s; }
.mini-bar-pred { background: linear-gradient(to top, #8b5cf6, #a78bfa); }
.pred-col .mini-label { color: #a78bfa; font-weight: 700; }
.mini-label { font-size: 0.5rem; color: rgba(255,255,255,0.4); }

/* Risk Section */
.risks-section { margin-bottom: 1rem; }
.risk-count { font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px; }
.risk-critical { color: #dc2626; background: #fee2e2; }
.risk-high { color: #d97706; background: #fef3c7; }
.risk-medium { color: #2563eb; background: #dbeafe; }
.risk-low { color: #059669; background: #d1fae5; }
.risk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; }
.risk-card { padding: 0.85rem; border-radius: 12px; border-left: 4px solid; transition: all 0.2s; }
.risk-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.risk-card.risk-critical { background: #fef2f2; border-color: #dc2626; }
.risk-card.risk-high { background: #fffbeb; border-color: #d97706; }
.risk-card.risk-medium { background: #eff6ff; border-color: #2563eb; }
.risk-card.risk-low { background: #f0fdf4; border-color: #059669; }
.risk-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; }
.risk-level-badge { font-size: 0.58rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: uppercase; display: flex; align-items: center; gap: 0.2rem; }
.risk-level-badge i { font-size: 0.55rem; }
.rlb-critical { background: #dc2626; color: white; }
.rlb-high { background: #d97706; color: white; }
.rlb-medium { background: #2563eb; color: white; }
.rlb-low { background: #059669; color: white; }
.risk-cat { font-size: 0.62rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em; }
.risk-title { font-size: 0.82rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.risk-desc { font-size: 0.72rem; color: #475569; margin: 0 0 0.3rem; line-height: 1.4; }
.risk-metric { font-size: 0.78rem; font-weight: 800; color: #0f172a; }

.no-risks { text-align: center; padding: 1.5rem; color: #059669; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem; }
.no-risks i { font-size: 1.2rem; }

/* Actions Section */
.actions-section { margin-bottom: 1.25rem; }
.actions-list { display: flex; flex-direction: column; gap: 0.65rem; }
.action-card { display: flex; gap: 0.75rem; padding: 0.85rem; background: #fafbfc; border-radius: 12px; border: 1px solid #f1f5f9; transition: all 0.2s; }
.action-card:hover { border-color: #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.action-priority { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 800; flex-shrink: 0; color: white; }
.ap-1 { background: linear-gradient(135deg, #dc2626, #ef4444); }
.ap-2 { background: linear-gradient(135deg, #d97706, #f59e0b); }
.ap-3 { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.ap-4 { background: linear-gradient(135deg, #6366f1, #818cf8); }
.ap-5 { background: linear-gradient(135deg, #94a3b8, #cbd5e1); }
.action-body { flex: 1; min-width: 0; }
.action-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.3rem; }
.action-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; }
.action-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.action-cat, .action-impact, .action-time { font-size: 0.55rem; font-weight: 700; padding: 0.08rem 0.3rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.02em; }
.action-cat { background: #f1f5f9; color: #64748b; }
.impact-high { background: #fee2e2; color: #dc2626; }
.impact-medium { background: #fef3c7; color: #d97706; }
.impact-low { background: #d1fae5; color: #059669; }
.action-time { background: #eef2ff; color: #6366f1; }
.action-desc { font-size: 0.75rem; color: #475569; margin: 0; line-height: 1.45; }

/* Analysis Grid */
.analysis-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 900px) { .analysis-grid { grid-template-columns: 1fr; } }

/* Section Card */
.section-card { background: white; border-radius: 14px; padding: 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.section-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.section-title i { font-size: 0.85rem; color: #6366f1; }

/* Revenue Chart */
.rev-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
.rev-metric { text-align: center; }
.rm-value { font-size: 1rem; font-weight: 700; color: #0f172a; display: block; }
.rm-label { font-size: 0.62rem; color: #94a3b8; }
.growth-val { display: flex; align-items: center; justify-content: center; gap: 0.2rem; }
.growth-val i { font-size: 0.75rem; }
.rev-chart { display: flex; gap: 0.3rem; align-items: flex-end; height: 90px; }
.rev-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.2rem; }
.rev-bar { width: 100%; border-radius: 4px 4px 0 0; min-height: 2px; transition: height 0.4s; }
.bar-above { background: linear-gradient(to top, #10b981, #6ee7b7); }
.bar-below { background: linear-gradient(to top, #f59e0b, #fcd34d); }
.rev-bar-label { font-size: 0.55rem; color: #94a3b8; }
.avg-line-label { text-align: center; font-size: 0.65rem; color: #94a3b8; margin-top: 0.4rem; }

/* Pipeline */
.pipeline-metrics { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.75rem; }
.pm-row { display: flex; justify-content: space-between; } .pm-label { font-size: 0.75rem; color: #64748b; } .pm-value { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.stage-bars { display: flex; flex-direction: column; gap: 0.3rem; }
.stage-row { display: grid; grid-template-columns: 80px 1fr 30px; align-items: center; gap: 0.4rem; }
.stage-label { font-size: 0.68rem; color: #64748b; }
.stage-bar-wrap { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.stage-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(135deg, #6366f1, #8b5cf6); transition: width 0.4s; }
.stage-count { font-size: 0.7rem; font-weight: 600; color: #475569; text-align: right; }

/* Customer Metrics */
.customer-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }
.cm-card { background: #fafbfc; border-radius: 10px; padding: 0.65rem; text-align: center; border: 1px solid #f1f5f9; }
.cm-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; display: block; }
.cm-label { font-size: 0.62rem; color: #94a3b8; }
.cm-change { font-size: 0.68rem; font-weight: 600; }

/* Channel Performance */
.channel-list { display: flex; flex-direction: column; gap: 0.55rem; }
.channel-row { display: flex; flex-direction: column; gap: 0.2rem; padding: 0.45rem 0; border-bottom: 1px solid #f8fafc; }
.channel-row:last-child { border-bottom: none; }
.ch-name { font-size: 0.78rem; font-weight: 600; color: #1e293b; }
.ch-metrics { display: flex; gap: 0.75rem; font-size: 0.68rem; }
.ch-leads { color: #64748b; } .ch-conv { font-weight: 600; } .ch-rev { font-weight: 600; color: #334155; }
.ch-bar-wrap { height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.ch-bar { height: 100%; border-radius: 3px; background: linear-gradient(135deg, #6366f1, #8b5cf6); transition: width 0.4s; }

/* Financial Health */
.fin-metrics { display: flex; flex-direction: column; gap: 0.4rem; }
.fm-row { display: flex; justify-content: space-between; } .fm-label { font-size: 0.75rem; color: #64748b; } .fm-value { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.fm-divider { height: 1px; background: #f1f5f9; margin: 0.2rem 0; }

/* Team Overview */
.team-metrics { display: flex; flex-direction: column; gap: 0.4rem; }
.tm-row { display: flex; justify-content: space-between; } .tm-label { font-size: 0.75rem; color: #64748b; } .tm-value { font-size: 0.82rem; font-weight: 700; color: #1e293b; }

/* Colors */
.text-green { color: #059669; } .text-red { color: #dc2626; } .text-amber { color: #d97706; }

.empty-mini { text-align: center; padding: 1rem; color: #cbd5e1; font-size: 0.78rem; }
</style>
