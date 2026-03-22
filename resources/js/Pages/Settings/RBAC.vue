<template>
  <div>
    <Head :title="isVi ? 'Phân quyền' : 'Roles & Permissions'" />

    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-shield" />
        </div>
        <div>
          <h1 class="page-title">{{ isVi ? 'Quản lý phân quyền' : 'Roles & Permissions' }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Cấu hình vai trò và quyền truy cập cho người dùng' : 'Configure roles and access permissions for users' }}</p>
        </div>
      </div>
      <div class="header-actions">
        <Button :label="isVi ? 'Khởi tạo mặc định' : 'Seed Defaults'" icon="pi pi-refresh" severity="secondary" outlined @click="seedDefaults" />
        <Button :label="isVi ? 'Tạo vai trò' : 'Create Role'" icon="pi pi-plus" @click="showCreateRole = true" />
      </div>
    </div>

    <!-- Tabs: Roles | Users -->
    <TabView v-model:activeIndex="activeTab">
      <!-- ─── Tab 1: Roles ─── -->
      <TabPanel :header="isVi ? 'Vai trò' : 'Roles'">
        <div class="roles-grid">
          <div v-for="role in roles" :key="role.id" class="role-card" :class="{ 'system-role': role.is_system, 'inactive-role': !role.is_active }">
            <div class="role-header">
              <div class="role-info">
                <div class="role-name-row">
                  <h3 class="role-name">{{ role.name }}</h3>
                  <span v-if="role.is_system" class="system-badge">{{ isVi ? 'Hệ thống' : 'System' }}</span>
                  <span v-if="!role.is_active" class="inactive-badge">{{ isVi ? 'Vô hiệu' : 'Inactive' }}</span>
                </div>
                <p class="role-desc">{{ role.description }}</p>
                <div class="role-stats">
                  <span class="role-stat"><i class="pi pi-users" /> {{ role.users_count }} {{ isVi ? 'người dùng' : 'users' }}</span>
                  <span class="role-stat"><i class="pi pi-key" /> {{ role.permissions_count }} {{ isVi ? 'quyền' : 'permissions' }}</span>
                </div>
              </div>
              <div class="role-actions">
                <Button icon="pi pi-pencil" text rounded size="small" @click="editRolePerms(role)" v-tooltip="isVi ? 'Sửa quyền' : 'Edit Permissions'" />
                <Button v-if="!role.is_system" icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteRole(role)" />
              </div>
            </div>

            <!-- Permission pills -->
            <div class="perm-pills">
              <span v-for="perm in role.permissions.slice(0, 8)" :key="perm" class="perm-pill">{{ perm }}</span>
              <span v-if="role.permissions.length > 8" class="perm-pill perm-more">+{{ role.permissions.length - 8 }}</span>
            </div>
          </div>
        </div>
      </TabPanel>

      <!-- ─── Tab 2: Users ─── -->
      <TabPanel :header="isVi ? 'Người dùng' : 'Users'">
        <DataTable :value="users" stripedRows size="small" class="users-table">
          <Column :header="isVi ? 'Tên' : 'Name'" field="name">
            <template #body="{ data }">
              <div class="user-cell">
                <span class="user-name">{{ data.name }}</span>
                <span class="user-email">{{ data.email }}</span>
              </div>
            </template>
          </Column>
          <Column :header="isVi ? 'Vai trò' : 'Roles'">
            <template #body="{ data }">
              <div v-if="data.owner" class="owner-badge">Owner</div>
              <div v-else class="user-roles">
                <span v-for="r in data.role_names" :key="r" class="user-role-pill">{{ r }}</span>
                <span v-if="!data.role_names.length" class="no-role">{{ isVi ? 'Chưa có vai trò' : 'No roles' }}</span>
              </div>
            </template>
          </Column>
          <Column :header="isVi ? 'Thao tác' : 'Actions'" style="width: 120px">
            <template #body="{ data }">
              <Button v-if="!data.owner" :label="isVi ? 'Phân quyền' : 'Assign'" icon="pi pi-user-edit" text size="small" @click="openAssignDialog(data)" />
            </template>
          </Column>
        </DataTable>
      </TabPanel>
    </TabView>

    <!-- ─── Create Role Dialog ─── -->
    <Dialog v-model:visible="showCreateRole" :header="isVi ? 'Tạo vai trò mới' : 'Create New Role'" modal :style="{ width: '440px' }">
      <div class="form-group">
        <label>{{ isVi ? 'Tên vai trò' : 'Role Name' }}</label>
        <InputText v-model="newRole.name" :placeholder="isVi ? 'VD: Trưởng phòng kinh doanh' : 'e.g. Sales Director'" class="w-full" />
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Mô tả' : 'Description' }}</label>
        <Textarea v-model="newRole.description" rows="2" class="w-full" />
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showCreateRole = false" />
        <Button :label="isVi ? 'Tạo' : 'Create'" icon="pi pi-check" @click="createRole" :loading="creating" />
      </template>
    </Dialog>

    <!-- ─── Edit Permissions Dialog ─── -->
    <Dialog v-model:visible="showEditPerms" :header="editingRole ? (isVi ? `Quyền: ${editingRole.name}` : `Permissions: ${editingRole.name}`) : ''" modal :style="{ width: '700px' }">
      <div v-for="group in permission_groups" :key="group.group" class="perm-group">
        <div class="perm-group-header">
          <Checkbox :modelValue="isGroupAllSelected(group)" @update:modelValue="toggleGroup(group, $event)" :binary="true" />
          <span class="perm-group-label">{{ group.label }}</span>
          <span class="perm-group-count">{{ group.permissions.length }}</span>
        </div>
        <div class="perm-group-items">
          <div v-for="p in group.permissions" :key="p.slug" class="perm-item">
            <Checkbox v-model="selectedPerms" :value="p.slug" />
            <span class="perm-item-label">{{ p.name }}</span>
            <span class="perm-item-slug">{{ p.slug }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showEditPerms = false" />
        <Button :label="isVi ? 'Lưu' : 'Save'" icon="pi pi-check" @click="savePermissions" :loading="savingPerms" />
      </template>
    </Dialog>

    <!-- ─── Assign Roles Dialog ─── -->
    <Dialog v-model:visible="showAssign" :header="assigningUser ? (isVi ? `Phân quyền: ${assigningUser.name}` : `Assign Roles: ${assigningUser.name}`) : ''" modal :style="{ width: '440px' }">
      <div class="assign-roles">
        <div v-for="role in roles" :key="role.id" class="assign-role-row">
          <Checkbox v-model="selectedUserRoles" :value="role.slug" />
          <div class="assign-role-info">
            <span class="assign-role-name">{{ role.name }}</span>
            <span class="assign-role-desc">{{ role.description }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showAssign = false" />
        <Button :label="isVi ? 'Lưu' : 'Save'" icon="pi pi-check" @click="saveUserRoles" :loading="savingRoles" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, DataTable, Column, Dialog, InputText, Textarea, Checkbox, TabView, TabPanel },
  layout: Layout,
  props: { roles: Array, permission_groups: Array, users: Array },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      activeTab: 0,
      showCreateRole: false,
      showEditPerms: false,
      showAssign: false,
      newRole: { name: '', description: '' },
      creating: false,
      editingRole: null,
      selectedPerms: [],
      savingPerms: false,
      assigningUser: null,
      selectedUserRoles: [],
      savingRoles: false,
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
  },
  methods: {
    async createRole() {
      this.creating = true
      await axios.post('/settings/roles', this.newRole)
      this.creating = false
      this.showCreateRole = false
      this.newRole = { name: '', description: '' }
      router.reload()
    },
    async deleteRole(role) {
      if (!confirm(this.isVi ? `Xoá vai trò "${role.name}"?` : `Delete role "${role.name}"?`)) return
      await axios.delete(`/settings/roles/${role.id}`)
      router.reload()
    },
    editRolePerms(role) {
      this.editingRole = role
      this.selectedPerms = [...role.permissions]
      this.showEditPerms = true
    },
    isGroupAllSelected(group) {
      return group.permissions.every(p => this.selectedPerms.includes(p.slug))
    },
    toggleGroup(group, val) {
      const slugs = group.permissions.map(p => p.slug)
      if (val) {
        this.selectedPerms = [...new Set([...this.selectedPerms, ...slugs])]
      } else {
        this.selectedPerms = this.selectedPerms.filter(s => !slugs.includes(s))
      }
    },
    async savePermissions() {
      this.savingPerms = true
      await axios.post(`/settings/roles/${this.editingRole.id}/permissions`, { permissions: this.selectedPerms })
      this.savingPerms = false
      this.showEditPerms = false
      router.reload()
    },
    openAssignDialog(user) {
      this.assigningUser = user
      this.selectedUserRoles = [...user.roles]
      this.showAssign = true
    },
    async saveUserRoles() {
      this.savingRoles = true
      await axios.post(`/settings/users/${this.assigningUser.id}/roles`, { roles: this.selectedUserRoles })
      this.savingRoles = false
      this.showAssign = false
      router.reload()
    },
    async seedDefaults() {
      if (!confirm(this.isVi ? 'Khởi tạo vai trò và quyền mặc định?' : 'Seed default roles and permissions?')) return
      await axios.post('/settings/roles/seed')
      router.reload()
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* ===== Roles Grid ===== */
.roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 0.85rem; }
.role-card {
  background: white; border: 1.5px solid #f1f5f9; border-radius: 14px;
  padding: 1.15rem; transition: all 0.25s;
}
.role-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04); transform: translateY(-1px); }
.role-card.system-role { border-left: 3px solid #6366f1; }
.role-card.inactive-role { opacity: 0.55; }
.role-header { display: flex; justify-content: space-between; align-items: flex-start; }
.role-name-row { display: flex; align-items: center; gap: 0.45rem; }
.role-name { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; }
.system-badge {
  font-size: 0.55rem; font-weight: 700; padding: 0.12rem 0.4rem;
  border-radius: 5px; background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: #4f46e5; text-transform: uppercase; letter-spacing: 0.04em;
}
.inactive-badge {
  font-size: 0.55rem; font-weight: 700; padding: 0.12rem 0.4rem;
  border-radius: 5px; background: #fef2f2; color: #ef4444;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.role-desc { font-size: 0.72rem; color: #64748b; margin: 0.25rem 0 0.5rem; }
.role-stats { display: flex; gap: 0.85rem; }
.role-stat {
  font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem;
}
.role-stat i { font-size: 0.6rem; }
.role-actions { display: flex; gap: 0.125rem; }

/* ===== Permission pills ===== */
.perm-pills { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.65rem; padding-top: 0.55rem; border-top: 1px solid #f8fafc; }
.perm-pill {
  font-size: 0.55rem; padding: 0.12rem 0.45rem; border-radius: 5px;
  background: #f8fafc; color: #64748b; border: 1px solid #f1f5f9;
  font-family: monospace; transition: all 0.15s;
}
.perm-pill:hover { background: #eef2ff; color: #6366f1; border-color: #c7d2fe; }
.perm-more { background: #eef2ff; color: #6366f1; border-color: #c7d2fe; font-weight: 600; }

/* ===== Users table ===== */
.user-cell { display: flex; flex-direction: column; }
.user-name { font-weight: 600; font-size: 0.82rem; color: #1e293b; }
.user-email { font-size: 0.7rem; color: #94a3b8; }
.owner-badge {
  font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.5rem;
  border-radius: 5px; background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white; display: inline-block;
}
.user-roles { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.user-role-pill {
  font-size: 0.65rem; padding: 0.12rem 0.45rem; border-radius: 5px;
  background: #eef2ff; color: #6366f1; font-weight: 500;
}
.no-role { font-size: 0.7rem; color: #cbd5e1; font-style: italic; }

/* ===== Forms ===== */
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.3rem; }
.w-full { width: 100%; }

/* ===== Permission editor ===== */
.perm-group { margin-bottom: 0.75rem; border: 1.5px solid #f1f5f9; border-radius: 10px; overflow: hidden; }
.perm-group-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.55rem 0.85rem; background: #fafbfc;
  border-bottom: 1px solid #f1f5f9;
}
.perm-group-label { font-size: 0.82rem; font-weight: 600; color: #1e293b; flex: 1; }
.perm-group-count {
  font-size: 0.58rem; color: #94a3b8; background: #f1f5f9;
  padding: 0.08rem 0.4rem; border-radius: 8px; font-weight: 600;
}
.perm-group-items { padding: 0.4rem 0.85rem; }
.perm-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0; transition: background 0.1s; }
.perm-item:hover { background: #fafbfc; margin: 0 -0.35rem; padding-left: 0.35rem; padding-right: 0.35rem; border-radius: 4px; }
.perm-item-label { font-size: 0.78rem; color: #334155; flex: 1; }
.perm-item-slug { font-size: 0.6rem; color: #94a3b8; font-family: monospace; }

/* ===== Assign dialog ===== */
.assign-roles { display: flex; flex-direction: column; gap: 0.4rem; }
.assign-role-row {
  display: flex; align-items: flex-start; gap: 0.55rem;
  padding: 0.5rem 0; border-bottom: 1px solid #f8fafc;
  transition: background 0.1s;
}
.assign-role-row:hover { background: #fafbfc; }
.assign-role-info { display: flex; flex-direction: column; }
.assign-role-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.assign-role-desc { font-size: 0.68rem; color: #94a3b8; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .roles-grid { grid-template-columns: 1fr; }
}
</style>
