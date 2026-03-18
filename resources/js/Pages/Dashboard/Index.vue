<template>
  <div>
    <Head :title="t('common.dashboard')" />
    <div class="exec-dashboard">

      <!-- ═══ HEADER ═══ -->
      <div class="exec-header">
        <div class="exec-header-left">
          <h1 class="exec-title">{{ isVi ? 'Bảng điều hành' : 'Command Center' }}</h1>
          <p class="exec-subtitle">
            <span v-if="metrics.strategy.plan_title" class="plan-badge">📋 {{ metrics.strategy.plan_title }}</span>
            {{ isVi ? 'Tổng quan toàn bộ hoạt động doanh nghiệp' : 'Full business overview across all modules' }}
          </p>
        </div>
        <div class="exec-header-right">
          <span class="last-updated"><i class="pi pi-clock" /> {{ formatTime(new Date()) }}</span>
          <button class="refresh-btn" @click="refreshData" :disabled="refreshing">
            <i class="pi pi-refresh" :class="{ 'pi-spin': refreshing }" />
            {{ isVi ? 'Làm mới' : 'Refresh' }}
          </button>
        </div>
      </div>

      <!-- ═══ AI ADVISOR & CEO PULSE ═══ -->
      <div v-if="aiBriefing" class="ai-briefing-section">
        <div class="pulse-card" :class="'pulse-' + aiBriefing.summary.status">
          <div class="pulse-header">
            <span class="pulse-dot-wrapper"><span class="pulse-dot"></span></span>
            <span class="pulse-title">{{ isVi ? 'CEO Briefing: Trạng thái vận hành' : 'CEO Briefing: Operational Pulse' }}</span>
            <span class="pulse-badge">{{ isVi ? aiBriefing.summary.vi : aiBriefing.summary.en }}</span>
          </div>
          <div class="briefing-grid">
            <!-- Critical Alerts -->
            <div class="briefing-col alerts-col">
              <div class="briefing-label"><i class="pi pi-exclamation-triangle" /> {{ isVi ? 'Cảnh báo rủi ro & Vận hành' : 'Critical Risks & Ops' }}</div>
              <div v-if="aiBriefing.critical_alerts.length > 0" class="alerts-list">
                <div v-for="alert in aiBriefing.critical_alerts" :key="alert.title_en" class="alert-item" :class="'alert-' + alert.severity">
                  <div class="alert-content">
                    <span class="alert-title">{{ isVi ? alert.title_vi : alert.title_en }}</span>
                    <span class="alert-action">{{ isVi ? alert.action_vi : alert.action_en }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="empty-state-brief"><i class="pi pi-check-circle" /> {{ isVi ? 'Chưa phát hiện rủi ro nghiêm trọng' : 'No critical risks detected' }}</div>
              <!-- Governance Items -->
              <div v-if="aiBriefing.governance_items.length > 0" class="governance-wrap mt-3">
                 <div class="briefing-label"><i class="pi pi-shield" /> {{ isVi ? 'Phê duyệt chờ' : 'Pending Approvals' }}</div>
                 <div v-for="gov in aiBriefing.governance_items" :key="gov.id" class="gov-item">
                    <div class="gov-info"><span class="gov-type">[{{ gov.type }}]</span> {{ gov.requester }}</div>
                    <div class="gov-reason">{{ isVi ? gov.reason_vi : gov.reason_en }}</div>
                    <div class="gov-date">{{ gov.date }}</div>
                 </div>
              </div>
            </div>
            <!-- Cashflow Projection -->
            <div class="briefing-col projection-col">
              <div class="briefing-label"><i class="pi pi-chart-line" /> {{ isVi ? 'Dự báo dòng tiền (3 tháng)' : 'Cashflow Projection (3M)' }}</div>
              <div class="projection-card">
                <div class="proj-main">
                  <span class="proj-val">Runway: {{ aiBriefing.financial_health.runway_months }} {{ isVi ? 'tháng' : 'months' }}</span>
                  <span class="proj-confidence" :class="'conf-' + aiBriefing.financial_health.confidence">{{ isVi ? 'Độ tin cậy:' : 'Confidence:' }} {{ aiBriefing.financial_health.confidence }}</span>
                </div>
                <div class="proj-detail">{{ isVi ? aiBriefing.financial_health.vi : aiBriefing.financial_health.en }}</div>
                <div class="runway-track"><div class="runway-fill" :style="{ width: Math.min(100, (aiBriefing.financial_health.runway_months / 12) * 100) + '%' }"></div></div>
              </div>
              <div v-if="aiBriefing.bottlenecks.length > 0" class="bottlenecks-wrap">
                <div class="briefing-label mt-3"><i class="pi pi-filter" /> {{ isVi ? 'Điểm nghẽn quy trình' : 'Process Bottlenecks' }}</div>
                <div v-for="bn in aiBriefing.bottlenecks" :key="bn.stage" class="bn-item">
                   <span class="bn-stage">{{ isVi ? bn.stage_vi : bn.stage_en }}</span>
                   <span class="bn-issue">{{ isVi ? bn.issue_vi : bn.issue_en }} (Avg {{ bn.avg_age }}d)</span>
                </div>
              </div>
            </div>
            <!-- Priority Actions -->
            <div class="briefing-col actions-col">
              <div class="briefing-label"><i class="pi pi-bolt" /> {{ isVi ? 'Hành động ưu tiên' : 'Priority Actions' }}</div>
              <div class="actions-list">
                <div v-for="action in aiBriefing.recommended_actions" :key="action.vi" class="action-item">
                  <i class="pi pi-arrow-right" /> {{ isVi ? action.vi : action.en }}
                </div>
              </div>
              <div class="simulator-trigger" @click="showSimulator = !showSimulator">
                <i class="pi" :class="showSimulator ? 'pi-chevron-up' : 'pi-sliders-h'" />
                {{ isVi ? 'Mô phỏng tăng trưởng (What-if)' : 'Growth Simulator (What-if)' }}
              </div>
            </div>
          </div>
          <!-- Simulator Panel -->
          <div v-if="showSimulator" class="simulator-panel anim-slide-down">
             <div class="sim-header">{{ isVi ? 'Tính toán tác động doanh thu' : 'Revenue Impact Calculator' }}</div>
             <div class="sim-controls">
                <div class="sim-control">
                   <label>{{ isVi ? 'Tuyển thêm Sales Rep' : 'Hire Sales Reps' }}</label>
                   <input type="number" v-model="simParams.hiring_count" min="0" max="10" @input="runSimulation" />
                </div>
                <div class="sim-control">
                   <label>{{ isVi ? 'Tăng Win Rate (%)' : 'Boost Win Rate (%)' }}</label>
                   <input type="number" v-model="simParams.win_rate_boost" min="0" max="20" @input="runSimulation" />
                </div>
                <div v-if="simResult" class="sim-result">
                   <div class="sim-lift">+{{ fmt(simResult.lift_value) }} <span class="lift-pct">({{ simResult.lift_percent }}%)</span></div>
                   <div class="sim-meta">{{ isVi ? 'Doanh thu dự kiến tháng tới:' : 'Projected revenue next month:' }} {{ fmt(simResult.simulated_monthly) }}</div>
                </div>
             </div>
          </div>
        </div>
      </div>

      <!-- ═══ KPI GRID (Executive) ═══ -->
      <div class="section-label">📊 {{ isVi ? 'Kinh doanh' : 'Sales & Revenue' }}</div>
      <div class="kpi-grid">
        <div class="kpi-card kpi-revenue">
          <div class="kpi-icon-wrapper revenue-icon"><i class="pi pi-dollar" /></div>
          <div class="kpi-content">
            <span class="kpi-label">{{ isVi ? 'Doanh thu tháng' : 'Monthly Revenue' }}</span>
            <span class="kpi-value">{{ fmt(metrics.revenue.monthly) }}</span>
            <span class="kpi-change" :class="metrics.revenue.monthly_growth >= 0 ? 'positive' : 'negative'">
              <i :class="metrics.revenue.monthly_growth >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
              {{ Math.abs(metrics.revenue.monthly_growth) }}%
              <span class="kpi-period">{{ isVi ? 'vs tháng trước' : 'vs last month' }}</span>
            </span>
          </div>
        </div>
        <div class="kpi-card kpi-pipeline">
          <div class="kpi-icon-wrapper pipeline-icon"><i class="pi pi-chart-bar" /></div>
          <div class="kpi-content">
            <span class="kpi-label">Pipeline</span>
            <span class="kpi-value">{{ fmt(metrics.pipeline.total_value) }}</span>
            <span class="kpi-detail">{{ metrics.pipeline.deal_count }} deals · {{ isVi ? 'Trọng số' : 'Weighted' }}: {{ fmt(metrics.pipeline.weighted_value) }}</span>
          </div>
        </div>
        <div class="kpi-card kpi-conversion">
          <div class="kpi-icon-wrapper conversion-icon"><i class="pi pi-percentage" /></div>
          <div class="kpi-content">
            <span class="kpi-label">{{ isVi ? 'Tỷ lệ chuyển đổi' : 'Conversion' }}</span>
            <span class="kpi-value">{{ metrics.conversion.lead_to_deal }}%</span>
            <span class="kpi-detail">Lead→Deal · {{ metrics.conversion.deal_to_won }}% Deal→Won</span>
          </div>
        </div>
        <div class="kpi-card kpi-cac">
          <div class="kpi-icon-wrapper cac-icon"><i class="pi pi-wallet" /></div>
          <div class="kpi-content">
            <span class="kpi-label">CAC</span>
            <span class="kpi-value">{{ fmt(metrics.cac.current) }}</span>
            <span class="kpi-change" :class="metrics.cac.change_percent <= 0 ? 'positive' : 'negative'">
              <i :class="metrics.cac.change_percent <= 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up'" />
              {{ Math.abs(metrics.cac.change_percent) }}%
            </span>
          </div>
        </div>
        <div class="kpi-card kpi-ltv">
          <div class="kpi-icon-wrapper ltv-icon"><i class="pi pi-heart" /></div>
          <div class="kpi-content">
            <span class="kpi-label">LTV</span>
            <span class="kpi-value">{{ fmt(metrics.ltv.ltv) }}</span>
            <span class="kpi-detail">LTV:CAC = {{ metrics.ltv.ltv_cac_ratio }}x
              <span class="badge" :class="metrics.ltv.ltv_cac_ratio >= 3 ? 'badge-success' : metrics.ltv.ltv_cac_ratio >= 1 ? 'badge-warning' : 'badge-danger'">
                {{ metrics.ltv.ltv_cac_ratio >= 3 ? 'Good' : metrics.ltv.ltv_cac_ratio >= 1 ? 'OK' : 'Poor' }}
              </span>
            </span>
          </div>
        </div>
        <div class="kpi-card kpi-churn">
          <div class="kpi-icon-wrapper churn-icon"><i class="pi pi-sign-out" /></div>
          <div class="kpi-content">
            <span class="kpi-label">Churn Rate</span>
            <span class="kpi-value">{{ metrics.churn.rate }}%</span>
            <span class="kpi-detail">{{ metrics.churn.lost_leads }} leads · {{ metrics.churn.lost_deals }} deals (90d)</span>
          </div>
        </div>
      </div>

      <!-- ═══ CRM QUICK STATS ═══ -->
      <div class="section-label">🎯 {{ isVi ? 'CRM Hoạt động' : 'CRM Operations' }}</div>
      <div class="crm-stats-grid">
        <div class="crm-stat-card" v-for="s in crmStatCards" :key="s.label">
          <div class="crm-stat-icon" :class="s.cls"><i :class="s.icon" /></div>
          <div class="crm-stat-info">
            <span class="crm-stat-value">{{ s.value }}</span>
            <span class="crm-stat-label">{{ s.label }}</span>
          </div>
        </div>
      </div>

      <!-- ═══ FINANCE + STRATEGY ═══ -->
      <div class="two-col-grid">
        <div class="module-card">
          <div class="module-header">
            <h3 class="module-title"><i class="pi pi-money-bill" /> {{ isVi ? 'Tài chính' : 'Finance' }}</h3>
            <a href="/finance" class="module-link">{{ isVi ? 'Chi tiết' : 'Details' }} →</a>
          </div>
          <div class="finance-grid">
            <div class="finance-item"><span class="fi-label">{{ isVi ? 'Thu tháng' : 'Income' }}</span><span class="fi-value text-green">{{ fmt(metrics.finance.monthly_income) }}</span></div>
            <div class="finance-item"><span class="fi-label">{{ isVi ? 'Chi tháng' : 'Expense' }}</span><span class="fi-value text-red">{{ fmt(metrics.finance.monthly_expense) }}</span></div>
            <div class="finance-item"><span class="fi-label">{{ isVi ? 'Lợi nhuận' : 'Profit' }}</span><span class="fi-value" :class="metrics.finance.monthly_profit >= 0 ? 'text-green' : 'text-red'">{{ fmt(metrics.finance.monthly_profit) }}</span></div>
            <div class="finance-item"><span class="fi-label">{{ isVi ? 'Biên LN' : 'Margin' }}</span><span class="fi-value">{{ metrics.finance.profit_margin }}%</span></div>
          </div>
          <div class="mini-chart-title">{{ isVi ? 'Xu hướng 6 tháng' : '6-Month Trend' }}</div>
          <div class="cashflow-bars">
            <div v-for="m in metrics.finance.cashflow_trend" :key="m.label" class="cashflow-bar-group">
              <div class="cashflow-bar-wrap">
                <div class="cashflow-bar income-bar" :style="{ height: cashflowHeight(m.income) + 'px' }" />
                <div class="cashflow-bar expense-bar" :style="{ height: cashflowHeight(m.expense) + 'px' }" />
              </div>
              <span class="cashflow-label">{{ m.label }}</span>
            </div>
          </div>
        </div>
        <div class="module-card">
          <div class="module-header">
            <h3 class="module-title"><i class="pi pi-compass" /> {{ isVi ? 'Chiến lược & OKR' : 'Strategy & OKR' }}</h3>
          </div>
          <div class="strategy-health-row">
            <div class="health-ring-wrap">
              <div class="health-ring" :style="healthRingStyle"><div class="health-ring-inner"><span class="health-ring-value">{{ metrics.strategy.avg_progress }}%</span><span class="health-ring-sub">{{ isVi ? 'Sức khoẻ' : 'Health' }}</span></div></div>
            </div>
            <div class="strategy-stats">
              <div class="ss-item"><span class="ss-num">{{ metrics.strategy.total_objectives }}</span><span class="ss-label">{{ isVi ? 'Mục tiêu' : 'Obj' }}</span></div>
              <div class="ss-item"><span class="ss-num text-green">{{ metrics.strategy.completed }}</span><span class="ss-label">{{ isVi ? 'Xong' : 'Done' }}</span></div>
              <div class="ss-item"><span class="ss-num" :class="metrics.strategy.at_risk > 0 ? 'text-amber' : ''">{{ metrics.strategy.at_risk }}</span><span class="ss-label">{{ isVi ? 'Rủi ro' : 'Risk' }}</span></div>
              <div class="ss-item"><span class="ss-num">{{ metrics.strategy.auto_tracked_krs }}<small>/{{ metrics.strategy.total_krs }}</small></span><span class="ss-label">Auto KR</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ HR + OPERATIONS ═══ -->
      <div class="two-col-grid">
        <div class="module-card">
          <div class="module-header">
            <h3 class="module-title"><i class="pi pi-users" /> {{ isVi ? 'Nhân sự' : 'HR' }}</h3>
          </div>
          <div class="hr-grid">
            <div class="hr-kpi"><span class="hr-val">{{ metrics.hr.headcount }}</span><span class="hr-lbl">{{ isVi ? 'Nhân viên' : 'Headcount' }}</span></div>
            <div class="hr-kpi"><span class="hr-val">{{ fmt(metrics.hr.revenue_per_employee) }}</span><span class="hr-lbl">Rev/Head</span></div>
            <div class="hr-kpi"><span class="hr-val" :class="metrics.hr.avg_kpi_achievement >= 80 ? 'text-green' : 'text-amber'">{{ metrics.hr.avg_kpi_achievement }}%</span><span class="hr-lbl">KPI</span></div>
            <div class="hr-kpi"><span class="hr-val">{{ metrics.hr.active_employees }}</span><span class="hr-lbl">Active</span></div>
          </div>
        </div>
        <div class="module-card">
          <div class="module-header">
            <h3 class="module-title"><i class="pi pi-cog" /> {{ isVi ? 'Vận hành' : 'Operations' }}</h3>
          </div>
          <div class="ops-grid">
            <div class="ops-stat"><span class="ops-icon"><i class="pi pi-building" /></span><span class="ops-value">{{ metrics.operations.total_customers }}</span><span class="ops-label">{{ isVi ? 'Khách hàng' : 'Customers' }}</span></div>
            <div class="ops-stat"><span class="ops-icon ops-green"><i class="pi pi-plus-circle" /></span><span class="ops-value">+{{ metrics.operations.new_customers_this_month }}</span><span class="ops-label">{{ isVi ? 'Mới tháng này' : 'New' }}</span></div>
            <div class="ops-stat"><span class="ops-icon ops-blue"><i class="pi pi-briefcase" /></span><span class="ops-value">{{ metrics.operations.active_projects }}</span><span class="ops-label">{{ isVi ? 'Dự án' : 'Projects' }}</span></div>
            <div class="ops-stat" :class="metrics.operations.overdue_projects > 0 ? 'ops-alert' : ''"><span class="ops-icon" :class="metrics.operations.overdue_projects > 0 ? 'ops-red' : 'ops-gray'"><i class="pi pi-exclamation-triangle" /></span><span class="ops-value">{{ metrics.operations.overdue_projects }}</span><span class="ops-label">{{ isVi ? 'Quá hạn' : 'Overdue' }}</span></div>
          </div>
        </div>
      </div>

      <!-- ═══ CHARTS ═══ -->
      <div class="charts-grid">
        <div class="chart-card chart-large">
          <div class="chart-header"><h3 class="chart-title"><i class="pi pi-chart-line" /> {{ isVi ? 'Xu hướng doanh thu 12 tháng' : 'Revenue Trend (12M)' }}</h3></div>
          <div class="chart-body"><canvas ref="revenueTrendChart" /></div>
        </div>
        <div class="chart-card chart-medium">
          <div class="chart-header"><h3 class="chart-title"><i class="pi pi-chart-pie" /> {{ isVi ? 'Deal theo giai đoạn' : 'Deals by Stage' }}</h3></div>
          <div class="chart-body"><canvas ref="stageChart" /></div>
        </div>
      </div>

      <!-- ═══ BOTTOM GRID ═══ -->
      <div class="bottom-grid">
        <div class="summary-card">
          <h3 class="summary-title"><i class="pi pi-calendar" /> {{ isVi ? 'Tổng kết doanh thu' : 'Revenue Summary' }}</h3>
          <div class="revenue-stats">
            <div class="revenue-item"><span class="revenue-label">{{ isVi ? 'Tháng này' : 'This Month' }}</span><span class="revenue-amount">{{ fmt(metrics.revenue.monthly) }}</span></div>
            <div class="revenue-item"><span class="revenue-label">{{ isVi ? 'Quý này' : 'This Quarter' }}</span><span class="revenue-amount">{{ fmt(metrics.revenue.quarterly) }}</span></div>
            <div class="revenue-item revenue-item-highlight"><span class="revenue-label">{{ isVi ? 'Năm nay' : 'This Year' }}</span><span class="revenue-amount">{{ fmt(metrics.revenue.yearly) }}</span></div>
          </div>
        </div>
        <div class="summary-card">
          <h3 class="summary-title"><i class="pi pi-star" /> {{ isVi ? 'Cơ hội lớn nhất' : 'Top Deals' }}</h3>
          <div class="top-deals-list">
            <div v-for="deal in metrics.top_deals" :key="deal.id" class="deal-item">
              <div class="deal-info"><span class="deal-title">{{ deal.title }}</span><span class="deal-meta">{{ deal.lead?.company || '—' }} <span class="deal-stage-badge">{{ deal.stage }}</span></span></div>
              <div class="deal-value">{{ fmt(deal.value) }}</div>
            </div>
            <div v-if="!metrics.top_deals.length" class="empty-state"><i class="pi pi-inbox" /><span>{{ isVi ? 'Không có' : 'No deals' }}</span></div>
          </div>
        </div>
        <div class="summary-card">
          <h3 class="summary-title"><i class="pi pi-filter" /> Pipeline Funnel</h3>
          <div class="funnel-list">
            <div v-for="stage in metrics.stage_distribution" :key="stage.stage" class="funnel-item">
              <div class="funnel-header"><span class="funnel-label">{{ stage.label }}</span><span class="funnel-count">{{ stage.count }} deals</span></div>
              <div class="funnel-bar-container"><div class="funnel-bar" :style="{ width: funnelWidth(stage) + '%' }" /></div>
              <span class="funnel-value">{{ fmt(stage.value) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CRM: QUICK ACTIONS + RECENT ACTIVITY ═══ -->
      <div class="section-label mt-3">⚡ {{ isVi ? 'Truy cập nhanh' : 'Quick Access' }}</div>
      <div class="two-col-grid">
        <div class="module-card">
          <div class="module-header"><h3 class="module-title">{{ isVi ? 'Hành động nhanh' : 'Quick Actions' }}</h3></div>
          <div class="qa-grid">
            <Link href="/leads/create" class="qa-btn qa-primary"><i class="pi pi-plus" /> {{ isVi ? 'Tạo Lead' : 'New Lead' }}</Link>
            <Link href="/deals" class="qa-btn"><i class="pi pi-briefcase" /> {{ isVi ? 'Pipeline' : 'Pipeline' }}</Link>
            <Link href="/contacts/create" class="qa-btn"><i class="pi pi-user-plus" /> {{ isVi ? 'Liên hệ mới' : 'New Contact' }}</Link>
            <Link href="/organizations/create" class="qa-btn"><i class="pi pi-building" /> {{ isVi ? 'Tổ chức mới' : 'New Org' }}</Link>
          </div>
        </div>
        <div class="module-card">
          <div class="module-header"><h3 class="module-title">{{ isVi ? 'Hoạt động gần đây' : 'Recent Activity' }}</h3></div>
          <div v-if="recentLeads && recentLeads.length > 0" class="activity-list">
            <div v-for="lead in recentLeads" :key="lead.id" class="activity-item">
              <div class="activity-dot" />
              <div class="activity-content">
                <Link :href="`/leads/${lead.id}/edit`" class="activity-name">{{ lead.name }}</Link>
                <span class="activity-company">{{ lead.company || 'No company' }}</span>
              </div>
              <Tag :value="statuses[lead.status] || lead.status" :severity="getStatusSeverity(lead.status)" />
            </div>
          </div>
          <div v-else class="empty-state"><i class="pi pi-inbox" /><span>{{ isVi ? 'Chưa có' : 'No activity' }}</span></div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Tag from 'primevue/tag'
import { useTranslation } from '@/composables/useTranslation'
import Chart from 'chart.js/auto'

export default {
  components: { Head, Link, Tag },
  layout: Layout,
  props: {
    metrics: Object,
    crmStats: { type: Object, default: () => ({}) },
    chartData: { type: Object, default: () => ({}) },
    recentLeads: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
  },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      refreshing: false,
      charts: {},
      aiBriefing: null,
      showSimulator: false,
      simParams: { hiring_count: 1, win_rate_boost: 5 },
      simResult: null,
      simTimeout: null,
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    healthRingStyle() {
      const p = this.metrics.strategy?.avg_progress ?? 0
      const color = p >= 70 ? '#10b981' : p >= 40 ? '#f59e0b' : '#ef4444'
      return { background: `conic-gradient(${color} ${p * 3.6}deg, #f1f5f9 0deg)` }
    },
    maxCashflow() {
      return Math.max(...(this.metrics.finance?.cashflow_trend ?? []).map(m => Math.max(m.income, m.expense)), 1)
    },
    crmStatCards() {
      const s = this.crmStats
      return [
        { label: this.isVi ? 'Tổng Leads' : 'Total Leads', value: s.totalLeads || 0, icon: 'pi pi-users', cls: 'bg-blue' },
        { label: this.isVi ? 'Deal mở' : 'Open Deals', value: s.openDeals || 0, icon: 'pi pi-briefcase', cls: 'bg-indigo' },
        { label: this.isVi ? 'Deal thắng' : 'Won Deals', value: s.wonDeals || 0, icon: 'pi pi-check-circle', cls: 'bg-green' },
        { label: this.isVi ? 'Task quá hạn' : 'Overdue Tasks', value: s.tasksOverdue || 0, icon: 'pi pi-exclamation-triangle', cls: s.tasksOverdue > 0 ? 'bg-red' : 'bg-gray' },
        { label: this.isVi ? 'Lead chất lượng cao' : 'High Quality Leads', value: s.highQualityLeads || 0, icon: 'pi pi-star', cls: 'bg-emerald' },
        { label: 'ICP Match', value: (s.icpMatchRate || 0) + '%', icon: 'pi pi-bullseye', cls: 'bg-purple' },
      ]
    },
  },
  mounted() {
    this.fetchAiBriefing()
    this.$nextTick(() => { this.initRevenueTrendChart(); this.initStageChart() })
  },
  beforeUnmount() { Object.values(this.charts).forEach(c => c?.destroy()) },
  methods: {
    fmt(value) {
      if (!value && value !== 0) return '—'
      if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'B ₫'
      if (value >= 1000000) return (value / 1000000).toFixed(0) + 'M ₫'
      return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + ' ₫'
    },
    formatTime(date) { return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }).format(date) },
    funnelWidth(stage) {
      const maxVal = Math.max(...this.metrics.stage_distribution.map(s => s.value || 1))
      return maxVal > 0 ? Math.max(((stage.value || 0) / maxVal) * 100, 4) : 4
    },
    cashflowHeight(value) { return Math.max((value / this.maxCashflow) * 48, 2) },
    getStatusSeverity(status) {
      const map = { new: 'info', contacted: 'warning', qualified: 'success', won: 'success', lost: 'danger' }
      return map[status] || 'secondary'
    },
    async fetchAiBriefing() {
      try {
        const res = await fetch('/api/sales/ai-advisor/briefing')
        if (res.ok) { this.aiBriefing = await res.json(); this.runSimulation() }
      } catch (e) { console.error('AI Briefing error:', e) }
    },
    async runSimulation() {
      clearTimeout(this.simTimeout)
      this.simTimeout = setTimeout(async () => {
        try {
          const res = await fetch('/api/sales/ai-advisor/simulate', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(this.simParams)
          })
          if (res.ok) { this.simResult = await res.json() }
        } catch (e) { console.error('Sim error:', e) }
      }, 300)
    },
    async refreshData() {
      this.refreshing = true
      try {
        const res = await fetch('/dashboard/refresh', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
        })
        if (res.ok) {
          const data = await res.json()
          Object.assign(this.metrics, data)
          this.fetchAiBriefing()
          Object.values(this.charts).forEach(c => c?.destroy())
          this.charts = {}
          this.$nextTick(() => { this.initRevenueTrendChart(); this.initStageChart() })
        }
      } finally { this.refreshing = false }
    },
    initRevenueTrendChart() {
      const ctx = this.$refs.revenueTrendChart
      if (!ctx) return
      const trends = this.metrics.trends || []
      this.charts.revenue = new Chart(ctx, {
        type: 'line',
        data: {
          labels: trends.map(t => t.short_label),
          datasets: [
            { label: this.isVi ? 'Doanh thu' : 'Revenue', data: trends.map(t => t.revenue), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', fill: true, tension: 0.4, pointBackgroundColor: '#6366f1', pointBorderWidth: 0, pointRadius: 4, pointHoverRadius: 7 },
            { label: 'Leads', data: trends.map(t => t.new_leads), borderColor: '#f59e0b', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.4, pointRadius: 3, yAxisID: 'y1' },
          ],
        },
        options: {
          responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
          plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } }, tooltip: { backgroundColor: '#1e293b', cornerRadius: 8, padding: 12 } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => this.fmt(v) } },
            y1: { position: 'right', beginAtZero: true, grid: { display: false } },
            x: { grid: { display: false } },
          },
        },
      })
    },
    initStageChart() {
      const ctx = this.$refs.stageChart
      if (!ctx) return
      const stages = this.metrics.stage_distribution || []
      this.charts.stage = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: stages.map(s => s.label), datasets: [{ data: stages.map(s => s.value || 0), backgroundColor: ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899'], borderWidth: 0, hoverOffset: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } } } },
      })
    },
  },
}
</script>

