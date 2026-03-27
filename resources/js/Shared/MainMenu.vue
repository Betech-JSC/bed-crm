<template>
  <div class="main-menu">

    <!-- ═══ Dashboard ═══ -->
    <div class="menu-section">
      <Link class="menu-link" :class="{ 'is-active': isUrl('') }" href="/">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('') }">
          <i class="pi pi-th-large" />
        </div>
        <span class="menu-label">{{ t('common.dashboard') }}</span>
      </Link>
    </div>

    <!-- ═══ CRM / Sales ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.sales') }}</div>
      <MenuItem href="/leads" icon="pi pi-bullseye" :active="isUrl('leads')">{{ t('common.leads') }}</MenuItem>
      <MenuItem href="/outbound-sales" icon="pi pi-send" :active="isUrl('outbound-sales')">Outbound Sales</MenuItem>
      <MenuItem href="/deals" icon="pi pi-briefcase" :active="isUrl('deals')">{{ t('common.deals') }}</MenuItem>
      <MenuItem href="/sales-pipeline" icon="pi pi-arrow-right-arrow-left" :active="isUrl('sales-pipeline')">Quy trình bán hàng</MenuItem>
      <MenuItem href="/contacts" icon="pi pi-id-card" :active="isUrl('contacts')">{{ t('common.contacts') }}</MenuItem>
      <MenuItem href="/organizations" icon="pi pi-building" :active="isUrl('organizations')">{{ t('common.organizations') }}</MenuItem>

    </div>

    <!-- ═══ Business ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">Kinh doanh</div>
      <MenuItem href="/products" icon="pi pi-box" :active="isUrl('products')">Sản phẩm & DV</MenuItem>
      <MenuItem href="/quotations" icon="pi pi-file-edit" :active="isUrl('quotations')">Báo giá</MenuItem>
      <MenuItem href="/contracts" icon="pi pi-file-check" :active="isUrl('contracts')">Hợp đồng</MenuItem>
      <MenuItem href="/approvals" icon="pi pi-verified" :active="isUrl('approvals') && !isUrl('approvals/')">Phê duyệt</MenuItem>
      <MenuItem href="/dropship" icon="pi pi-truck" :active="isUrl('dropship')">Dropship</MenuItem>
    </div>

    <!-- ═══ Loyalty (CSKH) ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">Loyalty & CSKH</div>
      <MenuItem href="/customers" icon="pi pi-heart" :active="isUrl('customers')">{{ t('common.customers') }}</MenuItem>
      <MenuItem href="/support-tickets" icon="pi pi-ticket" :active="isUrl('support-tickets')">Tickets Hỗ trợ</MenuItem>
    </div>

    <!-- ═══ Quản lý Dự án ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">Quản lý Dự án</div>
      <MenuItem href="/projects" icon="pi pi-folder" :active="isUrl('projects')">{{ t('common.projects') }}</MenuItem>
      <MenuItem href="/meetings" icon="pi pi-video" :active="isUrl('meetings')">Meetings</MenuItem>
    </div>

    <!-- ═══ Marketing ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.marketing') }}</div>
      <MenuItem href="/web-forms" icon="pi pi-file-edit" :active="isUrl('web-forms')">Web Forms</MenuItem>
      <MenuItem href="/utm-tracking" icon="pi pi-chart-line" :active="isUrl('utm-tracking')">UTM Tracking</MenuItem>
      <MenuItem href="/landing-pages" icon="pi pi-desktop" :active="isUrl('landing-pages')">Landing Pages</MenuItem>
      <MenuItem href="/seo-dashboard" icon="pi pi-search" :active="isUrl('seo-dashboard')">SEO Dashboard</MenuItem>
      <MenuItem href="/content-calendar" icon="pi pi-calendar" :active="isUrl('content-calendar')">Content Calendar</MenuItem>
      <MenuItem href="/customer-reviews" icon="pi pi-star" :active="isUrl('customer-reviews')">Customer Reviews</MenuItem>
      <MenuItem href="/referral-program" icon="pi pi-users" :active="isUrl('referral-program')">Referral Program</MenuItem>
      <MenuItem href="/link-bio" icon="pi pi-link" :active="isUrl('link-bio')">Link-in-Bio</MenuItem>
      <MenuItem href="/qr-codes" icon="pi pi-qrcode" :active="isUrl('qr-codes')">QR Codes</MenuItem>
      <MenuItem href="/case-studies" icon="pi pi-trophy" :active="isUrl('case-studies')">{{ t('common.case_studies') }}</MenuItem>
      <MenuGroup
        :title="t('common.email_marketing')"
        icon="pi pi-envelope"
        :is-open="openGroups.includes('email')"
        @toggle="toggleGroup('email')"
      >
        <MenuItem href="/email-templates" icon="pi pi-file" :active="isUrl('email-templates')">{{ t('common.templates') }}</MenuItem>
        <MenuItem href="/email-lists" icon="pi pi-list" :active="isUrl('email-lists')">{{ t('common.lists') }}</MenuItem>
        <MenuItem href="/email-campaigns" icon="pi pi-megaphone" :active="isUrl('email-campaigns')">{{ t('common.campaigns') }}</MenuItem>
        <MenuItem href="/email-automations" icon="pi pi-bolt" :active="isUrl('email-automations')">{{ t('common.automations') }}</MenuItem>
      </MenuGroup>
      <MenuGroup
        :title="t('common.content_social')"
        icon="pi pi-share-alt"
        :is-open="openGroups.includes('content')"
        @toggle="toggleGroup('content')"
      >
        <MenuItem href="/content-templates" icon="pi pi-palette" :active="isUrl('content-templates')">{{ t('common.content_templates') }}</MenuItem>
        <MenuItem href="/content-items" icon="pi pi-pencil" :active="isUrl('content-items')">{{ t('common.content_items') }}</MenuItem>
        <MenuItem href="/social-accounts" icon="pi pi-at" :active="isUrl('social-accounts')">{{ t('common.social_accounts') }}</MenuItem>
        <MenuItem href="/social-posts" icon="pi pi-send" :active="isUrl('social-posts')">{{ t('common.social_posts') }}</MenuItem>
      </MenuGroup>
      <MenuItem href="/gmb-dashboard" icon="pi pi-map-marker" :active="isUrl('gmb-dashboard')">Google My Business</MenuItem>
      <MenuItem href="/ai-content" icon="pi pi-sparkles" :active="isUrl('ai-content')">AI Content</MenuItem>
      <MenuItem href="/chatbot-flows" icon="pi pi-comments" :active="isUrl('chatbot-flows')">Chatbot Flows</MenuItem>
      <MenuItem href="/video-ads" icon="pi pi-video" :active="isUrl('video-ads')">Video Ads AI</MenuItem>
      <MenuItem href="/ai-data-hub" icon="pi pi-database" :active="isUrl('ai-data-hub')">AI Data Hub</MenuItem>
      <MenuItem href="/ai-agents" icon="pi pi-sparkles" :active="isUrl('ai-agents')">AI Agents</MenuItem>
    </div>

    <!-- ═══ Organization ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.org_structure') }}</div>
      <MenuItem href="/org-structure" icon="pi pi-sitemap" :active="isUrl('org-structure')">{{ t('common.org_chart') }}</MenuItem>
      <MenuItem href="/hr/employees" icon="pi pi-users" :active="isUrl('hr/employees')">{{ t('common.employees') }}</MenuItem>
      <MenuItem href="/hr/kpi-definitions" icon="pi pi-flag" :active="isUrl('hr/kpi-definitions')">{{ t('common.kpi_definitions') }}</MenuItem>
      <MenuItem href="/org-objectives" icon="pi pi-target" :active="isUrl('org-objectives')">{{ t('common.org_objectives') }}</MenuItem>
      <MenuItem href="/approvals" icon="pi pi-check-circle" :active="isUrl('approvals')">{{ t('common.approvals') }}</MenuItem>
      <MenuItem href="/culture" icon="pi pi-heart-fill" :active="isUrl('culture')">Văn hóa DN</MenuItem>
    </div>

    <!-- ═══ Strategy & Finance ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.strategy') }}</div>
      <MenuItem href="/strategy" icon="pi pi-compass" :active="isUrl('strategy') && !isUrl('strategy/')">{{ t('common.strategy') }}</MenuItem>
      <MenuItem href="/finance" icon="pi pi-chart-pie" :active="isUrl('finance') && !isUrl('finance/transactions')">{{ t('common.finance') }}</MenuItem>
      <MenuItem href="/finance/transactions" icon="pi pi-wallet" :active="isUrl('finance/transactions')">{{ t('common.transactions') }}</MenuItem>
      <MenuItem href="/intelligence" icon="pi pi-sparkles" :active="isUrl('intelligence')">{{ t('common.intelligence') }}</MenuItem>
      <MenuItem href="/reports" icon="pi pi-chart-bar" :active="isUrl('reports')">{{ t('common.reports') }}</MenuItem>
      <MenuItem href="/ceo-roadmap" icon="pi pi-flag-fill" :active="isUrl('ceo-roadmap')">Lộ trình CEO</MenuItem>
    </div>

    <!-- ═══ Tools ═══ -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.tools') }}</div>
      <MenuItem href="/my-tasks" icon="pi pi-check-square" :active="isUrl('my-tasks')">Công việc cá nhân</MenuItem>
      <MenuItem href="/ai-chat" icon="pi pi-sparkles" :active="isUrl('ai-chat')">AI Chat</MenuItem>
      <MenuItem href="/ai-trends" icon="pi pi-bolt" :active="isUrl('ai-trends')">AI Trends</MenuItem>
      <MenuItem href="/prompt-learning" icon="pi pi-graduation-cap" :active="isUrl('prompt-learning')">Học Prompts AI</MenuItem>
      <MenuItem href="/content-studio" icon="pi pi-palette" :active="isUrl('content-studio')">Content Studio</MenuItem>
      <MenuItem href="/wiki" icon="pi pi-book" :active="isUrl('wiki')">Wiki nội bộ</MenuItem>
      <MenuItem href="/files" icon="pi pi-folder-open" :active="isUrl('files')">{{ t('common.files') }}</MenuItem>
      <MenuItem href="/notifications" icon="pi pi-bell" :active="isUrl('notifications')">{{ t('common.notifications') }}</MenuItem>
      <MenuItem href="/system-logs" icon="pi pi-list" :active="isUrl('system-logs')">System Logs</MenuItem>
      <MenuItem href="/system-history" icon="pi pi-history" :active="isUrl('system-history')">Lịch sử</MenuItem>
      <MenuItem href="/system-trash" icon="pi pi-trash" :active="isUrl('system-trash')">Thùng rác</MenuItem>
      <MenuItem href="/documents" icon="pi pi-file-word" :active="isUrl('documents')">Biên bản & Biểu mẫu</MenuItem>
      <MenuItem href="/crm-guides" icon="pi pi-question-circle" :active="isUrl('crm-guides')">Hướng dẫn CRM</MenuItem>
      <MenuItem href="/dev-portal" icon="pi pi-code" :active="isUrl('dev-portal')">Dev Portal</MenuItem>
    </div>

    <!-- ═══ Settings ═══ -->
    <div class="menu-section menu-section-settings">
      <div class="menu-section-title">{{ t('common.settings') }}</div>
      <MenuGroup
        :title="t('common.system')"
        icon="pi pi-cog"
        :is-open="openGroups.includes('settings')"
        @toggle="toggleGroup('settings')"
      >
        <MenuItem href="/account-settings" icon="pi pi-sliders-h" :active="isUrl('account-settings')">{{ t('common.account_settings') }}</MenuItem>
        <MenuItem href="/smtp-settings" icon="pi pi-envelope" :active="isUrl('smtp-settings')">{{ t('common.smtp_settings') }}</MenuItem>
        <MenuItem href="/users" icon="pi pi-user" :active="isUrl('users')">{{ t('common.users') }}</MenuItem>
        <MenuItem href="/roles" icon="pi pi-shield" :active="isUrl('roles')">{{ t('common.roles') }}</MenuItem>
        <MenuItem href="/permissions" icon="pi pi-lock" :active="isUrl('permissions')">{{ t('common.permissions_menu') }}</MenuItem>
        <MenuItem href="/ai-providers" icon="pi pi-sparkles" :active="isUrl('ai-providers')">AI Providers</MenuItem>
        <MenuItem href="/social-platforms" icon="pi pi-share-alt" :active="isUrl('social-platforms')">Social Platforms</MenuItem>
      </MenuGroup>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import MenuGroup from '@/Shared/MenuGroup.vue'
