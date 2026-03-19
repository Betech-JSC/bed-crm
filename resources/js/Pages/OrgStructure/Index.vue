<template>
  <div>
    <Head :title="t('common.org_chart')" />

    <!-- Hero Header -->
    <div class="hero-banner">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon-wrap"><i class="pi pi-sitemap" /></div>
          <div>
            <h1 class="hero-title">{{ t('common.org_chart') }}</h1>
            <p class="hero-subtitle">{{ isVi ? 'Quản lý cơ cấu tổ chức công ty' : 'Manage your company organizational structure' }}</p>
          </div>
        </div>
        <div class="hero-actions">
          <Button :label="isVi ? 'Phòng ban mới' : 'New Department'" icon="pi pi-plus" @click="showDeptDialog = true" class="hero-btn" />
          <Button :label="isVi ? 'Nhóm mới' : 'New Team'" icon="pi pi-users" severity="secondary" outlined @click="showTeamDialog = true" />
          <Button icon="pi pi-camera" severity="secondary" outlined v-tooltip.bottom="isVi ? 'Lưu snapshot' : 'Save Snapshot'" @click="showSnapshotDialog = true" />
          <Button icon="pi pi-history" severity="secondary" outlined v-tooltip.bottom="isVi ? 'Lịch sử' : 'History'" @click="$inertia.visit('/org-structure/snapshots')" />
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card" v-for="s in statCards" :key="s.label">
        <div class="stat-icon-wrap" :style="{ background: s.bg }"><i :class="s.icon" :style="{ color: s.color }" /></div>
        <div class="stat-body">
          <span class="stat-number">{{ s.value }}</span>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </div>

    <!-- Org Chart Tree -->
    <div v-if="departments.length === 0" class="empty-state">
      <div class="empty-illustration">
        <i class="pi pi-sitemap" />
      </div>
      <h3>{{ isVi ? 'Chưa có phòng ban nào' : 'No departments yet' }}</h3>
      <p>{{ isVi ? 'Bắt đầu xây dựng cơ cấu tổ chức' : 'Start building your org structure' }}</p>
      <Button :label="isVi ? 'Tạo phòng ban đầu tiên' : 'Create First Department'" icon="pi pi-plus" @click="showDeptDialog = true" />
    </div>

    <div v-else class="org-tree">
      <div v-for="dept in departments" :key="dept.id" class="dept-card" @mouseenter="hoveredDept = dept.id" @mouseleave="hoveredDept = null">
        <div class="dept-accent" :style="{ background: dept.color || '#6366f1' }" />
        <div class="dept-body">
          <div class="dept-header">
            <div class="dept-icon-wrap" :style="{ background: (dept.color || '#6366f1') + '15', color: dept.color || '#6366f1' }">
              <i :class="dept.icon || 'pi pi-building'" />
            </div>
            <div class="dept-info">
              <h3 class="dept-name">{{ dept.name }}</h3>
              <div class="dept-meta">
                <span class="dept-code" v-if="dept.code">{{ dept.code }}</span>
                <span class="dept-count">{{ (dept.teams || []).length }} {{ isVi ? 'nhóm' : 'teams' }}</span>
              </div>
            </div>
            <Transition name="fade">
              <div class="dept-actions" v-show="hoveredDept === dept.id">
                <Button icon="pi pi-pencil" text rounded size="small" v-tooltip.top="t('common.edit')" @click.stop="editDept(dept)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip.top="t('common.delete')" @click.stop="deleteDept(dept)" />
              </div>
            </Transition>
          </div>

          <!-- Department Head -->
          <div v-if="dept.head" class="dept-head">
            <Avatar :label="(dept.head.first_name || '?').charAt(0)" shape="circle" size="small" :style="{ background: dept.color || '#6366f1', color: 'white' }" />
            <span class="head-name">{{ dept.head.first_name }} {{ dept.head.last_name }}</span>
            <span class="head-role">{{ isVi ? 'Trưởng phòng' : 'Department Head' }}</span>
          </div>

          <!-- Budget -->
          <div v-if="dept.budget_monthly > 0 || dept.budget_yearly > 0" class="dept-budget">
            <div class="budget-item" v-if="dept.budget_monthly > 0">
              <span class="budget-label">{{ isVi ? 'Ngân sách/tháng' : 'Monthly Budget' }}</span>
              <span class="budget-value">{{ formatCurrency(dept.budget_monthly) }}</span>
            </div>
          </div>

          <!-- Teams -->
          <div v-if="dept.teams && dept.teams.length" class="teams-section">
            <div class="teams-header">
              <span class="teams-title"><i class="pi pi-users" /> {{ isVi ? 'Nhóm' : 'Teams' }} ({{ dept.teams.length }})</span>
            </div>
            <div class="teams-grid">
              <div v-for="team in dept.teams" :key="team.id" class="team-card">
                <div class="team-header">
                  <span class="team-dot" :style="{ background: team.color || '#10b981' }" />
                  <span class="team-name">{{ team.name }}</span>
                  <span class="team-count">{{ (team.active_members || []).length }}</span>
                  <div class="team-actions">
                    <Button icon="pi pi-pencil" text rounded size="small" @click.stop="editTeam(team)" />
                    <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click.stop="deleteTeam(team)" />
                  </div>
                </div>
                <div v-if="team.active_members && team.active_members.length" class="member-avatars">
                  <Avatar v-for="(m, idx) in team.active_members.slice(0, 5)" :key="m.id"
                    :label="(m.user?.first_name || '?').charAt(0)"
                    shape="circle" size="small"
                    :style="{ background: avatarColors[idx % avatarColors.length], color: 'white', marginLeft: idx > 0 ? '-6px' : '0', border: '2px solid white', zIndex: 5 - idx }"
                    v-tooltip.top="(m.user?.first_name || '') + ' ' + (m.user?.last_name || '')" />
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
                <div>
                  <span class="sub-name">{{ sub.name }}</span>
                  <span class="sub-code" v-if="sub.code">{{ sub.code }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Department Dialog -->
    <Dialog v-model:visible="showDeptDialog" :header="editingDept ? t('common.edit') + ' ' + t('common.department') : isVi ? 'Tạo phòng ban mới' : 'Create New Department'" modal :style="{ width: '520px' }" :pt="{ root: { class: 'dialog-premium' } }">
      <div class="dialog-form">
        <div class="form-group"><label>{{ t('common.name') }} <span class="required">*</span></label><InputText v-model="deptForm.name" class="w-full" :placeholder="isVi ? 'VD: Phòng Kinh doanh' : 'e.g. Sales Department'" /></div>
        <div class="form-row">
          <div class="form-group"><label>{{ isVi ? 'Mã' : 'Code' }}</label><InputText v-model="deptForm.code" class="w-full" placeholder="SALES" /></div>
          <div class="form-group"><label>{{ isVi ? 'Màu sắc' : 'Color' }}</label><div class="color-picker-wrap"><InputText v-model="deptForm.color" type="color" class="color-input" /><span class="color-hex">{{ deptForm.color }}</span></div></div>
        </div>
        <div class="form-group"><label>{{ t('common.description') }}</label><Textarea v-model="deptForm.description" rows="2" class="w-full" autoResize /></div>
        <div class="form-row">
          <div class="form-group"><label>{{ isVi ? 'Ngân sách tháng' : 'Monthly Budget' }}</label><InputNumber v-model="deptForm.budget_monthly" mode="currency" currency="VND" locale="vi-VN" class="w-full" /></div>
          <div class="form-group"><label>{{ isVi ? 'Ngân sách năm' : 'Yearly Budget' }}</label><InputNumber v-model="deptForm.budget_yearly" mode="currency" currency="VND" locale="vi-VN" class="w-full" /></div>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <Button :label="t('common.cancel')" severity="secondary" text @click="closeDeptDialog" />
          <Button :label="editingDept ? t('common.save') : t('common.create')" icon="pi pi-check" @click="saveDept" :loading="saving" />
        </div>
      </template>
    </Dialog>

    <!-- Create Team Dialog -->
    <Dialog v-model:visible="showTeamDialog" :header="editingTeam ? t('common.edit') + ' ' + t('common.team') : isVi ? 'Tạo nhóm mới' : 'Create New Team'" modal :style="{ width: '520px' }">
      <div class="dialog-form">
        <div class="form-group"><label>{{ t('common.department') }} <span class="required">*</span></label>
          <Dropdown v-model="teamForm.department_id" :options="allDepartments" optionLabel="name" optionValue="id" :placeholder="isVi ? 'Chọn phòng ban' : 'Select department'" class="w-full" />
        </div>
        <div class="form-group"><label>{{ t('common.name') }} <span class="required">*</span></label><InputText v-model="teamForm.name" class="w-full" /></div>
        <div class="form-group"><label>{{ t('common.description') }}</label><Textarea v-model="teamForm.description" rows="2" class="w-full" autoResize /></div>
        <div class="form-row">
          <div class="form-group"><label>{{ isVi ? 'Màu sắc' : 'Color' }}</label><div class="color-picker-wrap"><InputText v-model="teamForm.color" type="color" class="color-input" /><span class="color-hex">{{ teamForm.color }}</span></div></div>
          <div class="form-group"><label>{{ isVi ? 'Sức chứa' : 'Capacity' }}</label><InputNumber v-model="teamForm.capacity" class="w-full" :placeholder="isVi ? 'Tối đa' : 'Max'" /></div>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer"><Button :label="t('common.cancel')" severity="secondary" text @click="closeTeamDialog" /><Button :label="editingTeam ? t('common.save') : t('common.create')" icon="pi pi-check" @click="saveTeam" :loading="saving" /></div>
      </template>
    </Dialog>

    <!-- Snapshot Dialog -->
    <Dialog v-model:visible="showSnapshotDialog" :header="isVi ? 'Lưu snapshot tổ chức' : 'Save Org Snapshot'" modal :style="{ width: '420px' }">
      <div class="dialog-form">
        <div class="snapshot-info">
          <i class="pi pi-info-circle" />
          <span>{{ isVi ? 'Snapshot lưu lại cơ cấu hiện tại để so sánh sau này' : 'Snapshots capture the current structure for future comparison' }}</span>
        </div>
        <div class="form-group"><label>{{ t('common.name') }} <span class="required">*</span></label><InputText v-model="snapshotForm.name" class="w-full" :placeholder="isVi ? 'VD: Cơ cấu Q1 2026' : 'e.g. Q1 2026 Structure'" /></div>
        <div class="form-group"><label>{{ t('common.description') }}</label><Textarea v-model="snapshotForm.description" rows="2" class="w-full" autoResize /></div>
      </div>
      <template #footer>
        <div class="dialog-footer"><Button :label="t('common.cancel')" severity="secondary" text @click="showSnapshotDialog = false" /><Button :label="isVi ? 'Lưu snapshot' : 'Save Snapshot'" icon="pi pi-camera" @click="takeSnapshot" :loading="saving" /></div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Avatar from 'primevue/avatar'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, Dialog, InputText, InputNumber, Textarea, Dropdown, Avatar },
  layout: Layout,
  props: { departments: Array, stats: Object, allDepartments: Array, allTeams: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      hoveredDept: null, saving: false,
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
    editDept(dept) { this.editingDept = dept; this.deptForm = { name: dept.name, code: dept.code, description: dept.description, color: dept.color || '#6366F1', parent_id: dept.parent_id, head_user_id: dept.head_user_id, budget_monthly: dept.budget_monthly, budget_yearly: dept.budget_yearly }; this.showDeptDialog = true },
    closeDeptDialog() { this.showDeptDialog = false; this.editingDept = null; this.deptForm = { name: '', code: '', description: '', color: '#6366F1', budget_monthly: 0, budget_yearly: 0 } },
    saveDept() { this.saving = true; const cb = { onFinish: () => { this.saving = false; this.closeDeptDialog() } }; this.editingDept ? router.put(`/departments/${this.editingDept.id}`, this.deptForm, cb) : router.post('/departments', this.deptForm, cb) },
    deleteDept(dept) { if (confirm(this.isVi ? 'Xác nhận xoá phòng ban này?' : 'Delete this department?')) { router.delete(`/departments/${dept.id}`) } },
    editTeam(team) { this.editingTeam = team; this.teamForm = { name: team.name, department_id: team.department_id, description: team.description, color: team.color || '#10B981', capacity: team.capacity }; this.showTeamDialog = true },
    closeTeamDialog() { this.showTeamDialog = false; this.editingTeam = null; this.teamForm = { name: '', department_id: null, description: '', color: '#10B981', capacity: null } },
    saveTeam() { this.saving = true; const cb = { onFinish: () => { this.saving = false; this.closeTeamDialog() } }; this.editingTeam ? router.put(`/teams/${this.editingTeam.id}`, this.teamForm, cb) : router.post('/teams', this.teamForm, cb) },
    deleteTeam(team) { if (confirm(this.isVi ? 'Xác nhận xoá nhóm này?' : 'Delete this team?')) { router.delete(`/teams/${team.id}`) } },
    takeSnapshot() { this.saving = true; router.post('/org-structure/snapshot', this.snapshotForm, { onFinish: () => { this.saving = false; this.showSnapshotDialog = false } }) },
  },
}
</script>

