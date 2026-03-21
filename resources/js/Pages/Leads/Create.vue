<template>
  <div>
    <Head :title="t('common.create_lead')" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <div class="breadcrumb-row">
          <Link href="/leads" class="breadcrumb-link">{{ t('common.leads') }}</Link>
          <i class="pi pi-angle-right breadcrumb-sep" />
          <span class="breadcrumb-current">{{ t('common.create_lead') }}</span>
        </div>
        <h1 class="page-title">{{ t('common.create_lead') }}</h1>
        <p class="page-subtitle">Thêm khách hàng tiềm năng mới vào hệ thống</p>
      </div>
    </div>

    <!-- Duplicate Warning -->
    <div v-if="form.errors.duplicate" class="alert alert-error">
      <i class="pi pi-exclamation-triangle" />
      <div class="alert-content">
        <span>{{ form.errors.duplicate }}</span>
        <Link v-if="form.errors.duplicate_id" :href="`/leads/${form.errors.duplicate_id}/edit`" class="alert-link">
          Xem lead hiện có →
        </Link>
      </div>
    </div>

    <form @submit.prevent="store">
      <div class="form-layout">
        <!-- Main Form -->
        <div class="form-main">
          <!-- Contact Information -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-user" />
              <h2 class="card-title">Thông tin liên hệ</h2>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.name') }} <span class="required">*</span></label>
                  <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" placeholder="Họ tên" />
                  <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.company') }}</label>
                  <InputText v-model="form.company" :class="{ 'p-invalid': form.errors.company }" placeholder="Tên công ty" />
                  <small v-if="form.errors.company" class="p-error">{{ form.errors.company }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.phone') }}</label>
                  <InputText v-model="form.phone" :class="{ 'p-invalid': form.errors.phone }" placeholder="09xx xxx xxx" />
                  <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.email') }}</label>
                  <InputText v-model="form.email" type="email" :class="{ 'p-invalid': form.errors.email }" placeholder="email@company.com" />
                  <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Classification -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-tag" />
              <h2 class="card-title">Phân loại</h2>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.source') }}</label>
                  <Select
                    v-model="form.source"
                    :options="sourceOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Chọn nguồn"
                    :class="{ 'p-invalid': form.errors.source }"
                  />
                  <small v-if="form.errors.source" class="p-error">{{ form.errors.source }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.status') }} <span class="required">*</span></label>
                  <Select
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': form.errors.status }"
                  />
                  <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.assigned_to') }}</label>
                  <Select
                    v-model="form.assigned_to"
                    :options="assignedOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Chưa phân công"
                    :class="{ 'p-invalid': form.errors.assigned_to }"
                  />
                  <small v-if="form.errors.assigned_to" class="p-error">{{ form.errors.assigned_to }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.tags') }} <span class="optional">(tùy chọn)</span></label>
                  <InputText v-model="tagsInput" placeholder="hot, vip, follow-up" />
                  <small class="hint">Phân cách bằng dấu phẩy</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-pencil" />
              <h2 class="card-title">Ghi chú</h2>
            </div>
            <div class="card-body">
              <div class="form-group">
                <Textarea v-model="form.notes" rows="4" :class="{ 'p-invalid': form.errors.notes }" placeholder="Ghi chú thêm về lead..." />
                <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="form-sidebar">
          <div class="sidebar-card">
            <h3 class="sidebar-title"><i class="pi pi-info-circle" /> Hướng dẫn</h3>
            <div class="guide-list">
              <div class="guide-item">
                <i class="pi pi-check-circle" />
                <span>Nhập <b>họ tên</b> là trường bắt buộc</span>
              </div>
              <div class="guide-item">
                <i class="pi pi-check-circle" />
                <span>Email hoặc SĐT để liên hệ lại</span>
              </div>
              <div class="guide-item">
                <i class="pi pi-check-circle" />
                <span>Chọn <b>nguồn</b> (source) để tracking</span>
              </div>
              <div class="guide-item">
                <i class="pi pi-check-circle" />
                <span>Phân công nhân viên phụ trách</span>
              </div>
            </div>
          </div>

          <div class="actions-card">
            <Link href="/leads">
              <Button :label="t('common.cancel')" severity="secondary" text class="w-full" />
            </Link>
            <Button
              :label="t('common.create_lead')"
              icon="pi pi-check"
              :loading="form.processing"
              type="submit"
              class="w-full"
            />
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, Textarea, Select, Button },
  layout: Layout,
  props: {
    statuses: Object,
    sources: Object,
    salesUsers: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  remember: 'form',
  data() {
    return {
      tagsInput: '',
      form: this.$inertia.form({
        name: '',
        phone: '',
        email: '',
        company: '',
        source: null,
        status: 'new',
        assigned_to: null,
        notes: '',
        tags: [],
      }),
    }
  },
  computed: {
    statusOptions() {
      return Object.entries(this.statuses).map(([value, label]) => ({ label, value }))
    },
    sourceOptions() {
      return Object.entries(this.sources).map(([value, label]) => ({ label, value }))
    },
    assignedOptions() {
      return [{ label: 'Chưa phân công', value: null }, ...this.salesUsers.map(user => ({ label: user.name, value: user.id }))]
    },
  },
  watch: {
    tagsInput(newVal) {
      this.form.tags = newVal.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0)
    },
  },
  methods: {
    store() {
      this.form.post('/leads')
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

.breadcrumb-row {
  display: flex; align-items: center; gap: 0.4rem;
  margin-bottom: 0.35rem;
}
.breadcrumb-link {
  font-size: 0.75rem; color: #6366f1; text-decoration: none;
  font-weight: 500; transition: color 0.15s;
}
.breadcrumb-link:hover { color: #4f46e5; }
.breadcrumb-sep { font-size: 0.6rem; color: #cbd5e1; }
.breadcrumb-current { font-size: 0.75rem; color: #94a3b8; }

/* ===== Alert ===== */
.alert {
  display: flex; align-items: flex-start; gap: 0.65rem;
  padding: 0.85rem 1rem; border-radius: 10px;
  margin-bottom: 1rem; font-size: 0.82rem;
}
.alert-error {
  background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
}
.alert-error i { font-size: 1rem; margin-top: 0.1rem; }
.alert-content { display: flex; flex-direction: column; gap: 0.25rem; }
.alert-link { font-size: 0.75rem; color: #6366f1; text-decoration: underline; }

/* ===== Form Layout ===== */
.form-layout { display: flex; gap: 1.25rem; align-items: flex-start; }
.form-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1rem; }

/* ===== Form Card ===== */
.form-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}
.card-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.85rem 1.25rem;
  background: #fafbfc;
  border-bottom: 1px solid #f1f5f9;
}
.card-header i { font-size: 0.85rem; color: #6366f1; }
.card-title { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0; }
.card-body { padding: 1.25rem; }

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}
.form-group { display: flex; flex-direction: column; }
.form-group label {
  font-size: 0.78rem; font-weight: 500; color: #475569;
  margin-bottom: 0.35rem;
}
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; font-size: 0.7rem; }
.hint { font-size: 0.7rem; color: #94a3b8; margin-top: 0.2rem; }

.form-group :deep(.p-inputtext),
.form-group :deep(.p-select) {
  width: 100%;
}

/* ===== Sidebar ===== */
.form-sidebar {
  width: 280px; flex-shrink: 0;
  position: sticky; top: 80px;
  display: flex; flex-direction: column; gap: 0.75rem;
}
.sidebar-card {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}
.sidebar-title {
  font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem;
  display: flex; align-items: center; gap: 0.4rem;
}
.sidebar-title i { color: #6366f1; font-size: 0.8rem; }

.guide-list { display: flex; flex-direction: column; gap: 0.5rem; }
.guide-item {
  display: flex; align-items: flex-start; gap: 0.4rem;
  font-size: 0.75rem; color: #64748b; line-height: 1.4;
}
.guide-item i { font-size: 0.65rem; color: #10b981; margin-top: 0.2rem; flex-shrink: 0; }
.guide-item b { font-weight: 600; color: #475569; }

.actions-card {
  display: flex; flex-direction: column; gap: 0.5rem;
}
.w-full { width: 100%; }

@media (max-width: 768px) {
  .form-layout { flex-direction: column; }
  .form-sidebar { width: 100%; position: static; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
