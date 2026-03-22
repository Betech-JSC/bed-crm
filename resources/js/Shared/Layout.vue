<template>
  <div>
    <div id="dropdown" />
    <div class="admin-layout">
      <!-- Sidebar -->
      <aside class="sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed, 'sidebar-mobile-open': mobileMenuOpen }">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
          <Link class="sidebar-logo" href="/">
            <img
              v-if="auth.user && auth.user.account && auth.user.account.logo"
              :src="auth.user.account.logo"
              :alt="auth.user.account.name || 'Logo'"
              class="logo-img"
            />
            <logo v-else class="logo-svg" width="120" height="28" />
          </Link>
          <button class="sidebar-toggle-btn" @click="toggleSidebar" title="Toggle sidebar">
            <i :class="sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'" />
          </button>
        </div>

        <!-- Sidebar Menu -->
        <nav class="sidebar-nav">
          <main-menu />
        </nav>

        <!-- Sidebar Footer / User -->
        <div class="sidebar-footer">
          <div class="user-card">
            <div class="user-avatar">
              <span>{{ auth.user.first_name?.charAt(0) }}{{ auth.user.last_name?.charAt(0) }}</span>
            </div>
            <div class="user-info">
              <span class="user-name">{{ auth.user.first_name }} {{ auth.user.last_name }}</span>
              <span class="user-role">{{ auth.user.role || 'User' }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Mobile overlay -->
      <div v-if="mobileMenuOpen" class="mobile-overlay" @click="mobileMenuOpen = false" />

      <!-- Main Content Area -->
      <div class="main-wrapper">
        <!-- Top Header Bar -->
        <header class="topbar">
          <div class="topbar-left">
            <button class="mobile-menu-btn" @click="mobileMenuOpen = !mobileMenuOpen">
              <i class="pi pi-bars" />
            </button>
            <div class="breadcrumb-area">
              <span class="breadcrumb-text">{{ auth.user.account.name }}</span>
            </div>
          </div>
          <div class="topbar-right">
            <LanguageSwitcher :locale="locale" />

            <!-- Notifications -->
            <div class="notif-wrapper" v-click-outside="() => notifOpen = false">
              <button class="topbar-icon-btn" title="Notifications" @click="notifOpen = !notifOpen">
                <i class="pi pi-bell" />
                <span v-if="notifications.unread_count > 0" class="notification-badge">{{ notifications.unread_count > 9 ? '9+' : notifications.unread_count }}</span>
              </button>

              <transition name="notif-slide">
                <div v-if="notifOpen" class="notif-panel">
                  <div class="notif-panel-header">
                    <h4>Thông báo</h4>
                    <button v-if="notifications.items.length" class="notif-mark-all" @click="markAllRead">Đánh dấu tất cả đã đọc</button>
                  </div>
                  <div class="notif-panel-body">
                    <div v-if="notifications.items.length" class="notif-list">
                      <div v-for="n in notifications.items" :key="n.id" class="notif-item" :class="'ni-' + (n.severity || 'info')" @click="openNotif(n)">
                        <div class="notif-icon-box"><i :class="n.icon || 'pi pi-info-circle'" /></div>
                        <div class="notif-content">
                          <span class="notif-title">{{ n.title }}</span>
                          <span class="notif-body" v-if="n.body">{{ n.body }}</span>
                          <span class="notif-time">{{ n.created_at }}</span>
                        </div>
                        <button class="notif-dismiss" @click.stop="markRead(n)" title="Đánh dấu đã đọc"><i class="pi pi-check" /></button>
                      </div>
                    </div>
                    <div v-else class="notif-empty">
                      <i class="pi pi-check-circle" />
                      <span>Không có thông báo mới</span>
                    </div>
                  </div>
                  <Link href="/notifications" class="notif-panel-footer" @click="notifOpen = false">Xem tất cả thông báo <i class="pi pi-arrow-right" /></Link>
                </div>
              </transition>
            </div>

            <!-- User dropdown -->
            <dropdown placement="bottom-end">
              <template #default>
                <div class="topbar-user-trigger">
                  <div class="topbar-avatar">
                    <span>{{ auth.user.first_name?.charAt(0) }}{{ auth.user.last_name?.charAt(0) }}</span>
                  </div>
                  <span class="topbar-username">{{ auth.user.first_name }}</span>
                  <i class="pi pi-chevron-down topbar-chevron" />
                </div>
              </template>
              <template #dropdown>
                <div class="user-dropdown">
                  <div class="user-dropdown-header">
                    <div class="dropdown-avatar">
                      <span>{{ auth.user.first_name?.charAt(0) }}{{ auth.user.last_name?.charAt(0) }}</span>
                    </div>
                    <div class="dropdown-user-info">
                      <span class="dropdown-user-name">{{ auth.user.first_name }} {{ auth.user.last_name }}</span>
                      <span class="dropdown-user-email">{{ auth.user.email }}</span>
                    </div>
                  </div>
                  <div class="user-dropdown-divider" />
                  <Link class="user-dropdown-item" :href="`/users/${auth.user.id}/edit`">
                    <i class="pi pi-user" />
                    <span>Hồ sơ cá nhân</span>
                  </Link>
                  <Link class="user-dropdown-item" href="/users">
                    <i class="pi pi-users" />
                    <span>Quản lý người dùng</span>
                  </Link>
                  <Link class="user-dropdown-item" href="/account-settings">
                    <i class="pi pi-cog" />
                    <span>Cài đặt</span>
                  </Link>
                  <div class="user-dropdown-divider" />
                  <Link class="user-dropdown-item user-dropdown-logout" href="/logout" method="delete" as="button">
                    <i class="pi pi-sign-out" />
                    <span>Đăng xuất</span>
                  </Link>
                </div>
              </template>
            </dropdown>
          </div>
        </header>

        <!-- Page Content -->
        <main class="main-content" scroll-region>
          <slot />
        </main>
      </div>

      <!-- AI Chat Widget (global) -->
      <ai-chat-widget />

      <!-- Toast Notifications (global) -->
      <flash-messages />
    </div>
  </div>
</template>

<script>
import { Link, router } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'
import Logo from '@/Shared/Logo.vue'
import Dropdown from '@/Shared/Dropdown.vue'
import MainMenu from '@/Shared/MainMenu.vue'
import FlashMessages from '@/Shared/FlashMessages.vue'
import LanguageSwitcher from '@/Shared/LanguageSwitcher.vue'
import AiChatWidget from '@/Shared/AiChatWidget.vue'

export default {
  components: {
    AiChatWidget,
    Dropdown,
    FlashMessages,
    Icon,
    LanguageSwitcher,
    Link,
    Logo,
    MainMenu,
  },
  directives: {
    'click-outside': {
      mounted(el, binding) {
        el._clickOutside = (e) => {
          if (!el.contains(e.target)) binding.value(e)
        }
        document.addEventListener('click', el._clickOutside)
      },
      unmounted(el) {
        document.removeEventListener('click', el._clickOutside)
      },
    },
  },
  props: {
    auth: Object,
    locale: {
      type: String,
      default: 'vi',
    },
    notifications: {
      type: Object,
      default: () => ({ unread_count: 0, items: [] }),
    },
  },
  data() {
    return {
      sidebarCollapsed: false,
      mobileMenuOpen: false,
      notifOpen: false,
    }
  },
  methods: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },
    markRead(n) {
      fetch(`/notifications/${n.id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' } })
        .then(() => {
          const idx = this.notifications.items.findIndex(i => i.id === n.id)
          if (idx !== -1) this.notifications.items.splice(idx, 1)
          this.notifications.unread_count = Math.max(0, this.notifications.unread_count - 1)
        })
    },
    markAllRead() {
      fetch('/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' } })
        .then(() => {
          this.notifications.items = []
          this.notifications.unread_count = 0
        })
    },
    openNotif(n) {
      this.markRead(n)
      this.notifOpen = false
      if (n.link) {
        router.visit(n.link)
      }
    },
  },
}
</script>

<style scoped>
/* ===== Layout Grid ===== */
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #f0f2f5;
}

/* ===== Sidebar ===== */
.sidebar {
  width: 260px;
  min-height: 100vh;
  background: linear-gradient(180deg, #111827 0%, #0f172a 50%, #111827 100%);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 50;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  overflow: hidden;
  transition: opacity 0.2s;
  flex: 1;
}

.logo-img {
  height: 32px;
  width: auto;
  max-width: 140px;
  object-fit: contain;
}

.logo-svg {
  fill: white;
  height: 28px;
  width: auto;
}

.sidebar-toggle-btn {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.sidebar-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
}

/* ===== Sidebar Nav ===== */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.1) transparent;
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
}

/* ===== Sidebar Footer ===== */
.sidebar-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.user-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
  transition: background 0.2s;
}

.user-card:hover {
  background: rgba(255, 255, 255, 0.08);
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ef6820, #e04f0f);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-avatar span {
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.user-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 0.68rem;
  color: rgba(255, 255, 255, 0.4);
  text-transform: capitalize;
}

/* ===== Main Wrapper ===== */
.main-wrapper {
  flex: 1;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== Top Bar ===== */
.topbar {
  height: 60px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 40;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mobile-menu-btn {
  display: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #f3f4f6;
  border: none;
  color: #374151;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  transition: all 0.2s;
}

.mobile-menu-btn:hover {
  background: #e5e7eb;
}

.breadcrumb-area {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.breadcrumb-text {
  font-size: 0.88rem;
  font-weight: 600;
  color: #374151;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.topbar-icon-btn {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  transition: all 0.2s;
  position: relative;
}

.topbar-icon-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.notif-wrapper { position: relative }
.notification-badge {
  position: absolute; top: 4px; right: 4px;
  min-width: 17px; height: 17px; padding: 0 4px;
  border-radius: 9px; background: #ef4444; color: white;
  font-size: 0.58rem; font-weight: 700; line-height: 17px;
  text-align: center; border: 2px solid white;
}
.notif-panel {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 380px; max-height: 480px;
  background: white; border-radius: 16px;
  box-shadow: 0 16px 48px rgba(0,0,0,.14); border: 1px solid #e5e7eb;
  display: flex; flex-direction: column; overflow: hidden; z-index: 100;
}
.notif-slide-enter-active, .notif-slide-leave-active { transition: all .2s ease }
.notif-slide-enter-from, .notif-slide-leave-to { opacity: 0; transform: translateY(-8px) }
.notif-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: .85rem 1rem; border-bottom: 1px solid #f3f4f6;
}
.notif-panel-header h4 { margin: 0; font-size: .9rem; font-weight: 700; color: #111827 }
.notif-mark-all {
  background: none; border: none; font-size: .68rem; font-weight: 600;
  color: #6366f1; cursor: pointer; padding: .2rem .4rem; border-radius: 6px;
  transition: background .2s; font-family: inherit;
}
.notif-mark-all:hover { background: #eef2ff }
.notif-panel-body { flex: 1; overflow-y: auto; max-height: 360px }
.notif-list { padding: .25rem 0 }
.notif-item {
  display: flex; align-items: flex-start; gap: .65rem;
  padding: .65rem 1rem; cursor: pointer; transition: background .15s;
  border-left: 3px solid transparent;
}
.notif-item:hover { background: #fafbfe }
.ni-info { border-left-color: #3b82f6 }
.ni-success { border-left-color: #16a34a }
.ni-warning { border-left-color: #f59e0b }
.ni-danger { border-left-color: #ef4444 }
.notif-icon-box {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; flex-shrink: 0;
}
.ni-info .notif-icon-box { background: #eff6ff; color: #3b82f6 }
.ni-success .notif-icon-box { background: #f0fdf4; color: #16a34a }
.ni-warning .notif-icon-box { background: #fffbeb; color: #f59e0b }
.ni-danger .notif-icon-box { background: #fef2f2; color: #ef4444 }
.notif-content { flex: 1; min-width: 0 }
.notif-title { display: block; font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3 }
.notif-body { display: block; font-size: .7rem; color: #64748b; margin-top: .1rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden }
.notif-time { display: block; font-size: .6rem; color: #94a3b8; margin-top: .2rem }
.notif-dismiss {
  width: 24px; height: 24px; border-radius: 6px; border: none;
  background: transparent; color: #94a3b8; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .6rem; transition: all .2s; flex-shrink: 0; margin-top: .15rem;
}
.notif-dismiss:hover { background: #dcfce7; color: #16a34a }
.notif-empty {
  display: flex; flex-direction: column; align-items: center; gap: .5rem;
  padding: 2rem 1rem; color: #94a3b8;
}
.notif-empty i { font-size: 1.5rem; color: #d1d5db }
.notif-empty span { font-size: .82rem }
.notif-panel-footer {
  display: flex; align-items: center; justify-content: center; gap: .3rem;
  padding: .65rem 1rem; border-top: 1px solid #f3f4f6;
  font-size: .78rem; font-weight: 600; color: #6366f1;
  text-decoration: none; transition: background .15s;
}
.notif-panel-footer:hover { background: #fafbfe }
.notif-panel-footer i { font-size: .6rem }

/* ===== User Trigger ===== */
.topbar-user-trigger {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.6rem;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s;
}

.topbar-user-trigger:hover {
  background: #f3f4f6;
}

.topbar-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #ef6820, #e04f0f);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.topbar-avatar span {
  font-size: 0.65rem;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.topbar-username {
  font-size: 0.85rem;
  font-weight: 500;
  color: #374151;
}

.topbar-chevron {
  font-size: 0.7rem;
  color: #9ca3af;
}

/* ===== User Dropdown ===== */
.user-dropdown {
  width: 260px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
  border: 1px solid #e5e7eb;
  overflow: hidden;
  margin-top: 0.5rem;
}

.user-dropdown-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
}

.dropdown-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ef6820, #e04f0f);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.dropdown-avatar span {
  font-size: 0.8rem;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
}

.dropdown-user-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dropdown-user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #111827;
}

.dropdown-user-email {
  font-size: 0.75rem;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-dropdown-divider {
  height: 1px;
  background: #f3f4f6;
}

.user-dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 1rem;
  font-size: 0.82rem;
  color: #374151;
  text-decoration: none;
  transition: all 0.15s;
  border: none;
  background: none;
  width: 100%;
  cursor: pointer;
  text-align: left;
}

.user-dropdown-item:hover {
  background: #f9fafb;
  color: #111827;
}

.user-dropdown-item i {
  font-size: 0.9rem;
  color: #9ca3af;
  width: 18px;
  text-align: center;
}

.user-dropdown-logout {
  color: #ef4444;
}

.user-dropdown-logout:hover {
  background: #fef2f2;
  color: #dc2626;
}

.user-dropdown-logout i {
  color: #ef4444;
}

/* ===== Main Content ===== */
.main-content {
  flex: 1;
  padding: 1.5rem 2rem;
  overflow-y: auto;
}

/* ===== Mobile Overlay ===== */
.mobile-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 45;
  backdrop-filter: blur(2px);
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 280px;
  }

  .sidebar-mobile-open {
    transform: translateX(0);
  }

  .sidebar-toggle-btn {
    display: none;
  }

  .main-wrapper {
    margin-left: 0;
  }

  .mobile-menu-btn {
    display: flex;
  }

  .mobile-overlay {
    display: block;
  }

  .main-content {
    padding: 1rem;
  }

  .topbar-username {
    display: none;
  }
}
</style>
