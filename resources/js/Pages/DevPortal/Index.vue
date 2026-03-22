<template>
  <div>
    <Head title="Dev Portal" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-code" /></div>
        <div>
          <h1 class="page-title">Dev Portal</h1>
          <p class="page-subtitle">Quản lý API Keys, Webhooks và theo dõi API Logs cho nhà phát triển</p>
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi-card"><div class="kpi-icon ki1"><i class="pi pi-key" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.active_keys }}<small>/{{ stats.total_keys }}</small></span><span class="kpi-label">API Keys Active</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki2"><i class="pi pi-link" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.active_webhooks }}<small>/{{ stats.total_webhooks }}</small></span><span class="kpi-label">Webhooks Active</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki3"><i class="pi pi-bolt" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.total_requests_today }}</span><span class="kpi-label">Requests Today</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki4"><i class="pi pi-exclamation-triangle" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.error_rate_today != null ? stats.error_rate_today + '%' : '0%' }}</span><span class="kpi-label">Error Rate</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <button class="tab-btn" :class="{active: activeTab === 'keys'}" @click="activeTab = 'keys'"><i class="pi pi-key" /> API Keys</button>
      <button class="tab-btn" :class="{active: activeTab === 'webhooks'}" @click="activeTab = 'webhooks'"><i class="pi pi-link" /> Webhooks</button>
      <button class="tab-btn" :class="{active: activeTab === 'logs'}" @click="activeTab = 'logs'"><i class="pi pi-list" /> API Logs</button>
      <button class="tab-btn" :class="{active: activeTab === 'docs'}" @click="activeTab = 'docs'"><i class="pi pi-book" /> API Docs</button>
    </div>

    <!-- ═══ API KEYS TAB ═══ -->
    <div v-if="activeTab === 'keys'">
      <div class="filter-bar"><div style="flex:1" /><Button label="Tạo API Key" icon="pi pi-plus" @click="openKeyDialog()" /></div>

      <div v-if="apiKeys.length" class="keys-list">
        <div v-for="k in apiKeys" :key="k.id" class="key-card" :class="{'key-disabled': !k.is_active || k.is_expired}">
          <div class="key-top">
            <div class="key-icon" :class="k.is_active && !k.is_expired ? 'kic-active' : 'kic-inactive'"><i class="pi pi-key" /></div>
            <div class="key-header">
              <h3>{{ k.name }}</h3>
              <div class="key-badges">
                <span class="key-status" :class="'ks-' + k.status_label.toLowerCase()">{{ k.status_label }}</span>
                <span v-for="p in (k.permissions || [])" :key="p" class="perm-tag">{{ p }}</span>
              </div>
            </div>
            <div class="key-actions">
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openKeyDialog(k)" />
              <Button icon="pi pi-refresh" text rounded size="small" v-tooltip="'Regenerate'" @click="regenerateKey(k)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteKey(k)" />
            </div>
          </div>
          <div class="key-code-row">
            <code class="key-code">{{ k.key }}</code>
            <button class="copy-btn" @click="copyText(k.key)" v-tooltip="'Copy'"><i class="pi pi-copy" /></button>
          </div>
          <div class="key-meta">
            <span><i class="pi pi-bolt" /> {{ k.total_requests.toLocaleString() }} requests</span>
            <span v-if="k.last_used_at"><i class="pi pi-clock" /> {{ k.last_used_at }}</span>
            <span v-if="k.rate_limit"><i class="pi pi-gauge" /> {{ k.rate_limit }}/h</span>
            <span v-if="k.expires_at"><i class="pi pi-calendar" /> Hết hạn: {{ k.expires_at }}</span>
            <span><i class="pi pi-user" /> {{ k.creator_name }}</span>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-key" /></div>
        <h3>Chưa có API Key</h3>
        <p>Tạo API Key để bắt đầu tích hợp hệ thống.</p>
        <Button label="Tạo API Key" icon="pi pi-plus" class="mt-1" @click="openKeyDialog()" />
      </div>
    </div>

    <!-- ═══ WEBHOOKS TAB ═══ -->
    <div v-if="activeTab === 'webhooks'">
      <div class="filter-bar"><div style="flex:1" /><Button label="Tạo Webhook" icon="pi pi-plus" @click="openWebhookDialog()" /></div>

      <div v-if="webhooks.length" class="wh-list">
        <div v-for="w in webhooks" :key="w.id" class="wh-card" :class="{'wh-disabled': !w.is_active}">
          <div class="wh-top">
            <div class="wh-icon" :class="w.is_active ? 'whi-active' : 'whi-inactive'"><i class="pi pi-link" /></div>
            <div class="wh-header">
              <h3>{{ w.name }}</h3>
              <code class="wh-url">{{ w.url }}</code>
            </div>
            <div class="wh-actions">
              <Button icon="pi pi-play" text rounded size="small" v-tooltip="'Test'" @click="testWebhook(w)" />
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openWebhookDialog(w)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteWebhook(w)" />
            </div>
          </div>
          <div class="wh-events">
            <span v-for="ev in (w.events || [])" :key="ev" class="event-tag">{{ ev }}</span>
          </div>
          <div class="wh-stats">
            <span class="ws-item"><strong>{{ w.total_deliveries }}</strong> deliveries</span>
            <span class="ws-item ws-fail"><strong>{{ w.total_failures }}</strong> failures</span>
            <span v-if="w.success_rate != null" class="ws-item ws-rate"><strong>{{ w.success_rate }}%</strong> success</span>
            <span v-if="w.last_triggered_at" class="ws-item"><i class="pi pi-clock" /> {{ w.last_triggered_at }}</span>
            <span v-if="w.last_status_code" class="ws-item" :class="w.last_status_code < 400 ? 'ws-ok' : 'ws-err'">HTTP {{ w.last_status_code }}</span>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-link" /></div>
        <h3>Chưa có Webhook</h3>
        <p>Tạo webhook để nhận thông báo real-time khi có sự kiện xảy ra.</p>
        <Button label="Tạo Webhook" icon="pi pi-plus" class="mt-1" @click="openWebhookDialog()" />
      </div>
    </div>

    <!-- ═══ API LOGS TAB ═══ -->
    <div v-if="activeTab === 'logs'">
      <div v-if="logs.length" class="logs-table-wrap">
        <table class="logs-table">
          <thead>
            <tr><th>Thời gian</th><th>Method</th><th>Endpoint</th><th>Status</th><th>API Key</th><th>IP</th><th>Duration</th></tr>
          </thead>
          <tbody>
            <tr v-for="l in logs" :key="l.id">
              <td class="log-time">{{ l.created_at }}</td>
              <td><span class="method-badge" :class="'m-' + l.method.toLowerCase()">{{ l.method }}</span></td>
              <td class="log-endpoint"><code>{{ l.endpoint }}</code></td>
              <td><span class="status-dot" :class="'sd-' + l.status_class">{{ l.status_code }}</span></td>
              <td class="log-key">{{ l.api_key_name }}</td>
              <td class="log-ip">{{ l.ip_address }}</td>
              <td class="log-dur">{{ l.duration_ms }}ms</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-list" /></div>
        <h3>Chưa có API Logs</h3>
        <p>Logs sẽ xuất hiện khi có request tới API.</p>
      </div>
    </div>

    <!-- ═══ API DOCS TAB ═══ -->
    <div v-if="activeTab === 'docs'" class="docs-section">
      <div class="docs-card">
        <h2><i class="pi pi-book" /> API Documentation</h2>
        <div class="docs-intro">
          <p>BED CRM API cho phép bạn tích hợp hệ thống CRM với các ứng dụng bên ngoài. Sử dụng API Key để xác thực mọi request.</p>
        </div>
        <div class="docs-block">
          <h3>🔐 Authentication</h3>
          <p>Thêm API Key vào header của mỗi request:</p>
          <pre class="code-block"><code>Authorization: Bearer bed_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
