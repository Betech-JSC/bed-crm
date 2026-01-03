<template>
  <div>
    <Head title="Roles" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Roles</h1>
        <p class="mt-1 text-gray-600">Manage user roles and permissions</p>
      </div>
      <Link href="/roles/create">
        <Button label="Create Role" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="roles"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No roles found. Create your first role.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/roles/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                  {{ data.name }}
                </Link>
                <Tag v-if="data.is_system" value="System" severity="warning" />
              </div>
            </template>
          </Column>

          <Column field="slug" header="Slug">
            <template #body="{ data }">
              <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ data.slug }}</code>
            </template>
          </Column>

          <Column field="users_count" header="Users" sortable>
            <template #body="{ data }">
              <Badge :value="data.users_count || 0" />
            </template>
          </Column>

          <Column field="permissions_count" header="Permissions" sortable>
            <template #body="{ data }">
              <Badge :value="data.permissions_count || 0" severity="info" />
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
                <Link :href="`/roles/${data.id}`">
                  <Button icon="pi pi-eye" severity="secondary" text rounded size="small" />
                </Link>
                <Link :href="`/roles/${data.id}/edit`">
                  <Button icon="pi pi-pencil" severity="secondary" text rounded size="small" :disabled="data.is_system" />
                </Link>
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  :disabled="data.is_system"
                  @click="confirmDelete(data.id, data.is_system)"
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
    roles: Array,
  },
  methods: {
    confirmDelete(id, isSystem) {
      if (isSystem) {
        alert('Cannot delete system roles.')
        return
      }
      if (confirm('Are you sure you want to delete this role?')) {
        router.delete(`/roles/${id}`)
      }
    },
  },
}
</script>

