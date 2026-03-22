<template>
  <div>
    <Head title="Social Platforms" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-share-alt" />
        </div>
        <div>
          <h1 class="page-title">Cấu hình Social Platforms</h1>
          <p class="page-subtitle">Thiết lập OAuth cho từng nền tảng mạng xã hội</p>
        </div>
      </div>
      <Link href="/social-accounts">
        <Button label="Xem tài khoản" icon="pi pi-arrow-right" severity="secondary" outlined size="small" />
      </Link>
    </div>

    <!-- Platform Cards -->
    <div class="platforms-grid">
      <div
        v-for="(config, key) in platforms"
        :key="key"
        class="platform-card"
        :class="{
          'platform-card--configured': config.is_configured,
          'platform-card--active': config.is_active,
        }"
      >
        <!-- Header Bar -->
        <div class="card-top-bar" :style="{ background: config.is_configured ? config.gradient : '#e2e8f0' }" />

        <div class="card-body">
          <!-- Top Section -->
          <div class="card-header">
            <div class="platform-icon" :style="{ background: config.gradient }">
              <i :class="config.icon" />
            </div>
            <div class="card-header-info">
              <h3>{{ config.label }}</h3>
              <p>{{ config.description }}</p>
            </div>
            <!-- Status -->
            <div class="platform-status">
              <span v-if="config.is_configured && config.is_active" class="status-chip status-chip--active">
                <i class="pi pi-check-circle" /> Hoạt động
              </span>
              <span v-else-if="config.is_configured" class="status-chip status-chip--inactive">
                <i class="pi pi-pause" /> Tạm dừng
              </span>
              <span v-else class="status-chip status-chip--unconfigured">
                <i class="pi pi-cog" /> Chưa cấu hình
              </span>
            </div>
          </div>

          <!-- Config Form -->
          <div class="config-form">
            <!-- Client ID -->
            <div class="form-group">
              <label>Client ID / App ID <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-key input-icon" />
                <input
                  v-model="formData[key].client_id"
                  :placeholder="`Nhập ${config.label} Client ID...`" class="form-input"
                />
              </div>
            </div>

            <!-- Client Secret -->
            <div class="form-group">
              <label>Client Secret / App Secret <span class="req">*</span></label>
              <div class="input-wrapper">
                <i class="pi pi-lock input-icon" />
                <input
                  v-model="formData[key].client_secret"
                  :type="showSecrets[key] ? 'text' : 'password'"
                  :placeholder="config.has_secret ? '••••••••••••••••' : `Nhập ${config.label} Secret...`"
                  class="form-input"
                />
                <button class="toggle-secret" @click="showSecrets[key] = !showSecrets[key]">
                  <i :class="showSecrets[key] ? 'pi pi-eye-slash' : 'pi pi-eye'" />
                </button>
              </div>
            </div>

            <!-- Redirect URI -->
            <div class="form-group">
              <label>Redirect URI</label>
              <div class="input-wrapper input-wrapper--readonly">
                <i class="pi pi-link input-icon" />
                <input
                  v-model="formData[key].redirect_uri"
                  class="form-input" readonly
                />
                <button class="copy-btn" @click="copyUri(key)" :title="'Copy'">
                  <i class="pi pi-copy" />
                </button>
              </div>
              <small class="form-hint">Thêm URI này vào OAuth settings của {{ config.label }}</small>
            </div>

            <!-- Scopes -->
            <div class="form-group">
              <label>Scopes</label>
              <div class="scopes-list">
                <span v-for="(scope, i) in formData[key].scopes" :key="i" class="scope-chip">
                  {{ scope }}
                  <button class="scope-remove" @click="removeScope(key, i)"><i class="pi pi-times" /></button>
                </span>
                <input
                  v-model="newScope[key]"
                  class="scope-input"
                  placeholder="Thêm scope..."
                  @keydown.enter.prevent="addScope(key)"
                />
              </div>
            </div>

            <!-- Actions -->
            <div class="card-actions">
              <Button
                :label="config.is_configured ? 'Cập nhật' : 'Lưu cấu hình'"
                :icon="config.is_configured ? 'pi pi-check' : 'pi pi-save'"
                size="small"
                @click="savePlatform(key)"
                :loading="saving[key]"
              />
              <Button
                v-if="config.is_configured"
                :label="config.is_active ? 'Tắt' : 'Bật'"
                :icon="config.is_active ? 'pi pi-pause' : 'pi pi-play'"
                :severity="config.is_active ? 'warning' : 'success'"
                size="small" outlined
                @click="togglePlatform(key)"
              />
              <Button
                v-if="config.is_configured"
                icon="pi pi-trash" severity="danger" size="small" text
                @click="deletePlatform(key)"
              />
            </div>
          </div>

          <!-- Setup Guide (collapsible) -->
          <div class="setup-guide">
            <button class="guide-toggle" @click="toggleGuide(key)">
              <i class="pi pi-question-circle" />
              <span>Hướng dẫn thiết lập {{ config.label }}</span>
              <i :class="openGuides[key] ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="guide-arrow" />
            </button>
            <div v-if="openGuides[key]" class="guide-content">
              <ol>
                <li v-for="(step, i) in config.setup_steps" :key="i">{{ step }}</li>
              </ol>
              <a :href="config.docs_url" target="_blank" class="docs-link">
                <i class="pi pi-external-link" /> Mở {{ config.label }} Developer Console
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Info -->
    <div class="security-banner">
      <i class="pi pi-shield" />
      <div>
        <h4>Bảo mật thông tin</h4>
        <p>Client Secret được mã hóa (AES-256-CBC) trước khi lưu vào database. Không ai có thể xem được secret đã lưu.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: { platforms: Object },
  data() {
    const formData = {}
    const showSecrets = {}
    const newScope = {}
    const openGuides = {}
    const saving = {}

    for (const [key, config] of Object.entries(this.platforms)) {
      formData[key] = {
        client_id: config.client_id || '',
        client_secret: '',
        redirect_uri: config.redirect_uri || config.default_redirect_uri || '',
        scopes: [...(config.scopes || config.default_scopes || [])],
      }
      showSecrets[key] = false
      newScope[key] = ''
      openGuides[key] = false
      saving[key] = false
    }

    return { formData, showSecrets, newScope, openGuides, saving }
  },
  methods: {
    savePlatform(platform) {
      const data = this.formData[platform]
      if (!data.client_id) {
        alert('Vui lòng nhập Client ID')
        return
      }
      // If secret is empty and already configured, skip validation
      if (!data.client_secret && !this.platforms[platform].has_secret) {
        alert('Vui lòng nhập Client Secret')
        return
      }

      this.saving[platform] = true

      const payload = {
        platform,
        client_id: data.client_id,
        client_secret: data.client_secret || 'UNCHANGED',
        redirect_uri: data.redirect_uri,
        scopes: data.scopes,
        is_active: true,
      }

      router.post('/social-platforms', payload, {
        preserveScroll: true,
        onFinish: () => { this.saving[platform] = false },
      })
    },

    togglePlatform(platform) {
      router.post(`/social-platforms/${platform}/toggle`, {}, { preserveScroll: true })
    },

    deletePlatform(platform) {
      if (confirm(`Xóa cấu hình ${this.platforms[platform]?.label}? Hành động này không thể hoàn tác.`)) {
        router.delete(`/social-platforms/${platform}`, { preserveScroll: true })
      }
    },

    toggleGuide(key) {
      this.openGuides[key] = !this.openGuides[key]
    },

    addScope(key) {
      const val = this.newScope[key]?.trim()
      if (val && !this.formData[key].scopes.includes(val)) {
        this.formData[key].scopes.push(val)
      }
      this.newScope[key] = ''
    },

    removeScope(key, index) {
      this.formData[key].scopes.splice(index, 1)
    },

    copyUri(key) {
      navigator.clipboard.writeText(this.formData[key].redirect_uri)
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
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }

/* ===== Platforms Grid ===== */
.platforms-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem; }