X-API-Secret: your-secret-key</code></pre>
        </div>
        <div class="docs-block">
          <h3>📡 Base URL</h3>
          <pre class="code-block"><code>https://your-domain.com/api/v1</code></pre>
        </div>
        <div class="docs-block">
          <h3>📋 Available Endpoints</h3>
          <div class="endpoint-list">
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/leads</code><span class="ep-desc">Danh sách leads</span></div>
            <div class="ep-item"><span class="method-badge m-post">POST</span><code>/api/v1/leads</code><span class="ep-desc">Tạo lead mới</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/deals</code><span class="ep-desc">Danh sách deals</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/contacts</code><span class="ep-desc">Danh sách contacts</span></div>
            <div class="ep-item"><span class="method-badge m-post">POST</span><code>/api/v1/contacts</code><span class="ep-desc">Tạo contact mới</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/customers</code><span class="ep-desc">Danh sách customers</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/products</code><span class="ep-desc">Danh sách sản phẩm</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/quotations</code><span class="ep-desc">Danh sách báo giá</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/contracts</code><span class="ep-desc">Danh sách hợp đồng</span></div>
            <div class="ep-item"><span class="method-badge m-get">GET</span><code>/api/v1/dropship/orders</code><span class="ep-desc">Danh sách đơn dropship</span></div>
            <div class="ep-item"><span class="method-badge m-post">POST</span><code>/api/v1/dropship/orders</code><span class="ep-desc">Tạo đơn dropship</span></div>
          </div>
        </div>
        <div class="docs-block">
          <h3>📬 Webhook Events</h3>
          <div class="events-grid">
            <span v-for="ev in availableEvents" :key="ev" class="event-doc-tag">{{ ev }}</span>
          </div>
        </div>
        <div class="docs-block">
          <h3>⚡ Rate Limiting</h3>
          <p>Mặc định <strong>1,000 requests/giờ</strong> per API Key. Vượt quá giới hạn sẽ nhận response <code>429 Too Many Requests</code>.</p>
        </div>
        <div class="docs-block">
          <h3>🔄 Response Format</h3>
          <pre class="code-block"><code>{
  "success": true,
  "data": { ... },
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 30
  }
}</code></pre>
        </div>
      </div>
    </div>

    <!-- ═══ API KEY DIALOG ═══ -->
    <div v-if="keyDialog" class="dialog-overlay" @click.self="keyDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon"><i class="pi pi-key" /></div><h3>{{ keyForm.id ? 'Cập nhật' : 'Tạo' }} API Key</h3></div>
          <button class="dialog-close" @click="keyDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="form-group"><label>Tên Key <span class="req">*</span></label><InputText v-model="keyForm.name" class="w-full" placeholder="VD: Shopify Integration" /></div>
          <div class="form-section">
            <div class="section-header"><i class="pi pi-shield section-icon" /><h4 class="section-title">Permissions</h4></div>
            <div class="perm-checks">
              <label v-for="p in availablePermissions" :key="p" class="perm-check"><input type="checkbox" :value="p" v-model="keyForm.permissions" /> {{ p }}</label>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Rate Limit (/giờ)</label><InputNumber v-model="keyForm.rate_limit" class="w-full" :min="1" /></div>
            <div class="form-group flex-1"><label>Hết hạn</label><InputText v-model="keyForm.expires_at" type="date" class="w-full" /></div>
          </div>
          <div class="form-group"><label>Allowed IPs <small>(mỗi IP 1 dòng)</small></label><Textarea v-model="keyForm.allowed_ips_text" rows="2" class="w-full" placeholder="192.168.1.1&#10;10.0.0.1" /></div>
          <div class="form-group"><label>Allowed Domains <small>(mỗi domain 1 dòng)</small></label><Textarea v-model="keyForm.allowed_domains_text" rows="2" class="w-full" placeholder="example.com&#10;myshop.myshopify.com" /></div>
          <div class="form-group"><label>Ghi chú</label><Textarea v-model="keyForm.notes" rows="2" class="w-full" /></div>
          <div v-if="keyForm.id" class="toggle-row">
            <div><label class="toggle-label">Active</label><small class="toggle-desc">Bật/tắt API Key</small></div>
            <InputSwitch v-model="keyForm.is_active" />
          </div>
        </div>
        <div class="dialog-footer">
          <Button label="Hủy" severity="secondary" outlined @click="keyDialog = false" />
          <Button :label="keyForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" @click="submitKey" :loading="keyForm.processing" />
        </div>
      </div>
    </div>

    <!-- ═══ WEBHOOK DIALOG ═══ -->
    <div v-if="whDialog" class="dialog-overlay" @click.self="whDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon dicon-wh"><i class="pi pi-link" /></div><h3>{{ whForm.id ? 'Cập nhật' : 'Tạo' }} Webhook</h3></div>
          <button class="dialog-close" @click="whDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="form-row">
            <div class="form-group flex-1"><label>Tên <span class="req">*</span></label><InputText v-model="whForm.name" class="w-full" placeholder="VD: Order Notification" /></div>
          </div>
          <div class="form-group"><label>URL Endpoint <span class="req">*</span></label><InputText v-model="whForm.url" class="w-full" placeholder="https://your-server.com/webhook" /></div>
          <div class="form-section">
            <div class="section-header"><i class="pi pi-bolt section-icon" /><h4 class="section-title">Events <span class="req">*</span></h4></div>
            <div class="events-checks">
              <label v-for="ev in availableEvents" :key="ev" class="ev-check"><input type="checkbox" :value="ev" v-model="whForm.events" /><code>{{ ev }}</code></label>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Retry Count</label><InputNumber v-model="whForm.retry_count" class="w-full" :min="0" :max="10" /></div>
            <div class="form-group flex-1"><label>Timeout (giây)</label><InputNumber v-model="whForm.timeout" class="w-full" :min="1" :max="60" /></div>
          </div>
          <div class="form-group"><label>Ghi chú</label><Textarea v-model="whForm.notes" rows="2" class="w-full" /></div>
          <div v-if="whForm.id" class="toggle-row">
            <div><label class="toggle-label">Active</label><small class="toggle-desc">Bật/tắt webhook</small></div>
            <InputSwitch v-model="whForm.is_active" />
          </div>
        </div>
        <div class="dialog-footer">
          <Button label="Hủy" severity="secondary" outlined @click="whDialog = false" />
          <Button :label="whForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" @click="submitWebhook" :loading="whForm.processing" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Textarea from 'primevue/textarea'

