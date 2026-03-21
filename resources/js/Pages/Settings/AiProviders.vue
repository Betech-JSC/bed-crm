<template>
  <div>
    <Head title="AI Providers" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-sparkles" />
        </div>
        <div>
          <h1 class="page-title">AI Providers</h1>
          <p class="page-subtitle">Quản lý và cấu hình các model AI tích hợp vào CRM</p>
        </div>
      </div>
      <div class="header-stats">
        <div class="stat-chip active">
          <i class="pi pi-check-circle" />
          <span>{{ activeCount }} Active</span>
        </div>
        <div class="stat-chip total">
          <i class="pi pi-box" />
          <span>{{ providers.length }}/{{ Object.keys(registry).length }} Providers</span>
        </div>
      </div>
    </div>

    <!-- Provider Grid -->
    <div class="providers-grid">
      <div
        v-for="(meta, slug) in registry"
        :key="slug"
        class="provider-card"
        :class="{
          'is-active': getProvider(slug)?.is_active,
          'is-default': getProvider(slug)?.is_default,
          'is-error': getProvider(slug)?.status === 'error',
        }"
      >
        <!-- Card Header -->
        <div class="card-header">
          <div class="provider-identity">
            <div class="provider-logo" :style="{ background: meta.color + '15', color: meta.color }">
              <svg v-if="slug === 'gemini'" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm0 2.4c5.3 0 9.6 4.3 9.6 9.6s-4.3 9.6-9.6 9.6-9.6-4.3-9.6-9.6 4.3-9.6 9.6-9.6zm0 2.4a7.2 7.2 0 100 14.4 7.2 7.2 0 000-14.4z"/></svg>
              <svg v-else-if="slug === 'openai'" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22.2 8.4c-.5-1.4-1.6-2.6-3-3.2-1.7-.8-3.7-.6-5.3.4-.8-.9-2-1.5-3.2-1.5-1.5 0-2.9.7-3.7 1.9C5.1 6.3 3.8 7.5 3.3 9c-.5 1.4-.3 3 .5 4.3-.3.7-.4 1.5-.4 2.3 0 1.5.7 2.9 1.9 3.7 1.4 1 3.1 1.2 4.7.6.8.9 2 1.5 3.2 1.5 1.5 0 2.9-.7 3.7-1.9 1.9-.3 3.2-1.5 3.7-3 .5-1.4.3-3-.4-4.1z"/></svg>
              <svg v-else-if="slug === 'claude'" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
              <i v-else class="pi pi-bolt" />
            </div>
            <div>
              <h3 class="provider-name">{{ meta.display_name }}</h3>
              <span class="provider-slug">{{ slug }}</span>
            </div>
          </div>
          <div class="card-badges">
            <span v-if="getProvider(slug)?.is_default" class="badge badge-default">
              <i class="pi pi-star-fill" /> Mặc định
            </span>
            <span v-if="getProvider(slug)?.is_active" class="badge badge-active">Active</span>
            <span v-if="getProvider(slug)?.status === 'connected'" class="badge badge-connected">
              <i class="pi pi-check" /> Connected
            </span>
            <span v-if="getProvider(slug)?.status === 'error'" class="badge badge-error">
              <i class="pi pi-times" /> Error
            </span>
          </div>
        </div>

        <!-- Card Body -->
        <div class="card-body">
          <!-- API Key -->
          <div class="field-group">
            <label class="field-label">
              <i class="pi pi-key" /> API Key
            </label>
            <div class="key-input-group">
              <input
                :type="showKeys[slug] ? 'text' : 'password'"
                :value="forms[slug]?.api_key ?? ''"
                @input="updateForm(slug, 'api_key', $event.target.value)"
                placeholder="Nhập API Key..."
                class="field-input"
              />
              <button class="key-toggle" @click="showKeys[slug] = !showKeys[slug]">
                <i :class="showKeys[slug] ? 'pi pi-eye-slash' : 'pi pi-eye'" />
              </button>
            </div>
            <span v-if="getProvider(slug)?.has_api_key && !forms[slug]?.api_key" class="key-status connected">
              <i class="pi pi-lock" /> Key đã được lưu
            </span>
          </div>

          <!-- Model Selection -->
          <div class="field-group">
            <label class="field-label">
              <i class="pi pi-microchip-ai" /> Model
            </label>
            <select
              :value="forms[slug]?.model ?? meta.default_model"
              @change="updateForm(slug, 'model', $event.target.value)"
              class="field-input"
            >
              <option v-for="(label, modelId) in meta.models" :key="modelId" :value="modelId">
                {{ label }}
              </option>
            </select>
          </div>

          <!-- Features -->
          <div class="feature-tags">
            <span class="feature-tag">
              <i class="pi pi-comments" /> Chat
            </span>
            <span v-if="meta.supports_embed" class="feature-tag">
              <i class="pi pi-database" /> Embeddings
            </span>
            <span class="feature-tag">
              <i class="pi pi-list" /> {{ Object.keys(meta.models).length }} models
            </span>
          </div>

          <!-- Usage Stats (if configured) -->
          <div v-if="getProvider(slug)?.total_requests > 0" class="usage-stats">
            <div class="usage-item">
              <span class="usage-label">Requests</span>
              <span class="usage-value">{{ formatNumber(getProvider(slug).total_requests) }}</span>
            </div>
            <div class="usage-item">
              <span class="usage-label">Tokens</span>
              <span class="usage-value">{{ formatNumber(getProvider(slug).total_tokens) }}</span>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="getProvider(slug)?.last_error" class="error-banner">
            <i class="pi pi-exclamation-triangle" />
            <span>{{ getProvider(slug).last_error }}</span>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer">
          <div class="footer-left">
            <button
              class="btn btn-save"
              @click="saveProvider(slug)"
              :disabled="saving[slug]"
            >
              <i :class="saving[slug] ? 'pi pi-spin pi-spinner' : 'pi pi-save'" />
              {{ saving[slug] ? 'Đang lưu...' : 'Lưu' }}
            </button>
            <button
              class="btn btn-test"
              @click="testConnection(slug)"
              :disabled="testing[slug] || !getProvider(slug)?.has_api_key"
            >
              <i :class="testing[slug] ? 'pi pi-spin pi-spinner' : 'pi pi-play'" />
              {{ testing[slug] ? 'Testing...' : 'Test' }}
            </button>
          </div>
          <div class="footer-right">
            <button
              v-if="getProvider(slug) && !getProvider(slug).is_default"
              class="btn btn-default"
              @click="setDefault(slug)"
              title="Đặt làm mặc định"
            >
              <i class="pi pi-star" />
            </button>
            <button
              v-if="getProvider(slug)"
              class="btn btn-toggle"
              :class="getProvider(slug).is_active ? 'active' : 'inactive'"
              @click="toggleProvider(slug)"
              :title="getProvider(slug).is_active ? 'Tắt' : 'Bật'"
            >
              <i :class="getProvider(slug).is_active ? 'pi pi-check' : 'pi pi-times'" />
            </button>
          </div>
        </div>

        <!-- Test Result Toast -->
        <div v-if="testResults[slug]" class="test-toast" :class="testResults[slug].success ? 'success' : 'error'">
          <i :class="testResults[slug].success ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
          <span>{{ testResults[slug].message }}</span>
          <button class="toast-close" @click="testResults[slug] = null"><i class="pi pi-times" /></button>
        </div>
      </div>
    </div>

    <!-- Info Banner -->
    <div class="info-banner">
      <i class="pi pi-info-circle" />
      <div>
        <strong>Cách hoạt động:</strong> AI Gateway tự động chọn provider mặc định. Nếu provider mặc định lỗi, hệ thống sẽ tự chuyển sang provider khác (fallback). 
        Bạn cần ít nhất 1 provider active để sử dụng các tính năng AI.
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: {
    providers: { type: Array, default: () => [] },
    registry: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      forms: this.initForms(),
      showKeys: {},
      saving: {},
      testing: {},
      testResults: {},
    }
  },
  computed: {
    activeCount() {
      return this.providers.filter(p => p.is_active).length
    },
  },
  methods: {
    initForms() {
      const forms = {}
      for (const slug of Object.keys(this.registry)) {
        const existing = this.providers.find(p => p.slug === slug)
        forms[slug] = {
          api_key: '',
          model: existing?.model || this.registry[slug]?.default_model || '',
          is_active: existing?.is_active ?? false,
        }
      }
      return forms
    },
    getProvider(slug) {
      return this.providers.find(p => p.slug === slug)
    },
    updateForm(slug, field, value) {
      if (!this.forms[slug]) {
        this.forms[slug] = { api_key: '', model: '', is_active: false }
      }
      this.forms[slug][field] = value
    },
    saveProvider(slug) {
      this.saving[slug] = true
      const form = this.forms[slug] || {}

      router.post('/ai-providers', {
        slug,
        api_key: form.api_key || null,
        model: form.model,
        is_active: true,
      }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
          this.saving[slug] = false
          this.forms[slug].api_key = ''
        },
      })
    },
    async testConnection(slug) {
      const provider = this.getProvider(slug)
      if (!provider) return

      this.testing[slug] = true
      this.testResults[slug] = null

      try {
        const response = await fetch(`/ai-providers/${provider.id}/test`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        })
        const data = await response.json()
        this.testResults[slug] = data

        // Auto-hide after 5 seconds
        setTimeout(() => { this.testResults[slug] = null }, 5000)
      } catch (e) {
        this.testResults[slug] = { success: false, message: 'Lỗi kết nối: ' + e.message }
      } finally {
        this.testing[slug] = false
      }
    },
    setDefault(slug) {
      const provider = this.getProvider(slug)
      if (!provider) return
      router.post(`/ai-providers/${provider.id}/default`, {}, {
        preserveState: true,
        preserveScroll: true,
      })
    },
    toggleProvider(slug) {
      const provider = this.getProvider(slug)
      if (!provider) return
      router.post(`/ai-providers/${provider.id}/toggle`, {}, {
        preserveState: true,
        preserveScroll: true,
      })
    },
    formatNumber(num) {
      if (num >= 1_000_000) return (num / 1_000_000).toFixed(1) + 'M'
      if (num >= 1_000) return (num / 1_000).toFixed(1) + 'K'
      return num?.toString() || '0'
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-stats { display: flex; gap: 0.5rem; }
.stat-chip {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; border-radius: 20px;
  font-size: 0.72rem; font-weight: 600;
}
.stat-chip.active { background: #ecfdf5; color: #059669; }
.stat-chip.total { background: #eef2ff; color: #6366f1; }

/* ===== Providers Grid ===== */
.providers-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 1.25rem; margin-bottom: 1.5rem;
}

/* ===== Provider Card ===== */
.provider-card {
  background: white; border-radius: 16px;
  border: 2px solid #e2e8f0;
  overflow: hidden; position: relative;
  transition: all 0.3s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.provider-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 8px 25px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.provider-card.is-active {
  border-color: #6366f1;
  box-shadow: 0 4px 20px rgba(99,102,241,0.12);
}
.provider-card.is-default {
  border-color: #6366f1;
  box-shadow: 0 4px 20px rgba(99,102,241,0.18);
}
.provider-card.is-default::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
}
.provider-card.is-error { border-color: #fca5a5; }

/* Card Header */
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.provider-identity { display: flex; align-items: center; gap: 0.65rem; }
.provider-logo {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; transition: transform 0.2s;
}
.provider-card:hover .provider-logo { transform: scale(1.08); }
.provider-name { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; }
.provider-slug { font-size: 0.65rem; color: #94a3b8; font-family: monospace; }

.card-badges { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.badge {
  font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.4rem;
  border-radius: 6px; display: flex; align-items: center; gap: 0.2rem;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-default { background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; }
.badge-active { background: #ecfdf5; color: #059669; }
.badge-connected { background: #ecfdf5; color: #059669; }
.badge-error { background: #fef2f2; color: #dc2626; }

/* Card Body */
.card-body { padding: 1.25rem; }
.field-group { margin-bottom: 1rem; }
.field-label {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.72rem; font-weight: 600; color: #475569;
  margin-bottom: 0.4rem;
}
.field-label i { font-size: 0.65rem; color: #6366f1; }
.field-input {
  width: 100%; padding: 0.55rem 0.75rem;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.82rem; color: #334155;
  background: #f8fafc; transition: all 0.2s;
  outline: none;
}
.field-input:focus {
  border-color: #6366f1; background: white;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.field-input::placeholder { color: #94a3b8; }
select.field-input { cursor: pointer; }

.key-input-group { position: relative; }
.key-input-group .field-input { padding-right: 2.5rem; font-family: monospace; font-size: 0.78rem; }
.key-toggle {
  position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.3rem;
  font-size: 0.82rem; transition: color 0.2s;
}
.key-toggle:hover { color: #6366f1; }

.key-status {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.65rem; margin-top: 0.3rem;
}
.key-status.connected { color: #059669; }

/* Feature Tags */
.feature-tags { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
.feature-tag {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.62rem; font-weight: 500;
  padding: 0.2rem 0.45rem; border-radius: 6px;
  background: #f1f5f9; color: #475569;
}
.feature-tag i { font-size: 0.58rem; }

/* Usage Stats */
.usage-stats {
  display: flex; gap: 0.75rem;
  padding: 0.5rem 0.75rem; background: #f8fafc;
  border-radius: 8px; margin-bottom: 0.5rem;
}
.usage-item { display: flex; flex-direction: column; }
.usage-label { font-size: 0.58rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.usage-value { font-size: 0.85rem; font-weight: 700; color: #1e293b; }

/* Error Banner */
.error-banner {
  display: flex; align-items: flex-start; gap: 0.4rem;
  padding: 0.5rem 0.65rem; border-radius: 8px;
  background: #fef2f2; border: 1px solid #fecaca;
  font-size: 0.72rem; color: #dc2626;
}
.error-banner i { margin-top: 0.15rem; flex-shrink: 0; }

/* Card Footer */
.card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1.25rem; background: #fafbfc;
  border-top: 1px solid #f1f5f9;
}
.footer-left, .footer-right { display: flex; gap: 0.4rem; }

.btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.75rem; border-radius: 8px;
  font-size: 0.72rem; font-weight: 600;
  border: 1.5px solid transparent; cursor: pointer;
  transition: all 0.2s;
}
.btn:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-save {
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; border: none;
}
.btn-save:hover:not(:disabled) { box-shadow: 0 4px 12px rgba(99,102,241,0.3); transform: scale(1.03); }
.btn-test { background: white; color: #475569; border-color: #e2e8f0; }
.btn-test:hover:not(:disabled) { border-color: #6366f1; color: #6366f1; }
.btn-default {
  background: white; color: #f59e0b; border-color: #fde68a;
  padding: 0.4rem 0.55rem;
}
.btn-default:hover { background: #fffbeb; border-color: #f59e0b; }
.btn-toggle {
  padding: 0.4rem 0.55rem; border-radius: 8px;
}
.btn-toggle.active { background: #ecfdf5; color: #10b981; border-color: #a7f3d0; }
.btn-toggle.inactive { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
.btn-toggle:hover { transform: scale(1.08); }

/* Test Toast */
.test-toast {
  position: absolute; bottom: 0; left: 0; right: 0;
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.65rem 1rem; font-size: 0.78rem; font-weight: 500;
  animation: slideUp 0.3s ease;
}
.test-toast.success { background: #ecfdf5; color: #059669; border-top: 2px solid #10b981; }
.test-toast.error { background: #fef2f2; color: #dc2626; border-top: 2px solid #ef4444; }
.test-toast i { font-size: 0.85rem; }
.toast-close {
  margin-left: auto; background: none; border: none;
  color: inherit; cursor: pointer; font-size: 0.72rem; opacity: 0.6;
}
.toast-close:hover { opacity: 1; }
@keyframes slideUp {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* Info Banner */
.info-banner {
  display: flex; align-items: flex-start; gap: 0.65rem;
  padding: 1rem 1.25rem; border-radius: 12px;
  background: linear-gradient(135deg, #eef2ff, #f5f3ff);
  border: 1px solid #e0e7ff;
  font-size: 0.82rem; color: #475569; line-height: 1.6;
}
.info-banner i { font-size: 1.1rem; color: #6366f1; margin-top: 0.15rem; flex-shrink: 0; }
.info-banner strong { color: #1e293b; }

/* Responsive */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .providers-grid { grid-template-columns: 1fr; }
  .card-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
  .card-footer { flex-direction: column; gap: 0.5rem; }
  .footer-left, .footer-right { width: 100%; }
}
</style>
