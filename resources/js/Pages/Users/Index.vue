<template>
  <div>
    <Head title="Users" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-users" />
        </div>
        <div>
          <h1 class="page-title">{{ t('common.users') }}</h1>
          <p class="page-subtitle">{{ users.length }} người dùng trong hệ thống</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip owners">
            <i class="pi pi-crown" />
            {{ users.filter(u => u.owner).length }} Owner
          </span>
          <span class="stat-chip members">
            <i class="pi pi-user" />
            {{ users.filter(u => !u.owner).length }} Member
          </span>
        </div>
        <Link href="/users/create">
          <Button :label="t('common.create') + ' User'" icon="pi pi-plus" />
        </Link>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search search-icon" />
        <input
          v-model="form.search"
          type="text"
          placeholder="Tìm theo tên, email..."
          class="search-input"
        />
        <button v-if="form.search" class="search-clear" @click="form.search = null">
          <i class="pi pi-times" />
        </button>
      </div>
      <div class="filter-group">
        <Select
          v-model="form.role"
          :options="roleOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('common.role')"
          showClear
          class="filter-select"
        />
        <Select
          v-model="form.trashed"
          :options="trashedOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Trạng thái"
          showClear
          class="filter-select"
        />
        <button v-if="hasFilters" class="reset-btn" @click="reset">
          <i class="pi pi-filter-slash" /> Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Users Grid -->
    <div v-if="users.length" class="users-grid">
      <Link
        v-for="user in users"
        :key="user.id"
        :href="`/users/${user.id}/edit`"
        class="user-card"
        :class="{ 'is-deleted': user.deleted_at }"
      >
        <!-- Avatar -->
        <div class="user-avatar" :class="user.owner ? 'avatar-owner' : 'avatar-member'">
          <img v-if="user.photo" :src="user.photo" class="avatar-img" />
          <span v-else class="avatar-initials">{{ getInitials(user.name) }}</span>
        </div>

        <!-- Info -->
        <div class="user-info">
          <div class="user-name-row">
            <h3 class="user-name">{{ user.name }}</h3>
            <span v-if="user.deleted_at" class="badge badge-deleted">
              <i class="pi pi-trash" /> Đã xóa
            </span>
          </div>
          <span class="user-email">{{ user.email }}</span>
        </div>

        <!-- Role Badge -->
        <div class="user-role">
          <span v-if="user.owner" class="role-badge role-owner">
            <i class="pi pi-crown" /> Owner
          </span>
          <span v-else class="role-badge role-user">
            <i class="pi pi-user" /> User
          </span>
        </div>

        <!-- Arrow -->
        <div class="user-arrow">
          <i class="pi pi-chevron-right" />
        </div>
      </Link>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="pi pi-users" />
      </div>
      <h3>Không tìm thấy người dùng.</h3>
      <p v-if="hasFilters">Thử thay đổi bộ lọc hoặc <button class="reset-link" @click="reset">xóa bộ lọc</button>.</p>
      <p v-else>Tạo người dùng đầu tiên để bắt đầu.</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, Select },
  layout: Layout,
  props: {
    filters: Object,
    users: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        role: this.filters.role,
        trashed: this.filters.trashed,
      },
      roleOptions: [
        { label: 'Owner', value: 'owner' },
        { label: 'User', value: 'user' },
      ],
      trashedOptions: [
        { label: 'Bao gồm đã xóa', value: 'with' },
        { label: 'Chỉ đã xóa', value: 'only' },
      ],
    }
  },
  computed: {
    hasFilters() {
      return this.form.search || this.form.role || this.form.trashed
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/users', pickBy(this.form), { preserveState: true })
      }, 300),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
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
.header-actions { display: flex; align-items: center; gap: 0.65rem; }