.platform-card {
  background: white; border-radius: 16px; border: 1.5px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
  transition: all 0.25s;
}
.platform-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
.platform-card--configured { border-color: #d1fae5; }
.platform-card--active { border-color: #a7f3d0; }

.card-top-bar { height: 4px; }

.card-body { padding: 1.15rem; }

/* Card Header */
.card-header { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1.15rem; }
.platform-icon {
  width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem; box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}
.card-header-info { flex: 1; min-width: 0; }
.card-header-info h3 { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
.card-header-info p { font-size: 0.72rem; color: #94a3b8; margin: 0.1rem 0 0; }

.platform-status { flex-shrink: 0; }
.status-chip {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.6rem; font-weight: 600; padding: 0.18rem 0.45rem; border-radius: 6px;
}
.status-chip i { font-size: 0.5rem; }
.status-chip--active { background: #ecfdf5; color: #059669; }
.status-chip--inactive { background: #fffbeb; color: #d97706; }
.status-chip--unconfigured { background: #f1f5f9; color: #64748b; }

/* Config Form */
.form-group { margin-bottom: 0.75rem; }
.form-group label {
  display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;
}
.req { color: #ef4444; }

.input-wrapper {
  display: flex; align-items: center;
  border: 1.5px solid #e2e8f0; border-radius: 9px; overflow: hidden;
  transition: border-color 0.2s;
}
.input-wrapper:focus-within { border-color: #6366f1; }
.input-wrapper--readonly { background: #f8fafc; }
.input-icon { padding: 0 0.55rem; color: #94a3b8; font-size: 0.72rem; }

.form-input {
  flex: 1; border: none; outline: none; padding: 0.5rem 0.55rem 0.5rem 0;
  font-size: 0.78rem; color: #1e293b; font-family: inherit; background: transparent;
}
.form-input::placeholder { color: #cbd5e1; }

.toggle-secret, .copy-btn {
  border: none; background: none; cursor: pointer; padding: 0 0.55rem;
  color: #94a3b8; transition: color 0.15s;
}
.toggle-secret:hover, .copy-btn:hover { color: #6366f1; }

.form-hint { font-size: 0.62rem; color: #94a3b8; margin-top: 0.2rem; display: block; }

/* Scopes */
.scopes-list {
  display: flex; flex-wrap: wrap; gap: 0.3rem;
  padding: 0.35rem; border: 1.5px solid #e2e8f0; border-radius: 9px;
}
.scope-chip {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.62rem; font-weight: 500; color: #6366f1; background: #eef2ff;
  padding: 0.15rem 0.35rem; border-radius: 5px;
}
.scope-remove {
  border: none; background: none; cursor: pointer; color: #94a3b8;
  padding: 0; font-size: 0.5rem; display: flex;
}
.scope-remove:hover { color: #ef4444; }
.scope-input {
  border: none; outline: none; font-size: 0.72rem; color: #1e293b;
  flex: 1; min-width: 100px; padding: 0.2rem 0.3rem; font-family: inherit;
}
.scope-input::placeholder { color: #cbd5e1; }

/* Card Actions */
.card-actions {
  display: flex; gap: 0.4rem; margin-top: 0.85rem;
  padding-top: 0.75rem; border-top: 1px solid #f8fafc;
}

/* Setup Guide */
.setup-guide {
  margin-top: 0.75rem; border-top: 1px solid #f8fafc; padding-top: 0.55rem;
}
.guide-toggle {
  display: flex; align-items: center; gap: 0.35rem; width: 100%;
  border: none; background: none; cursor: pointer; padding: 0.3rem 0;
  font-size: 0.72rem; color: #6366f1; font-family: inherit;
}
.guide-toggle i { font-size: 0.65rem; }
.guide-arrow { margin-left: auto; }

.guide-content {
  padding: 0.65rem; margin-top: 0.45rem; background: #f8fafc; border-radius: 8px;
}
.guide-content ol {
  padding-left: 1.15rem; margin: 0 0 0.5rem;
}
.guide-content li {
  font-size: 0.68rem; color: #475569; line-height: 1.5; margin-bottom: 0.3rem;
}
.docs-link {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.68rem; font-weight: 600; color: #6366f1; text-decoration: none;
}
.docs-link:hover { opacity: 0.8; }
.docs-link i { font-size: 0.6rem; }

/* ===== Security ===== */
.security-banner {
  display: flex; gap: 0.75rem; padding: 1rem 1.25rem;
  background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;
}
.security-banner > i { color: #10b981; font-size: 1.15rem; margin-top: 0.1rem; }
.security-banner h4 { font-size: 0.78rem; font-weight: 600; color: #166534; margin: 0 0 0.15rem; }
.security-banner p { font-size: 0.72rem; color: #15803d; margin: 0; line-height: 1.45; }

/* ===== Responsive ===== */
@media (max-width: 1024px) { .platforms-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
}
</style>
