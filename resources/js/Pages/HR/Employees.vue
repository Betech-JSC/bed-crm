<template>
  <div>
    <Head title="Employees" />

    <div class="page-header">
      <div>
        <h1 class="page-title">Employee Profiles</h1>
        <p class="page-subtitle">{{ employees.total || 0 }} employees</p>
      </div>
      <div class="header-actions">
        <Link href="/hr"><Button label="Dashboard" icon="pi pi-chart-line" severity="secondary" text /></Link>
        <Button label="Add Employee" icon="pi pi-plus" @click="showCreate = true" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <span class="p-input-icon-left" style="flex:1">
        <i class="pi pi-search" />
        <InputText v-model="form.search" placeholder="Search employees..." class="w-full" @input="handleSearch" />
      </span>
      <Select v-model="form.department" :options="departmentOptions" optionLabel="label" optionValue="value" placeholder="All Departments" class="filter-select" @change="applyFilter" />
      <Button icon="pi pi-refresh" severity="secondary" text @click="resetFilters" />
    </div>

    <!-- Employee Table -->
    <div class="table-card">
      <table class="emp-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Position</th>
            <th>Hire Date</th>
            <th>Tenure</th>
            <th>Base Salary</th>
            <th>Hourly Rate</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in employees.data" :key="emp.id" class="table-row">
            <td>
              <div class="emp-cell">
                <div class="cell-avatar">{{ initials(emp.name) }}</div>
                <div>
                  <Link :href="`/hr/employees/${emp.id}`" class="emp-link">{{ emp.name }}</Link>
                  <span class="emp-email">{{ emp.email }}</span>
                </div>
              </div>
            </td>
            <td><span class="dept-badge" :class="`dept-${emp.department}`">{{ departments[emp.department] || emp.department || 'N/A' }}</span></td>
            <td class="text-secondary">{{ emp.position || '—' }}</td>
            <td class="text-secondary">{{ emp.hire_date || '—' }}</td>
            <td class="text-secondary">{{ emp.tenure_months }}mo</td>
            <td class="num-cell">{{ formatCurrency(emp.base_salary) }}</td>
            <td class="num-cell">{{ formatCurrency(emp.hourly_rate) }}/h</td>
            <td>
              <div class="action-btns">
                <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="editEmployee(emp)" />
                <Button icon="pi pi-trash" severity="danger" text size="small" @click="deleteEmployee(emp)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!employees.data || employees.data.length === 0" class="empty-state">
        <i class="pi pi-users" /><span>No employees found.</span>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="employees.total > 0" class="pagination-wrapper">
      <span class="pagination-info">{{ employees.from }}–{{ employees.to }} / {{ employees.total }}</span>
      <Paginator :first="(employees.current_page - 1) * employees.per_page" :rows="employees.per_page" :totalRecords="employees.total" @page="onPage" template="PrevPageLink PageLinks NextPageLink" />
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog v-model:visible="showCreate" :header="editingEmployee ? 'Edit Employee' : 'Add Employee'" :modal="true" :style="{ width: '480px' }">
      <div class="form-grid">
        <div v-if="!editingEmployee" class="form-group">
          <label>User</label>
          <Select v-model="createForm.user_id" :options="availableUsers" optionLabel="name" optionValue="id" placeholder="Select user" class="w-full" filter />
        </div>
        <div class="form-group">
          <label>Department</label>
          <Select v-model="createForm.department" :options="departmentOptions.slice(1)" optionLabel="label" optionValue="value" placeholder="Select department" class="w-full" />
        </div>
        <div class="form-group">
          <label>Position</label>
          <InputText v-model="createForm.position" placeholder="e.g. Senior Developer" class="w-full" />
        </div>
        <div class="form-group">
          <label>Hire Date</label>
          <InputText v-model="createForm.hire_date" type="date" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Base Salary (VND)</label>
            <InputText v-model="createForm.base_salary" type="number" class="w-full" />
          </div>
          <div class="form-group">
            <label>Hourly Rate (VND)</label>
            <InputText v-model="createForm.hourly_rate" type="number" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Target Hours / Month</label>
          <InputText v-model="createForm.target_hours_monthly" type="number" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showCreate = false" />
        <Button :label="editingEmployee ? 'Update' : 'Create'" icon="pi pi-check" @click="submitEmployee" :loading="createForm.processing" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import Dialog from 'primevue/dialog'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'

