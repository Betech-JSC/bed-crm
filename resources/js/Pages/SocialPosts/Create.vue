<template>
  <div>
    <Head title="Tạo bài đăng" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/social-posts" class="breadcrumb-link">
        <i class="pi pi-arrow-left" /> Bài đăng
      </Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Tạo mới</span>
    </div>

    <!-- Layout -->
    <div class="create-layout">
      <!-- Main Form -->
      <div class="form-panel">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-icon"><i class="pi pi-send" /></div>
            <div>
              <h2>Tạo bài đăng</h2>
              <p>Chọn nội dung và tài khoản để đăng lên mạng xã hội</p>
            </div>
          </div>

          <form @submit.prevent="store">
            <!-- Content Item -->
            <div class="form-group">
              <label>Nội dung <span class="required">*</span></label>
              <Select
                v-model="form.content_item_id"
                :options="contentItems"
                optionLabel="title"
                optionValue="id"
                placeholder="Chọn nội dung đã tạo..."
                :class="{ 'p-invalid': form.errors.content_item_id }"
                class="w-full"
              >
                <template #option="{ option }">
                  <div class="option-item">
                    <span class="option-title">{{ option.title || 'Không tiêu đề' }}</span>
                    <span class="option-preview">{{ option.content }}</span>
                  </div>
                </template>
              </Select>
              <small v-if="form.errors.content_item_id" class="form-error">{{ form.errors.content_item_id }}</small>
            </div>

            <!-- Social Accounts -->
            <div class="form-group">
              <label>Tài khoản đăng <span class="required">*</span></label>
              <div v-if="socialAccounts.length" class="accounts-select">
                <label
                  v-for="account in socialAccounts"
                  :key="account.id"
                  class="account-checkbox"
                  :class="{ selected: form.social_account_ids.includes(account.id) }"
                >
                  <input
                    type="checkbox"
                    v-model="form.social_account_ids"
                    :value="account.id"
                  />
                  <div class="acc-icon" :style="{ background: getPlatformGradient(account.platform) }">
                    <i :class="getPlatformIcon(account.platform)" />
                  </div>
                  <div class="acc-info">
                    <span class="acc-name">{{ account.name }}</span>
                    <span class="acc-platform">{{ account.platform }}</span>
                  </div>
                  <i v-if="form.social_account_ids.includes(account.id)" class="pi pi-check acc-check" />
                </label>
              </div>
              <div v-else class="no-accounts-msg">
                <i class="pi pi-info-circle" />
                <span>Chưa kết nối tài khoản nào.</span>
                <Link href="/social-accounts/create" class="connect-link">Kết nối ngay</Link>
              </div>
              <small v-if="form.errors.social_account_ids" class="form-error">{{ form.errors.social_account_ids }}</small>
            </div>

            <!-- Content Override -->
            <div class="form-group">
              <label>Nội dung tùy chỉnh <span class="optional">(tuỳ chọn)</span></label>
              <textarea
                v-model="form.content"
                rows="4"
                class="form-textarea"
                placeholder="Để trống sẽ dùng nội dung từ content item đã chọn..."
              />
              <small v-if="form.errors.content" class="form-error">{{ form.errors.content }}</small>
            </div>

            <!-- Schedule -->
            <div class="form-group">
              <label>Lên lịch <span class="optional">(tuỳ chọn)</span></label>
              <div class="schedule-row">
                <button type="button" class="schedule-opt" :class="{ active: !scheduleMode }" @click="scheduleMode = false">
                  <i class="pi pi-bolt" /> Đăng ngay
                </button>
                <button type="button" class="schedule-opt" :class="{ active: scheduleMode }" @click="scheduleMode = true">
                  <i class="pi pi-clock" /> Hẹn giờ
                </button>
              </div>
              <Calendar
                v-if="scheduleMode"
                v-model="scheduledDate"
                showTime
                hourFormat="24"
                :minDate="new Date()"
                placeholder="Chọn ngày & giờ"
                class="w-full"
              />
            </div>

            <!-- Actions -->
            <div class="form-actions">
              <Link href="/social-posts">
                <Button label="Hủy" severity="secondary" text />
              </Link>
              <Button
                :label="scheduleMode ? '📅 Lên lịch' : '🚀 Đăng ngay'"
                :icon="scheduleMode ? 'pi pi-clock' : 'pi pi-send'"
                :loading="form.processing"
                type="submit"
                class="submit-btn"
              />
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar Tips -->
      <div class="sidebar-panel">
        <div class="tips-card">
          <div class="tips-header"><i class="pi pi-lightbulb" /> Lưu ý</div>
          <div class="tips-list">
            <div class="tip-item">
              <i class="pi pi-info-circle" />
              <span>Sử dụng <Link href="/content-studio" class="tip-link">Content Studio</Link> để AI tạo nội dung tự động</span>
            </div>
            <div class="tip-item">
              <i class="pi pi-clock" />
              <span>Lên lịch đăng vào giờ cao điểm (8-9h, 12h, 18-20h) để tăng tương tác</span>
            </div>
            <div class="tip-item">
              <i class="pi pi-image" />
              <span>Bài đăng có hình ảnh thu hút nhiều tương tác hơn 2-3 lần</span>
            </div>
            <div class="tip-item">
              <i class="pi pi-hashtag" />
              <span>Hashtags phù hợp giúp tăng reach trên Instagram và Twitter</span>
            </div>
          </div>
        </div>

        <!-- Quick Link -->
        <div class="quick-card">
          <h4>AI Content Studio</h4>
          <p>Tạo content + thumbnail bằng AI, tối ưu theo từng nền tảng</p>
          <Link href="/content-studio">
            <Button label="Mở Studio" icon="pi pi-palette" size="small" severity="secondary" outlined class="w-full" />
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Select, Calendar, Button },
  layout: Layout,
  props: {
    contentItems: Array,
    socialAccounts: Array,
  },
  data() {
    return {
      form: useForm({
        content_item_id: null,
        social_account_ids: [],
        content: '',
        scheduled_at: null,
      }),
      scheduledDate: null,
      scheduleMode: false,
    }
  },
  watch: {
    scheduledDate(val) { this.form.scheduled_at = val ? val.toISOString() : null },
  },
  methods: {
    store() { this.form.post('/social-posts') },
    getPlatformIcon(p) {
      return { facebook: 'pi pi-facebook', instagram: 'pi pi-instagram', linkedin: 'pi pi-linkedin', twitter: 'pi pi-twitter' }[p] || 'pi pi-globe'
    },
    getPlatformGradient(p) {
      return { facebook: 'linear-gradient(135deg, #1877F2, #0d65d9)', instagram: 'linear-gradient(135deg, #E4405F, #F77737)', linkedin: 'linear-gradient(135deg, #0A66C2, #004182)', twitter: 'linear-gradient(135deg, #1DA1F2, #0d8ecf)' }[p] || '#6366f1'
    },
  },
}
</script>

