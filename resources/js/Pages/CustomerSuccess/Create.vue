<template>
  <div>
    <Head :title="t('common.create_customer')" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/customers" class="breadcrumb-link"><i class="pi pi-arrow-left" /> {{ t('common.customer_success') }}</Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ t('common.create_customer') }}</span>
    </div>

    <div class="create-layout">
      <!-- Main Form -->
      <div class="form-panel">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-icon"><i class="pi pi-heart" /></div>
            <div>
              <h2>{{ t('common.create_customer') }}</h2>
              <p>Thêm khách hàng mới vào hệ thống Customer Success</p>
            </div>
          </div>

          <form @submit.prevent="store">
            <!-- Basic -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-user" /> Thông tin cơ bản</h4>
              <div class="form-grid">
                <div class="form-group span-2">
                  <label>{{ t('common.name') }} <span class="req">*</span></label>
                  <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" placeholder="Tên khách hàng..." />
                  <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.email') }}</label>
                  <InputText v-model="form.email" type="email" placeholder="email@example.com" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.phone') }}</label>
                  <InputText v-model="form.phone" placeholder="0x xxx xxx" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.organization') }}</label>
                  <Select v-model="form.organization_id" :options="orgOptions" optionLabel="label" optionValue="value" placeholder="— Chọn —" showClear />
                </div>
                <div class="form-group">
                  <label>{{ t('common.assigned_to') }}</label>
                  <Select v-model="form.assigned_to" :options="userOptions" optionLabel="label" optionValue="value" placeholder="— Chọn —" showClear />
                </div>
              </div>
            </div>

            <!-- Revenue -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-wallet" /> Doanh thu</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.lifecycle') }}</label>
                  <Select v-model="form.lifecycle_status" :options="statusOptions" optionLabel="label" optionValue="value" />
                </div>
                <div class="form-group">
                  <label>MRR</label>
                  <InputNumber v-model="form.mrr" mode="currency" currency="VND" locale="vi-VN" />
                </div>
                <div class="form-group">
                  <label>ARR</label>
                  <InputNumber v-model="form.arr" mode="currency" currency="VND" locale="vi-VN" />
                </div>
              </div>
            </div>

            <!-- Contract -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-file" /> Hợp đồng</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.start_date') }}</label>
                  <Calendar v-model="form.start_date" dateFormat="yy-mm-dd" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.contract_start') }}</label>
                  <Calendar v-model="form.contract_start" dateFormat="yy-mm-dd" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.contract_end') }}</label>
                  <Calendar v-model="form.contract_end" dateFormat="yy-mm-dd" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.contract_term') }}</label>
                  <Select v-model="form.contract_term" :options="termOptions" optionLabel="label" optionValue="value" placeholder="— Chọn —" />
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div class="form-section">
              <h4 class="section-label"><i class="pi pi-align-left" /> Ghi chú</h4>
              <div class="form-group">
                <Textarea v-model="form.notes" rows="3" placeholder="Ghi chú về khách hàng..." class="w-full" />
              </div>
            </div>

            <div class="form-actions">
              <Link href="/customers"><Button :label="t('common.cancel')" severity="secondary" text /></Link>
              <Button :label="t('common.create_customer')" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="sidebar-panel">
        <div class="tips-card">
          <div class="tips-header"><i class="pi pi-lightbulb" /> Lưu ý</div>
          <div class="tips-list">
            <div class="tip-item"><i class="pi pi-heart" /><span>Health Score được tự động tính dựa trên tương tác, MRR, và hợp đồng</span></div>
            <div class="tip-item"><i class="pi pi-dollar" /><span>MRR & ARR giúp theo dõi doanh thu recurring</span></div>
            <div class="tip-item"><i class="pi pi-calendar" /><span>Contract End giúp cảnh báo gia hạn hợp đồng</span></div>
            <div class="tip-item"><i class="pi pi-exclamation-triangle" /><span>Hệ thống tự phát hiện nguy cơ churn dựa trên health score</span></div>
          </div>
        </div>

        <div class="quick-card">
          <h4>Sau khi tạo</h4>
          <p>Bạn có thể thêm Support Tickets và Upsell Opportunities trong trang chỉnh sửa</p>
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
  props: { lifecycleStatuses: Object, renewalStatuses: Object, organizations: Array, contacts: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: this.$inertia.form({
        name: '', email: '', phone: '', organization_id: null, contact_id: null, assigned_to: null,
        lifecycle_status: 'onboarding', start_date: null, mrr: 0, arr: 0,
        contract_start: null, contract_end: null, contract_term: null, auto_renew: false, notes: '',
      }),
    }
  },
  computed: {
    orgOptions() { return this.organizations.map(o => ({ label: o.name, value: o.id })) },
    statusOptions() { return Object.entries(this.lifecycleStatuses).map(([v, l]) => ({ label: l, value: v })) },
    userOptions() { return this.salesUsers.map(u => ({ label: u.name, value: u.id })) },
    termOptions() { return [{ label: 'Hàng tháng', value: 'monthly' }, { label: 'Hàng quý', value: 'quarterly' }, { label: 'Hàng năm', value: 'yearly' }] },
  },
  methods: { store() { this.form.post('/customers') } },
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
.form-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #fef2f2, #fee2e2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #ef4444; }
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

.quick-card { background: linear-gradient(135deg, #fef2f2, #fff1f2); border-radius: 14px; padding: 1rem; border: 1px solid #fecaca; }
.quick-card h4 { font-size: 0.85rem; font-weight: 600; color: #b91c1c; margin: 0 0 0.3rem; }
.quick-card p { font-size: 0.72rem; color: #ef4444; margin: 0; line-height: 1.45; }

.w-full { width: 100%; }

@media (max-width: 1024px) { .create-layout { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-group.span-2 { grid-column: span 1; } }
</style>