export default {
  components: { Head, Link, Button, InputText, Select, Paginator, Dialog },
  layout: Layout,
  props: {
    employees: Object,
    filters: Object,
    departments: Object,
    availableUsers: Array,
  },
  data() {
    return {
      form: { search: this.filters?.search, department: this.filters?.department },
      showCreate: false,
      editingEmployee: null,
      createForm: this.freshForm(),
    }
  },
  computed: {
    departmentOptions() {
      return [
        { label: 'All Departments', value: null },
        ...Object.entries(this.departments).map(([v, l]) => ({ label: l, value: v })),
      ]
    },
  },
  methods: {
    freshForm() {
      return {
        user_id: null,
        department: null,
        position: null,
        hire_date: null,
        base_salary: 0,
        hourly_rate: 0,
        target_hours_monthly: 160,
        processing: false,
      }
    },
    handleSearch: throttle(function () {
      router.get('/hr/employees', pickBy(this.form), { preserveState: true })
    }, 300),
    applyFilter() {
      router.get('/hr/employees', pickBy(this.form), { preserveState: true })
    },
    resetFilters() {
      this.form = { search: null, department: null }
      router.get('/hr/employees')
    },
    editEmployee(emp) {
      this.editingEmployee = emp
      this.createForm = {
        department: emp.department,
        position: emp.position,
        hire_date: emp.hire_date,
        base_salary: emp.base_salary,
        hourly_rate: emp.hourly_rate,
        target_hours_monthly: 160,
        processing: false,
      }
      this.showCreate = true
    },
    deleteEmployee(emp) {
      if (confirm('Delete this employee profile?')) {
        router.delete(`/hr/employees/${emp.id}`)
      }
    },
    submitEmployee() {
      this.createForm.processing = true
      if (this.editingEmployee) {
        router.put(`/hr/employees/${this.editingEmployee.id}`, this.createForm, {
          onSuccess: () => { this.showCreate = false; this.editingEmployee = null; this.createForm = this.freshForm() },
          onFinish: () => { this.createForm.processing = false },
        })
      } else {
        router.post('/hr/employees', this.createForm, {
          onSuccess: () => { this.showCreate = false; this.createForm = this.freshForm() },
          onFinish: () => { this.createForm.processing = false },
        })
      }
    },
    formatCurrency(v) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0)
    },
    initials(n) {
      return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?'
    },
    onPage(e) {
      router.visit(`/hr/employees?page=${e.page + 1}`, { preserveState: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

.filter-bar { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.filter-select { min-width: 160px; }

.table-card { background: white; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow-x: auto; }
.emp-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; }
.emp-table th { font-weight: 600; color: #64748b; text-align: left; padding: 0.65rem 0.85rem; border-bottom: 2px solid #f1f5f9; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; }
.emp-table td { padding: 0.65rem 0.85rem; border-bottom: 1px solid #f8fafc; color: #334155; }
.table-row:hover td { background: #fafbfc; }

.emp-cell { display: flex; align-items: center; gap: 0.6rem; }
.cell-avatar { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.emp-link { font-weight: 600; color: #1e293b; text-decoration: none; display: block; }
.emp-link:hover { color: #6366f1; }
.emp-email { font-size: 0.68rem; color: #94a3b8; display: block; }
.text-secondary { color: #64748b; }
.num-cell { text-align: right; font-variant-numeric: tabular-nums; }
.action-btns { display: flex; gap: 0.2rem; }

.dept-badge { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.dept-sales { background: #dbeafe; color: #2563eb; } .dept-marketing { background: #fce7f3; color: #db2777; }
.dept-engineering { background: #d1fae5; color: #059669; } .dept-design { background: #fef3c7; color: #d97706; }
.dept-support { background: #e0e7ff; color: #4f46e5; } .dept-management { background: #f1f5f9; color: #475569; }
.dept-hr { background: #fae8ff; color: #a855f7; } .dept-finance { background: #ccfbf1; color: #0d9488; }

/* Form */
.form-grid { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

.empty-state { text-align: center; padding: 3rem; color: #94a3b8; } .empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
</style>
