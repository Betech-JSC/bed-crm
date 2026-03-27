<template>
  <div>
    <Head title="Tạo Ticket mới" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-ticket" style="color: #f59e0b; margin-right: 0.5rem;" />Tạo Ticket mới</h1>
        <p class="page-subtitle">Tạo yêu cầu hỗ trợ mới cho khách hàng</p>
      </div>
      <a href="/support-tickets" class="btn-secondary"><i class="pi pi-arrow-left" /> Quay lại</a>
    </div>

    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label>Tiêu đề <span class="required">*</span></label>
            <input v-model="form.subject" type="text" class="form-input" placeholder="Mô tả vấn đề..." required />
            <p v-if="errors.subject" class="error-text">{{ errors.subject }}</p>
          </div>

          <div class="form-group full">
            <label>Mô tả chi tiết</label>
            <textarea v-model="form.description" class="form-textarea" rows="4" placeholder="Chi tiết vấn đề cần hỗ trợ..." />
          </div>

          <div class="form-group">
            <label>Khách hàng</label>
            <select v-model="form.customer_id" class="form-select">
              <option :value="null">-- Chọn khách hàng --</option>
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
            <label>Mức ưu tiên <span class="required">*</span></label>
            <select v-model="form.priority" class="form-select" required>
              <option v-for="(label, key) in priorities" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>Danh mục</label>
            <select v-model="form.category" class="form-select">
              <option :value="null">-- Chọn danh mục --</option>
              <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <a href="/support-tickets" class="btn-secondary">Hủy</a>
          <button type="submit" class="btn-primary" :disabled="processing">
            <i class="pi pi-check" /> Tạo Ticket
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
  props: { statuses: Object, priorities: Object, categories: Object, customers: Array, users: Array },
  data() {
    return {
      processing: false,
      errors: {},
      form: {
        subject: '',
        description: '',
        priority: 'medium',
        category: null,
        customer_id: null,
        assigned_to: null,
      },
    }
  },
  methods: {
    submit() {
      this.processing = true
      router.post('/support-tickets', this.form, {
        onError: (errors) => { this.errors = errors },
        onFinish: () => { this.processing = false },
      })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(245,158,11,0.25); }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(245,158,11,0.35); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.btn-secondary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.82rem; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.15s; }
.btn-secondary:hover { background: #f8fafc; color: #334155; }

.form-card { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.required { color: #dc2626; }
.form-input, .form-select, .form-textarea { padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; color: #334155; transition: border-color 0.15s; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.1); }
.form-textarea { resize: vertical; min-height: 80px; }
.error-text { font-size: 0.72rem; color: #dc2626; margin: 0; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.65rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
