<template>
  <div>
    <Head title="Tạo người dùng" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <Link href="/users" class="back-btn" v-tooltip="'Quay lại'">
          <i class="pi pi-arrow-left" />
        </Link>
        <div>
          <h1 class="page-title">Tạo người dùng mới</h1>
          <p class="page-subtitle">Thêm thành viên mới vào hệ thống</p>
        </div>
      </div>
    </div>

    <!-- Form Card -->
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="store" class="card-body">
        <!-- Personal Info -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-user section-icon" />
            <h3 class="section-title">Thông tin cá nhân</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Họ <span class="req">*</span></label>
              <InputText v-model="form.first_name" class="w-full" placeholder="Nguyễn" :class="{ 'p-invalid': form.errors.first_name }" />
              <small v-if="form.errors.first_name" class="field-error">{{ form.errors.first_name }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Tên <span class="req">*</span></label>
              <InputText v-model="form.last_name" class="w-full" placeholder="Văn A" :class="{ 'p-invalid': form.errors.last_name }" />
              <small v-if="form.errors.last_name" class="field-error">{{ form.errors.last_name }}</small>
            </div>
          </div>
          <div class="form-group">
            <label>Ảnh đại diện</label>
            <div class="photo-upload">
              <div class="photo-placeholder">
                <i class="pi pi-camera" />
              </div>
              <div class="photo-actions">
                <input type="file" ref="photoInput" accept="image/*" @change="form.photo = $event.target.files[0]" class="hidden" />
                <Button label="Tải ảnh" icon="pi pi-upload" severity="secondary" outlined size="small" @click="$refs.photoInput.click()" />
                <span class="photo-hint">JPG, PNG — tối đa 2MB</span>
              </div>
            </div>
            <small v-if="form.errors.photo" class="field-error">{{ form.errors.photo }}</small>
          </div>
        </div>

        <!-- Account Info -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-lock section-icon" />
            <h3 class="section-title">Tài khoản</h3>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Email <span class="req">*</span></label>
              <InputText v-model="form.email" type="email" class="w-full" placeholder="email@company.vn" :class="{ 'p-invalid': form.errors.email }" />
              <small v-if="form.errors.email" class="field-error">{{ form.errors.email }}</small>
            </div>
            <div class="form-group flex-1">
              <label>Mật khẩu <span class="req">*</span></label>
              <Password v-model="form.password" toggleMask :feedback="false" class="w-full" placeholder="Tối thiểu 8 ký tự" :class="{ 'p-invalid': form.errors.password }" />
              <small v-if="form.errors.password" class="field-error">{{ form.errors.password }}</small>
            </div>
          </div>
        </div>

        <!-- Role -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-shield section-icon" />
            <h3 class="section-title">Vai trò</h3>
          </div>
          <div class="role-options">
            <div
              class="role-option"
              :class="{ active: !form.owner }"
              @click="form.owner = false"
            >
              <div class="role-icon member"><i class="pi pi-user" /></div>
              <div class="role-info">
                <span class="role-label">User</span>
                <span class="role-desc">Quyền hạn cơ bản, truy cập theo phân quyền</span>
              </div>
              <i v-if="!form.owner" class="pi pi-check-circle role-check" />
            </div>
            <div
              class="role-option"
              :class="{ active: form.owner }"
              @click="form.owner = true"
            >
              <div class="role-icon owner"><i class="pi pi-crown" /></div>
              <div class="role-info">
                <span class="role-label">Owner</span>
                <span class="role-desc">Toàn quyền quản trị, cấu hình hệ thống</span>
              </div>
              <i v-if="form.owner" class="pi pi-check-circle role-check" />
            </div>
          </div>
          <small v-if="form.errors.owner" class="field-error">{{ form.errors.owner }}</small>
        </div>

        <!-- Footer -->
        <div class="form-footer">
          <Link href="/users">
            <Button label="Hủy" severity="secondary" outlined />
          </Link>
          <Button type="submit" :label="t('common.create_user')" icon="pi pi-check" :loading="form.processing" />
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, Password, Button },
  layout: Layout,
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: this.$inertia.form({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        owner: false,
        photo: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/users')
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display: flex; align-items: center; margin-bottom: 1.5rem; }
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

/* ===== Form Card ===== */
.form-card {
  background: white; border-radius: 16px; border: 1.5px solid #e2e8f0;
  overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); max-width: 720px;
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
.hidden { display: none; }

/* Photo Upload */
.photo-upload { display: flex; align-items: center; gap: 1rem; }
.photo-placeholder {
  width: 56px; height: 56px; border-radius: 14px;
  background: linear-gradient(135deg, #e0e7ff, #eef2ff);
  display: flex; align-items: center; justify-content: center;
  color: #6366f1; font-size: 1.15rem;
}
.photo-actions { display: flex; flex-direction: column; gap: 0.3rem; }
.photo-hint { font-size: 0.62rem; color: #94a3b8; }

/* Role Options */
.role-options { display: flex; gap: 0.65rem; flex-wrap: wrap; }
.role-option {
  flex: 1; min-width: 220px;
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem; border-radius: 12px;
  border: 1.5px solid #e2e8f0; background: white;
  cursor: pointer; transition: all 0.2s;
}
.role-option:hover { border-color: #c7d2fe; }
.role-option.active {
  border-color: #6366f1;
  background: linear-gradient(135deg, #fafbff, #eef2ff);
  box-shadow: 0 2px 8px rgba(99,102,241,0.08);
}
.role-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; flex-shrink: 0;
}
.role-icon.member { background: #eef2ff; color: #6366f1; }
.role-icon.owner { background: #fef3c7; color: #d97706; }
.role-info { flex: 1; }
.role-label { font-size: 0.82rem; font-weight: 700; color: #1e293b; display: block; }
.role-desc { font-size: 0.62rem; color: #94a3b8; display: block; margin-top: 0.1rem; }
.role-check { color: #6366f1; font-size: 0.92rem; }

/* Footer */
.form-footer {
  display: flex; justify-content: flex-end; gap: 0.5rem;
  padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .form-row { flex-direction: column; }
  .flex-1 { min-width: 100%; }
  .role-options { flex-direction: column; }
  .role-option { min-width: 100%; }
}
</style>
