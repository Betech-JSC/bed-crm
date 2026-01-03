<template>
  <div>
    <!-- Dashboard -->
    <div class="mb-3">
      <Link class="group flex items-center py-2.5" href="/">
        <icon name="dashboard" class="mr-3 w-5 h-5 flex-shrink-0" :class="isUrl('') ? 'fill-white' : 'fill-primary-400 group-hover:fill-white'" />
        <div class="text-sm font-medium truncate" :class="isUrl('') ? 'text-white' : 'text-primary-300 group-hover:text-white'">Dashboard</div>
      </Link>
    </div>

    <!-- Sales Module -->
    <MenuGroup
      title="Sales"
      icon="briefcase"
      :is-open="openGroups.includes('sales')"
      @toggle="toggleGroup('sales')"
    >
      <MenuItem href="/leads" icon="target" :active="isUrl('leads')">Leads</MenuItem>
      <MenuItem href="/deals" icon="briefcase" :active="isUrl('deals')">Deals</MenuItem>
      <MenuItem href="/contacts" icon="users" :active="isUrl('contacts')">Contacts</MenuItem>
      <MenuItem href="/organizations" icon="office" :active="isUrl('organizations')">Organizations</MenuItem>
      <MenuItem href="/proposals" icon="document" :active="isUrl('proposals')">Proposals</MenuItem>
      <MenuItem href="/sales-playbooks" icon="book" :active="isUrl('sales-playbooks')">Sales Playbooks</MenuItem>
    </MenuGroup>

    <!-- Marketing Module -->
    <MenuGroup
      title="Marketing"
      icon="printer"
      :is-open="openGroups.includes('marketing')"
      @toggle="toggleGroup('marketing')"
    >
      <MenuItem href="/email-templates" icon="document" :active="isUrl('email-templates')">Email Templates</MenuItem>
      <MenuItem href="/email-lists" icon="users" :active="isUrl('email-lists')">Email Lists</MenuItem>
      <MenuItem href="/email-campaigns" icon="printer" :active="isUrl('email-campaigns')">Campaigns</MenuItem>
      <MenuItem href="/email-automations" icon="cog" :active="isUrl('email-automations')">Email Automations</MenuItem>
      <MenuItem href="/content-templates" icon="document" :active="isUrl('content-templates')">Content Templates</MenuItem>
      <MenuItem href="/content-items" icon="document" :active="isUrl('content-items')">Content Items</MenuItem>
      <MenuItem href="/social-accounts" icon="users" :active="isUrl('social-accounts')">Social Accounts</MenuItem>
      <MenuItem href="/social-posts" icon="printer" :active="isUrl('social-posts')">Social Posts</MenuItem>
    </MenuGroup>

    <!-- Automation Module -->
    <MenuGroup
      title="Automation"
      icon="cog"
      :is-open="openGroups.includes('automation')"
      @toggle="toggleGroup('automation')"
    >
      <MenuItem href="/workflows" icon="cog" :active="isUrl('workflows')">Workflows</MenuItem>
      <MenuItem href="/chat-widgets" icon="users" :active="isUrl('chat-widgets')">Chat Widgets</MenuItem>
      <MenuItem href="/chat-conversations" icon="users" :active="isUrl('chat-conversations')">Chat Conversations</MenuItem>
    </MenuGroup>

    <!-- Analytics Module -->
    <MenuGroup
      title="Analytics"
      icon="target"
      :is-open="openGroups.includes('analytics')"
      @toggle="toggleGroup('analytics')"
    >
      <MenuItem href="/reports" icon="printer" :active="isUrl('reports')">Reports</MenuItem>
      <MenuItem href="/icps" icon="target" :active="isUrl('icps')">ICP Profiles</MenuItem>
    </MenuGroup>

    <!-- Files -->
    <div class="mb-3">
      <Link class="group flex items-center py-2.5" href="/files">
        <icon name="document" class="mr-3 w-5 h-5 flex-shrink-0" :class="isUrl('files') ? 'fill-white' : 'fill-primary-400 group-hover:fill-white'" />
        <div class="text-sm font-medium truncate" :class="isUrl('files') ? 'text-white' : 'text-primary-300 group-hover:text-white'">Files</div>
      </Link>
    </div>

    <!-- Settings Section -->
    <div class="mt-8 pt-8 border-t border-primary-700">
      <MenuGroup
        title="Settings"
        icon="cog"
        :is-open="openGroups.includes('settings')"
        @toggle="toggleGroup('settings')"
      >
        <MenuItem href="/account-settings" icon="cog" :active="isUrl('account-settings')">Account Settings</MenuItem>
        <MenuItem href="/smtp-settings" icon="cog" :active="isUrl('smtp-settings')">SMTP Settings</MenuItem>
        <MenuItem href="/sla-settings" icon="clock" :active="isUrl('sla-settings')">SLA Settings</MenuItem>
        <MenuItem href="/users" icon="user" :active="isUrl('users')">Users</MenuItem>
        <MenuItem href="/roles" icon="users" :active="isUrl('roles')">Roles</MenuItem>
        <MenuItem href="/permissions" icon="cog" :active="isUrl('permissions')">Permissions</MenuItem>
      </MenuGroup>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'
import MenuGroup from '@/Shared/MenuGroup.vue'
import MenuItem from '@/Shared/MenuItem.vue'

export default {
  components: {
    Icon,
    Link,
    MenuGroup,
    MenuItem,
  },
  data() {
    return {
      openGroups: [],
    }
  },
  mounted() {
    // Auto-open groups that contain active items
    const currentUrl = this.$page.url.substr(1)
    
    if (currentUrl.startsWith('leads') || currentUrl.startsWith('deals') || 
        currentUrl.startsWith('contacts') || currentUrl.startsWith('organizations') ||
        currentUrl.startsWith('proposals') || currentUrl.startsWith('sales-playbooks')) {
      this.openGroups.push('sales')
    }
    
    if (currentUrl.startsWith('email-') || currentUrl.startsWith('content-') || 
        currentUrl.startsWith('social-')) {
      this.openGroups.push('marketing')
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
