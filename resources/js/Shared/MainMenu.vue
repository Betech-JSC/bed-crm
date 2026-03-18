<template>
  <div class="main-menu">
    <!-- Dashboard -->
    <div class="menu-section">
      <Link class="menu-link" :class="{ 'is-active': isUrl('') }" href="/">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('') }">
          <i class="pi pi-th-large" />
        </div>
        <span class="menu-label">{{ t('common.dashboard') }}</span>
      </Link>
    </div>


    <!-- Sales Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.sales') }}</div>
      <MenuGroup
        :title="t('common.sales_management')"
        icon="pi pi-shopping-bag"
        :is-open="openGroups.includes('sales')"
        @toggle="toggleGroup('sales')"
      >
        <MenuItem href="/leads" icon="pi pi-bullseye" :active="isUrl('leads')">{{ t('common.leads') }}</MenuItem>
        <MenuItem href="/deals" icon="pi pi-briefcase" :active="isUrl('deals')">{{ t('common.deals') }}</MenuItem>
        <MenuItem href="/contacts" icon="pi pi-id-card" :active="isUrl('contacts')">{{ t('common.contacts') }}</MenuItem>
        <MenuItem href="/organizations" icon="pi pi-building" :active="isUrl('organizations')">{{ t('common.organizations') }}</MenuItem>
        <MenuItem href="/proposals" icon="pi pi-file-edit" :active="isUrl('proposals')">{{ t('common.proposals') }}</MenuItem>
        <MenuItem href="/sales-playbooks" icon="pi pi-book" :active="isUrl('sales-playbooks')">{{ t('common.sales_playbooks') }}</MenuItem>
      </MenuGroup>
    </div>

    <!-- Customer Success Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.customer_success') }}</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('customers') }" href="/customers">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('customers') }">
          <i class="pi pi-heart" />
        </div>
        <span class="menu-label">{{ t('common.customers') }}</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('projects') }" href="/projects">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('projects') }">
          <i class="pi pi-folder" />
        </div>
        <span class="menu-label">{{ t('common.projects') }}</span>
      </Link>
    </div>

    <!-- HR & Performance Module -->
    <div class="menu-section">
      <div class="menu-section-title">HR &amp; Performance</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('hr') && !isUrl('hr/employees') && !isUrl('hr/kpi') }" href="/hr">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('hr') && !isUrl('hr/employees') && !isUrl('hr/kpi') }">
          <i class="pi pi-chart-bar" />
        </div>
        <span class="menu-label">Team Dashboard</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('hr/employees') }" href="/hr/employees">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('hr/employees') }">
          <i class="pi pi-users" />
        </div>
        <span class="menu-label">Employees</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('hr/kpi-definitions') }" href="/hr/kpi-definitions">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('hr/kpi-definitions') }">
          <i class="pi pi-flag" />
        </div>
        <span class="menu-label">KPI Definitions</span>
      </Link>
    </div>

    <!-- Finance Module -->
    <div class="menu-section">
      <div class="menu-section-title">Finance</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('finance') && !isUrl('finance/transactions') }" href="/finance">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('finance') && !isUrl('finance/transactions') }">
          <i class="pi pi-chart-pie" />
        </div>
        <span class="menu-label">Overview</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('finance/transactions') }" href="/finance/transactions">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('finance/transactions') }">
          <i class="pi pi-wallet" />
        </div>
        <span class="menu-label">Transactions</span>
      </Link>
    </div>

    <!-- AI Intelligence -->
    <!-- Strategy Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.strategy') || 'Strategy' }}</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('strategy') }" href="/strategy">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('strategy') }">
          <i class="pi pi-compass" />
        </div>
        <span class="menu-label">{{ isVi ? 'Bảng chiến lược' : 'Strategy Cockpit' }}</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('okrs') }" href="/okrs">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('okrs') }">
          <i class="pi pi-sitemap" />
        </div>
        <span class="menu-label">OKRs</span>
      </Link>
    </div>

    <!-- Intelligence Module -->
    <div class="menu-section">
      <div class="menu-section-title">Intelligence</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('intelligence') }" href="/intelligence">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('intelligence') }">
          <i class="pi pi-sparkles" />
        </div>
        <span class="menu-label">AI Analyst</span>
      </Link>
    </div>

    <!-- Marketing Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.marketing') }}</div>
      <MenuGroup
        :title="t('common.email_marketing')"
        icon="pi pi-envelope"
        :is-open="openGroups.includes('marketing')"
        @toggle="toggleGroup('marketing')"
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
    </div>

    <!-- Automation Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.automation') }}</div>
      <MenuGroup
        :title="t('common.automation')"
        icon="pi pi-sync"
        :is-open="openGroups.includes('automation')"
        @toggle="toggleGroup('automation')"
      >
        <MenuItem href="/workflows" icon="pi pi-sitemap" :active="isUrl('workflows')">{{ t('common.workflows') }}</MenuItem>
        <MenuItem href="/chat-widgets" icon="pi pi-comments" :active="isUrl('chat-widgets')">{{ t('common.chat_widgets') }}</MenuItem>
        <MenuItem href="/chat-conversations" icon="pi pi-comment" :active="isUrl('chat-conversations')">{{ t('common.conversations') }}</MenuItem>
      </MenuGroup>
    </div>

    <!-- Analytics Module -->
    <div class="menu-section">
      <div class="menu-section-title">{{ t('common.analytics') }}</div>
      <Link class="menu-link" :class="{ 'is-active': isUrl('reports') }" href="/reports">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('reports') }">
          <i class="pi pi-chart-bar" />
        </div>
        <span class="menu-label">{{ t('common.reports') }}</span>
      </Link>
      <Link class="menu-link" :class="{ 'is-active': isUrl('icps') }" href="/icps">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('icps') }">
          <i class="pi pi-star" />
        </div>
        <span class="menu-label">{{ t('common.icp_profiles') }}</span>
      </Link>
    </div>

    <!-- Notifications -->
    <div class="menu-section">
      <Link class="menu-link" :class="{ 'is-active': isUrl('notifications') }" href="/notifications">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('notifications') }">
          <i class="pi pi-bell" />
        </div>
        <span class="menu-label">{{ t('common.notifications') || 'Notifications' }}</span>
      </Link>
    </div>

    <!-- Files -->
    <div class="menu-section">
      <Link class="menu-link" :class="{ 'is-active': isUrl('files') }" href="/files">
        <div class="menu-icon-wrapper" :class="{ 'icon-active': isUrl('files') }">
          <i class="pi pi-folder" />
        </div>
        <span class="menu-label">{{ t('common.files') }}</span>
      </Link>
    </div>

    <!-- Settings Section -->
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
        <MenuItem href="/sla-settings" icon="pi pi-clock" :active="isUrl('sla-settings')">{{ t('common.sla_settings') }}</MenuItem>
        <MenuItem href="/users" icon="pi pi-user" :active="isUrl('users')">{{ t('common.users') }}</MenuItem>
        <MenuItem href="/roles" icon="pi pi-shield" :active="isUrl('roles')">{{ t('common.roles') }}</MenuItem>
        <MenuItem href="/permissions" icon="pi pi-lock" :active="isUrl('permissions')">{{ t('common.permissions_menu') }}</MenuItem>
        <MenuItem href="/settings/roles" icon="pi pi-verified" :active="isUrl('settings/roles')">{{ t('common.rbac') || 'RBAC' }}</MenuItem>
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
  computed: {
    isVi() { return this.locale === 'vi' },
  },
  mounted() {
    const currentUrl = this.$page.url.substr(1)
    
    if (currentUrl.startsWith('leads') || currentUrl.startsWith('deals') || 
        currentUrl.startsWith('contacts') || currentUrl.startsWith('organizations') ||
        currentUrl.startsWith('proposals') || currentUrl.startsWith('sales-playbooks')) {
      this.openGroups.push('sales')
    }
    
    if (currentUrl.startsWith('email-')) {
      this.openGroups.push('marketing')
    }

    if (currentUrl.startsWith('content-') || currentUrl.startsWith('social-')) {
      this.openGroups.push('content')
    }
    
    if (currentUrl.startsWith('workflows') || currentUrl.startsWith('chat-')) {
      this.openGroups.push('automation')
    }
    
    if (currentUrl.startsWith('reports') || currentUrl.startsWith('icps')) {
      this.openGroups.push('analytics')
    }
    
    if (currentUrl.startsWith('account-settings') || currentUrl.startsWith('smtp-settings') || 
        currentUrl.startsWith('sla-settings') || currentUrl.startsWith('users') ||
        currentUrl.startsWith('roles') || currentUrl.startsWith('permissions')) {
      this.openGroups.push('settings')
    }
  },
  methods: {
    isUrl(...urls) {
      let currentUrl = this.$page.url.substr(1)
      if (urls[0] === '') {
        return currentUrl === ''
      }
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