import MenuItem from '@/Shared/MenuItem.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Link,
    MenuGroup,
    MenuItem,
  },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      openGroups: [],
    }
  },
  mounted() {
    const currentUrl = this.$page.url.substr(1)

    // Auto-expand matching groups
    if (currentUrl.startsWith('email-')) this.openGroups.push('email')
    if (currentUrl.startsWith('content-') || currentUrl.startsWith('social-')) this.openGroups.push('content')
    if (['account-settings', 'smtp-settings', 'users', 'roles', 'permissions', 'ai-providers'].some(p => currentUrl.startsWith(p))) {
      this.openGroups.push('settings')
    }
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') return currentUrl === ''
      return urls.filter((url) => currentUrl.startsWith(url)).length
    },
    toggleGroup(group) {
      const index = this.openGroups.indexOf(group)
      if (index > -1) {
        this.openGroups.splice(index, 1)
      } else {
        this.openGroups.push(group)
      }
    },
  },
}
</script>

<style scoped>
.main-menu {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.menu-section {
  margin-bottom: 0.5rem;
}

.menu-section-settings {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.menu-section-title {
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.3);
  padding: 0.5rem 0.75rem 0.35rem;
}

/* ===== Menu Link (standalone items) ===== */
.menu-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.55rem 0.75rem;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.2s ease;
  margin-bottom: 1px;
}

.menu-link:hover {
  background: rgba(255, 255, 255, 0.06);
}

.menu-link.is-active {
  background: rgba(239, 104, 32, 0.12);
}

.menu-icon-wrapper {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}

.menu-icon-wrapper i {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.5);
  transition: color 0.2s;
}

.menu-link:hover .menu-icon-wrapper {
  background: rgba(255, 255, 255, 0.1);
}

.menu-link:hover .menu-icon-wrapper i {
  color: rgba(255, 255, 255, 0.8);
}

.icon-active {
  background: linear-gradient(135deg, rgba(239, 104, 32, 0.25), rgba(239, 104, 32, 0.15)) !important;
}

.icon-active i {
  color: #f48554 !important;
}

.menu-label {
  font-size: 0.82rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.6);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
}

.menu-link:hover .menu-label {
  color: rgba(255, 255, 255, 0.9);
}

.menu-link.is-active .menu-label {
  color: #f8b089;
  font-weight: 600;
}
</style>
