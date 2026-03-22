<template>
  <div>
    <Head title="Thùng rác" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper trash-icon">
          <i class="pi pi-trash" />
        </div>
        <div>
          <h1 class="page-title">Thùng rác</h1>
          <p class="page-subtitle">{{ totalTrashed }} mục đã xóa — có thể khôi phục hoặc xóa vĩnh viễn</p>
        </div>
      </div>
      <div class="header-actions">
        <Link href="/system-history">
          <Button label="Lịch sử" icon="pi pi-history" severity="secondary" outlined />
        </Link>
      </div>
    </div>

    <!-- Module Filter Chips -->
    <div v-if="moduleCounts.length" class="module-chips">
      <button
        class="module-chip"
        :class="{ active: form.module === 'all' || !form.module }"
        @click="form.module = null"
      >
        <i class="pi pi-th-large" />
        Tất cả
        <span class="chip-count">{{ totalTrashed }}</span>
      </button>
      <button
        v-for="mod in moduleCounts"
        :key="mod.key"
        class="module-chip"
        :class="{ active: form.module === mod.key }"
        @click="form.module = mod.key"
      >
        <i :class="mod.icon" />
        {{ mod.label }}
        <span class="chip-count">{{ mod.count }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="search-bar">
      <i class="pi pi-search" />
      <input v-model="form.search" type="text" placeholder="Tìm theo tên..." class="search-input" />
      <button v-if="form.search" class="search-clear" @click="form.search = null">
        <i class="pi pi-times" />
      </button>
    </div>

    <!-- Trash Items -->
    <div v-if="items.length" class="trash-list">
      <div v-for="item in items" :key="`${item.model_type}-${item.id}`" class="trash-item">
        <div class="item-icon" :style="getModuleColor(item.module)">
          <i :class="item.module_icon" />
        </div>
        <div class="item-info">
          <div class="item-name-row">
            <span class="item-name">{{ item.name }}</span>
            <span class="item-module-tag">{{ item.module_label }}</span>
          </div>
          <span class="item-deleted">
            <i class="pi pi-clock" /> Đã xóa {{ item.deleted_at_human }}
            <span class="item-date">({{ item.deleted_at }})</span>
          </span>
        </div>
        <div class="item-actions">
          <Button
            icon="pi pi-undo"
            v-tooltip="'Khôi phục'"
            severity="success"
            text
            rounded
            size="small"
            @click="restoreItem(item)"
            :loading="processing === `restore-${item.model_type}-${item.id}`"
          />
          <Button
            icon="pi pi-times"
            v-tooltip="'Xóa vĩnh viễn'"
            severity="danger"
            text
            rounded
            size="small"
            @click="forceDeleteItem(item)"
            :loading="processing === `delete-${item.model_type}-${item.id}`"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-check-circle" /></div>
      <h3>Thùng rác trống</h3>
      <p v-if="form.module || form.search">Không tìm thấy mục nào phù hợp với bộ lọc.</p>
      <p v-else>Chưa có dữ liệu nào bị xóa. Tuyệt vời!</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: {
    items: Array,
    filters: Object,
    moduleCounts: Array,
    totalTrashed: Number,
  },
  data() {
    return {
      form: {
        module: this.filters.module || null,
        search: this.filters.search || null,
      },
      processing: null,
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/system-trash', pickBy(this.form), { preserveState: true, preserveScroll: true })
      }, 400),
    },
  },
  methods: {
    restoreItem(item) {
      if (!confirm(`Khôi phục "${item.name}"?`)) return
      this.processing = `restore-${item.model_type}-${item.id}`
      this.$inertia.post('/system-trash/restore', {
        model_type: item.model_type,
        id: item.id,
      }, {
        preserveScroll: true,
        onFinish: () => { this.processing = null },
      })
    },
    forceDeleteItem(item) {
      if (!confirm(`Xóa vĩnh viễn "${item.name}"? Hành động này KHÔNG THỂ hoàn tác!`)) return
      this.processing = `delete-${item.model_type}-${item.id}`
      this.$inertia.delete('/system-trash/force-delete', {
        data: { model_type: item.model_type, id: item.id },
        preserveScroll: true,
        onFinish: () => { this.processing = null },
      })
    },
    getModuleColor(module) {
      const colors = {
        leads: { background: '#eef2ff', color: '#6366f1' },
        contacts: { background: '#f0fdf4', color: '#16a34a' },
        deals: { background: '#fffbeb', color: '#d97706' },
        customers: { background: '#fdf2f8', color: '#db2777' },
        organizations: { background: '#f5f3ff', color: '#7c3aed' },
        proposals: { background: '#ecfdf5', color: '#059669' },
        projects: { background: '#eff6ff', color: '#2563eb' },
        wiki: { background: '#f8fafc', color: '#475569' },
        playbooks: { background: '#fef3c7', color: '#92400e' },
      }
      return colors[module] || { background: '#f1f5f9', color: '#64748b' }
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.header-icon-wrapper.trash-icon {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  box-shadow: 0 4px 14px rgba(239,68,68,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* ===== Module Chips ===== */
.module-chips {
  display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem;
}
.module-chip {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.75rem; border-radius: 10px;
  border: 1.5px solid #e2e8f0; background: white;
  font-size: 0.72rem; font-weight: 600; color: #475569;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.module-chip i { font-size: 0.62rem; }
.module-chip:hover { border-color: #6366f1; color: #6366f1; }
.module-chip.active {
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  border-color: #6366f1; color: #4f46e5;
}
.chip-count {
  font-size: 0.55rem; background: #f1f5f9; color: #64748b;
  padding: 0.08rem 0.3rem; border-radius: 6px; font-weight: 700;
}
.module-chip.active .chip-count { background: #c7d2fe; color: #4f46e5; }

/* ===== Search ===== */
.search-bar {
  display: flex; align-items: center;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: white; overflow: hidden;
  margin-bottom: 1rem; transition: border-color 0.2s;
}
.search-bar:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.search-bar > .pi-search { padding: 0 0.65rem; color: #94a3b8; font-size: 0.78rem; }
.search-input {
  flex: 1; border: none; outline: none; padding: 0.6rem 0.5rem 0.6rem 0;
  font-size: 0.82rem; color: #1e293b; font-family: inherit;
}
.search-input::placeholder { color: #cbd5e1; }
.search-clear { background: none; border: none; padding: 0 0.55rem; color: #94a3b8; cursor: pointer; }
.search-clear:hover { color: #ef4444; }

/* ===== Trash List ===== */
.trash-list { display: flex; flex-direction: column; gap: 0.45rem; }

.trash-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem 1rem; background: white;
  border: 1.5px solid #f1f5f9; border-radius: 12px;
  transition: all 0.2s;
}
.trash-item:hover { border-color: #e2e8f0; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }

.item-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.82rem; flex-shrink: 0;
}

.item-info { flex: 1; min-width: 0; }
.item-name-row { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.item-name { font-size: 0.85rem; font-weight: 700; color: #1e293b; }
.item-module-tag {
  font-size: 0.52rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px;
  background: #f1f5f9; color: #64748b; text-transform: uppercase;
}
.item-deleted {
  font-size: 0.68rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem;
  margin-top: 0.1rem;
}
.item-deleted i { font-size: 0.58rem; }
.item-date { color: #cbd5e1; }

.item-actions { display: flex; gap: 0.125rem; flex-shrink: 0; }

/* ===== Empty State ===== */
.empty-state {
  text-align: center; padding: 3rem 2rem;
  background: white; border-radius: 16px; border: 2px dashed #e2e8f0;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem; font-size: 1.5rem; color: #059669;
}
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .module-chips { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 0.25rem; }
  .trash-item { flex-wrap: wrap; }
  .item-actions { margin-left: auto; }
}
</style>
