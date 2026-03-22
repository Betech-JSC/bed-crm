<template>
  <div>
    <Head title="SMTP Settings" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-envelope" />
        </div>
        <div>
          <h1 class="page-title">{{ t('common.smtp_settings') }}</h1>
          <p class="page-subtitle">Cấu hình máy chủ email gửi thông báo, xác nhận và tự động hóa</p>
        </div>
      </div>
      <div class="header-badges">
        <span v-if="smtpSetting?.is_active" class="status-chip status-active">
          <i class="pi pi-check-circle" /> Đang hoạt động
        </span>
        <span v-else class="status-chip status-inactive">
          <i class="pi pi-times-circle" /> Chưa kích hoạt
        </span>
      </div>
    </div>

    <!-- Main Form Card -->
    <div class="smtp-card">
      <div class="card-accent" />
      <form @submit.prevent="store" class="card-body">
        <!-- Server Settings -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-server section-icon" />
            <h3 class="section-title">Cấu hình máy chủ</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-2">
              <label>SMTP Host <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-globe input-icon" />
                <InputText v-model="form.host" placeholder="smtp.gmail.com" class="form-input-field" :class="{ 'p-invalid': form.errors.host }" />
              </div>
              <small v-if="form.errors.host" class="field-error">{{ form.errors.host }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Port <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-hashtag input-icon" />
                <InputNumber v-model="form.port" :min="1" :max="65535" placeholder="587" class="form-input-field" :class="{ 'p-invalid': form.errors.port }" />
              </div>
              <small v-if="form.errors.port" class="field-error">{{ form.errors.port }}</small>
              <small class="field-hint"><i class="pi pi-info-circle" /> 587 (TLS) · 465 (SSL) · 25 (SMTP)</small>
            </div>
          </div>
          <div class="form-group">
            <label>Encryption</label>
            <div class="encryption-chips">
              <button
                v-for="opt in encryptionOptions"
                :key="opt.value"
                type="button"
                class="encryption-chip"
                :class="{ active: form.encryption === opt.value }"
                @click="form.encryption = opt.value"
              >
                <i :class="opt.value === 'tls' ? 'pi pi-shield' : opt.value === 'ssl' ? 'pi pi-lock' : 'pi pi-minus'" />
                {{ opt.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Authentication -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-key section-icon" />
            <h3 class="section-title">Xác thực</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Username <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-user input-icon" />
                <InputText v-model="form.username" placeholder="your-email@gmail.com" class="form-input-field" :class="{ 'p-invalid': form.errors.username }" />
              </div>
              <small v-if="form.errors.username" class="field-error">{{ form.errors.username }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Password <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-lock input-icon" />
                <Password v-model="form.password" :feedback="false" toggleMask placeholder="Nhập mật khẩu..." class="form-input-field" :class="{ 'p-invalid': form.errors.password }" />
              </div>
              <small v-if="form.errors.password" class="field-error">{{ form.errors.password }}</small>
              <small v-if="smtpSetting && smtpSetting.password" class="field-hint">
                <i class="pi pi-info-circle" /> Để trống nếu giữ mật khẩu hiện tại
              </small>
            </div>
          </div>
        </div>

        <!-- Sender Info -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-send section-icon" />
            <h3 class="section-title">Thông tin người gửi</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Email gửi đi <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-at input-icon" />
                <InputText v-model="form.from_address" type="email" placeholder="noreply@company.vn" class="form-input-field" :class="{ 'p-invalid': form.errors.from_address }" />
              </div>
              <small v-if="form.errors.from_address" class="field-error">{{ form.errors.from_address }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Tên hiển thị</label>
              <div class="input-wrapper">
                <i class="pi pi-id-card input-icon" />
                <InputText v-model="form.from_name" placeholder="Tên công ty" class="form-input-field" :class="{ 'p-invalid': form.errors.from_name }" />
              </div>
              <small v-if="form.errors.from_name" class="field-error">{{ form.errors.from_name }}</small>
            </div>
          </div>
        </div>

        <!-- Activation & Actions -->
        <div class="form-section">
          <div class="activation-row">
            <div class="activation-info">
              <div class="activation-toggle">
                <InputSwitch v-model="form.is_active" inputId="is_active" />
                <label for="is_active" class="activation-label">Kích hoạt SMTP</label>
              </div>
              <span class="activation-desc">Bật để sử dụng cấu hình SMTP cho gửi email</span>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="form-footer">
          <div class="test-section">
            <div class="test-input-group">
              <i class="pi pi-envelope test-icon" />
              <input
                v-model="testEmail"
                type="email"
                placeholder="test@example.com"
                class="test-input"
              />
              <button
                type="button"
                class="test-btn"
                @click="sendTestEmail"
                :disabled="!testEmail || !form.is_active"
              >
                <i class="pi pi-send" /> Gửi test
              </button>
            </div>
          </div>
          <Button type="submit" label="Lưu cấu hình" icon="pi pi-check" :loading="form.processing" />
        </div>
      </form>
    </div>

    <!-- Info Banner -->
    <div class="info-banner">
      <i class="pi pi-info-circle" />
      <div>
        <strong>Hướng dẫn:</strong> Nếu dùng Gmail, bạn cần bật <strong>App Password</strong> (2-Step Verification → App passwords) 
        thay vì dùng mật khẩu Gmail trực tiếp. Host: <code>smtp.gmail.com</code>, Port: <code>587</code>, Encryption: <code>TLS</code>.
      </div>
    </div>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Password from 'primevue/password'
import InputSwitch from 'primevue/inputswitch'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, InputText, InputNumber, Password, InputSwitch, Button },
  layout: Layout,
  props: { smtpSetting: Object },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        host: this.smtpSetting?.host || '',
        port: this.smtpSetting?.port || 587,
        username: this.smtpSetting?.username || '',
        password: '',
        encryption: this.smtpSetting?.encryption || 'tls',
        from_address: this.smtpSetting?.from_address || '',
        from_name: this.smtpSetting?.from_name || '',
        is_active: this.smtpSetting?.is_active ?? true,
      }),
      testEmail: '',
      encryptionOptions: [
        { label: 'TLS', value: 'tls' },
        { label: 'SSL', value: 'ssl' },
        { label: 'None', value: null },
      ],
    }
  },
  methods: {
    store() {
      if (this.smtpSetting && this.form.password === '***') {
        this.form.password = ''
      }
      this.form.post('/smtp-settings', { forceFormData: true })
    },
    sendTestEmail() {
      if (!this.testEmail) return
      this.$inertia.post('/smtp-settings/test', {
        test_email: this.testEmail,
      }, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem;
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
.header-badges { display: flex; gap: 0.5rem; }
.status-chip {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.72rem; font-weight: 600; padding: 0.35rem 0.75rem;
  border-radius: 20px;
}
.status-chip i { font-size: 0.65rem; }
.status-active { background: #ecfdf5; color: #059669; }
.status-inactive { background: #fef2f2; color: #dc2626; }

/* ===== SMTP Card ===== */
.smtp-card {
  background: white; border-radius: 16px;
  border: 1.5px solid #e2e8f0; overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  margin-bottom: 1.25rem; max-width: 780px;
}
.card-accent {
  height: 4px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa, #6366f1);
  background-size: 200% 100%;
  animation: shimmer 3s ease infinite;
}
@keyframes shimmer {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}
.card-body { padding: 1.5rem; }

/* ===== Form Sections ===== */
.form-section {
  background: #fafbfc; border: 1px solid #f1f5f9;
  border-radius: 12px; padding: 1.15rem;
  margin-bottom: 1rem;
}
.section-header {
  display: flex; align-items: center; gap: 0.5rem;
  margin-bottom: 0.85rem; padding-bottom: 0.5rem;
  border-bottom: 1px solid #f1f5f9;
}
.section-icon { font-size: 0.85rem; color: #6366f1; }
.section-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; }

.form-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.form-group { margin-bottom: 0.75rem; min-width: 0; }
.flex-1 { flex: 1; min-width: 200px; }
.flex-2 { flex: 2; min-width: 240px; }

.form-group label {
  display: block; font-size: 0.72rem; font-weight: 600;
  color: #475569; margin-bottom: 0.35rem;
}
.req { color: #ef4444; }

.input-wrapper {
  display: flex; align-items: center;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: white; overflow: hidden; transition: all 0.2s;
}
.input-wrapper:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
}
.input-icon { padding: 0 0.55rem; color: #94a3b8; font-size: 0.72rem; flex-shrink: 0; }

.form-input-field { flex: 1; border: none !important; box-shadow: none !important; font-size: 0.82rem; }
.form-input-field :deep(input) { border: none !important; box-shadow: none !important; }

.field-error { display: block; font-size: 0.65rem; color: #ef4444; margin-top: 0.2rem; }
.field-hint {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.62rem; color: #94a3b8; margin-top: 0.25rem;
}
.field-hint i { font-size: 0.55rem; }

/* ===== Encryption Chips ===== */
.encryption-chips { display: flex; gap: 0.4rem; }
.encryption-chip {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.45rem 0.85rem; border-radius: 10px;
  border: 1.5px solid #e2e8f0; background: white;
  font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.encryption-chip i { font-size: 0.68rem; }
.encryption-chip:hover { border-color: #6366f1; color: #6366f1; }
.encryption-chip.active {
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  border-color: #6366f1; color: #4f46e5;
}

/* ===== Activation Row ===== */
.activation-row {
  display: flex; justify-content: space-between; align-items: center;
}
.activation-info { display: flex; flex-direction: column; gap: 0.25rem; }
.activation-toggle { display: flex; align-items: center; gap: 0.65rem; }
.activation-label { font-size: 0.82rem; font-weight: 600; color: #1e293b; cursor: pointer; }
.activation-desc { font-size: 0.65rem; color: #94a3b8; margin-left: 3.25rem; }

/* ===== Footer ===== */
.form-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}

.test-input-group {
  display: flex; align-items: center;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  overflow: hidden; background: white;
  transition: border-color 0.2s;
}
.test-input-group:focus-within { border-color: #6366f1; }
.test-icon { padding: 0 0.55rem; color: #94a3b8; font-size: 0.72rem; }
.test-input {
  border: none; outline: none; padding: 0.5rem 0.5rem 0.5rem 0;
  font-size: 0.78rem; color: #1e293b; font-family: inherit;
  min-width: 180px;
}
.test-input::placeholder { color: #cbd5e1; }
.test-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.5rem 0.85rem; background: #f8fafc;
  border: none; border-left: 1.5px solid #e2e8f0;
  font-size: 0.72rem; font-weight: 600; color: #6366f1;
  cursor: pointer; font-family: inherit;
  transition: all 0.2s;
}
.test-btn:hover:not(:disabled) { background: #eef2ff; }
.test-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.test-btn i { font-size: 0.65rem; }

/* ===== Info Banner ===== */
.info-banner {
  display: flex; align-items: flex-start; gap: 0.65rem;
  padding: 1rem 1.25rem; border-radius: 12px;
  background: linear-gradient(135deg, #eef2ff, #f5f3ff);
  border: 1px solid #e0e7ff;
  font-size: 0.82rem; color: #475569; line-height: 1.65;
  max-width: 780px;
}
.info-banner i { font-size: 1.1rem; color: #6366f1; margin-top: 0.15rem; flex-shrink: 0; }
.info-banner strong { color: #1e293b; }
.info-banner code {
  font-size: 0.72rem; background: #e0e7ff; color: #4f46e5;
  padding: 0.1rem 0.35rem; border-radius: 4px; font-family: monospace;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .form-row { flex-direction: column; }
  .flex-1, .flex-2 { min-width: 100%; }
  .form-footer { flex-direction: column; gap: 0.75rem; align-items: stretch; }
  .test-input-group { width: 100%; }
  .test-input { min-width: 0; flex: 1; }
}
</style>
