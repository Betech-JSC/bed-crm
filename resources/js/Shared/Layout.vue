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

            <!-- Notifications placeholder -->
            <button class="topbar-icon-btn" title="Notifications">
              <i class="pi pi-bell" />
              <span class="notification-dot" />
            </button>

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
          <flash-messages />
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'
import Logo from '@/Shared/Logo.vue'
import Dropdown from '@/Shared/Dropdown.vue'
import MainMenu from '@/Shared/MainMenu.vue'
import FlashMessages from '@/Shared/FlashMessages.vue'
import LanguageSwitcher from '@/Shared/LanguageSwitcher.vue'

export default {
  components: {
    Dropdown,
    FlashMessages,
    Icon,
    LanguageSwitcher,
    Link,
    Logo,
    MainMenu,
  },
  props: {
    auth: Object,
    locale: {
      type: String,
      default: 'vi',
    },
  },
  data() {
    return {
      sidebarCollapsed: false,
      mobileMenuOpen: false,
    }
  },
  methods: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
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
  text-decoration: none;
  overflow: hidden;
  transition: opacity 0.2s;
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

.notification-dot {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid white;
}

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
