<template>
  <div>
    <Head :title="`Role: ${role.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Role Info -->
      <div class="lg:col-span-1">
        <Card>
          <template #title>Role Information</template>
          <template #content>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium text-gray-600">Name</label>
                <p class="text-lg font-semibold">{{ role.name }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Slug</label>
                <p><code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ role.slug }}</code></p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <p>
                  <Tag :value="role.is_active ? 'Active' : 'Inactive'" :severity="role.is_active ? 'success' : 'secondary'" />
                  <Tag v-if="role.is_system" value="System" severity="warning" class="ml-2" />
                </p>
              </div>
              <div v-if="role.description">
                <label class="text-sm font-medium text-gray-600">Description</label>
                <p class="text-sm text-gray-700">{{ role.description }}</p>
              </div>
              <div class="pt-4 border-t">
                <div class="flex items-center gap-4">
                  <div>
                    <p class="text-sm text-gray-600">Users</p>
                    <p class="text-2xl font-bold">{{ role.users_count || 0 }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Permissions</p>
                    <p class="text-2xl font-bold">{{ permissions.length }}</p>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2 pt-4 border-t">
                <Link :href="`/roles/${role.id}/edit`" v-if="!role.is_system">
                  <Button label="Edit Role" icon="pi pi-pencil" class="w-full" />
                </Link>
                <Link href="/roles">
                  <Button label="Back to Roles" severity="secondary" outlined class="w-full" />
                </Link>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Permissions & Users -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Permissions -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span>Permissions</span>
              <Link :href="`/roles/${role.id}/edit`" v-if="!role.is_system">
                <Button label="Edit Permissions" icon="pi pi-pencil" size="small" outlined />
              </Link>
            </div>
          </template>
          <template #content>
            <div v-if="permissions.length > 0" class="space-y-4">
              <div v-for="group in groupedPermissions" :key="group.group" class="border rounded-lg p-4">
                <h4 class="font-semibold mb-3 text-gray-700">{{ group.group }}</h4>
                <div class="space-y-2">
                  <div v-for="permission in group.permissions" :key="permission.id" class="flex items-center gap-2">
                    <i class="pi pi-check-circle text-green-500"></i>
                    <span class="text-sm">{{ permission.name }}</span>
                    <code class="text-xs text-gray-500 ml-2">{{ permission.slug }}</code>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="py-8 text-center text-gray-500">
              No permissions assigned to this role.
            </div>
          </template>
        </Card>

        <!-- Users -->
        <Card>
          <template #title>Users with this Role</template>
          <template #content>
            <DataTable
              :value="users"
              :paginator="false"
              stripedRows
              responsiveLayout="scroll"
              class="p-datatable-sm"
            >
              <template #empty>
                <div class="py-8 text-center text-gray-500">No users assigned to this role.</div>
              </template>

              <Column field="name" header="Name">
                <template #body="{ data }">
                  <Link :href="`/users/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                    {{ data.name }}
                  </Link>
                </template>
              </Column>

              <Column field="email" header="Email">
                <template #body="{ data }">
                  <span class="text-sm text-gray-600">{{ data.email }}</span>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    Tag,
    DataTable,
    Column,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    role: Object,
    permissions: Array,
    users: Array,
    availablePermissions: Array,
  },
  computed: {
    breadcrumbItems() {
      return [
        { label: 'Roles', route: '/roles' },
        { label: this.role.name },
      ]
    },
    groupedPermissions() {
      const grouped = {}
      this.permissions.forEach(permission => {
        const group = permission.group || 'Other'
        if (!grouped[group]) {
          grouped[group] = {
            group,
            permissions: [],
          }
        }
        grouped[group].permissions.push(permission)
      })
      return Object.values(grouped)
    },
  },
}
</script>

