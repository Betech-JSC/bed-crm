<template>
  <div>
    <Head :title="project.name" />
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ project.name }}</h1>
        <div class="page-meta">
          <span class="status-chip" :class="`chip-${project.status}`">{{ statuses[project.status] }}</span>
          <span v-if="project.is_overdue" class="overdue-tag"><i class="pi pi-exclamation-triangle" /> Overdue</span>
          <span v-else-if="project.days_remaining !== null" class="days-tag">{{ project.days_remaining }}d remaining</span>
        </div>
      </div>
      <div class="header-actions">
        <Button :label="t('common.save')" icon="pi pi-check" :loading="form.processing" @click="update" />
      </div>
    </div>

    <!-- Profit Dashboard -->
    <div class="profit-grid">
      <div class="profit-card">
        <span class="profit-label">Revenue</span>
        <span class="profit-value text-blue">{{ fc(profitData.revenue) }}</span>
      </div>
      <div class="profit-card">
        <span class="profit-label">Total Cost</span>
        <span class="profit-value text-slate">{{ fc(profitData.total_cost) }}</span>
      </div>
      <div class="profit-card" :class="profitData.is_profitable ? 'card-positive' : 'card-negative'">
        <span class="profit-label">{{ t('common.profit') }}</span>
        <span class="profit-value">{{ fc(profitData.profit) }}</span>
      </div>
      <div class="profit-card">
        <span class="profit-label">Margin</span>
        <span class="profit-value" :class="profitData.margin >= 0 ? 'text-green' : 'text-red'">{{ profitData.margin }}%</span>
      </div>
      <div class="profit-card" :class="{ 'card-negative': profitData.is_over_budget }">
        <span class="profit-label">{{ t('common.budget') }} Used</span>
        <span class="profit-value">{{ profitData.budget_used }}%</span>
      </div>
    </div>

    <!-- Cost Breakdown -->
    <div class="breakdown-bar">
      <div v-if="profitData.total_cost > 0" class="bar-inner">
        <div class="bar-segment bar-labor" :style="{ flex: profitData.labor_cost }" title="Labor">Labor: {{ fc(profitData.labor_cost) }}</div>
        <div class="bar-segment bar-task" :style="{ flex: profitData.task_cost }" title="Tasks">Tasks: {{ fc(profitData.task_cost) }}</div>
        <div class="bar-segment bar-expense" :style="{ flex: profitData.expenses_cost }" title="Expenses">Exp: {{ fc(profitData.expenses_cost) }}</div>
      </div>
      <div v-else class="bar-empty">No costs recorded</div>
    </div>

    <!-- Project Settings Form -->
    <div class="section-card">
      <h3 class="section-title">{{ t('common.project_settings') }}</h3>
      <div class="form-grid">
        <div class="form-group"><label>{{ t('common.name') }}</label><InputText v-model="form.name" /></div>
        <div class="form-group"><label>{{ t('common.status') }}</label><Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" /></div>
        <div class="form-group"><label>{{ t('common.priority') }}</label><Select v-model="form.priority" :options="priorityOpts" optionLabel="label" optionValue="value" /></div>
        <div class="form-group"><label>{{ t('common.manager') }}</label><Select v-model="form.manager_id" :options="userOpts" optionLabel="label" optionValue="value" placeholder="—" /></div>
        <div class="form-group"><label>{{ t('common.due_date') }}</label><Calendar v-model="form.due_date" dateFormat="yy-mm-dd" /></div>
        <div class="form-group"><label>{{ t('common.budget') }}</label><InputNumber v-model="form.budget" mode="currency" currency="VND" locale="vi-VN" /></div>
        <div class="form-group"><label>Revenue</label><InputNumber v-model="form.revenue" mode="currency" currency="VND" locale="vi-VN" /></div>
      </div>
    </div>

    <!-- Tasks -->
    <div class="section-card">
      <div class="section-header">
        <h3 class="section-title">Tasks ({{ tasks.length }})</h3>
        <Button label="Add Task" icon="pi pi-plus" size="small" @click="showTaskForm = !showTaskForm" />
      </div>
      <div v-if="showTaskForm" class="inline-form">
        <InputText v-model="taskForm.title" placeholder="Task title" class="w-full" />
        <div class="inline-row">
          <Select v-model="taskForm.priority" :options="priorityOpts" optionLabel="label" optionValue="value" placeholder="Priority" />
          <Select v-model="taskForm.assigned_to" :options="userOpts" optionLabel="label" optionValue="value" placeholder="Assign" />
          <InputNumber v-model="taskForm.estimated_hours" placeholder="Est. hours" suffix=" hrs" :min="0" />
          <InputNumber v-model="taskForm.hourly_cost" placeholder="Cost/hr" mode="currency" currency="VND" locale="vi-VN" />
          <Button label="Add" icon="pi pi-plus" size="small" @click="addTask" :loading="taskForm.processing" />
        </div>
      </div>
      <div v-if="tasks.length" class="task-list">
        <div v-for="task in tasks" :key="task.id" class="task-row">
          <Select :modelValue="task.status" :options="taskStatusOpts" optionLabel="label" optionValue="value" class="task-status-sel" @update:modelValue="updateTaskStatus(task, $event)" />
          <div class="task-info">
            <span class="task-title" :class="{ 'task-done': task.status === 'done' }">{{ task.title }}</span>
            <span v-if="task.assigned_user" class="task-user">{{ task.assigned_user.name }}</span>
          </div>
          <div class="task-hours">
            <InputNumber :modelValue="task.actual_hours" suffix=" hrs" :min="0" :step="0.5" class="hours-input" @update:modelValue="updateTaskHours(task, $event)" />
            <span class="est-hours">/ {{ task.estimated_hours }}h</span>
          </div>
          <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteTask(task)" />
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-list" /> No tasks</div>
    </div>

    <!-- Resources -->
    <div class="section-card">
      <div class="section-header">
        <h3 class="section-title">Resources ({{ resources.length }})</h3>
        <Button label="Add Resource" icon="pi pi-user-plus" size="small" @click="showResForm = !showResForm" />
      </div>
      <div v-if="showResForm" class="inline-form">
        <div class="inline-row">
          <Select v-model="resForm.user_id" :options="userOpts.slice(1)" optionLabel="label" optionValue="value" placeholder="Select user" />
          <Select v-model="resForm.role" :options="roleOpts" optionLabel="label" optionValue="value" placeholder="Role" />
          <InputNumber v-model="resForm.hourly_rate" mode="currency" currency="VND" locale="vi-VN" placeholder="Rate/hr" />
          <InputNumber v-model="resForm.allocated_hours" suffix=" hrs" :min="0" placeholder="Allocated" />
          <Button label="Add" icon="pi pi-plus" size="small" @click="addResource" :loading="resForm.processing" />
        </div>
      </div>
      <div v-if="resources.length" class="resource-list">
        <div v-for="res in resources" :key="res.id" class="resource-row">
          <div class="res-user"><div class="mini-avatar">{{ initials(res.user?.name) }}</div><span>{{ res.user?.name }}</span></div>
          <span class="role-badge">{{ res.role }}</span>
          <div class="res-hours">
            <InputNumber :modelValue="res.logged_hours" suffix=" hrs" :min="0" :step="0.5" class="hours-input" @update:modelValue="updateResHours(res, $event)" />
            <span class="est-hours">/ {{ res.allocated_hours }}h</span>
          </div>
          <span class="res-util" :class="res.utilization > 100 ? 'over' : ''">{{ res.utilization }}%</span>
          <span class="res-cost">{{ fc(res.cost) }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-users" /> No resources</div>
    </div>

    <!-- Expenses -->
    <div class="section-card">
      <div class="section-header">
        <h3 class="section-title">Expenses ({{ expenses.length }})</h3>
        <Button label="Add Expense" icon="pi pi-plus" size="small" @click="showExpForm = !showExpForm" />
      </div>
      <div v-if="showExpForm" class="inline-form">
        <div class="inline-row">
          <InputText v-model="expForm.description" placeholder="Description" style="flex:1" />
          <InputNumber v-model="expForm.amount" mode="currency" currency="VND" locale="vi-VN" />
          <Select v-model="expForm.category" :options="catOpts" optionLabel="label" optionValue="value" placeholder="Category" />
          <Button label="Add" icon="pi pi-plus" size="small" @click="addExpense" :loading="expForm.processing" />
        </div>
      </div>
      <div v-if="expenses.length" class="expense-list">
        <div v-for="exp in expenses" :key="exp.id" class="expense-row">
          <span class="exp-desc">{{ exp.description }}</span>
          <span class="exp-cat">{{ exp.category }}</span>
          <span class="exp-amount">{{ fc(exp.amount) }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-wallet" /> No expenses</div>
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
  props: { project: Object, profitData: Object, tasks: Array, resources: Array, expenses: Array, statuses: Object, priorities: Object, taskStatuses: Object, resourceRoles: Object, expenseCategories: Object, customers: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: this.$inertia.form({ ...this.project }),
      showTaskForm: false, showResForm: false, showExpForm: false,
      taskForm: this.$inertia.form({ title: '', status: 'todo', priority: 'medium', assigned_to: null, estimated_hours: 0, hourly_cost: 0 }),
      resForm: this.$inertia.form({ user_id: null, role: 'member', hourly_rate: 0, allocated_hours: 0 }),
      expForm: this.$inertia.form({ description: '', amount: 0, category: 'other', date: null }),
    }
  },
  computed: {
    statusOpts() { return Object.entries(this.statuses).map(([v, l]) => ({ label: l, value: v })) },
    priorityOpts() { return Object.entries(this.priorities).map(([v, l]) => ({ label: l, value: v })) },
    taskStatusOpts() { return Object.entries(this.taskStatuses).map(([v, l]) => ({ label: l, value: v })) },
    roleOpts() { return Object.entries(this.resourceRoles).map(([v, l]) => ({ label: l, value: v })) },
    catOpts() { return Object.entries(this.expenseCategories).map(([v, l]) => ({ label: l, value: v })) },
    userOpts() { return [{ label: '—', value: null }, ...this.salesUsers.map(u => ({ label: u.name, value: u.id }))] },
  },
  methods: {
    update() { this.form.put(`/projects/${this.project.id}`) },
    fc(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    addTask() { this.taskForm.post(`/projects/${this.project.id}/tasks`, { preserveScroll: true, onSuccess: () => { this.taskForm.reset(); this.showTaskForm = false } }) },
    updateTaskStatus(task, status) { router.patch(`/projects/${this.project.id}/tasks/${task.id}`, { status }, { preserveScroll: true }) },
    updateTaskHours(task, hours) { router.patch(`/projects/${this.project.id}/tasks/${task.id}`, { actual_hours: hours }, { preserveScroll: true }) },
    deleteTask(task) { router.delete(`/projects/${this.project.id}/tasks/${task.id}`, { preserveScroll: true }) },
    addResource() { this.resForm.post(`/projects/${this.project.id}/resources`, { preserveScroll: true, onSuccess: () => { this.resForm.reset(); this.showResForm = false } }) },
    updateResHours(res, hours) { router.patch(`/projects/${this.project.id}/resources/${res.id}`, { logged_hours: hours }, { preserveScroll: true }) },
    addExpense() { this.expForm.post(`/projects/${this.project.id}/expenses`, { preserveScroll: true, onSuccess: () => { this.expForm.reset(); this.showExpForm = false } }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-meta { display: flex; gap: 0.5rem; margin-top: 0.35rem; align-items: center; }
.header-actions { display: flex; gap: 0.5rem; }
.status-chip { font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 5px; text-transform: uppercase; }
.chip-planning { background: #f1f5f9; color: #64748b; } .chip-in_progress { background: #dbeafe; color: #2563eb; } .chip-on_hold { background: #fef3c7; color: #d97706; } .chip-delayed { background: #fee2e2; color: #dc2626; } .chip-completed { background: #d1fae5; color: #059669; } .chip-cancelled { background: #f1f5f9; color: #94a3b8; }
.overdue-tag { font-size: 0.72rem; font-weight: 700; color: #dc2626; display: flex; align-items: center; gap: 0.25rem; }
.days-tag { font-size: 0.72rem; color: #64748b; }

/* Profit Grid */
.profit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.65rem; margin-bottom: 1rem; }
.profit-card { background: white; border-radius: 12px; padding: 0.85rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.profit-label { font-size: 0.68rem; color: #94a3b8; display: block; }
.profit-value { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.text-blue { color: #2563eb; } .text-green { color: #059669; } .text-red { color: #dc2626; } .text-slate { color: #64748b; }
.card-positive { border-color: #d1fae5; background: #f0fdf4; } .card-positive .profit-value { color: #059669; }
.card-negative { border-color: #fee2e2; background: #fef2f2; } .card-negative .profit-value { color: #dc2626; }

/* Breakdown Bar */
.breakdown-bar { margin-bottom: 1rem; }
.bar-inner { display: flex; height: 24px; border-radius: 6px; overflow: hidden; gap: 2px; }
.bar-segment { display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 600; color: white; min-width: 0; }
.bar-labor { background: #6366f1; } .bar-task { background: #f59e0b; } .bar-expense { background: #94a3b8; }
.bar-empty { text-align: center; font-size: 0.78rem; color: #cbd5e1; padding: 0.5rem; }

/* Section Cards */
.section-card { background: white; border-radius: 14px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.section-title { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.section-header .section-title { margin: 0; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.85rem; margin-bottom: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #334155; }

.inline-form { padding: 0.65rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.65rem; display: flex; flex-direction: column; gap: 0.5rem; }
.inline-row { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-end; }

/* Tasks */
.task-list { display: flex; flex-direction: column; gap: 0.3rem; }
.task-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.6rem; background: #f8fafc; border-radius: 8px; }
.task-status-sel { min-width: 110px; }
.task-info { flex: 1; display: flex; flex-direction: column; }
.task-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.task-done { text-decoration: line-through; color: #94a3b8; }
.task-user { font-size: 0.68rem; color: #94a3b8; }
.task-hours { display: flex; align-items: center; gap: 0.2rem; }
.hours-input { width: 80px; }
.est-hours { font-size: 0.68rem; color: #94a3b8; }

/* Resources */
.resource-list { display: flex; flex-direction: column; gap: 0.3rem; }
.resource-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.5rem 0.6rem; background: #f8fafc; border-radius: 8px; }
.res-user { display: flex; align-items: center; gap: 0.35rem; flex: 1; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.mini-avatar { width: 22px; height: 22px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; font-size: 0.55rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.role-badge { font-size: 0.62rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; background: #eef2ff; color: #6366f1; text-transform: capitalize; }
.res-hours { display: flex; align-items: center; gap: 0.2rem; }
.res-util { font-size: 0.75rem; font-weight: 700; color: #059669; } .res-util.over { color: #dc2626; }
.res-cost { font-size: 0.78rem; font-weight: 600; color: #64748b; }

/* Expenses */
.expense-list { display: flex; flex-direction: column; gap: 0.3rem; }
.expense-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.45rem 0.6rem; background: #f8fafc; border-radius: 8px; }
.exp-desc { flex: 1; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.exp-cat { font-size: 0.62rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; background: #f1f5f9; color: #64748b; text-transform: capitalize; }
.exp-amount { font-size: 0.82rem; font-weight: 700; color: #dc2626; }

.empty-mini { display: flex; align-items: center; gap: 0.5rem; padding: 1.5rem; color: #cbd5e1; font-size: 0.82rem; justify-content: center; }
</style>
