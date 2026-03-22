<template>
  <div>
    <Head :title="t('common.create_project')" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/projects" class="breadcrumb-link"><i class="pi pi-arrow-left" /> {{ t('common.projects') }}</Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ t('common.create_project') }}</span>
    </div>

    <div class="create-layout">
      <!-- Main Form -->
      <div class="form-panel">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-icon"><i class="pi pi-folder" /></div>
            <div>
              <h2>{{ t('common.create_project') }}</h2>
              <p>Thiết lập thông tin dự án mới</p>
            </div>
          </div>

          <form @submit.prevent="store">
            <!-- Basic Info -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-info-circle" /> Thông tin cơ bản</h4>
              <div class="form-grid">
                <div class="form-group span-2">
                  <label>{{ t('common.name') }} <span class="req">*</span></label>
                  <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" placeholder="Tên dự án..." />
                  <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.status') }}</label>
                  <Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.priority') }}</label>
                  <Select v-model="form.priority" :options="priorityOpts" optionLabel="label" optionValue="value" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.customer') }}</label>
                  <Select v-model="form.customer_id" :options="customerOpts" optionLabel="label" optionValue="value" placeholder="— Chọn khách hàng —" showClear />
                </div>
                <div class="form-group">
                  <label>{{ t('common.manager') }}</label>
                  <Select v-model="form.manager_id" :options="userOpts" optionLabel="label" optionValue="value" placeholder="— Chọn PM —" showClear />
                </div>
              </div>
            </div>

            <!-- Timeline -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-calendar" /> Timeline</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.start_date') }}</label>
                  <Calendar v-model="form.start_date" dateFormat="yy-mm-dd" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.due_date') }}</label>
                  <Calendar v-model="form.due_date" dateFormat="yy-mm-dd" />
                </div>
              </div>
            </div>

            <!-- Financials -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-wallet" /> Tài chính</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.budget') }}</label>
                  <InputNumber v-model="form.budget" mode="currency" currency="VND" locale="vi-VN" />
                </div>
                <div class="form-group">
                  <label>Revenue</label>
                  <InputNumber v-model="form.revenue" mode="currency" currency="VND" locale="vi-VN" />
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-align-left" /> Mô tả</h4>
              <div class="form-group">
                <Textarea v-model="form.description" rows="3" placeholder="Mô tả dự án..." class="w-full" />
              </div>
            </div>

            <div class="form-actions">
              <Link href="/projects"><Button :label="t('common.cancel')" severity="secondary" text /></Link>
              <Button :label="t('common.create_project')" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="sidebar-panel">
        <div class="tips-card">
          <div class="tips-header"><i class="pi pi-lightbulb" /> Hướng dẫn</div>
          <div class="tips-list">
            <div class="tip-item"><i class="pi pi-check" /><span>Đặt budget thực tế để theo dõi profit margin</span></div>
            <div class="tip-item"><i class="pi pi-calendar" /><span>Due date giúp cảnh báo khi dự án sắp trễ hạn</span></div>
            <div class="tip-item"><i class="pi pi-user" /><span>Gán PM để phân quyền quản lý tasks & resources</span></div>
            <div class="tip-item"><i class="pi pi-chart-line" /><span>Revenue & Budget dùng để tính profit tự động</span></div>
          </div>
        </div>

        <div class="quick-card">
          <h4>Sau khi tạo</h4>
          <p>Bạn có thể thêm Tasks, Resources và Expenses trong trang chỉnh sửa dự án</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, InputNumber, Textarea, Select, Calendar, Button },
  layout: Layout,
  props: { statuses: Object, priorities: Object, customers: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: this.$inertia.form({
        name: '', description: '', status: 'planning', priority: 'medium',
        customer_id: null, manager_id: null, start_date: null, due_date: null,
        budget: 0, revenue: 0, notes: '',
      }),
    }
  },
  computed: {
    statusOpts() { return Object.entries(this.statuses).map(([v, l]) => ({ label: l, value: v })) },
    priorityOpts() { return Object.entries(this.priorities).map(([v, l]) => ({ label: l, value: v })) },
    customerOpts() { return this.customers.map(c => ({ label: c.name, value: c.id })) },
    userOpts() { return this.salesUsers.map(u => ({ label: u.name, value: u.id })) },
  },
  methods: { store() { this.form.post('/projects') } },
}
</script>

<style scoped>
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

.create-layout { display: grid; grid-template-columns: 1fr 280px; gap: 1.25rem; }

.form-card { background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.05); padding: 1.5rem; }
.form-card-header { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f8fafc; }
.form-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #6366f1; }
.form-card-header h2 { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.form-card-header p { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }

.form-section { margin-bottom: 1.25rem; }
.section-label { font-size: 0.78rem; font-weight: 600; color: #475569; margin: 0 0 0.65rem; display: flex; align-items: center; gap: 0.35rem; }
.section-label i { font-size: 0.72rem; color: #6366f1; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.form-group.span-2 { grid-column: span 2; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.req { color: #ef4444; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid #f8fafc; }

/* Sidebar */
.sidebar-panel { display: flex; flex-direction: column; gap: 0.85rem; }
.tips-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
.tips-header { padding: 0.75rem 1rem; border-bottom: 1px solid #f8fafc; font-size: 0.82rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 0.35rem; }
.tips-header i { color: #f59e0b; }
.tips-list { padding: 0.75rem 1rem; }
.tip-item { display: flex; gap: 0.5rem; padding: 0.35rem 0; font-size: 0.72rem; color: #64748b; line-height: 1.45; }
.tip-item i { color: #10b981; font-size: 0.6rem; margin-top: 0.2rem; flex-shrink: 0; }

.quick-card { background: linear-gradient(135deg, #eef2ff, #f5f3ff); border-radius: 14px; padding: 1rem; border: 1px solid #e0e7ff; }
.quick-card h4 { font-size: 0.85rem; font-weight: 600; color: #4338ca; margin: 0 0 0.3rem; }
.quick-card p { font-size: 0.72rem; color: #6366f1; margin: 0; line-height: 1.45; }

.w-full { width: 100%; }

@media (max-width: 1024px) { .create-layout { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-group.span-2 { grid-column: span 1; } }
</style>
