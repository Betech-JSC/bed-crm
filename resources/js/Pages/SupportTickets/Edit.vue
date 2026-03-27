<template>
  <div>
    <Head :title="`Ticket #${ticket.id}`" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-ticket" style="color: #f59e0b; margin-right: 0.5rem;" />Ticket #{{ ticket.id }}</h1>
        <p class="page-subtitle">{{ ticket.subject }}</p>
      </div>
      <div class="header-actions">
        <a href="/support-tickets" class="btn-secondary"><i class="pi pi-arrow-left" /> Quay lại</a>
        <button class="btn-danger" @click="confirmDelete"><i class="pi pi-trash" /> Xóa</button>
      </div>
    </div>

    <!-- Ticket Meta -->
    <div class="meta-strip">
      <div class="meta-item">
        <span class="meta-label">Trạng thái</span>
        <span class="status-badge" :class="`status-${ticket.status}`">{{ statuses[ticket.status] }}</span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Ưu tiên</span>
        <span class="priority-badge" :class="`priority-${ticket.priority}`">{{ priorities[ticket.priority] }}</span>
      </div>
      <div v-if="ticket.customer" class="meta-item">
        <span class="meta-label">Khách hàng</span>
        <span class="meta-value"><i class="pi pi-building" /> {{ ticket.customer.name }}</span>
      </div>
      <div v-if="ticket.assigned_user" class="meta-item">
        <span class="meta-label">Phụ trách</span>
        <span class="meta-value"><i class="pi pi-user" /> {{ ticket.assigned_user.name }}</span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Ngày tạo</span>
        <span class="meta-value">{{ formatDate(ticket.created_at) }}</span>
      </div>
      <div v-if="ticket.resolved_at" class="meta-item">
        <span class="meta-label">Resolved</span>
        <span class="meta-value success-text">{{ formatDate(ticket.resolved_at) }}</span>
      </div>
    </div>

    <!-- Edit Form -->
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label>Tiêu đề <span class="required">*</span></label>
            <input v-model="form.subject" type="text" class="form-input" required />
          </div>

          <div class="form-group full">
            <label>Mô tả chi tiết</label>
            <textarea v-model="form.description" class="form-textarea" rows="4" />
          </div>

          <div class="form-group">
            <label>Trạng thái</label>
            <select v-model="form.status" class="form-select">
              <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Ưu tiên</label>
            <select v-model="form.priority" class="form-select">
              <option v-for="(label, key) in priorities" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Khách hàng</label>
            <select v-model="form.customer_id" class="form-select">
              <option :value="null">-- Chọn --</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Phụ trách</label>
            <select v-model="form.assigned_to" class="form-select">
              <option :value="null">-- Chưa gán --</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Danh mục</label>
            <select v-model="form.category" class="form-select">
              <option :value="null">-- Chọn --</option>
              <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <a href="/support-tickets" class="btn-secondary">Hủy</a>
          <button type="submit" class="btn-primary" :disabled="processing">
            <i class="pi pi-save" /> Lưu thay đổi
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { ticket: Object, statuses: Object, priorities: Object, categories: Object, customers: Array, users: Array },
  data() {
    return {
      processing: false,
      form: {
        subject: this.ticket.subject,
        description: this.ticket.description,
        status: this.ticket.status,
        priority: this.ticket.priority,
        category: this.ticket.category,
        customer_id: this.ticket.customer_id,
        assigned_to: this.ticket.assigned_to,
      },
    }
  },
  methods: {
    submit() {
      this.processing = true
      router.put(`/support-tickets/${this.ticket.id}`, this.form, {
        onFinish: () => { this.processing = false },
      })
    },
    confirmDelete() {
      if (confirm('Bạn chắc chắn muốn xóa ticket này?')) {
        router.delete(`/support-tickets/${this.ticket.id}`)
      }
    },
    formatDate(d) { return d ? new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—' },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.header-actions { display: flex; gap: 0.5rem; }

.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(245,158,11,0.25); }
.btn-primary:hover { transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.btn-secondary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.82rem; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.15s; }
.btn-secondary:hover { background: #f8fafc; }

.btn-danger { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: white; color: #dc2626; border: 1px solid #fecaca; border-radius: 10px; font-size: 0.82rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.btn-danger:hover { background: #fef2f2; }

/* Meta Strip */
.meta-strip { display: flex; gap: 1rem; padding: 0.85rem 1rem; background: white; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: 0 1px 2px rgba(0,0,0,0.04); margin-bottom: 1rem; flex-wrap: wrap; }
.meta-item { display: flex; flex-direction: column; gap: 0.15rem; }
.meta-label { font-size: 0.62rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.meta-value { font-size: 0.82rem; color: #334155; display: flex; align-items: center; gap: 0.25rem; }
.meta-value i { font-size: 0.72rem; color: #94a3b8; }
.success-text { color: #059669; font-weight: 600; }

.priority-badge { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 6px; }
.priority-urgent { background: #dc2626; color: white; }
.priority-high { background: #fef3c7; color: #d97706; }
.priority-medium { background: #eef2ff; color: #6366f1; }
.priority-low { background: #f1f5f9; color: #94a3b8; }

.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.5rem; border-radius: 20px; }
.status-open { background: #fef3c7; color: #d97706; }
.status-in_progress { background: #dbeafe; color: #2563eb; }
.status-waiting { background: #ede9fe; color: #7c3aed; }
.status-resolved { background: #d1fae5; color: #059669; }
.status-closed { background: #f1f5f9; color: #94a3b8; }

/* Form */
.form-card { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.required { color: #dc2626; }
.form-input, .form-select, .form-textarea { padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; color: #334155; transition: border-color 0.15s; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.1); }
.form-textarea { resize: vertical; min-height: 80px; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.65rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .form-grid { grid-template-columns: 1fr; }
  .meta-strip { gap: 0.5rem; }
}
</style>