<style scoped>
/* ===== Breadcrumb ===== */
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* ===== Layout ===== */
.create-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.25rem; }

/* ===== Form Card ===== */
.form-card {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); padding: 1.5rem;
}
.form-card-header {
  display: flex; align-items: center; gap: 0.85rem;
  margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f8fafc;
}
.form-icon {
  width: 44px; height: 44px; border-radius: 14px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #6366f1;
}
.form-card-header h2 { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
.form-card-header p { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }

/* Form Groups */
.form-group { margin-bottom: 1.15rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 0.4rem; }
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; font-size: 0.68rem; }
.form-error { color: #ef4444; font-size: 0.68rem; margin-top: 0.25rem; display: block; }

.form-textarea {
  width: 100%; padding: 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.82rem; font-family: inherit; color: #1e293b; outline: none;
  resize: vertical; line-height: 1.55; transition: border-color 0.2s;
}
.form-textarea:focus { border-color: #6366f1; }
.form-textarea::placeholder { color: #94a3b8; }

.w-full { width: 100%; }

/* Accounts Select */
.accounts-select { display: flex; flex-direction: column; gap: 0.4rem; }
.account-checkbox {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.6rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  cursor: pointer; transition: all 0.2s;
}
.account-checkbox:hover { border-color: #6366f1; }
.account-checkbox.selected { border-color: #6366f1; background: #eef2ff; }
.account-checkbox input { display: none; }
.acc-icon {
  width: 32px; height: 32px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.78rem;
}
.acc-info { flex: 1; }
.acc-name { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; }
.acc-platform { font-size: 0.62rem; color: #94a3b8; text-transform: capitalize; }
.acc-check { color: #6366f1; font-size: 0.72rem; }

.no-accounts-msg {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.75rem; border-radius: 10px; background: #fffbeb;
  font-size: 0.78rem; color: #d97706;
}
.no-accounts-msg i { font-size: 0.78rem; }
.connect-link { color: #6366f1; font-weight: 600; text-decoration: none; }

/* Schedule */
.schedule-row { display: flex; gap: 0.35rem; margin-bottom: 0.65rem; }
.schedule-opt {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.35rem;
  padding: 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: white; font-size: 0.78rem; cursor: pointer; transition: all 0.2s;
}
.schedule-opt.active { border-color: #6366f1; background: #eef2ff; color: #6366f1; font-weight: 600; }
.schedule-opt i { font-size: 0.72rem; }

/* Options */
.option-item {}
.option-title { font-weight: 600; display: block; }
.option-preview { font-size: 0.72rem; color: #94a3b8; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 350px; }

/* Actions */
.form-actions {
  display: flex; justify-content: flex-end; gap: 0.5rem;
  padding-top: 1rem; margin-top: 0.5rem; border-top: 1px solid #f8fafc;
}
.submit-btn {
  border-radius: 10px !important;
}

/* ===== Sidebar ===== */
.sidebar-panel { display: flex; flex-direction: column; gap: 0.85rem; }

.tips-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
}
.tips-header {
  padding: 0.75rem 1rem; border-bottom: 1px solid #f8fafc;
  font-size: 0.82rem; font-weight: 600; color: #1e293b;
  display: flex; align-items: center; gap: 0.35rem;
}
.tips-header i { color: #f59e0b; }
.tips-list { padding: 0.75rem 1rem; }
.tip-item {
  display: flex; gap: 0.5rem; padding: 0.4rem 0;
  font-size: 0.72rem; color: #64748b; line-height: 1.45;
}
.tip-item i { color: #94a3b8; font-size: 0.62rem; margin-top: 0.2rem; flex-shrink: 0; }
.tip-link { color: #6366f1; font-weight: 600; text-decoration: none; }

.quick-card {
  background: linear-gradient(135deg, #eef2ff, #f5f3ff);
  border-radius: 14px; padding: 1rem; border: 1px solid #e0e7ff;
}
.quick-card h4 { font-size: 0.85rem; font-weight: 600; color: #4338ca; margin: 0 0 0.3rem; }
.quick-card p { font-size: 0.72rem; color: #6366f1; margin: 0 0 0.75rem; line-height: 1.4; }

/* ===== Responsive ===== */
@media (max-width: 1024px) { .create-layout { grid-template-columns: 1fr; } }
</style>