export default {
  components: { Head, Button, InputText, InputNumber, InputSwitch, Textarea },
  layout: Layout,
  props: { apiKeys: Array, webhooks: Array, logs: Array, stats: Object, availableEvents: Array, availablePermissions: Array },
  data() {
    return {
      activeTab: 'keys',
      keyDialog: false,
      whDialog: false,
      keyForm: this.emptyKeyForm(),
      whForm: this.emptyWhForm(),
    }
  },
  mounted() {
    this._esc = (e) => { if (e.key === 'Escape') { this.keyDialog = false; this.whDialog = false } }
    document.addEventListener('keydown', this._esc)
  },
  beforeUnmount() { document.removeEventListener('keydown', this._esc) },
  methods: {
    emptyKeyForm() {
      return this.$inertia.form({
        id: null, name: '', permissions: ['read'], rate_limit: 1000,
        allowed_ips_text: '', allowed_domains_text: '',
        expires_at: '', notes: '', is_active: true,
      })
    },
    emptyWhForm() {
      return this.$inertia.form({
        id: null, name: '', url: '', events: [],
        retry_count: 3, timeout: 10, notes: '', is_active: true,
      })
    },
    openKeyDialog(k = null) {
      if (k) {
        this.keyForm = this.$inertia.form({
          ...k,
          allowed_ips_text: (k.allowed_ips || []).join('\n'),
          allowed_domains_text: (k.allowed_domains || []).join('\n'),
        })
      } else { this.keyForm = this.emptyKeyForm() }
      this.keyDialog = true
    },
    openWebhookDialog(w = null) {
      if (w) { this.whForm = this.$inertia.form({ ...w }) }
      else { this.whForm = this.emptyWhForm() }
      this.whDialog = true
    },
    submitKey() {
      const data = { ...this.keyForm.data() }
      data.allowed_ips = data.allowed_ips_text ? data.allowed_ips_text.split('\n').map(s => s.trim()).filter(Boolean) : null
      data.allowed_domains = data.allowed_domains_text ? data.allowed_domains_text.split('\n').map(s => s.trim()).filter(Boolean) : null
      delete data.allowed_ips_text; delete data.allowed_domains_text
      if (this.keyForm.id) {
        this.$inertia.put(`/dev-portal/api-keys/${this.keyForm.id}`, data, { preserveScroll: true, onSuccess: () => { this.keyDialog = false } })
      } else {
        this.$inertia.post('/dev-portal/api-keys', data, { preserveScroll: true, onSuccess: () => { this.keyDialog = false } })
      }
    },
    submitWebhook() {
      if (this.whForm.id) {
        this.whForm.put(`/dev-portal/webhooks/${this.whForm.id}`, { preserveScroll: true, onSuccess: () => { this.whDialog = false } })
      } else {
        this.whForm.post('/dev-portal/webhooks', { preserveScroll: true, onSuccess: () => { this.whDialog = false } })
      }
    },
    deleteKey(k) { if (confirm(`Xóa API Key "${k.name}"?`)) this.$inertia.delete(`/dev-portal/api-keys/${k.id}`, { preserveScroll: true }) },
    regenerateKey(k) { if (confirm(`Regenerate API Key "${k.name}"? Key cũ sẽ bị vô hiệu.`)) this.$inertia.post(`/dev-portal/api-keys/${k.id}/regenerate`, {}, { preserveScroll: true }) },
    deleteWebhook(w) { if (confirm(`Xóa Webhook "${w.name}"?`)) this.$inertia.delete(`/dev-portal/webhooks/${w.id}`, { preserveScroll: true }) },
    testWebhook(w) { this.$inertia.post(`/dev-portal/webhooks/${w.id}/test`, {}, { preserveScroll: true }) },
    copyText(text) {
      navigator.clipboard.writeText(text)
        .then(() => alert('Đã copy!'))
        .catch(() => {})
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#8b5cf6,#7c3aed); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(139,92,246,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }

/* ===== KPI Row ===== */
.kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.25rem }
.kpi-card { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; transition:all .25s }
.kpi-card:hover { border-color:#8b5cf6; box-shadow:0 4px 18px rgba(139,92,246,.06) }
.kpi-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.ki1 { background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#6366f1 }
.ki2 { background:linear-gradient(135deg,#ecfdf5,#d1fae5); color:#059669 }
.ki3 { background:linear-gradient(135deg,#fff7ed,#ffedd5); color:#ea580c }
.ki4 { background:linear-gradient(135deg,#fef2f2,#fecaca); color:#ef4444 }
.kpi-info { display:flex; flex-direction:column }
.kpi-value { font-size:1.1rem; font-weight:800; color:#0f172a }
.kpi-value small { font-size:.7rem; color:#94a3b8; font-weight:500 }
.kpi-label { font-size:.62rem; color:#94a3b8; font-weight:500 }

/* ===== Tab & Filter ===== */
.tab-bar { display:flex; gap:.35rem; margin-bottom:1rem; padding:.3rem; background:#f8fafc; border-radius:12px; border:1.5px solid #f1f5f9 }
.tab-btn { display:flex; align-items:center; gap:.35rem; padding:.45rem .85rem; border:none; border-radius:8px; background:transparent; font-size:.78rem; font-weight:600; color:#64748b; cursor:pointer; font-family:inherit; transition:all .2s }
.tab-btn i { font-size:.7rem }
.tab-btn:hover { color:#8b5cf6 }
.tab-btn.active { background:white; color:#7c3aed; box-shadow:0 2px 8px rgba(0,0,0,.06) }
.filter-bar { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; background:white; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:1rem }

/* ===== API Keys ===== */
.keys-list { display:flex; flex-direction:column; gap:.5rem }
.key-card { padding:.85rem 1.15rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; transition:all .25s }
.key-card:hover { border-color:#8b5cf6; box-shadow:0 4px 18px rgba(139,92,246,.06) }
.key-disabled { opacity:.55 }
.key-top { display:flex; align-items:center; gap:.65rem }
.key-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.kic-active { background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#6366f1 } .kic-inactive { background:#f1f5f9; color:#94a3b8 }
.key-header { flex:1; min-width:0 }
.key-header h3 { font-size:.85rem; font-weight:700; color:#1e293b; margin:0 }
.key-badges { display:flex; gap:.25rem; margin-top:.15rem; flex-wrap:wrap }
.key-status { font-size:.5rem; font-weight:700; padding:.06rem .3rem; border-radius:4px; text-transform:uppercase }
.ks-active { background:#dcfce7; color:#16a34a } .ks-disabled { background:#f1f5f9; color:#94a3b8 } .ks-expired { background:#fef2f2; color:#ef4444 }
.perm-tag { font-size:.5rem; font-weight:600; padding:.06rem .3rem; border-radius:4px; background:#eef2ff; color:#6366f1 }
.key-actions { display:flex; gap:.125rem }
.key-code-row { display:flex; align-items:center; gap:.5rem; margin:.45rem 0; padding:.4rem .65rem; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0 }
.key-code { font-size:.7rem; font-family:'SFMono-Regular',monospace; color:#334155; flex:1; word-break:break-all }
.copy-btn { background:none; border:none; padding:.25rem; border-radius:6px; cursor:pointer; color:#94a3b8; transition:all .2s }
.copy-btn:hover { background:#eef2ff; color:#6366f1 }
.key-meta { display:flex; gap:.6rem; flex-wrap:wrap }
.key-meta span { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.15rem }
.key-meta i { font-size:.52rem }

/* ===== Webhooks ===== */
.wh-list { display:flex; flex-direction:column; gap:.5rem }
.wh-card { padding:.85rem 1.15rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; transition:all .25s }
.wh-card:hover { border-color:#059669; box-shadow:0 4px 18px rgba(5,150,105,.06) }
.wh-disabled { opacity:.55 }
.wh-top { display:flex; align-items:center; gap:.65rem }
.wh-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.whi-active { background:linear-gradient(135deg,#ecfdf5,#d1fae5); color:#059669 } .whi-inactive { background:#f1f5f9; color:#94a3b8 }
.wh-header { flex:1; min-width:0 }
.wh-header h3 { font-size:.85rem; font-weight:700; color:#1e293b; margin:0 }
.wh-url { font-size:.62rem; color:#64748b; word-break:break-all }
.wh-actions { display:flex; gap:.125rem }
.wh-events { display:flex; gap:.25rem; flex-wrap:wrap; margin:.45rem 0 }
.event-tag { font-size:.5rem; font-weight:600; padding:.06rem .35rem; border-radius:4px; background:#ecfdf5; color:#059669; font-family:monospace }
.wh-stats { display:flex; gap:.65rem; flex-wrap:wrap; padding-top:.35rem; border-top:1px solid #f1f5f9 }
.ws-item { font-size:.62rem; color:#64748b; display:flex; align-items:center; gap:.15rem }
.ws-item strong { color:#1e293b }
.ws-item i { font-size:.52rem }
.ws-fail strong { color:#ef4444 }
.ws-rate strong { color:#059669 }
.ws-ok { color:#16a34a !important; font-weight:600 } .ws-err { color:#ef4444 !important; font-weight:600 }

/* ===== Logs Table ===== */
.logs-table-wrap { background:white; border-radius:14px; border:1.5px solid #f1f5f9; overflow:auto; max-height:500px }
.logs-table { width:100%; border-collapse:collapse; font-size:.72rem }
.logs-table thead { position:sticky; top:0; background:white; z-index:1 }
.logs-table th { padding:.6rem .75rem; text-align:left; font-weight:700; color:#475569; border-bottom:2px solid #e2e8f0; font-size:.65rem; text-transform:uppercase }
.logs-table td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; color:#334155 }
.logs-table tr:hover td { background:#fafbfe }
.log-time { font-family:monospace; font-size:.62rem; color:#94a3b8; white-space:nowrap }
.log-endpoint code { font-size:.62rem; color:#334155 }
.log-key { font-size:.62rem; color:#64748b }
.log-ip { font-size:.62rem; color:#94a3b8; font-family:monospace }
.log-dur { font-size:.62rem; color:#64748b }
.method-badge { font-size:.5rem; font-weight:700; padding:.12rem .35rem; border-radius:4px; text-transform:uppercase; display:inline-block; min-width:32px; text-align:center }
.m-get { background:#dbeafe; color:#3b82f6 } .m-post { background:#dcfce7; color:#16a34a } .m-put { background:#fef3c7; color:#d97706 } .m-patch { background:#e0e7ff; color:#6366f1 } .m-delete { background:#fef2f2; color:#ef4444 }
.status-dot { font-size:.62rem; font-weight:700; padding:.12rem .35rem; border-radius:4px }
.sd-success { background:#dcfce7; color:#16a34a } .sd-warning { background:#fef3c7; color:#d97706 } .sd-danger { background:#fef2f2; color:#ef4444 }

/* ===== API Docs ===== */
.docs-section { max-width:800px }
.docs-card { padding:1.5rem 2rem; background:white; border-radius:16px; border:1.5px solid #f1f5f9 }
.docs-card h2 { font-size:1.2rem; font-weight:800; color:#0f172a; margin:0 0 .75rem; display:flex; align-items:center; gap:.4rem }
.docs-intro p { font-size:.85rem; color:#475569; line-height:1.6; margin:0 0 1rem }
.docs-block { margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid #f1f5f9 }
.docs-block:last-child { border-bottom:none }
.docs-block h3 { font-size:.9rem; font-weight:700; color:#1e293b; margin:0 0 .5rem }
.docs-block p { font-size:.82rem; color:#475569; line-height:1.6; margin:0 0 .5rem }
.code-block { background:#1e293b; color:#e2e8f0; padding:.75rem 1rem; border-radius:8px; font-size:.72rem; overflow-x:auto; font-family:'SFMono-Regular',monospace; line-height:1.6; margin:0 }
.endpoint-list { display:flex; flex-direction:column; gap:.3rem }
.ep-item { display:flex; align-items:center; gap:.5rem; padding:.35rem .5rem; border-radius:6px; transition:background .2s }
.ep-item:hover { background:#f8fafc }
.ep-item code { font-size:.7rem; color:#334155; flex:1 }
.ep-desc { font-size:.62rem; color:#94a3b8 }
.events-grid { display:flex; gap:.25rem; flex-wrap:wrap }
.event-doc-tag { font-size:.55rem; font-weight:600; padding:.12rem .4rem; border-radius:4px; background:#eef2ff; color:#6366f1; font-family:monospace }

/* ===== Dialog ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:620px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-wide { width:720px }
.dialog-card * { box-sizing:border-box }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#8b5cf6,#7c3aed); display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.dicon-wh { background:linear-gradient(135deg,#059669,#047857) }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.form-section { margin-bottom:.75rem; padding-bottom:.5rem; border-bottom:1px solid #f8fafc }
.section-header { display:flex; align-items:center; gap:.4rem; margin-bottom:.5rem }
.section-icon { font-size:.7rem; color:#8b5cf6 }
.section-title { font-size:.75rem; font-weight:700; color:#475569; margin:0 }
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.form-group label small { font-weight:400; color:#94a3b8 }
.form-group :deep(.p-inputtext), .form-group :deep(.p-textarea), .form-group :deep(.p-inputnumber) { width:100% }
.req { color:#ef4444 }
.perm-checks { display:flex; gap:.75rem; flex-wrap:wrap }
.perm-check { display:flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:600; color:#334155; cursor:pointer }
.perm-check input { accent-color:#6366f1; width:14px; height:14px }
.events-checks { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:.25rem; max-height:200px; overflow-y:auto; padding:.25rem }
.ev-check { display:flex; align-items:center; gap:.25rem; font-size:.68rem; cursor:pointer; padding:.15rem .25rem; border-radius:4px }
.ev-check:hover { background:#f8fafc }
.ev-check input { accent-color:#059669; width:12px; height:12px }
.ev-check code { font-size:.6rem; color:#475569 }
.toggle-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem }
.toggle-label { font-size:.82rem; font-weight:600; color:#1e293b }
.toggle-desc { display:block; font-size:.62rem; color:#94a3b8 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:white; border-radius:0 0 18px 18px }

/* ===== Empty ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#eef2ff,#e0e7ff); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#6366f1 }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

@media (max-width:768px) {
  .kpi-row { grid-template-columns:repeat(2,1fr) }
  .form-row { flex-direction:column }
  .flex-1 { min-width:100% }
  .events-checks { grid-template-columns:1fr }
  .dialog-overlay { padding:.75rem }
  .ep-item { flex-direction:column; align-items:flex-start }
}
</style>
