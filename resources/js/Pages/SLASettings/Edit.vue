<template>
  <div>
    <Head :title="`Chỉnh sửa: ${slaSetting.name}`" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <Link href="/sla-settings" class="back-btn" v-tooltip="'Quay lại'">
          <i class="pi pi-arrow-left" />
        </Link>
        <div>
          <h1 class="page-title">{{ slaSetting.name }}</h1>
          <p class="page-subtitle">Chỉnh sửa thỏa thuận mức dịch vụ</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge" :class="slaSetting.is_active ? 'badge-active' : 'badge-inactive'">
          {{ slaSetting.is_active ? 'Active' : 'Inactive' }}
        </span>
        <span v-if="slaSetting.is_default" class="badge badge-default">
          <i class="pi pi-star-fill" /> Mặc định
        </span>
      </div>
    </div>

    <!-- Form Card -->
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="update" class="card-body">
        <!-- Basic Info -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-info-circle section-icon" />
            <h3 class="section-title">Thông tin cơ bản</h3>
          </div>
          <div class="form-group">
            <label>Tên SLA <span class="req">*</span></label>
            <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
            <small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small>
          </div>
          <div class="form-group">
            <label>Mô tả</label>
            <Textarea v-model="form.description" rows="2" class="w-full" :class="{ 'p-invalid': form.errors.description }" />
            <small v-if="form.errors.description" class="field-error">{{ form.errors.description }}</small>
          </div>
        </div>

        <!-- Thresholds -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-clock section-icon" />
            <h3 class="section-title">Ngưỡng thời gian</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Phản hồi đầu tiên (phút) <span class="req">*</span></label>
              <InputNumber v-model="form.first_response_threshold" :min="1" :max="1440" suffix=" phút" class="w-full" :class="{ 'p-invalid': form.errors.first_response_threshold }" />
              <small v-if="form.errors.first_response_threshold" class="field-error">{{ form.errors.first_response_threshold }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Cảnh báo (phút) <span class="req">*</span></label>
              <InputNumber v-model="form.warning_threshold" :min="1" :max="1440" suffix=" phút" class="w-full" :class="{ 'p-invalid': form.errors.warning_threshold }" />
              <small v-if="form.errors.warning_threshold" class="field-error">{{ form.errors.warning_threshold }}</small>
            </div>
          </div>
          <div class="form-group" style="max-width: 50%">
            <label>Ngưỡng nghiêm trọng (phút)</label>
            <InputNumber v-model="form.critical_threshold" :min="1" :max="1440" suffix=" phút" class="w-full" :class="{ 'p-invalid': form.errors.critical_threshold }" />
            <small v-if="form.errors.critical_threshold" class="field-error">{{ form.errors.critical_threshold }}</small>
          </div>
        </div>

        <!-- Notifications -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-bell section-icon" />
            <h3 class="section-title">Thông báo</h3>
          </div>
          <div class="notification-options">
            <div class="notify-item">
              <Checkbox v-model="form.notify_assigned_user" inputId="notify_assigned" :binary="true" />
              <div class="notify-info">
                <label for="notify_assigned" class="notify-label">Thông báo cho người được giao</label>
                <span class="notify-desc">Gửi thông báo đến nhân viên được giao lead</span>
              </div>
            </div>
            <div class="notify-item">
              <Checkbox v-model="form.notify_managers" inputId="notify_managers" :binary="true" />
              <div class="notify-info">
                <label for="notify_managers" class="notify-label">Thông báo cho quản lý</label>
                <span class="notify-desc">Gửi thông báo khi SLA sắp hoặc đã vi phạm</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Status -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-sliders-h section-icon" />
            <h3 class="section-title">Trạng thái</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Kích hoạt</label>
              <Select v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="form-group flex-1">
              <label>Đặt làm mặc định</label>
              <Select v-model="form.is_default" :options="defaultOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="form-footer">
          <Link href="/sla-settings">
            <Button label="Hủy" severity="secondary" outlined />
          </Link>
          <Button label="Cập nhật SLA" icon="pi pi-check" :loading="form.processing" type="submit" />
        </div>
      </form>
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
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, InputNumber, Textarea, Select, Checkbox, Button },
  layout: Layout,
  props: { slaSetting: Object },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: this.$inertia.form({
        name: this.slaSetting.name,
        description: this.slaSetting.description || '',
        first_response_threshold: this.slaSetting.first_response_threshold,
        warning_threshold: this.slaSetting.warning_threshold,
        critical_threshold: this.slaSetting.critical_threshold,
        notify_assigned_user: this.slaSetting.notify_assigned_user ?? true,
        notify_managers: this.slaSetting.notify_managers ?? true,
        notify_user_ids: this.slaSetting.notify_user_ids || [],
        is_active: this.slaSetting.is_active !== undefined ? this.slaSetting.is_active : true,
        is_default: this.slaSetting.is_default ?? false,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      defaultOptions: [
        { label: 'Không', value: false },
        { label: 'Có', value: true },
      ],
    }
  },
  methods: {
    update() {
      this.form.put(`/sla-settings/${this.slaSetting.id}`)
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.back-btn {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: white; border: 1.5px solid #e2e8f0; color: #64748b;
  text-decoration: none; transition: all 0.2s;
}
.back-btn:hover { border-color: #6366f1; color: #6366f1; }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }

.header-badges { display: flex; gap: 0.4rem; }
.badge {
  font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.45rem;
  border-radius: 6px; display: flex; align-items: center; gap: 0.2rem;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-active { background: #ecfdf5; color: #059669; }
.badge-inactive { background: #f1f5f9; color: #94a3b8; }
.badge-default { background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; }

/* ===== Form Card ===== */
.form-card {
  background: white; border-radius: 16px; border: 1.5px solid #e2e8f0;
  overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); max-width: 780px;
}
.card-accent {
  height: 4px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
}
.card-body { padding: 1.5rem; }

/* ===== Sections ===== */
.form-section {
  background: #fafbfc; border: 1px solid #f1f5f9; border-radius: 12px;
  padding: 1.15rem; margin-bottom: 1rem;
}
.section-header {
  display: flex; align-items: center; gap: 0.5rem;
  margin-bottom: 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid #f1f5f9;
}
.section-icon { font-size: 0.85rem; color: #6366f1; }
.section-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; }

.form-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.form-group { margin-bottom: 0.75rem; min-width: 0; }
.flex-1 { flex: 1; min-width: 200px; }
.w-full { width: 100%; }
.form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem; }
.req { color: #ef4444; }
.field-error { display: block; font-size: 0.65rem; color: #ef4444; margin-top: 0.2rem; }

/* ===== Notifications ===== */
.notification-options { display: flex; flex-direction: column; gap: 0.5rem; }
.notify-item {
  display: flex; align-items: flex-start; gap: 0.65rem;
  padding: 0.65rem; border-radius: 8px; background: white; border: 1px solid #f1f5f9;
  transition: border-color 0.2s;
}
.notify-item:hover { border-color: #e2e8f0; }
.notify-info { display: flex; flex-direction: column; }
.notify-label { font-size: 0.82rem; font-weight: 600; color: #1e293b; cursor: pointer; }
.notify-desc { font-size: 0.65rem; color: #94a3b8; margin-top: 0.1rem; }

/* ===== Footer ===== */
.form-footer {
  display: flex; justify-content: flex-end; gap: 0.5rem;
  padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .form-row { flex-direction: column; }
  .flex-1 { min-width: 100%; }
}
</style>
