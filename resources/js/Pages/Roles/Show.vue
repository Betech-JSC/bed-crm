<template>
  <div>
    <Head :title="`Vai trò: ${role.name}`" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/roles" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="role-avatar-lg" :style="{ background: roleColor }">{{ role.name?.charAt(0)?.toUpperCase() }}</div>
        <div>
          <h1 class="page-title">{{ role.name }}</h1>
          <div class="header-meta">
            <code class="role-slug">{{ role.slug }}</code>
            <span v-if="role.is_system" class="badge badge-system"><i class="pi pi-cog" /> System</span>
            <span :class="role.is_active ? 'badge badge-active' : 'badge badge-inactive'">
              {{ role.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>
      </div>
      <div class="header-actions" v-if="!role.is_system">
        <Link :href="`/roles/${role.id}/edit`" class="btn-edit"><i class="pi pi-pencil" /> Chỉnh sửa</Link>
      </div>
    </div>

    <div class="content-grid">
      <!-- Left: Info + Users -->
      <div class="content-left">
        <!-- Info Card -->
        <div class="info-card">
          <div class="info-card-header">
            <h3><i class="pi pi-info-circle" /> Thông tin</h3>
          </div>
          <div class="info-card-body">
            <p v-if="role.description" class="role-desc">{{ role.description }}</p>
            <p v-else class="role-desc empty">Chưa có mô tả</p>
            <div class="info-stats">
              <div class="info-stat">
                <div class="info-stat-icon blue"><i class="pi pi-users" /></div>
                <div><span class="info-stat-value">{{ users.length }}</span><span class="info-stat-label">Users</span></div>
              </div>
              <div class="info-stat">
                <div class="info-stat-icon purple"><i class="pi pi-lock" /></div>
                <div><span class="info-stat-value">{{ permissions.length }}</span><span class="info-stat-label">Quyền</span></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Card -->
        <div class="info-card">
          <div class="info-card-header">
            <h3><i class="pi pi-users" /> Người dùng ({{ users.length }})</h3>
          </div>
          <div class="info-card-body">
            <div v-if="users.length" class="user-list">
              <Link
                v-for="user in users"
                :key="user.id"
                :href="`/users/${user.id}/edit`"
                class="user-item"
              >
                <div class="user-avatar">{{ user.name?.charAt(0)?.toUpperCase() }}</div>
                <div>
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-email">{{ user.email }}</span>
                </div>
              </Link>
            </div>
            <div v-else class="empty-section">
              <i class="pi pi-users" />
              <span>Chưa có user nào được gán vai trò này</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Permissions -->
      <div class="content-right">
        <div class="info-card">
          <div class="info-card-header">
            <h3><i class="pi pi-lock" /> Quyền truy cập ({{ permissions.length }})</h3>
            <Link v-if="!role.is_system" :href="`/roles/${role.id}/edit`" class="header-link">
              <i class="pi pi-pencil" /> Sửa quyền
            </Link>
          </div>
          <div class="info-card-body">
            <div v-if="groupedPermissions.length" class="perm-groups">
              <div v-for="group in groupedPermissions" :key="group.group" class="perm-group">
                <div class="perm-group-header">
                  <span class="perm-group-name">{{ group.group }}</span>
                  <span class="perm-group-count">{{ group.permissions.length }}</span>
                </div>
                <div class="perm-group-items">
                  <div v-for="perm in group.permissions" :key="perm.id" class="perm-item">
                    <i class="pi pi-check-circle perm-check" />
                    <span class="perm-name">{{ perm.name }}</span>
                    <code class="perm-slug">{{ perm.slug }}</code>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="empty-section">
              <i class="pi pi-lock" />
              <span>Chưa có quyền nào được gán</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    role: Object,
    permissions: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    availablePermissions: Array,
  },
  computed: {
    roleColor() {
      return this.role.is_system
        ? 'linear-gradient(135deg, #475569, #334155)'
        : 'linear-gradient(135deg, #6366f1, #8b5cf6)'
    },
    groupedPermissions() {
      const grouped = {}
      this.permissions.forEach(p => {
        const g = p.group || 'Other'
        if (!grouped[g]) grouped[g] = { group: g, permissions: [] }
        grouped[g].permissions.push(p)
      })
      return Object.values(grouped).sort((a, b) => a.group.localeCompare(b.group))
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.back-btn {
  width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  color: #64748b; text-decoration: none; transition: all 0.2s;
}
.back-btn:hover { background: #e2e8f0; color: #1e293b; }
.role-avatar-lg {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.3rem; font-weight: 800;
  box-shadow: 0 4px 14px rgba(99,102,241,0.25);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.header-meta { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.15rem; }
.role-slug { font-size: 0.62rem; color: #94a3b8; font-family: monospace; background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; }
.badge { font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px; display: flex; align-items: center; gap: 0.2rem; }
.badge-system { background: #fef3c7; color: #92400e; }
.badge-active { background: #ecfdf5; color: #059669; }
.badge-inactive { background: #f1f5f9; color: #94a3b8; }
.btn-edit {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; font-size: 0.82rem; font-weight: 600;
  text-decoration: none; transition: all 0.2s;
}
.btn-edit:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* Content Grid */
.content-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.25rem; }
.content-left { display: flex; flex-direction: column; gap: 1.25rem; }

/* Info Cards */
.info-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.info-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.info-card-header h3 { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.35rem; }
.info-card-header h3 i { color: #6366f1; font-size: 0.82rem; }
.header-link { font-size: 0.72rem; font-weight: 600; color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.25rem; }
.header-link:hover { color: #4f46e5; }
.info-card-body { padding: 1.25rem; }

.role-desc { font-size: 0.82rem; color: #475569; line-height: 1.6; margin: 0 0 1rem; }
.role-desc.empty { color: #94a3b8; font-style: italic; }

.info-stats { display: flex; gap: 1.25rem; }
.info-stat { display: flex; align-items: center; gap: 0.5rem; }
.info-stat-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.info-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
.info-stat-icon.purple { background: #eef2ff; color: #6366f1; }
.info-stat-value { display: block; font-size: 1.2rem; font-weight: 800; color: #1e293b; }
.info-stat-label { font-size: 0.65rem; color: #94a3b8; }

/* User List */
.user-list { display: flex; flex-direction: column; gap: 0.3rem; }
.user-item {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.5rem 0.65rem; border-radius: 10px;
  text-decoration: none; transition: background 0.15s;
}
.user-item:hover { background: #f8fafc; }
.user-avatar {
  width: 32px; height: 32px; border-radius: 10px;
  background: linear-gradient(135deg, #ef6820, #e04f0f);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.7rem; font-weight: 700; flex-shrink: 0;
}
.user-name { display: block; font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.user-email { font-size: 0.68rem; color: #94a3b8; }

/* Permission Groups */
.perm-groups { display: flex; flex-direction: column; gap: 0.75rem; }
.perm-group { border: 1px solid #f1f5f9; border-radius: 10px; overflow: hidden; }
.perm-group-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.55rem 0.85rem; background: #fafbfc; border-bottom: 1px solid #f1f5f9;
}
.perm-group-name { font-size: 0.78rem; font-weight: 700; color: #475569; }
.perm-group-count { font-size: 0.58rem; font-weight: 700; background: #eef2ff; color: #6366f1; padding: 0.1rem 0.4rem; border-radius: 8px; }
.perm-group-items { padding: 0.25rem 0; }
.perm-item { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem; }
.perm-check { color: #10b981; font-size: 0.72rem; }
.perm-name { font-size: 0.78rem; color: #334155; }
.perm-slug { font-size: 0.55rem; color: #94a3b8; font-family: monospace; margin-left: auto; }

.empty-section {
  display: flex; flex-direction: column; align-items: center; gap: 0.4rem;
  padding: 2rem 1rem; color: #94a3b8; font-size: 0.78rem;
}
.empty-section i { font-size: 1.5rem; opacity: 0.4; }

@media (max-width: 768px) {
  .content-grid { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>
