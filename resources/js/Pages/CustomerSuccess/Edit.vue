<template>
  <div>
    <Head :title="customer.name" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/customers" class="breadcrumb-link"><i class="pi pi-arrow-left" /> {{ t('common.customer_success') }}</Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ customer.name }}</span>
    </div>

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ customer.name }}</h1>
        <div class="page-meta">
          <span class="status-badge" :class="`status-${customer.lifecycle_status}`"><i class="status-dot" />{{ lifecycleStatuses[customer.lifecycle_status] }}</span>
          <span class="health-badge" :class="healthClass(customer.health_score)">♥ {{ customer.health_score }}</span>
        </div>
      </div>
      <div class="header-actions">
        <Button label="Recalculate" icon="pi pi-sync" severity="secondary" text size="small" @click="recalculate" />
        <Button :label="t('common.save')" icon="pi pi-check" :loading="form.processing" @click="update" />
      </div>
    </div>

    <!-- Health Dashboard -->
    <div class="health-dashboard">
      <!-- Health Score -->
      <div class="health-score-card">
        <div class="score-circle" :class="healthClass(customer.health_score)">
          <span class="score-num">{{ customer.health_score }}</span>
          <span class="score-label">Health</span>
        </div>
        <div class="score-info">
          <h4>{{ healthLabel(customer.health_score) }}</h4>
          <p v-if="customer.health_score >= 80">Khách hàng hài lòng, tình trạng tốt</p>
          <p v-else-if="customer.health_score >= 60">Cần cải thiện một số yếu tố</p>
          <p v-else-if="customer.health_score >= 40">Có nguy cơ churn, cần chú ý</p>
          <p v-else>Nguy cơ cao, cần hành động ngay</p>
        </div>
      </div>

      <!-- Health Factors -->
      <div v-if="customer.health_factors" class="factors-card">
        <h4 class="mini-title">Health Factors</h4>
        <div class="factors-list">
          <div v-for="(score, factor) in customer.health_factors" :key="factor" class="factor-item">
            <div class="factor-header">
              <span class="factor-name">{{ factor }}</span>
              <span class="factor-score" :class="healthClass(score)">{{ score }}</span>
            </div>
            <div class="factor-bar-bg"><div class="factor-bar" :class="healthClass(score)" :style="{ width: score + '%' }" /></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Health Trend -->
    <div v-if="healthHistory.length > 0" class="trend-card">
      <h4 class="mini-title"><i class="pi pi-chart-line" /> Xu hướng Health Score</h4>
      <div class="trend-bar">
        <div v-for="(log, i) in healthHistory.slice().reverse()" :key="i" class="trend-point" :title="`${log.date}: ${log.score}`">
          <div class="trend-column" :class="healthClass(log.score)" :style="{ height: log.score + '%' }" />
          <span class="trend-label">{{ log.date }}</span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tab-nav">
      <button v-for="tab in tabs" :key="tab.key" class="tab-btn" :class="{ 'tab-active': activeTab === tab.key }" @click="activeTab = tab.key">
        <i :class="tab.icon" /> {{ tab.label }}
        <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Tab: Info -->
    <div v-if="activeTab === 'info'" class="section-card">
      <h3 class="section-title"><i class="pi pi-user" /> {{ t('common.customer_info') }}</h3>
      <div class="form-grid">
        <div class="form-group"><label>{{ t('common.name') }} <span class="req">*</span></label><InputText v-model="form.name" /></div>
        <div class="form-group"><label>{{ t('common.email') }}</label><InputText v-model="form.email" type="email" /></div>
        <div class="form-group"><label>{{ t('common.phone') }}</label><InputText v-model="form.phone" /></div>
        <div class="form-group"><label>{{ t('common.lifecycle') }}</label><Select v-model="form.lifecycle_status" :options="statusOpts" optionLabel="label" optionValue="value" /></div>
        <div class="form-group"><label>MRR</label><InputNumber v-model="form.mrr" mode="currency" currency="VND" locale="vi-VN" /></div>
        <div class="form-group"><label>ARR</label><InputNumber v-model="form.arr" mode="currency" currency="VND" locale="vi-VN" /></div>
        <div class="form-group"><label>{{ t('common.contract_end') }}</label><Calendar v-model="form.contract_end" dateFormat="yy-mm-dd" /></div>
        <div class="form-group"><label>{{ t('common.renewal') }}</label><Select v-model="form.renewal_status" :options="renewalOpts" optionLabel="label" optionValue="value" placeholder="—" /></div>
        <div class="form-group"><label>{{ t('common.assigned_to') }}</label><Select v-model="form.assigned_to" :options="userOpts" optionLabel="label" optionValue="value" placeholder="—" showClear /></div>
      </div>
      <div class="form-group" style="margin-top: 0.85rem;">
        <label>{{ t('common.notes') }}</label>
        <Textarea v-model="form.notes" rows="3" class="w-full" />
      </div>
    </div>

    <!-- Tab: Tickets -->
    <div v-if="activeTab === 'tickets'" class="section-card">
      <div class="section-header">
        <h3 class="section-title"><i class="pi pi-inbox" /> Support Tickets ({{ tickets.length }})</h3>
        <Button label="Tạo Ticket" icon="pi pi-plus" size="small" @click="showTicketForm = !showTicketForm" />
      </div>
      <div v-if="showTicketForm" class="inline-form">
        <InputText v-model="ticketForm.subject" placeholder="Tiêu đề..." class="w-full" />
        <div class="inline-row">
          <Select v-model="ticketForm.priority" :options="priorityOpts" optionLabel="label" optionValue="value" placeholder="Ưu tiên" />
          <Select v-model="ticketForm.category" :options="categoryOpts" optionLabel="label" optionValue="value" placeholder="Danh mục" />
          <Button label="Tạo" icon="pi pi-check" size="small" @click="createTicket" :loading="ticketForm.processing" />
        </div>
      </div>
      <div v-if="tickets.length" class="tickets-list">
        <div v-for="ticket in tickets" :key="ticket.id" class="ticket-row">
          <span class="priority-badge" :class="`priority-${ticket.priority}`"><i class="pi pi-flag" /> {{ ticket.priority }}</span>
          <div class="ticket-info">
            <span class="ticket-subject">{{ ticket.subject }}</span>
            <span class="ticket-date"><i class="pi pi-clock" /> {{ formatDate(ticket.created_at) }}</span>
          </div>
          <Select :modelValue="ticket.status" :options="ticketStatusOpts" optionLabel="label" optionValue="value" class="ticket-status-sel" @update:modelValue="updateTicketStatus(ticket, $event)" />
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-inbox" /> Chưa có ticket</div>
    </div>

    <!-- Tab: Upsells -->
    <div v-if="activeTab === 'upsells'" class="section-card">
      <div class="section-header">
        <h3 class="section-title"><i class="pi pi-chart-line" /> Upsell Opportunities ({{ upsells.length }})</h3>
        <Button label="Thêm Upsell" icon="pi pi-plus" size="small" @click="showUpsellForm = !showUpsellForm" />
      </div>
      <div v-if="showUpsellForm" class="inline-form">
        <InputText v-model="upsellForm.title" placeholder="Tên cơ hội..." class="w-full" />
        <div class="inline-row">
          <InputNumber v-model="upsellForm.value" mode="currency" currency="VND" locale="vi-VN" placeholder="Giá trị" />
          <Select v-model="upsellForm.type" :options="typeOpts" optionLabel="label" optionValue="value" placeholder="Loại" />
          <Button label="Tạo" icon="pi pi-check" size="small" @click="createUpsell" :loading="upsellForm.processing" />
        </div>
      </div>
      <div v-if="upsells.length" class="upsells-list">
        <div v-for="up in upsells" :key="up.id" class="upsell-row">
          <div class="upsell-info">
            <span class="upsell-title">{{ up.title }}</span>
            <span class="type-badge">{{ up.type }}</span>
          </div>
          <span class="upsell-value">{{ formatCurrency(up.value) }}</span>
          <span class="status-badge-sm" :class="`upsell-${up.status}`">{{ up.status }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-chart-line" /> Chưa có cơ hội</div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, InputNumber, Textarea, Select, Calendar, Button },
  layout: Layout,
  props: { customer: Object, tickets: Array, upsells: Array, healthHistory: Array, lifecycleStatuses: Object, renewalStatuses: Object, ticketStatuses: Object, ticketPriorities: Object, ticketCategories: Object, upsellStatuses: Object, upsellTypes: Object, organizations: Array, contacts: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      activeTab: 'info',
      form: this.$inertia.form({ ...this.customer }),
      showTicketForm: false, showUpsellForm: false,
      ticketForm: this.$inertia.form({ subject: '', description: '', priority: 'medium', category: 'general', assigned_to: null }),
      upsellForm: this.$inertia.form({ title: '', description: '', value: 0, type: 'upsell', target_close_date: null, assigned_to: null }),
    }
  },
  computed: {
    tabs() {
      return [
        { key: 'info', label: 'Thông tin', icon: 'pi pi-user' },
        { key: 'tickets', label: 'Tickets', icon: 'pi pi-inbox', count: this.tickets.length },
        { key: 'upsells', label: 'Upsell', icon: 'pi pi-chart-line', count: this.upsells.length },
      ]
    },
    statusOpts() { return Object.entries(this.lifecycleStatuses).map(([v, l]) => ({ label: l, value: v })) },
    renewalOpts() { return [{ label: '—', value: null }, ...Object.entries(this.renewalStatuses).map(([v, l]) => ({ label: l, value: v }))] },
    userOpts() { return [{ label: '—', value: null }, ...this.salesUsers.map(u => ({ label: u.name, value: u.id }))] },
    priorityOpts() { return Object.entries(this.ticketPriorities).map(([v, l]) => ({ label: l, value: v })) },
    categoryOpts() { return Object.entries(this.ticketCategories).map(([v, l]) => ({ label: l, value: v })) },
    ticketStatusOpts() { return Object.entries(this.ticketStatuses).map(([v, l]) => ({ label: l, value: v })) },
    typeOpts() { return Object.entries(this.upsellTypes).map(([v, l]) => ({ label: l, value: v })) },
  },
  methods: {
    update() { this.form.put(`/customers/${this.customer.id}`) },
    recalculate() { router.post(`/customers/${this.customer.id}/recalculate-health`, {}, { preserveScroll: true }) },
    createTicket() { this.ticketForm.post(`/customers/${this.customer.id}/tickets`, { preserveScroll: true, onSuccess: () => { this.ticketForm.reset(); this.showTicketForm = false } }) },
    createUpsell() { this.upsellForm.post(`/customers/${this.customer.id}/upsells`, { preserveScroll: true, onSuccess: () => { this.upsellForm.reset(); this.showUpsellForm = false } }) },
    updateTicketStatus(ticket, newStatus) { router.patch(`/customers/${this.customer.id}/tickets/${ticket.id}`, { status: newStatus }, { preserveScroll: true }) },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    formatDate(d) { return new Date(d).toLocaleDateString('vi-VN', { month: 'short', day: 'numeric' }) },
    healthClass(s) { if (s >= 80) return 'health-great'; if (s >= 60) return 'health-good'; if (s >= 40) return 'health-fair'; return 'health-poor' },
    healthLabel(s) { if (s >= 80) return 'Excellent'; if (s >= 60) return 'Good'; if (s >= 40) return 'Fair'; return 'Poor' },
  },
}
</script>

