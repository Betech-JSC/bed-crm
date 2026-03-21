<template>
  <div>
    <Head title="Tài khoản mạng xã hội" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-share-alt" style="color: #8b5cf6; margin-right: 0.5rem;" />
          Tài khoản mạng xã hội
        </h1>
        <p class="page-subtitle">Kết nối & quản lý tài khoản để đăng bài tự động</p>
      </div>
      <Button label="Kết nối tài khoản" icon="pi pi-plus" @click="showConnectModal = true" />
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon stat-icon--purple"><i class="pi pi-link" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.total }}</span>
          <span class="stat-label">Tổng tài khoản</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--green"><i class="pi pi-check-circle" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.connected }}</span>
          <span class="stat-label">Đã kết nối</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--red"><i class="pi pi-exclamation-triangle" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.expired }}</span>
          <span class="stat-label">Cần gia hạn</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--blue"><i class="pi pi-send" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.total_posts }}</span>
          <span class="stat-label">Bài đã đăng</span>
        </div>
      </div>
    </div>

    <!-- Platform Quick Connect (when empty) -->
    <div v-if="!socialAccounts.length" class="empty-connect-state">
      <div class="empty-illustration">
        <i class="pi pi-share-alt" />
      </div>
      <h3>Chưa kết nối tài khoản nào</h3>
      <p>Kết nối tài khoản mạng xã hội để bắt đầu tạo & đăng bài tự động</p>
      <div class="platform-connect-row">
        <button
          v-for="(meta, key) in platformsMeta"
          :key="key"
          class="platform-connect-btn"
          :style="{ '--pc-color': meta.color }"
          @click="connectPlatform(key)"
        >
          <i :class="meta.icon" />
          <span>{{ meta.label }}</span>
        </button>
      </div>
    </div>

    <!-- Accounts Grid -->
    <div v-else class="accounts-grid">
      <div
        v-for="account in socialAccounts"
        :key="account.id"
        class="account-card"
        :class="{
          'account-card--expired': account.is_token_expired,
          'account-card--disconnected': !account.is_connected,
        }"
      >
        <!-- Status Bar -->
        <div class="card-status-bar" :style="{ background: getStatusGradient(account) }" />

        <!-- Card Body -->
        <div class="card-body">
          <!-- Platform Icon & Name -->
          <div class="card-top">
            <div class="platform-icon" :style="{ background: platformsMeta[account.platform]?.gradient }">
              <i :class="platformsMeta[account.platform]?.icon" />
            </div>
            <div class="card-connection-status">
              <span v-if="account.is_token_expired" class="status-chip status-chip--expired">
                <i class="pi pi-exclamation-triangle" /> Hết hạn
              </span>
              <span v-else-if="!account.is_connected" class="status-chip status-chip--disconnected">
                <i class="pi pi-times" /> Mất kết nối
              </span>
              <span v-else class="status-chip status-chip--connected">
                <i class="pi pi-check" /> Đã kết nối
              </span>
            </div>
          </div>

          <!-- Account Info -->
          <h3 class="card-name">{{ account.name }}</h3>
          <p class="card-username" v-if="account.username">@{{ account.username }}</p>
          <p class="card-platform-label">{{ platformsMeta[account.platform]?.label }}</p>

          <!-- Meta Info -->
          <div class="card-meta">
            <div class="meta-item">
              <i class="pi pi-send" />
              <span>{{ account.published_count }} bài đăng</span>
            </div>
            <div class="meta-item" v-if="account.last_sync_at">
              <i class="pi pi-clock" />
              <span>{{ account.last_sync_at }}</span>
            </div>
            <div class="meta-item" v-if="account.token_expires_at">
              <i class="pi pi-key" />
              <span>Token: {{ account.token_expires_at }}</span>
            </div>
          </div>
        </div>

        <!-- Card Actions -->
        <div class="card-actions">
          <button class="card-action-btn card-action-btn--info" title="Kiểm tra kết nối" @click="validateConnection(account.id)">
            <i class="pi pi-check-circle" />
          </button>
          <button class="card-action-btn card-action-btn--warning" title="Làm mới token" @click="refreshToken(account.id)">
            <i class="pi pi-refresh" />
          </button>
          <button class="card-action-btn card-action-btn--danger" title="Ngắt kết nối" @click="disconnect(account.id)">
            <i class="pi pi-trash" />
          </button>
        </div>
      </div>

      <!-- Add Account Card -->
      <button class="add-account-card" @click="showConnectModal = true">
        <div class="add-icon"><i class="pi pi-plus" /></div>
        <span>Kết nối thêm</span>
      </button>
    </div>

    <!-- Connect Platform Modal -->
    <Dialog v-model:visible="showConnectModal" modal header="Kết nối mạng xã hội" :style="{ width: '560px' }" :draggable="false">
      <div class="connect-modal">
        <p class="connect-subtitle">Chọn nền tảng bạn muốn kết nối. Bạn sẽ được chuyển đến trang đăng nhập của từng nền tảng.</p>

        <div class="connect-grid">
          <button
            v-for="(meta, key) in platformsMeta"
            :key="key"
            class="connect-option"
            :class="{ 'connect-option--connected': isAlreadyConnected(key) }"
            @click="connectPlatform(key)"
          >
            <div class="connect-option-icon" :style="{ background: meta.gradient }">
              <i :class="meta.icon" />
            </div>
            <div class="connect-option-info">
              <span class="connect-option-name">{{ meta.label }}</span>
              <span v-if="isAlreadyConnected(key)" class="connect-option-status">
                <i class="pi pi-check" /> Đã kết nối {{ getConnectedCount(key) }} tài khoản
              </span>
              <span v-else class="connect-option-status connect-option-status--none">
                Chưa kết nối
              </span>
            </div>
            <i class="pi pi-chevron-right connect-arrow" />
          </button>
        </div>

        <!-- How it works -->
        <div class="connect-howto">
          <h4><i class="pi pi-info-circle" /> Cách thức hoạt động</h4>
          <div class="howto-steps">
            <div class="howto-step">
              <div class="howto-num">1</div>
              <span>Chọn nền tảng</span>
            </div>
            <div class="howto-arrow"><i class="pi pi-arrow-right" /></div>
            <div class="howto-step">
              <div class="howto-num">2</div>
              <span>Đăng nhập & cấp quyền</span>
            </div>
            <div class="howto-arrow"><i class="pi pi-arrow-right" /></div>
            <div class="howto-step">
              <div class="howto-num">3</div>
              <span>Sẵn sàng đăng bài!</span>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Confirm Disconnect Dialog -->
    <Dialog v-model:visible="showDisconnectDialog" modal header="Ngắt kết nối" :style="{ width: '420px' }" :draggable="false">
      <div class="disconnect-content">
        <div class="disconnect-icon"><i class="pi pi-exclamation-triangle" /></div>
        <p>Bạn có chắc muốn ngắt kết nối tài khoản này? Các bài đăng đã lên lịch sẽ không thể thực hiện.</p>
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showDisconnectDialog = false" />
        <Button label="Ngắt kết nối" severity="danger" icon="pi pi-trash" @click="confirmDisconnect" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, Dialog },
  layout: Layout,
  props: {
    socialAccounts: Array,
    stats: Object,
    platformsMeta: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      showConnectModal: false,
      showDisconnectDialog: false,
      disconnectId: null,
    }
  },
  methods: {
    async connectPlatform(platform) {
      this.showConnectModal = false
      try {
        const res = await fetch(`/api/social-platforms/${platform}/auth-url`, {
          headers: { 'Accept': 'application/json' },
        })
        const data = await res.json()
        if (data.success && data.auth_url) {
          window.location.href = data.auth_url
        } else {
          // Redirect to settings page
          if (confirm(`${this.platformsMeta[platform]?.label} chưa được cấu hình OAuth.\n\nBạn có muốn đi đến trang cấu hình?`)) {
            router.visit('/social-platforms')
          }
        }
      } catch (e) {
        if (confirm('Nền tảng chưa được cấu hình. Đi đến trang cấu hình Social Platforms?')) {
          router.visit('/social-platforms')
        }
      }
    },
    refreshToken(id) {
      router.post(`/social-accounts/${id}/refresh`, {}, { preserveScroll: true })
    },
    validateConnection(id) {
      router.post(`/social-accounts/${id}/validate`, {}, { preserveScroll: true })
    },
    disconnect(id) {
      this.disconnectId = id
      this.showDisconnectDialog = true
    },
    confirmDisconnect() {
      if (this.disconnectId) {
        router.delete(`/social-accounts/${this.disconnectId}`)
      }
      this.showDisconnectDialog = false
      this.disconnectId = null
    },
    isAlreadyConnected(platform) {
      return this.socialAccounts.some(a => a.platform === platform)
    },
    getConnectedCount(platform) {
      return this.socialAccounts.filter(a => a.platform === platform).length
    },
    getStatusGradient(account) {
      if (account.is_token_expired) return 'linear-gradient(90deg, #ef4444, #f97316)'
      if (!account.is_connected) return 'linear-gradient(90deg, #94a3b8, #cbd5e1)'
      return this.platformsMeta[account.platform]?.gradient || '#10b981'
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Stats Grid ===== */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
.stat-card {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem 1rem; border-radius: 12px;
  background: white; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.stat-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.92rem;
}
.stat-icon--purple { background: #eef2ff; color: #6366f1; }
.stat-icon--green { background: #ecfdf5; color: #10b981; }
.stat-icon--red { background: #fef2f2; color: #ef4444; }
.stat-icon--blue { background: #eff6ff; color: #3b82f6; }
.stat-body { display: flex; flex-direction: column; }
.stat-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.stat-label { font-size: 0.68rem; color: #94a3b8; }

/* ===== Empty Connect State ===== */
.empty-connect-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 3rem 2rem; background: white; border-radius: 16px;
  border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  text-align: center;
}
.empty-illustration {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; color: #6366f1; margin-bottom: 1rem;
}
.empty-connect-state h3 { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin: 0 0 0.3rem; }
.empty-connect-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; max-width: 400px; }

.platform-connect-row { display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: center; }
.platform-connect-btn {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.65rem 1.15rem; border-radius: 12px;
  border: 2px solid #e2e8f0; background: white;
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.25s; color: #475569;
}
.platform-connect-btn i { font-size: 1.05rem; color: var(--pc-color); }
.platform-connect-btn:hover {
  border-color: var(--pc-color);
  box-shadow: 0 4px 14px color-mix(in sRGB, var(--pc-color) 20%, transparent);
  transform: translateY(-2px);
}

/* ===== Accounts Grid ===== */
.accounts-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.85rem;
}

.account-card {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.account-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-3px);
}
.account-card--expired { border-color: #fecaca; }
.account-card--disconnected { opacity: 0.7; }

.card-status-bar { height: 4px; }

.card-body { padding: 1.15rem; }

.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.platform-icon {
  width: 44px; height: 44px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.15rem;
  box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

/* Status Chips */
.status-chip {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.62rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 6px;
}
.status-chip i { font-size: 0.55rem; }
.status-chip--connected { background: #ecfdf5; color: #059669; }
.status-chip--expired { background: #fef2f2; color: #dc2626; }
.status-chip--disconnected { background: #f1f5f9; color: #64748b; }

.card-name { font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0; }
.card-username { font-size: 0.72rem; color: #94a3b8; margin: 0.1rem 0 0; }
.card-platform-label { font-size: 0.68rem; color: #6366f1; font-weight: 500; margin: 0.2rem 0 0; }

.card-meta {
  display: flex; flex-direction: column; gap: 0.25rem;
  margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px solid #f8fafc;
}
.meta-item {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.68rem; color: #64748b;
}
.meta-item i { font-size: 0.58rem; color: #94a3b8; }

/* Card Actions */
.card-actions {
  display: flex; border-top: 1px solid #f8fafc; background: #fafbfc;
}
.card-action-btn {
  flex: 1; display: flex; align-items: center; justify-content: center;
  padding: 0.55rem; border: none; background: transparent;
  cursor: pointer; font-size: 0.78rem; transition: all 0.2s;
}
.card-action-btn:not(:last-child) { border-right: 1px solid #f1f5f9; }
.card-action-btn--info { color: #3b82f6; }
.card-action-btn--info:hover { background: #eff6ff; }
.card-action-btn--warning { color: #f59e0b; }
.card-action-btn--warning:hover { background: #fffbeb; }
.card-action-btn--danger { color: #ef4444; }
.card-action-btn--danger:hover { background: #fef2f2; }

/* Add Account Card */
.add-account-card {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.5rem; padding: 2rem; border-radius: 16px;
  border: 2px dashed #e2e8f0; background: transparent;
  cursor: pointer; transition: all 0.2s; color: #94a3b8;
  min-height: 200px;
}
.add-account-card:hover { border-color: #6366f1; color: #6366f1; background: #fafafe; }
.add-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: #f1f5f9; display: flex; align-items: center; justify-content: center;
  font-size: 1rem; transition: all 0.2s;
}
.add-account-card:hover .add-icon { background: #eef2ff; }
.add-account-card span { font-size: 0.78rem; font-weight: 500; }

/* ===== Connect Modal ===== */
.connect-modal { padding: 0.25rem 0; }
.connect-subtitle { font-size: 0.82rem; color: #64748b; margin: 0 0 1.15rem; }

.connect-grid { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem; }
.connect-option {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.85rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 12px;
  background: white; cursor: pointer; transition: all 0.2s;
}
.connect-option:hover { border-color: #6366f1; box-shadow: 0 2px 8px rgba(99,102,241,0.1); }
.connect-option--connected { border-color: #d1fae5; background: #f0fdf4; }

.connect-option-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem;
}
.connect-option-info { flex: 1; }
.connect-option-name { font-size: 0.88rem; font-weight: 600; color: #1e293b; display: block; }
.connect-option-status { font-size: 0.68rem; color: #10b981; display: flex; align-items: center; gap: 0.2rem; }
.connect-option-status i { font-size: 0.58rem; }
.connect-option-status--none { color: #94a3b8; }
.connect-arrow { color: #cbd5e1; font-size: 0.72rem; }

/* How it works */
.connect-howto {
  background: #f8fafc; border-radius: 12px; padding: 1rem;
}
.connect-howto h4 {
  font-size: 0.78rem; font-weight: 600; color: #475569; margin: 0 0 0.65rem;
  display: flex; align-items: center; gap: 0.3rem;
}
.connect-howto h4 i { color: #6366f1; font-size: 0.72rem; }
.howto-steps { display: flex; align-items: center; justify-content: center; gap: 0; }
.howto-step { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; }
.howto-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: #6366f1; color: white; font-size: 0.72rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.howto-step span { font-size: 0.65rem; color: #64748b; text-align: center; max-width: 90px; }
.howto-arrow { color: #cbd5e1; margin: 0 0.75rem; margin-bottom: 1rem; }

/* Disconnect Dialog */
.disconnect-content { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0.5rem; }
.disconnect-icon { font-size: 2.5rem; color: #f59e0b; margin-bottom: 0.75rem; }
.disconnect-icon i { font-size: 2.5rem; }
.disconnect-content p { font-size: 0.82rem; color: #64748b; }

/* ===== Responsive ===== */
@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr; }
  .accounts-grid { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .platform-connect-row { flex-direction: column; }
  .howto-steps { flex-direction: column; }
  .howto-arrow { transform: rotate(90deg); margin: 0.25rem 0; }
}
</style>
