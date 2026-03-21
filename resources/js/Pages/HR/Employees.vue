<template>
  <div>
    <Head title="Nhân viên" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-users" /></div>
        <div>
          <h1 class="page-title">Quản lý nhân viên</h1>
          <p class="page-subtitle">{{ employees.total || 0 }} nhân viên</p>
        </div>
      </div>
      <div class="header-actions">
        <Link href="/hr" class="btn-secondary"><i class="pi pi-chart-line" /> Dashboard HR</Link>
        <button class="btn-primary" @click="showCreate = true"><i class="pi pi-plus" /> Thêm nhân viên</button>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="pi pi-users" /></div>
        <div><span class="stat-value">{{ employees.total || 0 }}</span><span class="stat-label">Tổng nhân viên</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="pi pi-dollar" /></div>
        <div><span class="stat-value">{{ formatShort(avgSalary) }}</span><span class="stat-label">Lương TB</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><i class="pi pi-building" /></div>
        <div><span class="stat-value">{{ uniqueDepartments }}</span><span class="stat-label">Phòng ban</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="pi pi-clock" /></div>
        <div><span class="stat-value">{{ avgTenure }}m</span><span class="stat-label">Tenure TB</span></div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" placeholder="Tìm nhân viên..." class="search-input" @input="handleSearch" />
      </div>
      <div class="filter-chips">
        <button
          class="filter-chip" :class="{ active: !form.department }"
          @click="form.department = null; applyFilter()"
        >Tất cả</button>
        <button
          v-for="[key, label] in Object.entries(departments)"
          :key="key"
          class="filter-chip" :class="[`chip-${key}`, { active: form.department === key }]"
          @click="form.department = key; applyFilter()"
        >{{ label }}</button>
      </div>
      <div class="view-toggle">
        <button :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'"><i class="pi pi-list" /></button>
        <button :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'"><i class="pi pi-th-large" /></button>
      </div>
    </div>

    <!-- Table View -->
    <div v-if="viewMode === 'table'" class="table-card">
      <table class="emp-table">
        <thead>
          <tr>
            <th>Nhân viên</th>
            <th>Phòng ban</th>
            <th>Vị trí</th>
            <th>Ngày vào</th>
            <th>Tenure</th>
            <th class="text-right">Lương cơ bản</th>
            <th class="text-right">Giờ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in employees.data" :key="emp.id" class="table-row">
            <td>
              <div class="emp-cell">
                <div class="cell-avatar" :style="{ background: avatarGradient(emp) }">{{ initials(emp.name) }}</div>
                <div>
                  <Link :href="`/hr/employees/${emp.id}`" class="emp-link">{{ emp.name }}</Link>
                  <span class="emp-email">{{ emp.email }}</span>
                </div>
              </div>
            </td>
            <td><span class="dept-badge" :class="`dept-${emp.department}`">{{ departments[emp.department] || emp.department || '—' }}</span></td>
            <td class="td-secondary">{{ emp.position || '—' }}</td>
            <td class="td-secondary">{{ emp.hire_date || '—' }}</td>
            <td>
              <div class="tenure-bar">
                <div class="tenure-fill" :style="{ width: Math.min(emp.tenure_months / 60 * 100, 100) + '%' }" />
                <span>{{ emp.tenure_months }}m</span>
              </div>
            </td>
            <td class="td-number">{{ formatCurrency(emp.base_salary) }}</td>
            <td class="td-number">{{ formatCurrency(emp.hourly_rate) }}<small>/h</small></td>
            <td>
              <div class="row-actions">
                <Link :href="`/hr/employees/${emp.id}`" class="action-btn" title="Chi tiết"><i class="pi pi-eye" /></Link>
                <button class="action-btn" @click="editEmployee(emp)" title="Sửa"><i class="pi pi-pencil" /></button>
                <button class="action-btn danger" @click="deleteEmployee(emp)" title="Xóa"><i class="pi pi-trash" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!employees.data?.length" class="empty-state">
        <div class="empty-icon"><i class="pi pi-users" /></div>
        <h3>Chưa có nhân viên</h3>
        <p>Thêm nhân viên đầu tiên để bắt đầu</p>
      </div>
    </div>

    <!-- Grid View -->
    <div v-if="viewMode === 'grid'" class="emp-grid">
      <div v-for="emp in employees.data" :key="emp.id" class="emp-card">
        <div class="emp-card-header">
          <div class="card-avatar" :style="{ background: avatarGradient(emp) }">{{ initials(emp.name) }}</div>
          <div class="card-badges">
            <span class="dept-badge" :class="`dept-${emp.department}`">{{ departments[emp.department] || emp.department || '—' }}</span>
          </div>
        </div>
        <div class="emp-card-body">
          <Link :href="`/hr/employees/${emp.id}`" class="card-name">{{ emp.name }}</Link>
          <span class="card-position">{{ emp.position || 'Chưa có vị trí' }}</span>
          <div class="card-stats">
            <div class="card-stat"><i class="pi pi-calendar" /><span>{{ emp.hire_date || '—' }}</span></div>
            <div class="card-stat"><i class="pi pi-clock" /><span>{{ emp.tenure_months }}m</span></div>
          </div>
          <div class="card-salary">
            <div><span class="salary-label">Lương</span><span class="salary-value">{{ formatCurrency(emp.base_salary) }}</span></div>
            <div><span class="salary-label">Giờ</span><span class="salary-value">{{ formatCurrency(emp.hourly_rate) }}/h</span></div>
          </div>
        </div>
        <div class="emp-card-footer">
          <Link :href="`/hr/employees/${emp.id}`" class="card-action">Chi tiết</Link>
          <div class="card-footer-btns">
            <button class="action-btn" @click="editEmployee(emp)"><i class="pi pi-pencil" /></button>
            <button class="action-btn danger" @click="deleteEmployee(emp)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="employees.total > 0" class="pagination">
      <span class="page-info">{{ employees.from }}–{{ employees.to }} / {{ employees.total }}</span>
      <div class="page-btns">
        <button :disabled="!employees.prev_page_url" @click="goPage(employees.current_page - 1)" class="page-btn"><i class="pi pi-chevron-left" /></button>
        <span class="page-current">{{ employees.current_page }}</span>
        <button :disabled="!employees.next_page_url" @click="goPage(employees.current_page + 1)" class="page-btn"><i class="pi pi-chevron-right" /></button>
      </div>
    </div>

    <!-- Create / Edit Dialog -->
    <div v-if="showCreate" class="modal-overlay" @click.self="closeDialog">
      <div class="modal-panel">
        <div class="modal-header">
          <h3><i class="pi pi-user-plus" /> {{ editingEmployee ? 'Cập nhật nhân viên' : 'Thêm nhân viên mới' }}</h3>
          <button class="modal-close" @click="closeDialog"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div v-if="!editingEmployee" class="form-group">
            <label class="form-label">Người dùng <span class="required">*</span></label>
            <select v-model="createForm.user_id" class="form-select">
              <option :value="null">Chọn người dùng</option>
              <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Phòng ban</label>
            <select v-model="createForm.department" class="form-select">
              <option :value="null">Chọn phòng ban</option>
              <option v-for="[k, v] in Object.entries(departments)" :key="k" :value="k">{{ v }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Vị trí</label>
            <input v-model="createForm.position" class="form-input" placeholder="VD: Senior Developer" />
          </div>
          <div class="form-group">
            <label class="form-label">Ngày vào làm</label>
            <input v-model="createForm.hire_date" type="date" class="form-input" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Lương cơ bản (VND)</label>
              <input v-model="createForm.base_salary" type="number" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">Hourly Rate (VND)</label>
              <input v-model="createForm.hourly_rate" type="number" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Giờ mục tiêu / tháng</label>
            <input v-model="createForm.target_hours_monthly" type="number" class="form-input" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeDialog">Hủy</button>
          <button class="btn-primary" @click="submitEmployee" :disabled="createForm.processing">
            <i :class="createForm.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check'" />
            {{ editingEmployee ? 'Cập nhật' : 'Tạo mới' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'

export default {
  components: { Head, Link },
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
      viewMode: 'table',
      showCreate: false,
      editingEmployee: null,
      createForm: this.freshForm(),
    }
  },
  computed: {
    avgSalary() {
      if (!this.employees.data?.length) return 0
      return this.employees.data.reduce((s, e) => s + (e.base_salary || 0), 0) / this.employees.data.length
    },
    uniqueDepartments() {
      return new Set(this.employees.data?.map(e => e.department).filter(Boolean) || []).size
    },
    avgTenure() {
      if (!this.employees.data?.length) return 0
      return Math.round(this.employees.data.reduce((s, e) => s + (e.tenure_months || 0), 0) / this.employees.data.length)
    },
  },
  methods: {
    freshForm() {
      return { user_id: null, department: null, position: null, hire_date: null, base_salary: 0, hourly_rate: 0, target_hours_monthly: 160, processing: false }
    },
    handleSearch: throttle(function () {
      router.get('/hr/employees', pickBy(this.form), { preserveState: true })
    }, 300),
    applyFilter() {
      router.get('/hr/employees', pickBy(this.form), { preserveState: true })
    },
    editEmployee(emp) {
      this.editingEmployee = emp
      this.createForm = { department: emp.department, position: emp.position, hire_date: emp.hire_date, base_salary: emp.base_salary, hourly_rate: emp.hourly_rate, target_hours_monthly: 160, processing: false }
      this.showCreate = true
    },
    deleteEmployee(emp) {
      if (confirm(`Xóa nhân viên "${emp.name}"?`)) router.delete(`/hr/employees/${emp.id}`)
    },
    closeDialog() { this.showCreate = false; this.editingEmployee = null; this.createForm = this.freshForm() },
    submitEmployee() {
      this.createForm.processing = true
      const opts = { onSuccess: () => this.closeDialog(), onFinish: () => { this.createForm.processing = false } }
      if (this.editingEmployee) router.put(`/hr/employees/${this.editingEmployee.id}`, this.createForm, opts)
      else router.post('/hr/employees', this.createForm, opts)
    },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0) },
    formatShort(v) {
      if (v >= 1e9) return (v / 1e9).toFixed(1) + 'B'
      if (v >= 1e6) return (v / 1e6).toFixed(1) + 'M'
      if (v >= 1e3) return (v / 1e3).toFixed(0) + 'K'
      return v.toFixed(0)
    },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    avatarGradient(emp) {
      const colors = ['linear-gradient(135deg,#6366f1,#8b5cf6)','linear-gradient(135deg,#10b981,#059669)','linear-gradient(135deg,#f59e0b,#d97706)','linear-gradient(135deg,#ef4444,#dc2626)','linear-gradient(135deg,#3b82f6,#2563eb)','linear-gradient(135deg,#ec4899,#db2777)']
      return colors[emp.id % colors.length]
    },
    goPage(p) { router.visit(`/hr/employees?page=${p}`, { preserveState: true }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.btn-secondary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; border-radius: 10px; background: white; color: #475569; font-size: 0.82rem; font-weight: 600; border: 1.5px solid #e2e8f0; text-decoration: none; transition: all 0.2s; }
.btn-secondary:hover { border-color: #3b82f6; color: #3b82f6; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.85rem; margin-bottom: 1.25rem; }
.stat-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; background: white; border-radius: 14px; border: 1px solid #e2e8f0; transition: all 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.stat-icon.blue { background: #eff6ff; color: #3b82f6; } .stat-icon.green { background: #ecfdf5; color: #10b981; }
.stat-icon.purple { background: #eef2ff; color: #6366f1; } .stat-icon.orange { background: #fff7ed; color: #f59e0b; }
.stat-value { display: block; font-size: 1.3rem; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 0.68rem; color: #94a3b8; }

/* Filters */
.filter-bar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; transition: border-color 0.2s; min-width: 220px; }
.search-box:focus-within { border-color: #3b82f6; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-chips { display: flex; gap: 0.3rem; flex-wrap: wrap; flex: 1; }
.filter-chip { padding: 0.3rem 0.65rem; border-radius: 20px; font-size: 0.65rem; font-weight: 600; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.filter-chip:hover { border-color: #3b82f6; color: #3b82f6; }
.filter-chip.active { background: #3b82f6; color: white; border-color: #3b82f6; }
.view-toggle { display: flex; background: #f1f5f9; border-radius: 8px; padding: 2px; }
.view-toggle button { width: 32px; height: 32px; border: none; border-radius: 6px; background: transparent; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; transition: all 0.2s; }
.view-toggle button.active { background: white; color: #1e293b; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

/* Table */
.table-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.emp-table { width: 100%; border-collapse: collapse; }
.emp-table th { font-weight: 700; color: #64748b; text-align: left; padding: 0.75rem 1rem; border-bottom: 2px solid #e2e8f0; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.05em; background: #fafbfc; }
.emp-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; }
.table-row:hover td { background: #f8fafc; }
.text-right { text-align: right; }
.emp-cell { display: flex; align-items: center; gap: 0.65rem; }
.cell-avatar { width: 36px; height: 36px; border-radius: 10px; color: white; font-size: 0.65rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.emp-link { font-weight: 700; color: #1e293b; text-decoration: none; display: block; font-size: 0.85rem; }
.emp-link:hover { color: #3b82f6; }
.emp-email { font-size: 0.68rem; color: #94a3b8; display: block; }
.td-secondary { color: #64748b; }
.td-number { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; color: #334155; }
.td-number small { font-weight: 400; color: #94a3b8; }
.tenure-bar { display: flex; align-items: center; gap: 0.35rem; }
.tenure-bar .tenure-fill { width: 50px; height: 4px; background: #e2e8f0; border-radius: 2px; position: relative; overflow: hidden; }
.tenure-bar .tenure-fill::after { content: ''; position: absolute; top: 0; left: 0; height: 100%; background: linear-gradient(90deg, #3b82f6, #6366f1); border-radius: 2px; width: inherit; }
.tenure-bar span { font-size: 0.72rem; font-weight: 600; color: #475569; }
.row-actions { display: flex; gap: 0.25rem; opacity: 0; transition: opacity 0.15s; }
.table-row:hover .row-actions { opacity: 1; }
.action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; text-decoration: none; transition: all 0.2s; }
.action-btn:hover { border-color: #3b82f6; color: #3b82f6; }
.action-btn.danger:hover { border-color: #ef4444; color: #ef4444; }

/* Dept badges */
.dept-badge { font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
.dept-sales { background: #dbeafe; color: #2563eb; } .dept-marketing { background: #fce7f3; color: #db2777; }
.dept-engineering { background: #d1fae5; color: #059669; } .dept-design { background: #fef3c7; color: #d97706; }
.dept-support { background: #e0e7ff; color: #4f46e5; } .dept-management { background: #f1f5f9; color: #475569; }
.dept-hr { background: #fae8ff; color: #a855f7; } .dept-finance { background: #ccfbf1; color: #0d9488; }

/* Grid */
.emp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.emp-card { background: white; border-radius: 16px; border: 2px solid #e2e8f0; overflow: hidden; transition: all 0.3s; }
.emp-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transform: translateY(-2px); }
.emp-card-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.25rem 0; }
.card-avatar { width: 48px; height: 48px; border-radius: 14px; color: white; font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.emp-card-body { padding: 0.85rem 1.25rem; }
.card-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; text-decoration: none; display: block; }
.card-name:hover { color: #3b82f6; }
.card-position { font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 0.1rem; }
.card-stats { display: flex; gap: 1rem; margin-top: 0.75rem; }
.card-stat { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; color: #64748b; }
.card-stat i { font-size: 0.68rem; color: #94a3b8; }
.card-salary { display: flex; justify-content: space-between; margin-top: 0.65rem; padding: 0.5rem 0.65rem; background: #f8fafc; border-radius: 8px; }
.salary-label { font-size: 0.58rem; color: #94a3b8; display: block; }
.salary-value { font-size: 0.78rem; font-weight: 700; color: #1e293b; }
.emp-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 1.25rem; border-top: 1px solid #f1f5f9; }
.card-action { font-size: 0.72rem; font-weight: 600; color: #3b82f6; text-decoration: none; }
.card-action:hover { color: #2563eb; }
.card-footer-btns { display: flex; gap: 0.25rem; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; }
.page-info { font-size: 0.75rem; color: #94a3b8; }
.page-btns { display: flex; align-items: center; gap: 0.4rem; }
.page-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; transition: all 0.2s; }
.page-btn:hover:not(:disabled) { border-color: #3b82f6; color: #3b82f6; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.page-current { font-size: 0.82rem; font-weight: 700; color: #1e293b; padding: 0 0.5rem; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s; }
@keyframes fadeIn { from { opacity: 0; } }
.modal-panel { background: white; border-radius: 20px; width: 90%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.modal-header h3 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.modal-header h3 i { color: #3b82f6; }
.modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; padding: 0.3rem; border-radius: 6px; transition: all 0.2s; }
.modal-close:hover { color: #1e293b; background: #f1f5f9; }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; max-height: 60vh; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover { border-color: #94a3b8; }

/* Form */
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.required { color: #ef4444; }
.form-input, .form-select { width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; color: #334155; background: #f8fafc; outline: none; transition: all 0.2s; font-family: inherit; box-sizing: border-box; }
.form-input:focus, .form-select:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .filter-bar { flex-direction: column; }
  .emp-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
