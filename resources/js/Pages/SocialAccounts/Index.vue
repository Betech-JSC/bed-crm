<template>
  <div>
    <Head title="Social Accounts" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.social_accounts') }}</h1>
        <p class="mt-1 text-gray-600">Manage your connected social media accounts</p>
      </div>
      <Link href="/social-accounts/create">
        <Button label="Connect Account" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="socialAccounts"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No social accounts connected. Connect your first account to start posting.</div>
          </template>

          <Column field="platform" header="Platform" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Tag :value="data.platform.toUpperCase()" :severity="getPlatformSeverity(data.platform)" />
              </div>
            </template>
          </Column>

          <Column field="name" header="Account Name" sortable>
            <template #body="{ data }">
              <div>
                <div class="font-medium">{{ data.name }}</div>
                <div v-if="data.username" class="text-sm text-gray-500">@{{ data.username }}</div>
              </div>
            </template>
          </Column>

          <Column field="is_connected" header="Connection Status">
            <template #body="{ data }">
              <Tag
                :value="data.is_connected ? 'Connected' : 'Disconnected'"
                :severity="data.is_connected ? 'success' : 'danger'"
              />
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column field="last_sync_at" header="Last Sync">
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.last_sync_at || 'Never' }}</span>
            </template>
          </Column>

          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button
                  icon="pi pi-refresh"
                  text
                  rounded
                  severity="info"
                  v-tooltip.top="'Refresh Token'"
                  @click="refreshToken(data.id)"
                />
                <Button
                  icon="pi pi-check-circle"
                  text
                  rounded
                  severity="success"
                  v-tooltip.top="'Validate Connection'"
                  @click="validateConnection(data.id)"
                />
                <Button
                  icon="pi pi-trash"
                  text
                  rounded
                  severity="danger"
                  v-tooltip.top="'Disconnect'"
                  @click="disconnect(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Tag,
    Button,
  },
  layout: Layout,
  props: {
    socialAccounts: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  methods: {
    getPlatformSeverity(platform) {
      const severities = {
        linkedin: 'info',
        twitter: 'info',
        facebook: 'primary',
        instagram: 'warning',
      }
      return severities[platform] || 'secondary'
    },
    refreshToken(accountId) {
      if (confirm('Refresh access token for this account?')) {
        router.post(`/social-accounts/${accountId}/refresh`)
      }
    },
    validateConnection(accountId) {
      router.post(`/social-accounts/${accountId}/validate`)
    },
    disconnect(accountId) {
      if (confirm('Are you sure you want to disconnect this account?')) {
        router.delete(`/social-accounts/${accountId}`)
      }
    },
  },
}
</script>



