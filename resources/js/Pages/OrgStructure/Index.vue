<template>
  <div>
    <Head :title="t('common.org_chart')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-sitemap" /></div>
        <div>
          <h1 class="page-title">{{ t('common.org_chart') }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Quản lý cơ cấu tổ chức công ty' : 'Manage organizational structure' }}</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-icon" @click="$inertia.visit('/org-structure/snapshots')" title="Lịch sử"><i class="pi pi-history" /></button>
        <button class="btn-icon" @click="showSnapshotDialog = true" title="Snapshot"><i class="pi pi-camera" /></button>
        <button class="btn-secondary" @click="showTeamDialog = true"><i class="pi pi-users" /> {{ isVi ? 'Nhóm mới' : 'New Team' }}</button>
        <button class="btn-primary" @click="showDeptDialog = true"><i class="pi pi-plus" /> {{ isVi ? 'Phòng ban mới' : 'New Department' }}</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div v-for="s in statCards" :key="s.label" class="stat-card">
        <div class="stat-icon" :style="{ background: s.bg, color: s.color }"><i :class="s.icon" /></div>
        <div><span class="stat-value">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <!-- View Toggle -->
    <div class="toolbar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="searchQuery" :placeholder="isVi ? 'Tìm phòng ban, nhóm...' : 'Search departments...'" class="search-input" />
      </div>
      <div class="view-toggle">
        <button :class="{ active: viewMode === 'tree' }" @click="viewMode = 'tree'"><i class="pi pi-sitemap" /></button>
        <button :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'"><i class="pi pi-th-large" /></button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredDepts.length === 0" class="empty-state">
      <div class="empty-icon"><i class="pi pi-sitemap" /></div>
      <h3>{{ isVi ? 'Chưa có phòng ban nào' : 'No departments yet' }}</h3>
      <p>{{ isVi ? 'Bắt đầu xây dựng cơ cấu tổ chức' : 'Start building your org structure' }}</p>
      <button class="btn-primary" @click="showDeptDialog = true"><i class="pi pi-plus" /> {{ isVi ? 'Tạo phòng ban' : 'Create Department' }}</button>
    </div>

    <!-- Tree View -->
    <div v-else-if="viewMode === 'tree'" class="org-tree">
      <div v-for="dept in filteredDepts" :key="dept.id" class="dept-card">
        <div class="dept-accent" :style="{ background: dept.color || '#6366f1' }" />
        <div class="dept-body">
          <div class="dept-header">
            <div class="dept-icon-wrap" :style="{ background: (dept.color || '#6366f1') + '18', color: dept.color || '#6366f1' }">
              <i :class="dept.icon || 'pi pi-building'" />
            </div>
            <div class="dept-info">
              <h3 class="dept-name">{{ dept.name }}</h3>
              <div class="dept-meta">
                <span v-if="dept.code" class="code-badge" :style="{ background: (dept.color || '#6366f1') + '15', color: dept.color || '#6366f1' }">{{ dept.code }}</span>
                <span class="dept-count"><i class="pi pi-users" /> {{ (dept.teams || []).length }} {{ isVi ? 'nhóm' : 'teams' }}</span>
              </div>
            </div>
            <div class="dept-actions">
              <button class="action-btn" @click="editDept(dept)"><i class="pi pi-pencil" /></button>
              <button class="action-btn danger" @click="deleteDept(dept)"><i class="pi pi-trash" /></button>
            </div>
          </div>

          <!-- Head -->
          <div v-if="dept.head" class="dept-head">
            <div class="head-avatar" :style="{ background: dept.color || '#6366f1' }">{{ (dept.head.first_name || '?')[0] }}</div>
            <div class="head-info">
              <span class="head-name">{{ dept.head.first_name }} {{ dept.head.last_name }}</span>
              <span class="head-role">{{ isVi ? 'Trưởng phòng' : 'Head' }}</span>
            </div>
          </div>

          <!-- Budget -->
          <div v-if="dept.budget_monthly > 0" class="dept-budget">
            <span class="budget-label"><i class="pi pi-wallet" /> {{ isVi ? 'Ngân sách/tháng' : 'Monthly Budget' }}</span>
            <span class="budget-value">{{ formatCurrency(dept.budget_monthly) }}</span>
          </div>

          <!-- Teams -->
          <div v-if="dept.teams && dept.teams.length" class="teams-section">
            <div class="teams-header">
              <span><i class="pi pi-users" /> {{ isVi ? 'Nhóm' : 'Teams' }} ({{ dept.teams.length }})</span>
            </div>
            <div class="teams-grid">
              <div v-for="team in dept.teams" :key="team.id" class="team-card">
                <div class="team-header-row">
                  <span class="team-dot" :style="{ background: team.color || '#10b981' }" />
                  <span class="team-name">{{ team.name }}</span>
                  <span class="team-member-count">{{ (team.active_members || []).length }}</span>
                  <div class="team-btns">
                    <button class="action-btn sm" @click="editTeam(team)"><i class="pi pi-pencil" /></button>
                    <button class="action-btn sm danger" @click="deleteTeam(team)"><i class="pi pi-trash" /></button>
                  </div>
                </div>
                <div v-if="team.active_members && team.active_members.length" class="member-row">
                  <div v-for="(m, idx) in team.active_members.slice(0, 5)" :key="m.id" class="member-avatar"
                    :style="{ background: avatarColors[idx % avatarColors.length], marginLeft: idx > 0 ? '-6px' : '0', zIndex: 5 - idx }"
                    :title="(m.user?.first_name || '') + ' ' + (m.user?.last_name || '')">
                    {{ (m.user?.first_name || '?')[0] }}
                  </div>
                  <span v-if="team.active_members.length > 5" class="member-more">+{{ team.active_members.length - 5 }}</span>
                </div>
                <div v-else class="no-members">{{ isVi ? 'Chưa có thành viên' : 'No members' }}</div>
              </div>
            </div>
          </div>

          <!-- Sub-departments -->
          <div v-if="dept.children && dept.children.length" class="sub-depts">
            <div class="sub-label"><i class="pi pi-arrow-right" /> {{ isVi ? 'Phòng ban con' : 'Sub-departments' }}</div>
            <div class="sub-grid">
              <div v-for="sub in dept.children" :key="sub.id" class="sub-card" :style="{ borderLeftColor: sub.color || '#94a3b8' }">
                <i :class="sub.icon || 'pi pi-building'" class="sub-icon" :style="{ color: sub.color || '#94a3b8' }" />
                <div><span class="sub-name">{{ sub.name }}</span><span v-if="sub.code" class="sub-code">{{ sub.code }}</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Grid View -->
    <div v-else class="dept-grid">
      <div v-for="dept in filteredDepts" :key="dept.id" class="grid-card">
        <div class="grid-accent" :style="{ background: `linear-gradient(135deg, ${dept.color || '#6366f1'}, ${dept.color || '#6366f1'}cc)` }" />
        <div class="grid-body">
          <div class="grid-top">
            <div class="grid-icon" :style="{ background: (dept.color || '#6366f1') + '15', color: dept.color || '#6366f1' }">
              <i :class="dept.icon || 'pi pi-building'" />
            </div>
            <div class="grid-btns">
              <button class="action-btn sm" @click="editDept(dept)"><i class="pi pi-pencil" /></button>
              <button class="action-btn sm danger" @click="deleteDept(dept)"><i class="pi pi-trash" /></button>
            </div>
          </div>
          <h3 class="grid-name">{{ dept.name }}</h3>
          <span v-if="dept.code" class="code-badge sm" :style="{ background: (dept.color || '#6366f1') + '15', color: dept.color || '#6366f1' }">{{ dept.code }}</span>
          <p v-if="dept.description" class="grid-desc">{{ dept.description }}</p>
          <div class="grid-stats">
            <div class="gs"><i class="pi pi-users" /><span>{{ (dept.teams || []).length }} {{ isVi ? 'nhóm' : 'teams' }}</span></div>
            <div v-if="dept.budget_monthly > 0" class="gs"><i class="pi pi-wallet" /><span>{{ formatShort(dept.budget_monthly) }}</span></div>
          </div>
          <div v-if="dept.head" class="grid-head">
            <div class="head-avatar sm" :style="{ background: dept.color || '#6366f1' }">{{ (dept.head.first_name || '?')[0] }}</div>
            <span>{{ dept.head.first_name }} {{ dept.head.last_name }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Dept Dialog -->
    <div v-if="showDeptDialog" class="modal-overlay" @click.self="closeDeptDialog">
      <div class="modal-panel">
        <div class="modal-header">
          <h3><i class="pi pi-building" /> {{ editingDept ? t('common.edit') : isVi ? 'Tạo phòng ban' : 'Create Department' }}</h3>
          <button class="modal-close" @click="closeDeptDialog"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div class="form-group"><label>{{ t('common.name') }} <span class="req">*</span></label><input v-model="deptForm.name" class="form-input" :placeholder="isVi ? 'VD: Phòng Kinh doanh' : 'e.g. Sales'" /></div>
          <div class="form-row">
            <div class="form-group"><label>{{ isVi ? 'Mã' : 'Code' }}</label><input v-model="deptForm.code" class="form-input" placeholder="SALES" /></div>
            <div class="form-group"><label>{{ isVi ? 'Màu' : 'Color' }}</label><div class="color-row"><input v-model="deptForm.color" type="color" class="color-picker" /><span class="color-hex">{{ deptForm.color }}</span></div></div>
          </div>
          <div class="form-group"><label>{{ t('common.description') }}</label><textarea v-model="deptForm.description" class="form-input" rows="2" /></div>
          <div class="form-row">
            <div class="form-group"><label>{{ isVi ? 'Ngân sách/tháng (VND)' : 'Monthly Budget' }}</label><input v-model="deptForm.budget_monthly" type="number" class="form-input" /></div>
            <div class="form-group"><label>{{ isVi ? 'Ngân sách/năm (VND)' : 'Yearly Budget' }}</label><input v-model="deptForm.budget_yearly" type="number" class="form-input" /></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeDeptDialog">{{ t('common.cancel') }}</button>
          <button class="btn-primary" @click="saveDept" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> {{ editingDept ? t('common.save') : t('common.create') }}</button>
        </div>
      </div>
    </div>

    <!-- Team Dialog -->
    <div v-if="showTeamDialog" class="modal-overlay" @click.self="closeTeamDialog">
      <div class="modal-panel">
        <div class="modal-header">
          <h3><i class="pi pi-users" /> {{ editingTeam ? t('common.edit') : isVi ? 'Tạo nhóm' : 'Create Team' }}</h3>
          <button class="modal-close" @click="closeTeamDialog"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>{{ t('common.department') }} <span class="req">*</span></label>
            <select v-model="teamForm.department_id" class="form-input">
              <option :value="null">{{ isVi ? 'Chọn phòng ban' : 'Select department' }}</option>
              <option v-for="d in allDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>
          <div class="form-group"><label>{{ t('common.name') }} <span class="req">*</span></label><input v-model="teamForm.name" class="form-input" /></div>
          <div class="form-group"><label>{{ t('common.description') }}</label><textarea v-model="teamForm.description" class="form-input" rows="2" /></div>
          <div class="form-row">
            <div class="form-group"><label>{{ isVi ? 'Màu' : 'Color' }}</label><div class="color-row"><input v-model="teamForm.color" type="color" class="color-picker" /><span class="color-hex">{{ teamForm.color }}</span></div></div>
            <div class="form-group"><label>{{ isVi ? 'Sức chứa' : 'Capacity' }}</label><input v-model="teamForm.capacity" type="number" class="form-input" /></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeTeamDialog">{{ t('common.cancel') }}</button>
          <button class="btn-primary" @click="saveTeam" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> {{ editingTeam ? t('common.save') : t('common.create') }}</button>
        </div>
      </div>
    </div>

    <!-- Snapshot Dialog -->
    <div v-if="showSnapshotDialog" class="modal-overlay" @click.self="showSnapshotDialog = false">
      <div class="modal-panel sm">
        <div class="modal-header">
          <h3><i class="pi pi-camera" /> {{ isVi ? 'Lưu Snapshot' : 'Save Snapshot' }}</h3>
          <button class="modal-close" @click="showSnapshotDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="modal-body">
          <div class="info-banner"><i class="pi pi-info-circle" /> {{ isVi ? 'Snapshot lưu lại cơ cấu hiện tại để so sánh sau này' : 'Captures current structure for comparison' }}</div>
          <div class="form-group"><label>{{ t('common.name') }} <span class="req">*</span></label><input v-model="snapshotForm.name" class="form-input" :placeholder="isVi ? 'VD: Q1 2026' : 'e.g. Q1 2026'" /></div>
          <div class="form-group"><label>{{ t('common.description') }}</label><textarea v-model="snapshotForm.description" class="form-input" rows="2" /></div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showSnapshotDialog = false">{{ t('common.cancel') }}</button>
          <button class="btn-primary" @click="takeSnapshot" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-camera'" /> {{ isVi ? 'Lưu' : 'Save' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head },
  layout: Layout,
  props: { departments: Array, stats: Object, allDepartments: Array, allTeams: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      searchQuery: '', viewMode: 'tree', hoveredDept: null, saving: false,
      showDeptDialog: false, editingDept: null,
      deptForm: { name: '', code: '', description: '', color: '#6366F1', parent_id: null, head_user_id: null, budget_monthly: 0, budget_yearly: 0 },
      showTeamDialog: false, editingTeam: null,
      teamForm: { name: '', department_id: null, description: '', color: '#10B981', capacity: null },
      showSnapshotDialog: false, snapshotForm: { name: '', description: '' },
      avatarColors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4'],
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    filteredDepts() {
      if (!this.searchQuery) return this.departments
      const q = this.searchQuery.toLowerCase()
      return this.departments.filter(d => {
        const nameMatch = d.name?.toLowerCase().includes(q) || d.code?.toLowerCase().includes(q)
        const teamMatch = (d.teams || []).some(t => t.name?.toLowerCase().includes(q))
        return nameMatch || teamMatch
      })
    },
    statCards() {
      return [
        { label: this.isVi ? 'Phòng ban' : 'Departments', value: this.stats?.total_departments || 0, icon: 'pi pi-building', color: '#6366f1', bg: '#eef2ff' },
        { label: this.isVi ? 'Nhóm' : 'Teams', value: this.stats?.total_teams || 0, icon: 'pi pi-users', color: '#10b981', bg: '#ecfdf5' },
        { label: this.isVi ? 'Nhân viên' : 'Employees', value: this.stats?.total_employees || 0, icon: 'pi pi-user', color: '#f59e0b', bg: '#fffbeb' },
        { label: this.isVi ? 'Vị trí' : 'Positions', value: this.stats?.total_positions || 0, icon: 'pi pi-briefcase', color: '#8b5cf6', bg: '#f5f3ff' },
      ]
    },
  },
  methods: {
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    formatShort(v) { return v >= 1e9 ? (v/1e9).toFixed(1)+'B' : v >= 1e6 ? (v/1e6).toFixed(1)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : v },
    editDept(dept) { this.editingDept = dept; this.deptForm = { name: dept.name, code: dept.code, description: dept.description, color: dept.color || '#6366F1', parent_id: dept.parent_id, head_user_id: dept.head_user_id, budget_monthly: dept.budget_monthly, budget_yearly: dept.budget_yearly }; this.showDeptDialog = true },
    closeDeptDialog() { this.showDeptDialog = false; this.editingDept = null; this.deptForm = { name: '', code: '', description: '', color: '#6366F1', budget_monthly: 0, budget_yearly: 0 } },
    saveDept() { this.saving = true; const cb = { onFinish: () => { this.saving = false; this.closeDeptDialog() } }; this.editingDept ? router.put(`/departments/${this.editingDept.id}`, this.deptForm, cb) : router.post('/departments', this.deptForm, cb) },
    deleteDept(dept) { if (confirm(this.isVi ? 'Xóa phòng ban này?' : 'Delete?')) router.delete(`/departments/${dept.id}`) },
    editTeam(team) { this.editingTeam = team; this.teamForm = { name: team.name, department_id: team.department_id, description: team.description, color: team.color || '#10B981', capacity: team.capacity }; this.showTeamDialog = true },
    closeTeamDialog() { this.showTeamDialog = false; this.editingTeam = null; this.teamForm = { name: '', department_id: null, description: '', color: '#10B981', capacity: null } },
    saveTeam() { this.saving = true; const cb = { onFinish: () => { this.saving = false; this.closeTeamDialog() } }; this.editingTeam ? router.put(`/teams/${this.editingTeam.id}`, this.teamForm, cb) : router.post('/teams', this.teamForm, cb) },
    deleteTeam(team) { if (confirm(this.isVi ? 'Xóa nhóm này?' : 'Delete?')) router.delete(`/teams/${team.id}`) },
    takeSnapshot() { this.saving = true; router.post('/org-structure/snapshot', this.snapshotForm, { onFinish: () => { this.saving = false; this.showSnapshotDialog = false } }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #4338ca); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.header-actions { display: flex; gap: 0.4rem; align-items: center; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-secondary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; border-radius: 10px; background: white; color: #475569; font-size: 0.82rem; font-weight: 600; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { border-color: #6366f1; color: #6366f1; }
.btn-icon { width: 38px; height: 38px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; transition: all 0.2s; }
.btn-icon:hover { border-color: #6366f1; color: #6366f1; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.85rem; margin-bottom: 1.25rem; }
.stat-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; background: white; border-radius: 14px; border: 1px solid #e2e8f0; transition: all 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.stat-value { display: block; font-size: 1.3rem; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 0.68rem; color: #94a3b8; }

/* Toolbar */
.toolbar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; flex: 1; max-width: 320px; }
.search-box:focus-within { border-color: #6366f1; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.view-toggle { display: flex; background: #f1f5f9; border-radius: 8px; padding: 2px; }
.view-toggle button { width: 32px; height: 32px; border: none; border-radius: 6px; background: transparent; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; transition: all 0.2s; }
.view-toggle button.active { background: white; color: #1e293b; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

/* Org Tree */
.org-tree { display: flex; flex-direction: column; gap: 1rem; }
.dept-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.3s; }
.dept-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
.dept-accent { height: 4px; }
.dept-body { padding: 1.25rem 1.5rem; }
.dept-header { display: flex; align-items: center; gap: 0.85rem; }
.dept-icon-wrap { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
.dept-info { flex: 1; min-width: 0; }
.dept-name { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.dept-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.15rem; }
.code-badge { font-size: 0.58rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.04em; }
.code-badge.sm { font-size: 0.55rem; margin-top: 0.3rem; display: inline-block; }
.dept-count { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.dept-count i { font-size: 0.65rem; }
.dept-actions { display: flex; gap: 0.25rem; opacity: 0; transition: opacity 0.15s; }
.dept-card:hover .dept-actions { opacity: 1; }
.action-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; transition: all 0.2s; }
.action-btn:hover { border-color: #6366f1; color: #6366f1; }
.action-btn.danger:hover { border-color: #ef4444; color: #ef4444; }
.action-btn.sm { width: 24px; height: 24px; font-size: 0.58rem; border-radius: 6px; }

/* Department Head */
.dept-head { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
.head-avatar { width: 32px; height: 32px; border-radius: 8px; color: white; font-size: 0.65rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.head-avatar.sm { width: 24px; height: 24px; border-radius: 6px; font-size: 0.55rem; }
.head-info { display: flex; flex-direction: column; }
.head-name { font-size: 0.82rem; font-weight: 600; color: #334155; }
.head-role { font-size: 0.65rem; color: #94a3b8; }

/* Budget */
.dept-budget { display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem; padding: 0.55rem 0.85rem; background: #f8fafc; border-radius: 10px; }
.budget-label { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.3rem; }
.budget-label i { font-size: 0.68rem; }
.budget-value { font-size: 0.82rem; font-weight: 700; color: #10b981; }

/* Teams */
.teams-section { margin-top: 1rem; }
.teams-header { margin-bottom: 0.5rem; }
.teams-header span { font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.35rem; }
.teams-header span i { font-size: 0.7rem; }
.teams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.5rem; }
.team-card { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 0.7rem 0.85rem; transition: all 0.2s; }
.team-card:hover { background: #f1f5f9; border-color: #e2e8f0; }
.team-header-row { display: flex; align-items: center; gap: 0.5rem; }
.team-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.team-name { font-size: 0.85rem; font-weight: 600; color: #334155; flex: 1; }
.team-member-count { font-size: 0.62rem; font-weight: 700; color: #94a3b8; background: white; padding: 0.1rem 0.4rem; border-radius: 4px; }
.team-btns { display: flex; gap: 0.15rem; opacity: 0; transition: opacity 0.2s; }
.team-card:hover .team-btns { opacity: 1; }
.member-row { display: flex; align-items: center; margin-top: 0.5rem; padding-left: 1rem; }
.member-avatar { width: 26px; height: 26px; border-radius: 7px; color: white; font-size: 0.55rem; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid white; position: relative; }
.member-more { font-size: 0.65rem; font-weight: 700; color: #94a3b8; margin-left: 0.35rem; }
.no-members { font-size: 0.7rem; color: #cbd5e1; margin-top: 0.35rem; padding-left: 1rem; font-style: italic; }

/* Sub-departments */
.sub-depts { margin-top: 1rem; }
.sub-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.sub-label i { font-size: 0.6rem; }
.sub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.35rem; }
.sub-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: #fafbfe; border: 1px solid #f1f5f9; border-left: 3px solid; border-radius: 8px; }
.sub-icon { font-size: 0.85rem; }
.sub-name { font-size: 0.82rem; font-weight: 600; color: #334155; display: block; }
.sub-code { font-size: 0.6rem; color: #94a3b8; display: block; }

/* Grid View */
.dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.grid-card { background: white; border: 2px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.3s; }
.grid-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transform: translateY(-2px); }
.grid-accent { height: 4px; }
.grid-body { padding: 1.15rem 1.25rem; }
.grid-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }
.grid-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.grid-btns { display: flex; gap: 0.2rem; opacity: 0; transition: opacity 0.2s; }
.grid-card:hover .grid-btns { opacity: 1; }
.grid-name { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.grid-desc { font-size: 0.75rem; color: #94a3b8; margin: 0.3rem 0 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.grid-stats { display: flex; gap: 1rem; margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px solid #f1f5f9; }
.gs { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; color: #64748b; }
.gs i { font-size: 0.68rem; color: #94a3b8; }
.grid-head { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.6rem; font-size: 0.75rem; color: #475569; font-weight: 500; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; background: white; border: 2px dashed #e2e8f0; border-radius: 20px; }
.empty-icon { width: 72px; height: 72px; border-radius: 20px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s; }
@keyframes fadeIn { from { opacity: 0; } }
.modal-panel { background: white; border-radius: 20px; width: 90%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: slideUp 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.modal-panel.sm { max-width: 420px; }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.modal-header h3 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.modal-header h3 i { color: #6366f1; }
.modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; padding: 0.3rem; border-radius: 6px; }
.modal-close:hover { color: #1e293b; background: #f1f5f9; }
.modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.85rem; max-height: 60vh; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }

/* Form */
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.req { color: #ef4444; }
.form-input { width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; color: #334155; background: #f8fafc; outline: none; transition: all 0.2s; font-family: inherit; box-sizing: border-box; resize: vertical; }
.form-input:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.color-row { display: flex; align-items: center; gap: 0.5rem; }
.color-picker { width: 42px; height: 36px; padding: 2px; cursor: pointer; border-radius: 8px; border: 1.5px solid #e2e8f0; }
.color-hex { font-size: 0.72rem; color: #64748b; font-family: monospace; }
.info-banner { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 1rem; background: #eef2ff; border-radius: 10px; font-size: 0.78rem; color: #4338ca; line-height: 1.4; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .dept-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .toolbar { flex-direction: column; }
  .search-box { max-width: 100%; width: 100%; }
}
</style>