.stat-chips { display: flex; gap: 0.4rem; }
.stat-chip {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.65rem; border-radius: 20px;
  font-size: 0.68rem; font-weight: 600;
}
.stat-chip i { font-size: 0.6rem; }
.stat-chip.owners { background: #fffbeb; color: #d97706; }
.stat-chip.members { background: #eef2ff; color: #6366f1; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.85rem 1.15rem; background: white;
  border: 1.5px solid #e2e8f0; border-radius: 14px;
  margin-bottom: 1.25rem; flex-wrap: wrap;
}

.search-box {
  display: flex; align-items: center; flex: 1; min-width: 220px;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  overflow: hidden; transition: border-color 0.2s;
}
.search-box:focus-within {
  border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
}
.search-icon { padding: 0 0.65rem; color: #94a3b8; font-size: 0.78rem; }
.search-input {
  flex: 1; border: none; outline: none; padding: 0.55rem 0.5rem 0.55rem 0;
  font-size: 0.82rem; color: #1e293b; font-family: inherit;
}
.search-input::placeholder { color: #cbd5e1; }
.search-clear {
  background: none; border: none; cursor: pointer; padding: 0 0.55rem;
  color: #94a3b8; font-size: 0.72rem; transition: color 0.15s;
}
.search-clear:hover { color: #ef4444; }

.filter-group { display: flex; gap: 0.5rem; align-items: center; }
.filter-select { min-width: 140px; font-size: 0.82rem; }
.reset-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.45rem 0.75rem; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: white; font-size: 0.72rem; font-weight: 600; color: #64748b;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.reset-btn i { font-size: 0.65rem; }
.reset-btn:hover { border-color: #ef4444; color: #ef4444; }

/* ===== Users Grid ===== */
.users-grid {
  display: flex; flex-direction: column; gap: 0.5rem;
}

.user-card {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.85rem 1.15rem; background: white;
  border: 1.5px solid #f1f5f9; border-radius: 14px;
  text-decoration: none; color: inherit;
  transition: all 0.25s;
}
.user-card:hover {
  border-color: #6366f1; box-shadow: 0 4px 18px rgba(99,102,241,0.08);
  transform: translateX(2px);
}
.user-card.is-deleted { opacity: 0.55; }

/* Avatar */
.user-avatar {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 0.78rem; font-weight: 700;
  overflow: hidden;
}
.avatar-owner { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
.avatar-member { background: linear-gradient(135deg, #e0e7ff, #eef2ff); color: #6366f1; }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials { letter-spacing: 0.04em; }

/* Info */
.user-info { flex: 1; min-width: 0; }
.user-name-row { display: flex; align-items: center; gap: 0.4rem; }
.user-name { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; }
.user-email { font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 0.1rem; }

.badge-deleted {
  font-size: 0.52rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px;
  background: #fef2f2; color: #ef4444; display: flex; align-items: center; gap: 0.15rem;
  text-transform: uppercase;
}
.badge-deleted i { font-size: 0.45rem; }

/* Role */
.role-badge {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.65rem; font-weight: 600; padding: 0.25rem 0.55rem;
  border-radius: 8px;
}
.role-badge i { font-size: 0.55rem; }
.role-owner { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.role-user { background: #f1f5f9; color: #64748b; }

/* Arrow */
.user-arrow {
  color: #cbd5e1; font-size: 0.72rem; flex-shrink: 0;
  transition: all 0.2s;
}
.user-card:hover .user-arrow { color: #6366f1; transform: translateX(3px); }

/* ===== Empty State ===== */
.empty-state {
  text-align: center; padding: 3rem 2rem;
  background: white; border-radius: 16px; border: 2px dashed #e2e8f0;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem; font-size: 1.5rem; color: #6366f1;
}
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.reset-link {
  background: none; border: none; color: #6366f1;
  font-weight: 600; cursor: pointer; text-decoration: underline;
  font-size: inherit; font-family: inherit;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; justify-content: space-between; }
  .filter-bar { flex-direction: column; }
  .search-box { min-width: 100%; }
  .filter-group { width: 100%; flex-wrap: wrap; }
  .user-card { flex-wrap: wrap; }
  .user-role { margin-left: auto; }
}
</style>
