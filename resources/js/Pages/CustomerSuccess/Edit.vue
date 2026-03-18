<template>
  <div>
    <Head :title="customer.name" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ customer.name }}</h1>
        <div class="page-meta">
          <span class="status-badge" :class="`status-${customer.lifecycle_status}`"><i class="status-dot" />{{ lifecycleStatuses[customer.lifecycle_status] }}</span>
          <span class="health-badge" :class="healthClass(customer.health_score)">♥ {{ customer.health_score }}</span>
        </div>
      </div>
      <div class="header-actions">
        <Button label="Recalculate Health" icon="pi pi-sync" severity="secondary" outlined size="small" @click="recalculate" />
        <Button :label="t('common.save')" icon="pi pi-check" :loading="form.processing" @click="update" />
      </div>
    </div>

    <!-- Health Score Trend -->
    <div v-if="healthHistory.length > 0" class="trend-card">
      <h3 class="section-title">Health Score Trend</h3>
      <div class="trend-bar">
        <div v-for="(log, i) in healthHistory.slice().reverse()" :key="i" class="trend-point" :title="`${log.date}: ${log.score}`">
          <div class="trend-column" :class="healthClass(log.score)" :style="{ height: log.score + '%' }"></div>
          <span class="trend-label">{{ log.date }}</span>
        </div>
      </div>
    </div>

    <!-- Health Factors -->
    <div v-if="customer.health_factors" class="factors-card">
      <h3 class="section-title">Health Factors</h3>
      <div class="factors-grid">
        <div v-for="(score, factor) in customer.health_factors" :key="factor" class="factor-item">
          <div class="factor-bar-bg"><div class="factor-bar" :class="healthClass(score)" :style="{ width: score + '%' }"></div></div>
          <div class="factor-info">
            <span class="factor-name">{{ factor }}</span>
            <span class="factor-score" :class="healthClass(score)">{{ score }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Form -->
    <div class="form-card">
      <h3 class="section-title">{{ t('common.customer_info') }}</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>{{ t('common.name') }} <span class="req">*</span></label>
          <InputText v-model="form.name" />
        </div>
        <div class="form-group">
          <label>{{ t('common.email') }}</label>
          <InputText v-model="form.email" type="email" />
        </div>
        <div class="form-group">
          <label>{{ t('common.phone') }}</label>
          <InputText v-model="form.phone" />
        </div>
        <div class="form-group">
          <label>{{ t('common.lifecycle') }}</label>
          <Select v-model="form.lifecycle_status" :options="statusOpts" optionLabel="label" optionValue="value" />
        </div>
        <div class="form-group">
          <label>MRR</label>
          <InputNumber v-model="form.mrr" mode="currency" currency="VND" locale="vi-VN" />
        </div>
        <div class="form-group">
          <label>ARR</label>
          <InputNumber v-model="form.arr" mode="currency" currency="VND" locale="vi-VN" />
        </div>
        <div class="form-group">
          <label>{{ t('common.contract_end') }}</label>
          <Calendar v-model="form.contract_end" dateFormat="yy-mm-dd" />
        </div>
        <div class="form-group">
          <label>{{ t('common.renewal') }}</label>
          <Select v-model="form.renewal_status" :options="renewalOpts" optionLabel="label" optionValue="value" placeholder="—" />
        </div>
        <div class="form-group">
          <label>{{ t('common.assigned_to') }}</label>
          <Select v-model="form.assigned_to" :options="userOpts" optionLabel="label" optionValue="value" placeholder="—" />
        </div>
      </div>
      <div class="form-group full">
        <label>{{ t('common.notes') }}</label>
        <Textarea v-model="form.notes" rows="3" />
      </div>
    </div>

    <!-- Support Tickets -->
    <div class="section-card">
      <div class="section-header">
        <h3 class="section-title">Support Tickets ({{ tickets.length }})</h3>
        <Button label="New Ticket" icon="pi pi-plus" size="small" @click="showTicketForm = !showTicketForm" />
      </div>

      <div v-if="showTicketForm" class="inline-form">
        <InputText v-model="ticketForm.subject" placeholder="Subject" class="w-full" />
        <div class="inline-row">
          <Select v-model="ticketForm.priority" :options="priorityOpts" optionLabel="label" optionValue="value" placeholder="Priority" />
          <Select v-model="ticketForm.category" :options="categoryOpts" optionLabel="label" optionValue="value" placeholder="Category" />
          <Button label="Create" icon="pi pi-check" size="small" @click="createTicket" :loading="ticketForm.processing" />
        </div>
      </div>

      <div v-if="tickets.length" class="tickets-list">
        <div v-for="ticket in tickets" :key="ticket.id" class="ticket-row">
          <div class="ticket-info">
            <span class="ticket-subject">{{ ticket.subject }}</span>
            <span class="ticket-date">{{ formatDate(ticket.created_at) }}</span>
          </div>
          <span class="priority-badge" :class="`priority-${ticket.priority}`">{{ ticket.priority }}</span>
          <Select :modelValue="ticket.status" :options="ticketStatusOpts" optionLabel="label" optionValue="value" class="ticket-status-sel" @update:modelValue="updateTicketStatus(ticket, $event)" />
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-inbox" /> No tickets</div>
    </div>

    <!-- Upsell Opportunities -->
    <div class="section-card">
      <div class="section-header">
        <h3 class="section-title">Upsell Opportunities ({{ upsells.length }})</h3>
        <Button label="Add Upsell" icon="pi pi-plus" size="small" @click="showUpsellForm = !showUpsellForm" />
      </div>

      <div v-if="showUpsellForm" class="inline-form">
        <InputText v-model="upsellForm.title" placeholder="Title" class="w-full" />
        <div class="inline-row">
          <InputNumber v-model="upsellForm.value" mode="currency" currency="VND" locale="vi-VN" placeholder="Value" />
          <Select v-model="upsellForm.type" :options="typeOpts" optionLabel="label" optionValue="value" placeholder="Type" />
          <Button label="Create" icon="pi pi-check" size="small" @click="createUpsell" :loading="upsellForm.processing" />
        </div>
      </div>

      <div v-if="upsells.length" class="upsells-list">
        <div v-for="up in upsells" :key="up.id" class="upsell-row">
          <span class="upsell-title">{{ up.title }}</span>
          <span class="upsell-value">{{ formatCurrency(up.value) }}</span>
          <span class="type-badge">{{ up.type }}</span>
          <span class="status-badge-sm" :class="`upsell-${up.status}`">{{ up.status }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-chart-line" /> No opportunities</div>
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
      form: this.$inertia.form({ ...this.customer }),
      showTicketForm: false,
      showUpsellForm: false,
      ticketForm: this.$inertia.form({ subject: '', description: '', priority: 'medium', category: 'general', assigned_to: null }),
      upsellForm: this.$inertia.form({ title: '', description: '', value: 0, type: 'upsell', target_close_date: null, assigned_to: null }),
    }
  },
  computed: {
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
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-meta { display: flex; gap: 0.5rem; margin-top: 0.35rem; }
.header-actions { display: flex; gap: 0.5rem; }

.health-badge { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 20px; color: white; }
.health-great { background: #10b981; color: #fff; }
.health-good { background: #3b82f6; color: #fff; }
.health-fair { background: #f59e0b; color: #fff; }
.health-poor { background: #ef4444; color: #fff; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-onboarding { background: #eef2ff; color: #6366f1; } .status-onboarding .status-dot { background: #6366f1; }
.status-active { background: #d1fae5; color: #059669; } .status-active .status-dot { background: #059669; }
.status-at_risk { background: #fef3c7; color: #d97706; } .status-at_risk .status-dot { background: #d97706; }
.status-churned { background: #fee2e2; color: #dc2626; } .status-churned .status-dot { background: #dc2626; }
.status-reactivated { background: #ede9fe; color: #7c3aed; } .status-reactivated .status-dot { background: #7c3aed; }

/* Trend */
.trend-card, .factors-card, .form-card, .section-card { background: white; border-radius: 14px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.section-title { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem; }
.trend-bar { display: flex; align-items: flex-end; gap: 3px; height: 80px; }
.trend-point { display: flex; flex-direction: column; align-items: center; flex: 1; }
.trend-column { width: 100%; border-radius: 2px 2px 0 0; min-height: 4px; transition: height 0.3s; }
.trend-label { font-size: 0.55rem; color: #94a3b8; margin-top: 0.2rem; }

/* Factors */
.factors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.65rem; }
.factor-item { display: flex; flex-direction: column; gap: 0.25rem; }
.factor-bar-bg { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.factor-bar { height: 100%; border-radius: 3px; }
.factor-info { display: flex; justify-content: space-between; }
.factor-name { font-size: 0.72rem; color: #64748b; text-transform: capitalize; }
.factor-score { font-size: 0.72rem; font-weight: 700; }

/* Form */
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.82rem; font-weight: 600; color: #334155; }
.req { color: #ef4444; }

/* Section */
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.section-header .section-title { margin: 0; }

/* Inline form */
.inline-form { display: flex; flex-direction: column; gap: 0.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.75rem; }
.inline-row { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* Tickets */
.tickets-list { display: flex; flex-direction: column; gap: 0.35rem; }
.ticket-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 8px; }
.ticket-info { flex: 1; display: flex; flex-direction: column; }
.ticket-subject { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.ticket-date { font-size: 0.68rem; color: #94a3b8; }
.priority-badge { font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.priority-low { background: #f1f5f9; color: #64748b; }
.priority-medium { background: #fef3c7; color: #d97706; }
.priority-high { background: #fee2e2; color: #dc2626; }
.priority-urgent { background: #dc2626; color: white; }
.ticket-status-sel { min-width: 120px; }

/* Upsells */
.upsells-list { display: flex; flex-direction: column; gap: 0.35rem; }
.upsell-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 8px; }
.upsell-title { flex: 1; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.upsell-value { font-size: 0.82rem; font-weight: 700; color: #059669; }
.type-badge { font-size: 0.62rem; font-weight: 600; padding: 0.12rem 0.35rem; border-radius: 4px; background: #eef2ff; color: #6366f1; text-transform: capitalize; }
.status-badge-sm { font-size: 0.62rem; font-weight: 600; padding: 0.12rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.upsell-identified { background: #f1f5f9; color: #64748b; }
.upsell-qualified { background: #dbeafe; color: #2563eb; }
.upsell-proposed { background: #fef3c7; color: #d97706; }
.upsell-won { background: #d1fae5; color: #059669; }
.upsell-lost { background: #fee2e2; color: #dc2626; }

.empty-mini { display: flex; align-items: center; gap: 0.5rem; padding: 1.5rem; color: #cbd5e1; font-size: 0.82rem; justify-content: center; }
</style>
