<template>
  <div>
    <Head :title="project.name" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/projects" class="breadcrumb-link"><i class="pi pi-arrow-left" /> {{ t('common.projects') }}</Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ project.name }}</span>
    </div>

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ project.name }}</h1>
        <div class="page-meta">
          <span class="status-chip" :class="`chip-${project.status}`">{{ statuses[project.status] }}</span>
          <span v-if="project.is_overdue" class="overdue-tag"><i class="pi pi-exclamation-triangle" /> Quá hạn</span>
          <span v-else-if="project.days_remaining !== null" class="days-tag"><i class="pi pi-clock" /> {{ project.days_remaining }}d còn lại</span>
        </div>
      </div>
      <div class="header-actions">
        <Button :label="t('common.save')" icon="pi pi-check" :loading="form.processing" @click="update" />
      </div>
    </div>

    <!-- Profit Dashboard -->
    <div class="profit-grid">
      <div class="profit-card">
        <div class="profit-icon profit-icon--blue"><i class="pi pi-chart-line" /></div>
        <div class="profit-info">
          <span class="profit-label">Doanh thu</span>
          <span class="profit-value text-blue">{{ fc(profitData.revenue) }}</span>
        </div>
      </div>
      <div class="profit-card">
        <div class="profit-icon profit-icon--slate"><i class="pi pi-minus-circle" /></div>
        <div class="profit-info">
          <span class="profit-label">Chi phí</span>
          <span class="profit-value text-slate">{{ fc(profitData.total_cost) }}</span>
        </div>
      </div>
      <div class="profit-card" :class="profitData.is_profitable ? 'card-positive' : 'card-negative'">
        <div class="profit-icon" :class="profitData.is_profitable ? 'profit-icon--green' : 'profit-icon--red'">
          <i :class="profitData.is_profitable ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
        </div>
        <div class="profit-info">
          <span class="profit-label">{{ t('common.profit') }}</span>
          <span class="profit-value">{{ fc(profitData.profit) }}</span>
        </div>
      </div>
      <div class="profit-card">
        <div class="profit-icon" :class="profitData.margin >= 0 ? 'profit-icon--green' : 'profit-icon--red'">
          <i class="pi pi-percentage" />
        </div>
        <div class="profit-info">
          <span class="profit-label">Margin</span>
          <span class="profit-value" :class="profitData.margin >= 0 ? 'text-green' : 'text-red'">{{ profitData.margin }}%</span>
        </div>
      </div>
      <div class="profit-card" :class="{ 'card-negative': profitData.is_over_budget }">
        <div class="profit-icon" :class="profitData.is_over_budget ? 'profit-icon--red' : 'profit-icon--amber'">
          <i class="pi pi-wallet" />
        </div>
        <div class="profit-info">
          <span class="profit-label">{{ t('common.budget') }} Used</span>
          <span class="profit-value">{{ profitData.budget_used }}%</span>
        </div>
      </div>
    </div>

    <!-- Cost Breakdown -->
    <div class="breakdown-bar">
      <div v-if="profitData.total_cost > 0" class="bar-inner">
        <div class="bar-segment bar-labor" :style="{ flex: profitData.labor_cost }" title="Labor">Labor: {{ fc(profitData.labor_cost) }}</div>
        <div class="bar-segment bar-task" :style="{ flex: profitData.task_cost }" title="Tasks">Tasks: {{ fc(profitData.task_cost) }}</div>
        <div class="bar-segment bar-expense" :style="{ flex: profitData.expenses_cost }" title="Expenses">Exp: {{ fc(profitData.expenses_cost) }}</div>
      </div>
      <div v-else class="bar-empty"><i class="pi pi-info-circle" /> Chưa có chi phí nào được ghi nhận</div>
    </div>

    <!-- Tabs -->
    <div class="tab-nav">
      <button v-for="tab in tabs" :key="tab.key" class="tab-btn" :class="{ 'tab-active': activeTab === tab.key }" @click="activeTab = tab.key">
        <i :class="tab.icon" /> {{ tab.label }}
        <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Tab: Settings -->
    <div v-if="activeTab === 'settings'" class="section-card">
      <h3 class="section-title"><i class="pi pi-cog" /> {{ t('common.project_settings') }}</h3>
      <div class="form-grid">
        <div class="form-group"><label>{{ t('common.name') }}</label><InputText v-model="form.name" /></div>
        <div class="form-group"><label>{{ t('common.status') }}</label><Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" /></div>
        <div class="form-group"><label>{{ t('common.priority') }}</label><Select v-model="form.priority" :options="priorityOpts" optionLabel="label" optionValue="value" /></div>
        <div class="form-group"><label>{{ t('common.manager') }}</label><Select v-model="form.manager_id" :options="userOpts" optionLabel="label" optionValue="value" placeholder="—" showClear /></div>
        <div class="form-group"><label>{{ t('common.due_date') }}</label><Calendar v-model="form.due_date" dateFormat="yy-mm-dd" /></div>
        <div class="form-group"><label>{{ t('common.budget') }}</label><InputNumber v-model="form.budget" mode="currency" currency="VND" locale="vi-VN" /></div>
        <div class="form-group"><label>Revenue</label><InputNumber v-model="form.revenue" mode="currency" currency="VND" locale="vi-VN" /></div>
      </div>
    </div>

    <!-- Tab: Tasks -->
    <div v-if="activeTab === 'tasks'" class="section-card">
      <div class="section-header">
        <h3 class="section-title"><i class="pi pi-list" /> Tasks ({{ tasks.length }})</h3>
        <Button label="Thêm Task" icon="pi pi-plus" size="small" @click="showTaskForm = !showTaskForm" />
      </div>
      <div v-if="showTaskForm" class="inline-form">
        <InputText v-model="taskForm.title" placeholder="Tiêu đề task..." class="w-full" />
        <div class="inline-row">
          <Select v-model="taskForm.priority" :options="priorityOpts" optionLabel="label" optionValue="value" placeholder="Ưu tiên" />
          <Select v-model="taskForm.assigned_to" :options="userOpts" optionLabel="label" optionValue="value" placeholder="Giao cho" showClear />
          <InputNumber v-model="taskForm.estimated_hours" placeholder="Giờ dự kiến" suffix=" hrs" :min="0" />
          <InputNumber v-model="taskForm.hourly_cost" placeholder="Chi phí/h" mode="currency" currency="VND" locale="vi-VN" />
          <Button label="Thêm" icon="pi pi-plus" size="small" @click="addTask" :loading="taskForm.processing" />
        </div>
      </div>
      <div v-if="tasks.length" class="task-list">
        <div v-for="task in tasks" :key="task.id" class="task-row">
          <Select :modelValue="task.status" :options="taskStatusOpts" optionLabel="label" optionValue="value" class="task-status-sel" @update:modelValue="updateTaskStatus(task, $event)" />
          <div class="task-info">
            <span class="task-title" :class="{ 'task-done': task.status === 'done' }">{{ task.title }}</span>
            <span v-if="task.assigned_user" class="task-user"><i class="pi pi-user" /> {{ task.assigned_user.name }}</span>
          </div>
          <div class="task-hours">
            <InputNumber :modelValue="task.actual_hours" suffix=" hrs" :min="0" :step="0.5" class="hours-input" @update:modelValue="updateTaskHours(task, $event)" />
            <span class="est-hours">/ {{ task.estimated_hours }}h</span>
          </div>
          <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteTask(task)" />
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-list" /> Chưa có task nào</div>
    </div>

    <!-- Tab: Resources -->
    <div v-if="activeTab === 'resources'" class="section-card">
      <div class="section-header">
        <h3 class="section-title"><i class="pi pi-users" /> Resources ({{ resources.length }})</h3>
        <Button label="Thêm Resource" icon="pi pi-user-plus" size="small" @click="showResForm = !showResForm" />
      </div>
      <div v-if="showResForm" class="inline-form">
        <div class="inline-row">
          <Select v-model="resForm.user_id" :options="userOpts.filter(o => o.value)" optionLabel="label" optionValue="value" placeholder="Chọn thành viên" />
          <Select v-model="resForm.role" :options="roleOpts" optionLabel="label" optionValue="value" placeholder="Vai trò" />
          <InputNumber v-model="resForm.hourly_rate" mode="currency" currency="VND" locale="vi-VN" placeholder="Giá/h" />
          <InputNumber v-model="resForm.allocated_hours" suffix=" hrs" :min="0" placeholder="Giờ phân bổ" />
          <Button label="Thêm" icon="pi pi-plus" size="small" @click="addResource" :loading="resForm.processing" />
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
          <div class="util-bar">
            <div class="util-bar-bg"><div class="util-bar-fill" :class="res.utilization > 100 ? 'util-over' : 'util-ok'" :style="{ width: Math.min(res.utilization, 100) + '%' }" /></div>
            <span class="res-util" :class="res.utilization > 100 ? 'over' : ''">{{ res.utilization }}%</span>
          </div>
          <span class="res-cost">{{ fc(res.cost) }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-users" /> Chưa có thành viên</div>
    </div>

    <!-- Tab: Expenses -->
    <div v-if="activeTab === 'expenses'" class="section-card">
      <div class="section-header">
        <h3 class="section-title"><i class="pi pi-wallet" /> Expenses ({{ expenses.length }})</h3>
        <Button label="Thêm Chi phí" icon="pi pi-plus" size="small" @click="showExpForm = !showExpForm" />
      </div>
      <div v-if="showExpForm" class="inline-form">
        <div class="inline-row">
          <InputText v-model="expForm.description" placeholder="Mô tả chi phí" style="flex:1" />
          <InputNumber v-model="expForm.amount" mode="currency" currency="VND" locale="vi-VN" />
          <Select v-model="expForm.category" :options="catOpts" optionLabel="label" optionValue="value" placeholder="Danh mục" />
          <Button label="Thêm" icon="pi pi-plus" size="small" @click="addExpense" :loading="expForm.processing" />
        </div>
      </div>
      <div v-if="expenses.length" class="expense-list">
        <div v-for="exp in expenses" :key="exp.id" class="expense-row">
          <span class="exp-desc">{{ exp.description }}</span>
          <span class="exp-cat">{{ exp.category }}</span>
          <span class="exp-amount">{{ fc(exp.amount) }}</span>
        </div>
      </div>
      <div v-else class="empty-mini"><i class="pi pi-wallet" /> Chưa có chi phí</div>
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
      activeTab: 'settings',
      form: this.$inertia.form({ ...this.project }),
      showTaskForm: false, showResForm: false, showExpForm: false,
      taskForm: this.$inertia.form({ title: '', status: 'todo', priority: 'medium', assigned_to: null, estimated_hours: 0, hourly_cost: 0 }),
      resForm: this.$inertia.form({ user_id: null, role: 'member', hourly_rate: 0, allocated_hours: 0 }),
      expForm: this.$inertia.form({ description: '', amount: 0, category: 'other', date: null }),
    }
  },
  computed: {
    tabs() {
      return [
        { key: 'settings', label: 'Cài đặt', icon: 'pi pi-cog' },
        { key: 'tasks', label: 'Tasks', icon: 'pi pi-list', count: this.tasks.length },
        { key: 'resources', label: 'Resources', icon: 'pi pi-users', count: this.resources.length },
        { key: 'expenses', label: 'Chi phí', icon: 'pi pi-wallet', count: this.expenses.length },
      ]
    },
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
.header-actions { display: flex; gap: 0.5rem; }

.status-chip { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.chip-planning { background: #f1f5f9; color: #64748b; }
.chip-in_progress { background: #dbeafe; color: #2563eb; }
.chip-on_hold { background: #fef3c7; color: #d97706; }
.chip-delayed { background: #fee2e2; color: #dc2626; }
.chip-completed { background: #d1fae5; color: #059669; }
.chip-cancelled { background: #f1f5f9; color: #94a3b8; }
.overdue-tag { font-size: 0.72rem; font-weight: 700; color: #dc2626; display: flex; align-items: center; gap: 0.25rem; }
.overdue-tag i { font-size: 0.62rem; }
.days-tag { font-size: 0.72rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.days-tag i { font-size: 0.62rem; }

/* Profit */
.profit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem; margin-bottom: 0.85rem; }
.profit-card { background: white; border-radius: 12px; padding: 0.75rem; display: flex; align-items: center; gap: 0.55rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; transition: all 0.2s; }
.profit-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px); }
.profit-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; flex-shrink: 0; }
.profit-icon--blue { background: #dbeafe; color: #2563eb; }
.profit-icon--slate { background: #f1f5f9; color: #64748b; }
.profit-icon--green { background: #d1fae5; color: #059669; }
.profit-icon--red { background: #fee2e2; color: #dc2626; }
.profit-icon--amber { background: #fef3c7; color: #d97706; }
.profit-info { display: flex; flex-direction: column; }
.profit-label { font-size: 0.62rem; color: #94a3b8; }
.profit-value { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.text-blue { color: #2563eb; } .text-green { color: #059669; } .text-red { color: #dc2626; } .text-slate { color: #64748b; }
.card-positive { border-color: #d1fae5; background: #f0fdf4; } .card-positive .profit-value { color: #059669; }
.card-negative { border-color: #fee2e2; background: #fef2f2; } .card-negative .profit-value { color: #dc2626; }

/* Breakdown */
.breakdown-bar { margin-bottom: 0.85rem; }
.bar-inner { display: flex; height: 24px; border-radius: 6px; overflow: hidden; gap: 2px; }
.bar-segment { display: flex; align-items: center; justify-content: center; font-size: 0.58rem; font-weight: 600; color: white; min-width: 0; }
.bar-labor { background: linear-gradient(90deg, #6366f1, #818cf8); }
.bar-task { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.bar-expense { background: #94a3b8; }
.bar-empty { text-align: center; font-size: 0.78rem; color: #cbd5e1; padding: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; }
.bar-empty i { font-size: 0.72rem; }

/* Tabs */
.tab-nav { display: flex; gap: 0.25rem; margin-bottom: 1rem; background: white; border-radius: 12px; padding: 0.3rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.tab-btn { display: flex; align-items: center; gap: 0.35rem; padding: 0.55rem 0.85rem; border: none; background: transparent; border-radius: 8px; font-size: 0.78rem; font-weight: 500; color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.tab-btn:hover { background: #f8fafc; color: #334155; }
.tab-btn i { font-size: 0.72rem; }
.tab-active { background: #eef2ff; color: #6366f1; font-weight: 600; }
.tab-count { font-size: 0.6rem; font-weight: 700; background: rgba(99,102,241,0.1); color: #6366f1; padding: 0.05rem 0.35rem; border-radius: 10px; }

/* Section Cards */
.section-card { background: white; border-radius: 14px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.section-title { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.35rem; }
.section-title i { font-size: 0.82rem; color: #6366f1; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.section-header .section-title { margin: 0; }

/* Form */
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }

.inline-form { padding: 0.75rem; background: #f8fafc; border-radius: 10px; margin-bottom: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem; }
.inline-row { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-end; }

/* Tasks */
.task-list { display: flex; flex-direction: column; gap: 0.35rem; }
.task-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 8px; transition: all 0.15s; }
.task-row:hover { background: #f1f5f9; }
.task-status-sel { min-width: 110px; }
.task-info { flex: 1; display: flex; flex-direction: column; }
.task-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.task-done { text-decoration: line-through; color: #94a3b8; }
.task-user { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem; }
.task-user i { font-size: 0.55rem; }
.task-hours { display: flex; align-items: center; gap: 0.2rem; }
.hours-input { width: 80px; }
.est-hours { font-size: 0.65rem; color: #94a3b8; }

/* Resources */
.resource-list { display: flex; flex-direction: column; gap: 0.35rem; }
.resource-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.65rem; background: #f8fafc; border-radius: 8px; transition: all 0.15s; }
.resource-row:hover { background: #f1f5f9; }
.res-user { display: flex; align-items: center; gap: 0.35rem; flex: 1; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.mini-avatar { width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.role-badge { font-size: 0.58rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; background: #eef2ff; color: #6366f1; text-transform: capitalize; }
.res-hours { display: flex; align-items: center; gap: 0.2rem; }
.util-bar { display: flex; align-items: center; gap: 0.3rem; }
.util-bar-bg { width: 50px; height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.util-bar-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
.util-ok { background: #10b981; }
.util-over { background: #ef4444; }
.res-util { font-size: 0.72rem; font-weight: 700; color: #059669; }
.res-util.over { color: #dc2626; }
.res-cost { font-size: 0.78rem; font-weight: 600; color: #64748b; }

/* Expenses */
.expense-list { display: flex; flex-direction: column; gap: 0.35rem; }
.expense-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 8px; transition: all 0.15s; }
.expense-row:hover { background: #f1f5f9; }
.exp-desc { flex: 1; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.exp-cat { font-size: 0.58rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; background: #f1f5f9; color: #64748b; text-transform: capitalize; }
.exp-amount { font-size: 0.82rem; font-weight: 700; color: #dc2626; }

/* Empty */
.empty-mini { display: flex; align-items: center; gap: 0.5rem; padding: 2rem; color: #cbd5e1; font-size: 0.82rem; justify-content: center; }
.empty-mini i { font-size: 1rem; }

.w-full { width: 100%; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; gap: 0.5rem; }
  .tab-nav { flex-wrap: wrap; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
