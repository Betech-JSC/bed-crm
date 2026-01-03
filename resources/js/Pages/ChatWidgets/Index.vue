<template>
  <div>
    <Head title="Chat Widgets" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Chat Widgets</h1>
        <p class="mt-1 text-gray-600">Manage your AI chat widgets for website embedding</p>
      </div>
      <Link href="/chat-widgets/create">
        <Button label="Create Widget" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="widgets"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">
              No chat widgets found. Create your first widget to start engaging with visitors.
            </div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/chat-widgets/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                  {{ data.name }}
                </Link>
                <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
              </div>
            </template>
          </Column>

          <Column header="Embed Code">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ data.widget_key }}</code>
                <Button
                  icon="pi pi-copy"
                  text
                  rounded
                  size="small"
                  @click="copyEmbedCode(data.embed_url)"
                  v-tooltip="'Copy embed code'"
                />
              </div>
            </template>
          </Column>

          <Column header="Conversations">
            <template #body="{ data }">
              <Badge :value="data.conversations_count" />
            </template>
          </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.created_at }}</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <Link :href="`/chat-widgets/${data.id}/edit`">
                <Button icon="pi pi-arrow-right" text rounded />
              </Link>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Button,
    Tag,
    Badge,
  },
  layout: Layout,
  props: {
    widgets: Array,
  },
  methods: {
    copyEmbedCode(embedUrl) {
      const embedCode = `<script src="${embedUrl}"><\/script>`
      navigator.clipboard.writeText(embedCode).then(() => {
        // Show toast notification (you can use PrimeVue Toast here)
        alert('Embed code copied to clipboard!')
      })
    },
  },
}
</script>