<style scoped>
/* AI Briefing */
.ai-briefing-section { margin-bottom: 1.5rem; }
.pulse-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
.pulse-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
.pulse-stable::before { background: #3b82f6; } .pulse-behind::before { background: #ef4444; } .pulse-excellent::before { background: #10b981; }
.pulse-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; }
.pulse-dot-wrapper { display: flex; align-items: center; justify-content: center; width: 12px; height: 12px; }
.pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.2); animation: pulse 2s infinite; }
.pulse-behind .pulse-dot { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.2); }
.pulse-excellent .pulse-dot { background: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,0.2); }
@keyframes pulse { 0%{transform:scale(.95);opacity:.8} 70%{transform:scale(1.1);opacity:.3} 100%{transform:scale(.95);opacity:.8} }
.pulse-title { font-weight: 700; color: #1e293b; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
.pulse-badge { background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; color: #475569; font-weight: 500; flex: 1; }
.briefing-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 1.5rem; }
.briefing-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem; }
.alert-item { background: #fff1f2; border-left: 3px solid #f43f5e; padding: 0.6rem; border-radius: 4px; margin-bottom: 0.5rem; }
.alert-title { display: block; font-weight: 700; color: #9f1239; font-size: 0.85rem; }
.alert-action { display: block; font-size: 0.8rem; color: #be123c; margin-top: 0.2rem; font-style: italic; }
.projection-card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.75rem; }
.proj-main { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.proj-val { font-size: 1.1rem; font-weight: 800; color: #0369a1; }
.proj-confidence { font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px; text-transform: uppercase; }
.conf-high { background: #dcfce7; color: #166534; } .conf-low { background: #fef3c7; color: #92400e; }
.proj-detail { font-size: 0.75rem; color: #0c4a6e; line-height: 1.3; }
.runway-track { height: 6px; background: #e0f2fe; border-radius: 3px; margin-top: 0.75rem; overflow: hidden; }
.runway-fill { height: 100%; background: #0ea5e9; border-radius: 3px; }
.action-item { font-size: 0.8rem; color: #334155; margin-bottom: 0.4rem; display: flex; align-items: flex-start; gap: 0.5rem; }
.action-item i { margin-top: 3px; font-size: 0.7rem; color: #3b82f6; }
.empty-state-brief { font-size: 0.8rem; color: #94a3b8; display: flex; align-items: center; gap: 0.5rem; }
.empty-state-brief i { color: #10b981; }
.bn-item { border-left: 2px solid #f59e0b; padding-left: 0.5rem; margin-bottom: 0.4rem; }
.bn-stage { display: block; font-size: 0.75rem; font-weight: 700; color: #1e293b; }
.bn-issue { display: block; font-size: 0.7rem; color: #b45309; }
.governance-wrap { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem; }
.gov-item { margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; }
.gov-item:last-child { margin-bottom: 0; border-bottom: none; }
.gov-info { font-size: 0.75rem; font-weight: 700; color: #334155; } .gov-type { color: #6366f1; }
.gov-reason { font-size: 0.75rem; color: #475569; } .gov-date { font-size: 0.65rem; color: #94a3b8; }
.simulator-trigger { margin-top: 1rem; cursor: pointer; color: #3b82f6; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.6rem; background: #eff6ff; border-radius: 6px; width: fit-content; }
.simulator-panel { background: #f8fafc; border-top: 1px solid #e2e8f0; margin: 1rem -1rem -1rem -1rem; padding: 1rem; }
.sim-header { font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 0.75rem; }
.sim-controls { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; align-items: flex-end; }
.sim-control { display: flex; flex-direction: column; gap: 0.3rem; }
.sim-control label { font-size: 0.7rem; color: #64748b; font-weight: 600; }
.sim-control input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.35rem 0.5rem; font-size: 0.85rem; outline: none; }
.sim-control input:focus { border-color: #3b82f6; }
.sim-result { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.75rem; }
.sim-lift { font-size: 1rem; font-weight: 800; color: #10b981; } .lift-pct { font-size: 0.75rem; font-weight: 600; color: #166534; }
.sim-meta { font-size: 0.7rem; color: #64748b; }
.mt-3 { margin-top: 0.75rem; }
.anim-slide-down { animation: slideDown 0.3s ease-out; }
@keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }

/* Header */
.exec-dashboard { padding: 0; }
.exec-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
.exec-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.exec-subtitle { font-size: 0.8rem; color: #64748b; margin: 0.2rem 0 0; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.plan-badge { background: linear-gradient(135deg,#eef2ff,#e0e7ff); color: #6366f1; padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
.exec-header-right { display: flex; align-items: center; gap: 0.65rem; }
.last-updated { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.3rem; }
.refresh-btn { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: white; border: none; padding: 0.45rem 0.85rem; border-radius: 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; transition: all 0.2s; }
.refresh-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.refresh-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.section-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; margin: 0.5rem 0 0.35rem; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
.kpi-card { background: white; border-radius: 10px; padding: 0.85rem; display: flex; align-items: flex-start; gap: 0.65rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; transition: all 0.2s; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.kpi-icon-wrapper { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.kpi-icon-wrapper i { font-size: 1rem; color: white; }
.revenue-icon { background: linear-gradient(135deg,#10b981,#059669); }
.pipeline-icon { background: linear-gradient(135deg,#6366f1,#4f46e5); }
.conversion-icon { background: linear-gradient(135deg,#f59e0b,#d97706); }
.cac-icon { background: linear-gradient(135deg,#ef4444,#dc2626); }
.ltv-icon { background: linear-gradient(135deg,#ec4899,#db2777); }
.churn-icon { background: linear-gradient(135deg,#64748b,#475569); }
.kpi-content { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.kpi-label { font-size: 0.6rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; }
.kpi-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.kpi-change { font-size: 0.68rem; display: flex; align-items: center; gap: 0.25rem; }
.kpi-change.positive { color: #10b981; } .kpi-change.negative { color: #ef4444; }
.kpi-change i { font-size: 0.6rem; } .kpi-period { color: #94a3b8; font-weight: 400; }
.kpi-detail { font-size: 0.65rem; color: #64748b; }
.badge { display: inline-block; padding: 0.05rem 0.35rem; border-radius: 5px; font-size: 0.55rem; font-weight: 700; text-transform: uppercase; margin-left: 0.2rem; }
.badge-success { background: #d1fae5; color: #059669; } .badge-warning { background: #fef3c7; color: #d97706; } .badge-danger { background: #fee2e2; color: #dc2626; }

/* CRM Stats Grid */
.crm-stats-grid { display: grid; grid-template-columns: repeat(6,1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
.crm-stat-card { background: white; border-radius: 10px; padding: 0.75rem; display: flex; align-items: center; gap: 0.6rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s; }
.crm-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.crm-stat-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.crm-stat-icon i { font-size: 0.9rem; color: white; }
.bg-blue { background: #3b82f6; } .bg-indigo { background: #6366f1; } .bg-green { background: #10b981; }
.bg-red { background: #ef4444; } .bg-gray { background: #94a3b8; } .bg-emerald { background: #059669; } .bg-purple { background: #8b5cf6; }
.crm-stat-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; display: block; }
.crm-stat-label { font-size: 0.6rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }

/* Two-col grid */
.two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem; }
.module-card { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; }
.module-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.module-title { font-size: 0.82rem; font-weight: 700; color: #334155; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.module-title i { color: #6366f1; }
.module-link { font-size: 0.65rem; color: #6366f1; text-decoration: none; }
.text-green { color: #10b981; } .text-red { color: #ef4444; } .text-amber { color: #f59e0b; }

/* Finance */
.finance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem; margin-bottom: 0.65rem; }
.finance-item { padding: 0.45rem 0.5rem; background: #f8fafc; border-radius: 6px; display: flex; flex-direction: column; }
.fi-label { font-size: 0.6rem; color: #94a3b8; font-weight: 500; } .fi-value { font-size: 0.85rem; font-weight: 700; color: #1e293b; }
.mini-chart-title { font-size: 0.6rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.35rem; }
.cashflow-bars { display: flex; gap: 0.35rem; align-items: flex-end; height: 54px; }
.cashflow-bar-group { display: flex; flex-direction: column; align-items: center; gap: 0.15rem; flex: 1; }
.cashflow-bar-wrap { display: flex; gap: 1px; align-items: flex-end; }
.cashflow-bar { width: 8px; border-radius: 2px 2px 0 0; transition: height 0.4s; }
.income-bar { background: #10b981; } .expense-bar { background: #f87171; }
.cashflow-label { font-size: 0.55rem; color: #94a3b8; }

/* Strategy */
.strategy-health-row { display: flex; align-items: center; gap: 1rem; }
.health-ring { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.health-ring-inner { background: white; width: 54px; height: 54px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.health-ring-value { font-size: 0.9rem; font-weight: 700; color: #1e293b; line-height: 1; }
.health-ring-sub { font-size: 0.45rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.strategy-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem; flex: 1; }
.ss-item { text-align: center; padding: 0.3rem; background: #f8fafc; border-radius: 6px; }
.ss-num { display: block; font-size: 1.1rem; font-weight: 700; color: #1e293b; } .ss-num small { font-size: 0.6rem; color: #94a3b8; }
.ss-label { font-size: 0.55rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }

/* HR */
.hr-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0.35rem; }
.hr-kpi { text-align: center; padding: 0.5rem 0.3rem; background: #f8fafc; border-radius: 8px; }
.hr-val { display: block; font-size: 1rem; font-weight: 700; color: #1e293b; }
.hr-lbl { font-size: 0.55rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }

/* Operations */
.ops-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.ops-stat { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0.6rem; background: #f8fafc; border-radius: 8px; }
.ops-stat.ops-alert { background: #fffbeb; }
.ops-icon { font-size: 1.1rem; margin-bottom: 0.2rem; }
.ops-green i { color: #10b981; } .ops-blue i { color: #6366f1; } .ops-red i { color: #ef4444; } .ops-gray i { color: #94a3b8; }
.ops-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.ops-label { font-size: 0.6rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }

/* Charts */
.charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem; }
.chart-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden; }
.chart-header { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; }
.chart-title { font-size: 0.82rem; font-weight: 600; color: #334155; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.chart-title i { color: #6366f1; } .chart-body { padding: 0.75rem; height: 260px; }

/* Bottom Grid */
.bottom-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 0.75rem; margin-bottom: 0.75rem; }
.summary-card { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; }
.summary-title { font-size: 0.82rem; font-weight: 700; color: #334155; margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.4rem; }
.summary-title i { color: #6366f1; }
.revenue-stats { display: flex; flex-direction: column; gap: 0.4rem; }
.revenue-item { display: flex; justify-content: space-between; align-items: center; padding: 0.45rem 0.65rem; background: #f8fafc; border-radius: 6px; }
.revenue-item-highlight { background: linear-gradient(135deg,rgba(99,102,241,0.08),rgba(139,92,246,0.08)); border: 1px solid rgba(99,102,241,0.12); }
.revenue-label { font-size: 0.75rem; color: #64748b; } .revenue-amount { font-size: 0.82rem; font-weight: 700; color: #0f172a; }
.revenue-item-highlight .revenue-amount { color: #6366f1; }
.top-deals-list { display: flex; flex-direction: column; gap: 0.35rem; }
.deal-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 6px; }
.deal-info { display: flex; flex-direction: column; min-width: 0; }
.deal-title { font-size: 0.75rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.deal-meta { font-size: 0.65rem; color: #94a3b8; }
.deal-stage-badge { background: rgba(99,102,241,0.1); color: #6366f1; padding: 0.02rem 0.25rem; border-radius: 3px; font-size: 0.55rem; font-weight: 600; }
.deal-value { font-size: 0.78rem; font-weight: 700; color: #10b981; white-space: nowrap; }
.funnel-list { display: flex; flex-direction: column; gap: 0.55rem; }
.funnel-item { display: flex; flex-direction: column; gap: 0.15rem; }
.funnel-header { display: flex; justify-content: space-between; } .funnel-label { font-size: 0.72rem; font-weight: 600; color: #334155; } .funnel-count { font-size: 0.65rem; color: #94a3b8; }
.funnel-bar-container { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.funnel-bar { height: 100%; background: linear-gradient(90deg,#6366f1,#8b5cf6); border-radius: 3px; transition: width 0.5s; }
.funnel-value { font-size: 0.68rem; color: #64748b; }
.empty-state { text-align: center; padding: 1.5rem; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 0.4rem; font-size: 0.78rem; }

/* Quick Actions */
.qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.qa-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.65rem 0.75rem; border-radius: 8px; background: #f9fafb; border: 1px solid #e5e7eb; font-size: 0.8rem; font-weight: 500; color: #374151; text-decoration: none; transition: all 0.2s; }
.qa-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.qa-btn i { font-size: 0.85rem; color: #6b7280; }
.qa-primary { background: linear-gradient(135deg,#6366f1,#4f46e5); border-color: transparent; color: white; }
.qa-primary:hover { box-shadow: 0 4px 12px rgba(99,102,241,0.3); color: white; }
.qa-primary i { color: white; }

/* Activity */
.activity-list { display: flex; flex-direction: column; gap: 0.35rem; }
.activity-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.65rem; border-radius: 8px; background: #f9fafb; }
.activity-item:hover { background: #f3f4f6; }
.activity-dot { width: 8px; height: 8px; border-radius: 50%; background: #6366f1; flex-shrink: 0; }
.activity-content { flex: 1; min-width: 0; }
.activity-name { display: block; font-size: 0.82rem; font-weight: 600; color: #111827; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.activity-name:hover { color: #6366f1; }
.activity-company { font-size: 0.72rem; color: #9ca3af; }

/* Responsive */
@media (max-width: 1400px) { .kpi-grid, .crm-stats-grid { grid-template-columns: repeat(3,1fr); } }
@media (max-width: 1024px) { .two-col-grid, .charts-grid { grid-template-columns: 1fr; } .briefing-grid { grid-template-columns: 1fr; } .sim-controls { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .kpi-grid, .crm-stats-grid { grid-template-columns: repeat(2,1fr); } .bottom-grid { grid-template-columns: 1fr; } .hr-grid { grid-template-columns: repeat(2,1fr); } }
</style>
