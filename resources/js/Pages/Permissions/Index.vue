<template>
  <div>
    <Head title="Permissions" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Permissions</h1>
        <p class="mt-1 text-gray-600">Manage system permissions</p>
      </div>
      <Link href="/permissions/create">
        <Button label="Create Permission" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="permissions"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No permissions found. Create your first permission.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/permissions/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="slug" header="Slug">
            <template #body="{ data }">
              <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ data.slug }}</code>
            </template>
          </Column>

          <Column field="group" header="Group" sortable>
            <template #body="{ data }">
              <Tag :value="data.group || 'Other'" severity="info" />
            </template>
          </Column>

          <Column field="roles_count" header="Roles" sortable>
            <template #body="{ data }">
              <Badge :value="data.roles_count || 0" />
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/permissions/${data.id}/edit`">
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
    permissions: Array,
    groups: Array,
  },
  methods: {
    confirmDelete(id) {
      if (confirm('Are you sure you want to delete this permission?')) {
        router.delete(`/permissions/${id}`)
      }
    },
  },
}
</script>

