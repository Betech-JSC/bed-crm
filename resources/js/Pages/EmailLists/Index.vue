<template>
  <div>
    <Head title="Email Lists" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">{{ t('common.lists') }}</h1>
        <p class="mt-1 text-gray-600">Manage your contact lists for email campaigns</p>
      </div>
      <Link href="/email-lists/create">
        <Button label="Create List" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="lists"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No email lists found. Create your first list.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/email-lists/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="type" header="Type">
            <template #body="{ data }">
              <Tag :value="data.type" severity="info" />
            </template>
          </Column>

          <Column field="contacts_count" header="Contacts" sortable>
            <template #body="{ data }">
              <Badge :value="data.contacts_count || 0" />
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.created_at }}</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/email-lists/${data.id}`">
                  <Button icon="pi pi-eye" severity="secondary" text rounded size="small" />
                </Link>
                <Link :href="`/email-lists/${data.id}/edit`">
                  <Button icon="pi pi-pencil" severity="secondary" text rounded size="small" />
                </Link>
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  @click="confirmDelete(data.id)"
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
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    DataTable,
    Column,
    Tag,
    Badge,
  },
  layout: Layout,
  props: {
    lists: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  methods: {
    confirmDelete(id) {
      if (confirm('Are you sure you want to delete this list?')) {
        router.delete(`/email-lists/${id}`)
      }
    },
  },
}
</script>

