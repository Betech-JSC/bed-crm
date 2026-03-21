<template>
  <div>
    <Head title="Roles" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <i class="pi pi-shield" />
        </div>
        <div>
          <h1 class="page-title">Vai trò hệ thống</h1>
          <p class="page-subtitle">Quản lý vai trò và phân quyền truy cập</p>
        </div>
      </div>
      <Link href="/roles/create" class="btn-create">
        <i class="pi pi-plus" /> Tạo vai trò
      </Link>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon purple"><i class="pi pi-shield" /></div>
        <div><span class="stat-value">{{ roles.length }}</span><span class="stat-label">Tổng vai trò</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="pi pi-check-circle" /></div>
        <div><span class="stat-value">{{ activeRoles }}</span><span class="stat-label">Đang hoạt động</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="pi pi-users" /></div>
        <div><span class="stat-value">{{ totalUsers }}</span><span class="stat-label">Tổng users</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="pi pi-lock" /></div>
        <div><span class="stat-value">{{ totalPermissions }}</span><span class="stat-label">Quyền</span></div>
      </div>
    </div>

    <!-- Roles Grid -->
    <div class="roles-grid">
      <div
        v-for="role in roles"
        :key="role.id"
        class="role-card"
        :class="{ 'is-system': role.is_system, 'is-inactive': !role.is_active }"
      >
        <div class="role-card-header">
          <div class="role-identity">
            <div class="role-avatar" :style="{ background: roleColor(role) }">
              {{ roleInitial(role) }}
            </div>
            <div>
              <h3 class="role-name">{{ role.name }}</h3>
              <code class="role-slug">{{ role.slug }}</code>
            </div>
          </div>
          <div class="role-badges">
            <span v-if="role.is_system" class="badge badge-system"><i class="pi pi-cog" /> System</span>
            <span v-if="role.is_active" class="badge badge-active">Active</span>
            <span v-else class="badge badge-inactive">Inactive</span>
          </div>
        </div>

        <div class="role-card-body">
          <p v-if="role.description" class="role-desc">{{ role.description }}</p>

          <div class="role-stats">
            <div class="role-stat">
              <i class="pi pi-users" />
              <span class="role-stat-value">{{ role.users_count || 0 }}</span>
              <span class="role-stat-label">Users</span>
            </div>
            <div class="role-stat">
              <i class="pi pi-lock" />
              <span class="role-stat-value">{{ role.permissions_count || 0 }}</span>
              <span class="role-stat-label">Quyền</span>
            </div>
          </div>
        </div>

        <div class="role-card-footer">
          <Link :href="`/roles/${role.id}`" class="btn-action">
            <i class="pi pi-eye" /> Chi tiết
          </Link>
          <div class="footer-actions">
            <Link :href="`/roles/${role.id}/edit`" class="btn-icon-action" :class="{ disabled: role.is_system }" title="Sửa">
              <i class="pi pi-pencil" />
            </Link>
            <button class="btn-icon-action danger" :disabled="role.is_system" @click="confirmDelete(role)" title="Xóa">
              <i class="pi pi-trash" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!roles.length" class="empty-state">
      <div class="empty-icon"><i class="pi pi-shield" /></div>
      <h3>Chưa có vai trò nào</h3>
      <p>Tạo vai trò đầu tiên để phân quyền truy cập cho nhân viên</p>
      <Link href="/roles/create" class="btn-create">
        <i class="pi pi-plus" /> Tạo vai trò
      </Link>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    roles: { type: Array, default: () => [] },
  },
  computed: {
    activeRoles() { return this.roles.filter(r => r.is_active).length },
    totalUsers() { return this.roles.reduce((s, r) => s + (r.users_count || 0), 0) },
    totalPermissions() {
      const max = Math.max(...this.roles.map(r => r.permissions_count || 0), 0)
      return max
    },
  },
  methods: {
    roleColor(role) {
      const colors = [
        'linear-gradient(135deg, #6366f1, #8b5cf6)',
        'linear-gradient(135deg, #10b981, #059669)',
        'linear-gradient(135deg, #f59e0b, #d97706)',
        'linear-gradient(135deg, #ef4444, #dc2626)',
        'linear-gradient(135deg, #3b82f6, #2563eb)',
        'linear-gradient(135deg, #ec4899, #db2777)',
      ]
      return role.is_system ? 'linear-gradient(135deg, #475569, #334155)' : colors[role.id % colors.length]
    },
    roleInitial(role) {
      return role.name?.charAt(0)?.toUpperCase() || 'R'
    },
    confirmDelete(role) {
      if (role.is_system) return
      if (confirm(`Xóa vai trò "${role.name}"? Action này không thể hoàn tác.`)) {
        router.delete(`/roles/${role.id}`)
      }
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }

.btn-create {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.55rem 1.1rem; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; font-size: 0.82rem; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer;
  transition: all 0.2s;
}
.btn-create:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat-card {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1rem 1.25rem; background: white; border-radius: 14px;
  border: 1px solid #e2e8f0; transition: all 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.stat-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.stat-icon.purple { background: #eef2ff; color: #6366f1; }
.stat-icon.green { background: #ecfdf5; color: #10b981; }
.stat-icon.blue { background: #eff6ff; color: #3b82f6; }
.stat-icon.orange { background: #fff7ed; color: #f59e0b; }
.stat-value { display: block; font-size: 1.35rem; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 0.7rem; color: #94a3b8; }

/* Roles Grid */
.roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1rem; }

.role-card {
  background: white; border-radius: 16px; border: 2px solid #e2e8f0;
  overflow: hidden; transition: all 0.3s;
}
.role-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transform: translateY(-2px); }
.role-card.is-system { border-color: #fde68a; }
.role-card.is-system:hover { border-color: #f59e0b; }
.role-card.is-inactive { opacity: 0.6; }

.role-card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; }
.role-identity { display: flex; align-items: center; gap: 0.65rem; }
.role-avatar {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem; font-weight: 800;
}
.role-name { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; }
.role-slug { font-size: 0.62rem; color: #94a3b8; font-family: monospace; background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; }

.role-badges { display: flex; gap: 0.3rem; }
.badge {
  font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px;
  display: flex; align-items: center; gap: 0.2rem; text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-system { background: #fef3c7; color: #92400e; }
.badge-active { background: #ecfdf5; color: #059669; }
.badge-inactive { background: #f1f5f9; color: #94a3b8; }

.role-card-body { padding: 0 1.25rem 1rem; }
.role-desc { font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem; line-height: 1.5; }
.role-stats { display: flex; gap: 1.5rem; }
.role-stat { display: flex; align-items: center; gap: 0.3rem; }
.role-stat i { font-size: 0.78rem; color: #94a3b8; }
.role-stat-value { font-size: 0.92rem; font-weight: 700; color: #1e293b; }
.role-stat-label { font-size: 0.68rem; color: #94a3b8; }

.role-card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.65rem 1.25rem; background: #fafbfc; border-top: 1px solid #f1f5f9;
}
.btn-action {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.72rem; font-weight: 600; color: #6366f1;
  text-decoration: none; transition: all 0.2s;
}
.btn-action:hover { color: #4f46e5; }
.footer-actions { display: flex; gap: 0.3rem; }
.btn-icon-action {
  width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: white; color: #64748b; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; text-decoration: none; transition: all 0.2s;
}
.btn-icon-action:hover { border-color: #6366f1; color: #6366f1; }
.btn-icon-action.danger:hover { border-color: #ef4444; color: #ef4444; }
.btn-icon-action.disabled, .btn-icon-action:disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }

.empty-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 4rem 2rem; text-align: center;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 18px;
  background: #eef2ff; color: #6366f1;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; margin-bottom: 1rem;
}
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .roles-grid { grid-template-columns: 1fr; }
}
</style>
