<template>
  <div>
    <Head title="Chỉnh sửa vai trò" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/roles" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div>
          <h1 class="page-title">Chỉnh sửa vai trò</h1>
          <p class="page-subtitle">{{ role.name }}</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit" class="edit-form">
      <div class="form-grid">
        <!-- Left: Basic Info -->
        <div class="form-left">
          <div class="form-card">
            <div class="form-card-header">
              <h3><i class="pi pi-info-circle" /> Thông tin cơ bản</h3>
            </div>
            <div class="form-card-body">
              <div class="form-group">
                <label class="form-label">Tên vai trò <span class="required">*</span></label>
                <input v-model="form.name" class="form-input" :class="{ error: form.errors.name }" placeholder="VD: Quản lý bán hàng" />
                <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
              </div>

              <div class="form-group">
                <label class="form-label">Slug <span class="required">*</span></label>
                <input v-model="form.slug" class="form-input mono" :class="{ error: form.errors.slug }" placeholder="VD: sales-manager" />
                <span v-if="form.errors.slug" class="form-error">{{ form.errors.slug }}</span>
              </div>

              <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea v-model="form.description" class="form-textarea" rows="3" placeholder="Mô tả về vai trò này..." />
              </div>

              <div class="form-group">
                <label class="toggle-row">
                  <input type="checkbox" v-model="form.is_active" class="toggle-input" />
                  <span class="toggle-switch" />
                  <span class="toggle-label">Kích hoạt vai trò</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Permissions -->
        <div class="form-right">
          <div class="form-card">
            <div class="form-card-header">
              <h3><i class="pi pi-lock" /> Phân quyền</h3>
              <div class="header-meta">
                <span class="selected-count">{{ form.permissions.length }} đã chọn</span>
                <button type="button" class="btn-select-all" @click="selectAll">
                  {{ allSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
                </button>
              </div>
            </div>
            <div class="form-card-body perm-body">
              <!-- Search -->
              <div class="perm-search">
                <i class="pi pi-search" />
                <input v-model="permSearch" placeholder="Tìm quyền..." class="perm-search-input" />
              </div>

              <!-- Permission Groups -->
              <div class="perm-groups">
                <div v-for="group in filteredPermGroups" :key="group.group" class="perm-group">
                  <div class="perm-group-header" @click="toggleGroupExpand(group.group)">
                    <div class="perm-group-left">
                      <i :class="expandedGroups.includes(group.group) ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" class="expand-icon" />
                      <span class="perm-group-name">{{ group.group }}</span>
                      <span class="perm-group-count">{{ getGroupSelectedCount(group) }}/{{ group.permissions.length }}</span>
                    </div>
                    <button type="button" class="btn-group-toggle" @click.stop="toggleGroup(group)">
                      {{ isGroupSelected(group) ? 'Bỏ hết' : 'Chọn hết' }}
                    </button>
                  </div>

                  <div v-if="expandedGroups.includes(group.group)" class="perm-group-items">
                    <label v-for="perm in group.permissions" :key="perm.id" class="perm-checkbox">
                      <input
                        type="checkbox"
                        :value="perm.id"
                        v-model="form.permissions"
                        class="checkbox-input"
                      />
                      <span class="checkbox-mark" />
                      <div class="checkbox-content">
                        <span class="checkbox-label">{{ perm.name }}</span>
                        <code class="checkbox-slug">{{ perm.slug }}</code>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="form-footer">
        <Link href="/roles" class="btn-cancel">Hủy</Link>
        <button type="submit" class="btn-save" :disabled="form.processing">
          <i :class="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check'" />
          {{ form.processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    role: Object,
    permissions: { type: Array, default: () => [] },
    selectedPermissions: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: useForm({
        name: this.role?.name || '',
        slug: this.role?.slug || '',
        description: this.role?.description || '',
        is_active: this.role?.is_active ?? true,
        permissions: this.selectedPermissions || [],
      }),
      permSearch: '',
      expandedGroups: this.permissions.map(g => g.group),
    }
  },
  computed: {
    filteredPermGroups() {
      if (!this.permSearch) return this.permissions
      const q = this.permSearch.toLowerCase()
      return this.permissions.map(g => ({
        ...g,
        permissions: g.permissions.filter(p =>
          p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)
        ),
      })).filter(g => g.permissions.length > 0)
    },
    allSelected() {
      const allIds = this.permissions.flatMap(g => g.permissions.map(p => p.id))
      return allIds.length > 0 && allIds.every(id => this.form.permissions.includes(id))
    },
  },
  methods: {
    submit() {
      this.form.put(`/roles/${this.role.id}`)
    },
    toggleGroupExpand(group) {
      const idx = this.expandedGroups.indexOf(group)
      if (idx >= 0) this.expandedGroups.splice(idx, 1)
      else this.expandedGroups.push(group)
    },
    isGroupSelected(group) {
      return group.permissions.every(p => this.form.permissions.includes(p.id))
    },
    getGroupSelectedCount(group) {
      return group.permissions.filter(p => this.form.permissions.includes(p.id)).length
    },
    toggleGroup(group) {
      const ids = group.permissions.map(p => p.id)
      if (this.isGroupSelected(group)) {
        this.form.permissions = this.form.permissions.filter(id => !ids.includes(id))
      } else {
        ids.forEach(id => { if (!this.form.permissions.includes(id)) this.form.permissions.push(id) })
      }
    },
    selectAll() {
      if (this.allSelected) {
        this.form.permissions = []
      } else {
        this.form.permissions = this.permissions.flatMap(g => g.permissions.map(p => p.id))
      }
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
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }

/* Form Grid */
.form-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.25rem; }

/* Form Card */
.form-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.form-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.form-card-header h3 { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.35rem; }
.form-card-header h3 i { color: #6366f1; font-size: 0.82rem; }
.header-meta { display: flex; align-items: center; gap: 0.5rem; }
.selected-count { font-size: 0.65rem; font-weight: 700; color: #6366f1; background: #eef2ff; padding: 0.2rem 0.5rem; border-radius: 8px; }
.btn-select-all {
  font-size: 0.65rem; font-weight: 600; color: #6366f1; background: none;
  border: 1px solid #e0e7ff; padding: 0.2rem 0.5rem; border-radius: 6px; cursor: pointer; transition: all 0.2s;
}
.btn-select-all:hover { background: #eef2ff; }
.form-card-body { padding: 1.25rem; }

/* Form Groups */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem; }
.required { color: #ef4444; }
.form-input, .form-textarea {
  width: 100%; padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.85rem; color: #334155; background: #f8fafc; outline: none;
  transition: all 0.2s; font-family: inherit;
}
.form-input:focus, .form-textarea:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.form-input.mono { font-family: monospace; font-size: 0.82rem; }
.form-input.error { border-color: #ef4444; }
.form-textarea { resize: vertical; }
.form-error { font-size: 0.68rem; color: #ef4444; margin-top: 0.2rem; display: block; }

/* Toggle */
.toggle-row { display: flex; align-items: center; gap: 0.6rem; cursor: pointer; }
.toggle-input { display: none; }
.toggle-switch {
  width: 40px; height: 22px; border-radius: 11px; background: #cbd5e1;
  position: relative; transition: background 0.2s; flex-shrink: 0;
}
.toggle-switch::after {
  content: ''; position: absolute; top: 2px; left: 2px;
  width: 18px; height: 18px; border-radius: 50%; background: white;
  transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.toggle-input:checked + .toggle-switch { background: #6366f1; }
.toggle-input:checked + .toggle-switch::after { transform: translateX(18px); }
.toggle-label { font-size: 0.82rem; font-weight: 500; color: #475569; }

/* Permissions */
.perm-body { padding: 0; }
.perm-search {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.6rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.perm-search i { color: #94a3b8; font-size: 0.82rem; }
.perm-search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.perm-groups { max-height: 500px; overflow-y: auto; }

.perm-group { border-bottom: 1px solid #f1f5f9; }
.perm-group:last-child { border-bottom: none; }
.perm-group-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.65rem 1.25rem; cursor: pointer; transition: background 0.15s;
}
.perm-group-header:hover { background: #fafbfc; }
.perm-group-left { display: flex; align-items: center; gap: 0.4rem; }
.expand-icon { font-size: 0.6rem; color: #94a3b8; transition: transform 0.2s; }
.perm-group-name { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.perm-group-count { font-size: 0.55rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.35rem; border-radius: 6px; }
.btn-group-toggle {
  font-size: 0.6rem; font-weight: 600; color: #6366f1;
  background: none; border: none; cursor: pointer; padding: 0.15rem 0.4rem;
  border-radius: 4px; transition: all 0.15s;
}
.btn-group-toggle:hover { background: #eef2ff; }

.perm-group-items { padding: 0.25rem 0; }
.perm-checkbox {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 1.25rem 0.4rem 2.5rem; cursor: pointer; transition: background 0.15s;
}
.perm-checkbox:hover { background: #f8fafc; }
.checkbox-input { display: none; }
.checkbox-mark {
  width: 18px; height: 18px; border: 2px solid #cbd5e1; border-radius: 5px;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; flex-shrink: 0; position: relative;
}
.checkbox-input:checked + .checkbox-mark {
  background: #6366f1; border-color: #6366f1;
}
.checkbox-input:checked + .checkbox-mark::after {
  content: '✓'; color: white; font-size: 0.65rem; font-weight: 800;
}
.checkbox-content { min-width: 0; }
.checkbox-label { font-size: 0.8rem; color: #334155; display: block; }
.checkbox-slug { font-size: 0.58rem; color: #94a3b8; font-family: monospace; }

/* Footer */
.form-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  margin-top: 1.25rem; padding: 1rem 0;
}
.btn-cancel {
  padding: 0.55rem 1.25rem; border-radius: 10px; border: 1.5px solid #e2e8f0;
  background: white; color: #64748b; font-size: 0.82rem; font-weight: 600;
  text-decoration: none; transition: all 0.2s; cursor: pointer;
}
.btn-cancel:hover { border-color: #94a3b8; color: #1e293b; }
.btn-save {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.55rem 1.25rem; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; font-size: 0.82rem; font-weight: 600;
  border: none; cursor: pointer; transition: all 0.2s;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
}
</style>