<style scoped>
/* Breadcrumb */
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-meta { display: flex; gap: 0.5rem; margin-top: 0.35rem; align-items: center; }
.header-actions { display: flex; gap: 0.5rem; align-items: center; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.5rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-onboarding { background: #eef2ff; color: #6366f1; } .status-onboarding .status-dot { background: #6366f1; }
.status-active { background: #d1fae5; color: #059669; } .status-active .status-dot { background: #059669; }
.status-at_risk { background: #fef3c7; color: #d97706; } .status-at_risk .status-dot { background: #d97706; }
.status-churned { background: #fee2e2; color: #dc2626; } .status-churned .status-dot { background: #dc2626; }
.status-reactivated { background: #ede9fe; color: #7c3aed; } .status-reactivated .status-dot { background: #7c3aed; }

/* Health Badge */
.health-badge { font-size: 0.72rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 20px; color: white; }
.health-great { background: #10b981; color: #fff; }
.health-good { background: #3b82f6; color: #fff; }
.health-fair { background: #f59e0b; color: #fff; }
.health-poor { background: #ef4444; color: #fff; }

/* Health Dashboard */
.health-dashboard { display: grid; grid-template-columns: auto 1fr; gap: 0.85rem; margin-bottom: 0.85rem; }

.health-score-card { background: white; border-radius: 14px; padding: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 1rem; }
.score-circle { width: 72px; height: 72px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
.score-num { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.score-label { font-size: 0.55rem; font-weight: 600; opacity: 0.85; text-transform: uppercase; margin-top: 0.1rem; }
.score-info h4 { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0 0 0.15rem; }
.score-info p { font-size: 0.72rem; color: #64748b; margin: 0; }

.factors-card { background: white; border-radius: 14px; padding: 1rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.mini-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0 0 0.65rem; display: flex; align-items: center; gap: 0.3rem; }
.mini-title i { font-size: 0.72rem; color: #6366f1; }
.factors-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.55rem; }
.factor-item { display: flex; flex-direction: column; gap: 0.2rem; }
.factor-header { display: flex; justify-content: space-between; }
.factor-name { font-size: 0.68rem; color: #64748b; text-transform: capitalize; }
.factor-score { font-size: 0.68rem; font-weight: 700; }
.factor-bar-bg { height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.factor-bar { height: 100%; border-radius: 3px; transition: width 0.4s; }

/* Trend */
.trend-card { background: white; border-radius: 14px; padding: 1rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 0.85rem; }
.trend-bar { display: flex; align-items: flex-end; gap: 3px; height: 80px; }
.trend-point { display: flex; flex-direction: column; align-items: center; flex: 1; }
.trend-column { width: 100%; border-radius: 3px 3px 0 0; min-height: 4px; transition: height 0.3s; }
.trend-label { font-size: 0.5rem; color: #94a3b8; margin-top: 0.2rem; }

/* Tabs */
.tab-nav { display: flex; gap: 0.25rem; margin-bottom: 1rem; background: white; border-radius: 12px; padding: 0.3rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.tab-btn { display: flex; align-items: center; gap: 0.35rem; padding: 0.55rem 0.85rem; border: none; background: transparent; border-radius: 8px; font-size: 0.78rem; font-weight: 500; color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.tab-btn:hover { background: #f8fafc; color: #334155; }
.tab-btn i { font-size: 0.72rem; }
.tab-active { background: #eef2ff; color: #6366f1; font-weight: 600; }
.tab-count { font-size: 0.6rem; font-weight: 700; background: rgba(99,102,241,0.1); color: #6366f1; padding: 0.05rem 0.35rem; border-radius: 10px; }

/* Section */
.section-card { background: white; border-radius: 14px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.section-title { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.35rem; }
.section-title i { font-size: 0.82rem; color: #6366f1; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.section-header .section-title { margin: 0; }

/* Form */
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.req { color: #ef4444; }

.inline-form { padding: 0.75rem; background: #f8fafc; border-radius: 10px; margin-bottom: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem; }
.inline-row { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* Tickets */
.tickets-list { display: flex; flex-direction: column; gap: 0.35rem; }
.ticket-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.65rem; background: #f8fafc; border-radius: 8px; transition: all 0.15s; }
.ticket-row:hover { background: #f1f5f9; }
.ticket-info { flex: 1; display: flex; flex-direction: column; }
.ticket-subject { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.ticket-date { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.ticket-date i { font-size: 0.55rem; }
.priority-badge { font-size: 0.58rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 4px; text-transform: capitalize; display: flex; align-items: center; gap: 0.2rem; flex-shrink: 0; }
.priority-badge i { font-size: 0.5rem; }
.priority-low { background: #f1f5f9; color: #64748b; }
.priority-medium { background: #fef3c7; color: #d97706; }
.priority-high { background: #fee2e2; color: #dc2626; }
.priority-urgent { background: #dc2626; color: white; }
.ticket-status-sel { min-width: 120px; }

/* Upsells */
.upsells-list { display: flex; flex-direction: column; gap: 0.35rem; }
.upsell-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.55rem 0.65rem; background: #f8fafc; border-radius: 8px; transition: all 0.15s; }
.upsell-row:hover { background: #f1f5f9; }
.upsell-info { flex: 1; display: flex; align-items: center; gap: 0.5rem; }
.upsell-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.upsell-value { font-size: 0.82rem; font-weight: 700; color: #059669; }
.type-badge { font-size: 0.58rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; background: #eef2ff; color: #6366f1; text-transform: capitalize; }
.status-badge-sm { font-size: 0.58rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.upsell-identified { background: #f1f5f9; color: #64748b; }
.upsell-qualified { background: #dbeafe; color: #2563eb; }
.upsell-proposed { background: #fef3c7; color: #d97706; }
.upsell-won { background: #d1fae5; color: #059669; }
.upsell-lost { background: #fee2e2; color: #dc2626; }

/* Empty */
.empty-mini { display: flex; align-items: center; gap: 0.5rem; padding: 2rem; color: #cbd5e1; font-size: 0.82rem; justify-content: center; }
.empty-mini i { font-size: 1rem; }

.w-full { width: 100%; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; gap: 0.5rem; }
  .health-dashboard { grid-template-columns: 1fr; }
  .tab-nav { flex-wrap: wrap; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
