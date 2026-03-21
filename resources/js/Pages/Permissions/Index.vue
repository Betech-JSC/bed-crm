<template>
  <div>
    <Head title="Permissions" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <i class="pi pi-lock" />
        </div>
        <div>
          <h1 class="page-title">Phân quyền hệ thống</h1>
          <p class="page-subtitle">{{ permissions.length }} quyền · {{ groupList.length }} nhóm</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="pi pi-search" />
          <input v-model="searchQuery" placeholder="Tìm quyền..." class="search-input" />
        </div>
        <Link href="/permissions/create" class="btn-create">
          <i class="pi pi-plus" /> Tạo quyền
        </Link>
      </div>
    </div>

    <!-- View Toggle -->
    <div class="view-controls">
      <div class="view-toggle">
        <button :class="{ active: viewMode === 'group' }" @click="viewMode = 'group'">
          <i class="pi pi-th-large" /> Theo nhóm
        </button>
        <button :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
          <i class="pi pi-list" /> Danh sách
        </button>
      </div>
      <div class="filter-chips">
        <button
          v-for="g in groupList"
          :key="g"
          class="filter-chip"
          :class="{ active: activeGroup === g }"
          @click="activeGroup = activeGroup === g ? null : g"
        >
          {{ g }}
          <span class="chip-count">{{ getGroupCount(g) }}</span>
        </button>
      </div>
    </div>

    <!-- Group View -->
    <div v-if="viewMode === 'group'" class="permissions-grid">
      <div
        v-for="group in filteredGroups"
        :key="group.name"
        class="perm-group-card"
      >
        <div class="group-header">
          <div class="group-icon" :style="{ background: groupColor(group.name) }">
            <i :class="groupIcon(group.name)" />
          </div>
          <div>
            <h3 class="group-name">{{ group.name }}</h3>
            <span class="group-count">{{ group.permissions.length }} quyền</span>
          </div>
        </div>

        <div class="group-body">
          <div
            v-for="perm in group.permissions"
            :key="perm.id"
            class="perm-item"
            :class="{ inactive: !perm.is_active }"
          >
            <div class="perm-item-left">
              <div class="perm-dot" :class="perm.is_active ? 'active' : 'inactive'" />
              <div>
                <span class="perm-name">{{ perm.name }}</span>
                <code class="perm-slug">{{ perm.slug }}</code>
              </div>
            </div>
            <div class="perm-item-right">
              <span v-if="perm.roles_count" class="perm-roles">
                <i class="pi pi-shield" /> {{ perm.roles_count }}
              </span>
              <Link :href="`/permissions/${perm.id}/edit`" class="perm-edit">
                <i class="pi pi-pencil" />
              </Link>
              <button class="perm-delete" @click="confirmDelete(perm)">
                <i class="pi pi-trash" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="perm-table-wrapper">
      <table class="perm-table">
        <thead>
          <tr>
            <th>Tên</th>
            <th>Slug</th>
            <th>Nhóm</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="perm in filteredPermissions" :key="perm.id" :class="{ inactive: !perm.is_active }">
            <td>
              <Link :href="`/permissions/${perm.id}/edit`" class="perm-link">{{ perm.name }}</Link>
            </td>
            <td><code class="perm-slug">{{ perm.slug }}</code></td>
            <td>
              <span class="group-badge" :style="{ background: groupColor(perm.group) + '15', color: groupColorText(perm.group) }">
                {{ perm.group || 'Other' }}
              </span>
            </td>
            <td>
              <span class="perm-roles"><i class="pi pi-shield" /> {{ perm.roles_count || 0 }}</span>
            </td>
            <td>
              <span class="status-dot" :class="perm.is_active ? 'active' : 'inactive'">
                {{ perm.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="table-actions">
                <Link :href="`/permissions/${perm.id}/edit`" class="perm-edit"><i class="pi pi-pencil" /></Link>
                <button class="perm-delete" @click="confirmDelete(perm)"><i class="pi pi-trash" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!filteredPermissions.length" class="empty-table">
        Không tìm thấy quyền nào
      </div>
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
    permissions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
  },
  data() {
    return {
      viewMode: 'group',
      searchQuery: '',
      activeGroup: null,
    }
  },
  computed: {
    groupList() {
      const groups = new Set()
      this.permissions.forEach(p => groups.add(p.group || 'Other'))
      return Array.from(groups).sort()
    },
    groupedPermissions() {
      const grouped = {}
      this.permissions.forEach(p => {
        const g = p.group || 'Other'
        if (!grouped[g]) grouped[g] = { name: g, permissions: [] }
        grouped[g].permissions.push(p)
      })
      return Object.values(grouped).sort((a, b) => a.name.localeCompare(b.name))
    },
    filteredGroups() {
      let groups = this.groupedPermissions
      if (this.activeGroup) groups = groups.filter(g => g.name === this.activeGroup)
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        groups = groups.map(g => ({
          ...g,
          permissions: g.permissions.filter(p =>
            p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)
          ),
        })).filter(g => g.permissions.length > 0)
      }
      return groups
    },
    filteredPermissions() {
      let perms = this.permissions
      if (this.activeGroup) perms = perms.filter(p => (p.group || 'Other') === this.activeGroup)
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        perms = perms.filter(p => p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q))
      }
      return perms
    },
  },
  methods: {
    getGroupCount(g) {
      return this.permissions.filter(p => (p.group || 'Other') === g).length
    },
    groupColor(name) {
      const colors = { Leads: '#6366f1', Deals: '#10b981', Contacts: '#3b82f6', Users: '#f59e0b', Roles: '#ec4899', Settings: '#8b5cf6', Reports: '#14b8a6', Projects: '#f97316', Marketing: '#ef4444' }
      return colors[name] || '#64748b'
    },
    groupColorText(name) {
      return this.groupColor(name)
    },
    groupIcon(name) {
      const icons = { Leads: 'pi pi-flag', Deals: 'pi pi-dollar', Contacts: 'pi pi-address-book', Users: 'pi pi-users', Roles: 'pi pi-shield', Settings: 'pi pi-cog', Reports: 'pi pi-chart-bar', Projects: 'pi pi-briefcase', Marketing: 'pi pi-megaphone' }
      return icons[name] || 'pi pi-lock'
    },
    confirmDelete(perm) {
      if (confirm(`Xóa quyền "${perm.name}"?`)) {
        router.delete(`/permissions/${perm.id}`)
      }
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(245,158,11,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.header-actions { display: flex; align-items: center; gap: 0.75rem; }
.search-box {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; background: white; transition: border-color 0.2s;
}
.search-box:focus-within { border-color: #6366f1; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; width: 180px; color: #334155; background: transparent; }
.btn-create {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.55rem 1.1rem; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; font-size: 0.82rem; font-weight: 600;
  text-decoration: none; transition: all 0.2s;
}
.btn-create:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* View Controls */
.view-controls { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.view-toggle { display: flex; background: #f1f5f9; border-radius: 10px; padding: 3px; }
.view-toggle button {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.75rem; border: none; border-radius: 8px;
  font-size: 0.72rem; font-weight: 600; color: #64748b;
  background: transparent; cursor: pointer; transition: all 0.2s;
}
.view-toggle button.active { background: white; color: #1e293b; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.filter-chips { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.filter-chip {
  display: flex; align-items: center; gap: 0.25rem;
  padding: 0.3rem 0.6rem; border-radius: 20px;
  font-size: 0.65rem; font-weight: 600; border: 1.5px solid #e2e8f0;
  background: white; color: #64748b; cursor: pointer; transition: all 0.2s;
}
.filter-chip:hover { border-color: #6366f1; color: #6366f1; }
.filter-chip.active { background: #6366f1; color: white; border-color: #6366f1; }
.chip-count { font-size: 0.55rem; background: rgba(0,0,0,0.06); padding: 0.1rem 0.3rem; border-radius: 8px; }
.filter-chip.active .chip-count { background: rgba(255,255,255,0.2); }

/* Group View */
.permissions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1rem; }
.perm-group-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.2s; }
.perm-group-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.group-header {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.group-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.9rem;
}
.group-name { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; }
.group-count { font-size: 0.65rem; color: #94a3b8; }
.group-body { padding: 0.5rem 0; }

.perm-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.5rem 1.25rem; transition: background 0.15s;
}
.perm-item:hover { background: #f8fafc; }
.perm-item.inactive { opacity: 0.5; }
.perm-item-left { display: flex; align-items: center; gap: 0.5rem; }
.perm-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.perm-dot.active { background: #10b981; }
.perm-dot.inactive { background: #cbd5e1; }
.perm-name { font-size: 0.8rem; font-weight: 500; color: #334155; display: block; }
.perm-slug { font-size: 0.6rem; color: #94a3b8; font-family: monospace; background: #f1f5f9; padding: 0.05rem 0.3rem; border-radius: 3px; }
.perm-item-right { display: flex; align-items: center; gap: 0.3rem; opacity: 0; transition: opacity 0.15s; }
.perm-item:hover .perm-item-right { opacity: 1; }
.perm-roles { font-size: 0.62rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.perm-edit, .perm-delete {
  width: 26px; height: 26px; border-radius: 6px; border: none;
  background: transparent; color: #94a3b8; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.68rem; text-decoration: none; transition: all 0.15s;
}
.perm-edit:hover { color: #6366f1; background: #eef2ff; }
.perm-delete:hover { color: #ef4444; background: #fef2f2; }

/* Table View */
.perm-table-wrapper { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.perm-table { width: 100%; border-collapse: collapse; }
.perm-table th {
  text-align: left; padding: 0.75rem 1rem; font-size: 0.68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;
  border-bottom: 2px solid #e2e8f0; background: #fafbfc;
}
.perm-table td { padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; }
.perm-table tr:hover { background: #f8fafc; }
.perm-table tr.inactive { opacity: 0.5; }
.perm-link { color: #6366f1; font-weight: 600; text-decoration: none; }
.perm-link:hover { color: #4f46e5; }
.group-badge {
  font-size: 0.62rem; font-weight: 700; padding: 0.2rem 0.5rem;
  border-radius: 6px; text-transform: uppercase; letter-spacing: 0.03em;
}
.status-dot { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; }
.status-dot::before { content: ''; width: 7px; height: 7px; border-radius: 50%; }
.status-dot.active { color: #10b981; }
.status-dot.active::before { background: #10b981; }
.status-dot.inactive { color: #94a3b8; }
.status-dot.inactive::before { background: #cbd5e1; }
.table-actions { display: flex; gap: 0.2rem; }
.empty-table { padding: 3rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; }
  .search-input { width: 100%; }
  .permissions-grid { grid-template-columns: 1fr; }
  .view-controls { flex-direction: column; }
}
</style>
