<template>
  <div>
    <Head title="Financial Overview" />

    <div class="page-header">
      <div>
        <h1 class="page-title">Financial Overview</h1>
        <p class="page-subtitle">{{ analytics.summary.period_label }}</p>
      </div>
      <div class="header-actions">
        <Link href="/finance/transactions"><Button label="All Transactions" icon="pi pi-list" severity="secondary" /></Link>
        <Button label="Add Transaction" icon="pi pi-plus" @click="showCreate = true" />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="summary-card card-income">
        <div class="card-icon"><i class="pi pi-arrow-down-left" /></div>
        <div class="card-body">
          <span class="card-value">{{ fmt(analytics.summary.total_income) }}</span>
          <span class="card-label">Total Income</span>
          <span class="card-change" :class="analytics.summary.income_growth >= 0 ? 'change-up' : 'change-down'">
            <i :class="analytics.summary.income_growth >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
            {{ Math.abs(analytics.summary.income_growth) }}%
          </span>
        </div>
      </div>
      <div class="summary-card card-expense">
        <div class="card-icon"><i class="pi pi-arrow-up-right" /></div>
        <div class="card-body">
          <span class="card-value">{{ fmt(analytics.summary.total_expenses) }}</span>
          <span class="card-label">Total Expenses</span>
          <span class="card-change" :class="analytics.summary.expense_growth <= 0 ? 'change-up' : 'change-down'">
            <i :class="analytics.summary.expense_growth <= 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up'" />
            {{ Math.abs(analytics.summary.expense_growth) }}%
          </span>
        </div>
      </div>
      <div class="summary-card card-cashflow" :class="analytics.summary.net_cashflow >= 0 ? 'positive-glow' : 'negative-glow'">
        <div class="card-icon"><i class="pi pi-wallet" /></div>
        <div class="card-body">
          <span class="card-value">{{ fmt(analytics.summary.net_cashflow) }}</span>
          <span class="card-label">Net Cashflow</span>
        </div>
      </div>
      <div class="summary-card card-margin">
        <div class="card-icon"><i class="pi pi-percentage" /></div>
        <div class="card-body">
          <span class="card-value">{{ analytics.summary.profit_margin }}%</span>
          <span class="card-label">Profit Margin</span>
        </div>
      </div>
      <div class="summary-card card-burn">
        <div class="card-icon"><i class="pi pi-bolt" /></div>
        <div class="card-body">
          <span class="card-value">{{ fmt(analytics.summary.burn_rate) }}</span>
          <span class="card-label">Burn Rate/mo</span>
        </div>
      </div>
      <div class="summary-card card-runway">
        <div class="card-icon"><i class="pi pi-clock" /></div>
        <div class="card-body">
          <span class="card-value">{{ analytics.summary.runway_months }}<small>mo</small></span>
          <span class="card-label">Runway</span>
        </div>
      </div>
      <div class="summary-card card-balance">
        <div class="card-icon"><i class="pi pi-database" /></div>
        <div class="card-body">
          <span class="card-value">{{ fmt(analytics.summary.cash_balance) }}</span>
          <span class="card-label">Cash Balance</span>
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Cashflow Trend Chart -->
      <div class="section-card chart-wide">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-chart-line" /> Cashflow Trend (12 months)</h2>
        </div>
        <div class="bar-chart">
          <div class="chart-y-labels">
            <span>{{ fmtCompact(maxCashflow) }}</span>
            <span>{{ fmtCompact(maxCashflow / 2) }}</span>
            <span>0</span>
          </div>
          <div class="chart-bars">
            <div v-for="item in analytics.cashflow_trend" :key="item.month" class="chart-col">
              <div class="bar-group">
                <div class="bar bar-income" :style="{ height: barHeight(item.income) + 'px' }" :title="'Income: ' + fmt(item.income)"></div>
                <div class="bar bar-expense" :style="{ height: barHeight(item.expenses) + 'px' }" :title="'Expense: ' + fmt(item.expenses)"></div>
              </div>
              <span class="bar-label">{{ item.label }}</span>
            </div>
          </div>
        </div>
        <div class="chart-legend">
          <span class="legend-item"><span class="legend-dot dot-income"></span> Income</span>
          <span class="legend-item"><span class="legend-dot dot-expense"></span> Expenses</span>
        </div>
      </div>

      <!-- Profit Margin Trend -->
      <div class="section-card chart-half">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-chart-bar" /> Profit Margin Trend</h2>
        </div>
        <div class="margin-chart">
          <div v-for="item in analytics.profit_trend" :key="item.month" class="margin-col">
            <div class="margin-bar-wrap">
              <div class="margin-bar" :class="item.margin >= 0 ? 'bar-positive' : 'bar-negative'" :style="{ height: Math.abs(item.margin) * 1.2 + 'px' }">
                <span class="margin-val">{{ item.margin }}%</span>
              </div>
            </div>
            <span class="margin-label">{{ item.label }}</span>
          </div>
        </div>
      </div>

      <!-- Expense Breakdown -->
      <div class="section-card chart-half">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-chart-pie" /> Expense Breakdown</h2>
        </div>
        <div class="breakdown-list">
          <div v-for="(item, idx) in analytics.expense_breakdown" :key="item.category" class="bd-row">
            <span class="bd-rank" :class="`rank-${idx + 1}`">{{ idx + 1 }}</span>
            <div class="bd-info">
              <span class="bd-name">{{ item.label }}</span>
              <div class="bd-bar-wrap">
                <div class="bd-bar" :style="{ width: expPercent(item.total) + '%' }"></div>
              </div>
            </div>
            <span class="bd-amount">{{ fmtCompact(item.total) }}</span>
          </div>
          <div v-if="analytics.expense_breakdown.length === 0" class="empty-mini">No expenses recorded</div>
        </div>
      </div>

      <!-- Burn Rate Analysis -->
      <div class="section-card chart-half">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-bolt" /> Burn Rate Analysis</h2>
        </div>
        <div class="burn-metrics">
          <div class="burn-metric">
            <span class="bm-label">Gross Burn Rate</span>
            <span class="bm-value">{{ fmt(analytics.burn_rate.gross_burn_rate) }}/mo</span>
          </div>
          <div class="burn-metric">
            <span class="bm-label">Net Burn Rate</span>
            <span class="bm-value" :class="analytics.burn_rate.net_burn_rate > 0 ? 'text-red' : 'text-green'">{{ fmt(analytics.burn_rate.net_burn_rate) }}/mo</span>
          </div>
          <div class="burn-metric">
            <span class="bm-label">Avg Monthly Income</span>
            <span class="bm-value text-green">{{ fmt(analytics.burn_rate.avg_monthly_income) }}</span>
          </div>
          <div class="burn-metric">
            <span class="bm-label">Cash Balance</span>
            <span class="bm-value">{{ fmt(analytics.burn_rate.cash_balance) }}</span>
          </div>
          <div class="burn-divider"></div>
          <div class="runway-display">
            <div class="runway-ring">
              <svg viewBox="0 0 36 36" class="runway-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="ring-fg" :class="runwayColor" :stroke-dasharray="`${Math.min(analytics.burn_rate.net_runway_months * 4, 100)}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <span class="runway-text">{{ analytics.burn_rate.net_runway_months }}</span>
            </div>
            <div class="runway-info">
              <span class="runway-title">Net Runway</span>
              <span class="runway-sub">months remaining</span>
              <span class="burn-trend" :class="analytics.burn_rate.burn_trend_percent <= 0 ? 'text-green' : 'text-red'">
                <i :class="analytics.burn_rate.burn_trend_percent <= 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up'" />
                {{ Math.abs(analytics.burn_rate.burn_trend_percent) }}% trend
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Comparison -->
      <div class="section-card chart-half">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-arrows-h" /> Month over Month</h2>
        </div>
        <div class="comparison-grid">
          <div class="comp-item">
            <span class="comp-label">{{ t('common.income') }}</span>
            <div class="comp-values">
              <span class="comp-current">{{ fmtCompact(analytics.monthly_comparison.income.current) }}</span>
              <span class="comp-arrow" :class="analytics.monthly_comparison.income.change >= 0 ? 'arrow-up' : 'arrow-down'">
                <i :class="analytics.monthly_comparison.income.change >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
                {{ Math.abs(analytics.monthly_comparison.income.change) }}%
              </span>
            </div>
            <span class="comp-prev">vs {{ fmtCompact(analytics.monthly_comparison.income.previous) }}</span>
          </div>
          <div class="comp-item">
            <span class="comp-label">Expenses</span>
            <div class="comp-values">
              <span class="comp-current">{{ fmtCompact(analytics.monthly_comparison.expenses.current) }}</span>
              <span class="comp-arrow" :class="analytics.monthly_comparison.expenses.change <= 0 ? 'arrow-up' : 'arrow-down'">
                <i :class="analytics.monthly_comparison.expenses.change <= 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up'" />
                {{ Math.abs(analytics.monthly_comparison.expenses.change) }}%
              </span>
            </div>
            <span class="comp-prev">vs {{ fmtCompact(analytics.monthly_comparison.expenses.previous) }}</span>
          </div>
          <div class="comp-item">
            <span class="comp-label">Profit</span>
            <div class="comp-values">
              <span class="comp-current" :class="analytics.monthly_comparison.profit.current >= 0 ? 'text-green' : 'text-red'">{{ fmtCompact(analytics.monthly_comparison.profit.current) }}</span>
            </div>
            <span class="comp-prev">vs {{ fmtCompact(analytics.monthly_comparison.profit.previous) }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="section-card chart-wide">
        <div class="section-header">
          <h2 class="section-title"><i class="pi pi-list" /> Recent Transactions</h2>
          <Link href="/finance/transactions" class="see-all">View All →</Link>
        </div>
        <div class="txn-list">
          <div v-for="txn in analytics.recent_transactions" :key="txn.id" class="txn-row">
            <div class="txn-icon" :class="txn.type === 'income' ? 'txn-in' : 'txn-out'">
              <i :class="txn.type === 'income' ? 'pi pi-arrow-down-left' : 'pi pi-arrow-up-right'" />
            </div>
            <div class="txn-info">
              <span class="txn-desc">{{ txn.description }}</span>
              <span class="txn-cat">{{ txn.category_label }}</span>
            </div>
            <span class="txn-date">{{ txn.transaction_date }}</span>
            <span class="txn-amount" :class="txn.type === 'income' ? 'text-green' : 'text-red'">
              {{ txn.type === 'income' ? '+' : '−' }}{{ fmt(txn.amount) }}
            </span>
          </div>
          <div v-if="analytics.recent_transactions.length === 0" class="empty-mini">No transactions yet</div>
        </div>
      </div>
    </div>

    <!-- Create Transaction Dialog -->
    <Dialog v-model:visible="showCreate" header="Record Transaction" :modal="true" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="type-toggle">
          <button class="toggle-btn" :class="{ active: txnForm.type === 'income' }" @click="txnForm.type = 'income'; txnForm.category = null">
            <i class="pi pi-arrow-down-left" /> Income
          </button>
          <button class="toggle-btn toggle-expense" :class="{ active: txnForm.type === 'expense' }" @click="txnForm.type = 'expense'; txnForm.category = null">
            <i class="pi pi-arrow-up-right" /> Expense
          </button>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <Select v-model="txnForm.category" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Select category" class="w-full" />
        </div>
        <div class="form-group">
          <label>Description *</label>
          <InputText v-model="txnForm.description" placeholder="What is this transaction for?" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Amount (VND) *</label>
            <InputText v-model="txnForm.amount" type="number" class="w-full" />
          </div>
          <div class="form-group">
            <label>Date *</label>
            <InputText v-model="txnForm.transaction_date" type="date" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Reference (Invoice #, Receipt #)</label>
          <InputText v-model="txnForm.reference" placeholder="Optional" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group form-check">
            <label><input type="checkbox" v-model="txnForm.is_recurring" /> Recurring</label>
          </div>
          <div class="form-group" v-if="txnForm.is_recurring">
            <label>Period</label>
            <Select v-model="txnForm.recurring_period" :options="recurringOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>{{ t('common.notes') }}</label>
          <Textarea v-model="txnForm.notes" rows="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showCreate = false" />
        <Button label="Save" icon="pi pi-check" @click="submitTransaction" :loading="saving" />
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

const INCOME_CATS = {
  deal_revenue: 'Deal Revenue', project_revenue: 'Project Revenue',
  service_fee: 'Service Fee', subscription_income: 'Subscription Income', other_income: 'Other Income',
}
const EXPENSE_CATS = {
  salary: 'Salary & Wages', office: 'Office & Rent', software: 'Software & Tools',
  marketing: 'Marketing & Ads', hosting: 'Hosting & Infrastructure', travel: 'Travel & Transport',
  equipment: 'Equipment & Hardware', tax: 'Tax & Compliance', insurance: 'Insurance',
  contractor: 'Contractors & Freelancers', other_expense: 'Other Expense',
}

export default {
  components: { Head, Link, Button, InputText, Textarea, Select, Dialog },
  layout: Layout,
  props: { analytics: Object },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      showCreate: false,
      saving: false,
      txnForm: this.freshForm(),
    }
  },
  computed: {
    maxCashflow() {
      const vals = (this.analytics.cashflow_trend || []).flatMap(i => [i.income, i.expenses])
      return Math.max(...vals, 1)
    },
    maxExpense() {
      const vals = (this.analytics.expense_breakdown || []).map(i => i.total)
      return Math.max(...vals, 1)
    },
    categoryOptions() {
      const cats = this.txnForm.type === 'income' ? INCOME_CATS : EXPENSE_CATS
      return Object.entries(cats).map(([v, l]) => ({ value: v, label: l }))
    },
    recurringOptions() {
      return [
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' },
        { value: 'yearly', label: 'Yearly' },
      ]
    },
    runwayColor() {
      const m = this.analytics.burn_rate?.net_runway_months || 0
      if (m >= 12) return 'ring-green'
      if (m >= 6) return 'ring-blue'
      if (m >= 3) return 'ring-amber'
      return 'ring-red'
    },
  },
  methods: {
    freshForm() {
      return {
        type: 'expense', category: null, description: '', amount: 0,
        transaction_date: new Date().toISOString().split('T')[0],
        reference: '', is_recurring: false, recurring_period: 'monthly', notes: '',
      }
    },
    submitTransaction() {
      this.saving = true
      router.post('/finance/transactions', this.txnForm, {
        onSuccess: () => { this.showCreate = false; this.txnForm = this.freshForm() },
        onFinish: () => { this.saving = false },
      })
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
    barHeight(val) { return Math.round((val / this.maxCashflow) * 120) },
    expPercent(val) { return Math.round((val / this.maxExpense) * 100) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

/* Summary */
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.summary-card { background: white; border-radius: 14px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; transition: all 0.2s; position: relative; overflow: hidden; }
.summary-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.08); transform: translateY(-1px); }
.card-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; flex-shrink: 0; }
.card-income .card-icon { background: linear-gradient(135deg, #10b981, #059669); }
.card-expense .card-icon { background: linear-gradient(135deg, #f43f5e, #e11d48); }
.card-cashflow .card-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.card-margin .card-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.card-burn .card-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
.card-runway .card-icon { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.card-balance .card-icon { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.positive-glow { border-color: #bbf7d0; } .negative-glow { border-color: #fecaca; }
.card-body { display: flex; flex-direction: column; min-width: 0; }
.card-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-value small { font-size: 0.72rem; font-weight: 500; color: #94a3b8; }
.card-label { font-size: 0.68rem; color: #94a3b8; }
.card-change { font-size: 0.62rem; font-weight: 600; display: flex; align-items: center; gap: 0.15rem; margin-top: 0.1rem; }
.card-change i { font-size: 0.55rem; }
.change-up { color: #059669; } .change-down { color: #dc2626; }

/* Dashboard Grid */
.dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.chart-wide { grid-column: 1 / -1; }
.chart-half { min-width: 0; }
@media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }

/* Section Card */
.section-card { background: white; border-radius: 14px; padding: 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.section-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.section-title i { font-size: 0.85rem; color: #6366f1; }
.see-all { font-size: 0.75rem; color: #6366f1; text-decoration: none; font-weight: 600; }
.see-all:hover { text-decoration: underline; }

/* Bar Chart */
.bar-chart { display: flex; gap: 0.5rem; padding: 0.5rem 0; }
.chart-y-labels { display: flex; flex-direction: column; justify-content: space-between; font-size: 0.6rem; color: #94a3b8; width: 50px; text-align: right; padding-right: 0.4rem; height: 130px; }
.chart-bars { display: flex; gap: 0.35rem; flex: 1; align-items: flex-end; height: 130px; }
.chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.25rem; }
.bar-group { display: flex; gap: 2px; align-items: flex-end; }
.bar { width: 14px; border-radius: 3px 3px 0 0; transition: height 0.4s; min-height: 2px; cursor: pointer; }
.bar:hover { opacity: 0.8; }
.bar-income { background: linear-gradient(to top, #10b981, #34d399); }
.bar-expense { background: linear-gradient(to top, #f43f5e, #fb7185); }
.bar-label { font-size: 0.58rem; color: #94a3b8; }
.chart-legend { display: flex; gap: 1rem; justify-content: center; margin-top: 0.5rem; }
.legend-item { font-size: 0.68rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem; }
.legend-dot { width: 8px; height: 8px; border-radius: 2px; }
.dot-income { background: #10b981; } .dot-expense { background: #f43f5e; }

/* Profit Margin Chart */
.margin-chart { display: flex; gap: 0.3rem; align-items: flex-end; height: 120px; padding-top: 20px; }
.margin-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.2rem; }
.margin-bar-wrap { height: 90px; display: flex; align-items: flex-end; }
.margin-bar { width: 18px; border-radius: 3px 3px 0 0; min-height: 2px; position: relative; transition: height 0.4s; }
.bar-positive { background: linear-gradient(to top, #10b981, #6ee7b7); }
.bar-negative { background: linear-gradient(to top, #f43f5e, #fda4af); }
.margin-val { position: absolute; top: -16px; left: 50%; transform: translateX(-50%); font-size: 0.55rem; font-weight: 700; color: #475569; white-space: nowrap; }
.margin-label { font-size: 0.55rem; color: #94a3b8; }

/* Expense Breakdown */
.breakdown-list { display: flex; flex-direction: column; gap: 0.55rem; }
.bd-row { display: flex; align-items: center; gap: 0.5rem; }
.bd-rank { width: 20px; height: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; flex-shrink: 0; }
.rank-1 { background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; }
.rank-2 { background: #fecdd3; color: #be123c; }
.rank-3 { background: #fee2e2; color: #dc2626; }
.rank-4, .rank-5 { background: #f1f5f9; color: #64748b; }
.bd-info { flex: 1; min-width: 0; }
.bd-name { font-size: 0.75rem; font-weight: 500; color: #334155; display: block; margin-bottom: 0.15rem; }
.bd-bar-wrap { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.bd-bar { height: 100%; border-radius: 3px; background: linear-gradient(135deg, #f43f5e, #fb7185); transition: width 0.4s; }
.bd-amount { font-size: 0.75rem; font-weight: 700; color: #334155; white-space: nowrap; }

/* Burn Rate */
.burn-metrics { display: flex; flex-direction: column; gap: 0.5rem; }
.burn-metric { display: flex; justify-content: space-between; align-items: center; }
.bm-label { font-size: 0.75rem; color: #64748b; }
.bm-value { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.burn-divider { height: 1px; background: #f1f5f9; margin: 0.3rem 0; }
.runway-display { display: flex; align-items: center; gap: 1rem; padding-top: 0.3rem; }
.runway-ring { position: relative; width: 72px; height: 72px; flex-shrink: 0; }
.runway-svg { transform: rotate(-90deg); width: 72px; height: 72px; }
.ring-bg { fill: none; stroke: #f1f5f9; stroke-width: 3; }
.ring-fg { fill: none; stroke-width: 3; stroke-linecap: round; transition: stroke-dasharray 0.6s; }
.ring-green { stroke: #10b981; } .ring-blue { stroke: #3b82f6; } .ring-amber { stroke: #f59e0b; } .ring-red { stroke: #ef4444; }
.runway-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.1rem; font-weight: 800; color: #0f172a; }
.runway-info { display: flex; flex-direction: column; }
.runway-title { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.runway-sub { font-size: 0.65rem; color: #94a3b8; }
.burn-trend { font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 0.2rem; margin-top: 0.2rem; }
.burn-trend i { font-size: 0.6rem; }

/* Monthly Comparison */
.comparison-grid { display: flex; flex-direction: column; gap: 0.75rem; }
.comp-item { padding: 0.6rem; background: #fafbfc; border-radius: 10px; }
.comp-label { font-size: 0.68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; font-weight: 600; }
.comp-values { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.15rem; }
.comp-current { font-size: 1rem; font-weight: 700; color: #0f172a; }
.comp-arrow { font-size: 0.68rem; font-weight: 600; display: flex; align-items: center; gap: 0.15rem; }
.comp-arrow i { font-size: 0.58rem; }
.arrow-up { color: #059669; } .arrow-down { color: #dc2626; }
.comp-prev { font-size: 0.65rem; color: #94a3b8; }

/* Transactions List */
.txn-list { display: flex; flex-direction: column; gap: 0.4rem; }
.txn-row { display: grid; grid-template-columns: 36px 1fr auto auto; align-items: center; gap: 0.65rem; padding: 0.5rem 0; border-bottom: 1px solid #f8fafc; }
.txn-row:last-child { border-bottom: none; }
.txn-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.txn-in { background: #d1fae5; color: #059669; } .txn-out { background: #fee2e2; color: #dc2626; }
.txn-icon i { font-size: 0.75rem; }
.txn-info { min-width: 0; }
.txn-desc { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.txn-cat { font-size: 0.65rem; color: #94a3b8; }
.txn-date { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; }
.txn-amount { font-size: 0.82rem; font-weight: 700; white-space: nowrap; text-align: right; }

/* Colors */
.text-green { color: #059669; } .text-red { color: #dc2626; }

/* Form */
.form-grid { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.form-check label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; }
.type-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.toggle-btn { padding: 0.65rem; border-radius: 10px; border: 2px solid #f1f5f9; background: white; font-size: 0.82rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem; transition: all 0.2s; }
.toggle-btn:hover { border-color: #e2e8f0; }
.toggle-btn.active { border-color: #10b981; background: #ecfdf5; color: #059669; }
.toggle-expense.active { border-color: #f43f5e; background: #fff1f2; color: #e11d48; }

.empty-mini { text-align: center; padding: 1.5rem; color: #cbd5e1; font-size: 0.78rem; }
</style>