<style scoped>
/* ===== Hero Banner ===== */
.hero-banner { background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%); border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.25rem; position: relative; overflow: hidden; }
.hero-banner::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
.hero-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
.hero-left { display: flex; align-items: center; gap: 1rem; }
.hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; }
.hero-title { font-size: 1.35rem; font-weight: 700; color: white; margin: 0; letter-spacing: -0.01em; }
.hero-subtitle { font-size: 0.78rem; color: rgba(255,255,255,0.7); margin: 0.15rem 0 0; }
.hero-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.hero-btn { background: white !important; color: #4338ca !important; border: none !important; font-weight: 600; }

/* ===== Stats Row ===== */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
@media (max-width: 768px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
.stat-card { background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 1rem 1.15rem; display: flex; align-items: center; gap: 0.85rem; transition: all 0.25s; }
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-1px); }
.stat-icon-wrap { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon-wrap i { font-size: 1.15rem; }
.stat-body { display: flex; flex-direction: column; }
.stat-number { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -0.02em; }
.stat-label { font-size: 0.68rem; color: #94a3b8; font-weight: 500; margin-top: 0.1rem; }

/* ===== Org Tree ===== */
.org-tree { display: flex; flex-direction: column; gap: 1rem; }

/* ===== Dept Card ===== */
.dept-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden; transition: all 0.3s ease; }
.dept-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.07); }
.dept-accent { height: 4px; }
.dept-body { padding: 1.25rem 1.5rem; }
.dept-header { display: flex; align-items: center; gap: 0.85rem; }
.dept-icon-wrap { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
.dept-info { flex: 1; min-width: 0; }
.dept-name { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.01em; }
.dept-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.15rem; }
.dept-code { font-size: 0.62rem; font-weight: 700; color: #6366f1; background: #eef2ff; padding: 0.1rem 0.4rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
.dept-count { font-size: 0.68rem; color: #94a3b8; }
.dept-actions { display: flex; gap: 0.1rem; }

/* Dept Head */
.dept-head { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f8fafc; }
.head-name { font-size: 0.82rem; color: #334155; font-weight: 600; }
.head-role { font-size: 0.65rem; color: #94a3b8; margin-left: auto; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 4px; }

/* Budget */
.dept-budget { margin-top: 0.75rem; padding: 0.6rem 0.85rem; background: #f8fafc; border-radius: 10px; }
.budget-item { display: flex; justify-content: space-between; align-items: center; }
.budget-label { font-size: 0.7rem; color: #94a3b8; }
.budget-value { font-size: 0.82rem; font-weight: 700; color: #10b981; }

/* ===== Teams Section ===== */
.teams-section { margin-top: 1rem; }
.teams-header { margin-bottom: 0.5rem; }
.teams-title { font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.35rem; }
.teams-title i { font-size: 0.7rem; }
.teams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.5rem; }
.team-card { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 0.75rem 1rem; transition: all 0.2s; }
.team-card:hover { background: #f1f5f9; }
.team-header { display: flex; align-items: center; gap: 0.5rem; }
.team-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.team-name { font-size: 0.85rem; font-weight: 600; color: #334155; flex: 1; }
.team-count { font-size: 0.62rem; font-weight: 700; color: #94a3b8; background: white; padding: 0.1rem 0.4rem; border-radius: 4px; }
.team-actions { display: flex; gap: 0; opacity: 0; transition: opacity 0.2s; }
.team-card:hover .team-actions { opacity: 1; }
.member-avatars { display: flex; align-items: center; margin-top: 0.5rem; padding-left: 1rem; }
.member-more { font-size: 0.65rem; font-weight: 700; color: #94a3b8; margin-left: 0.35rem; }
.no-members { font-size: 0.7rem; color: #cbd5e1; margin-top: 0.35rem; padding-left: 1rem; font-style: italic; }

/* ===== Sub-departments ===== */
.sub-depts { margin-top: 1rem; }
.sub-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.sub-label i { font-size: 0.6rem; }
.sub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.35rem; }
.sub-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: #fafbfe; border: 1px solid #f1f5f9; border-left: 3px solid; border-radius: 8px; }
.sub-icon { font-size: 0.85rem; }
.sub-name { font-size: 0.82rem; font-weight: 600; color: #334155; }
.sub-code { font-size: 0.6rem; color: #94a3b8; display: block; }

/* ===== Empty State ===== */
.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 2px dashed #e2e8f0; }
.empty-illustration { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #eef2ff, #e0e7ff); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-illustration i { font-size: 2rem; color: #6366f1; }
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

/* ===== Dialog ===== */
.dialog-form { display: flex; flex-direction: column; gap: 0; }
.form-group { margin-bottom: 0.85rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.3rem; }
.required { color: #ef4444; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.w-full { width: 100%; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.5rem; }
.color-picker-wrap { display: flex; align-items: center; gap: 0.5rem; }
.color-input { width: 44px; height: 36px; padding: 2px; cursor: pointer; border-radius: 8px; border: 1px solid #e2e8f0; }
.color-hex { font-size: 0.72rem; color: #64748b; font-family: monospace; }
.snapshot-info { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 1rem; background: #eef2ff; border-radius: 10px; margin-bottom: 1rem; font-size: 0.78rem; color: #4338ca; }
.snapshot-info i { margin-top: 0.1rem; }

/* ===== Transitions ===== */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
