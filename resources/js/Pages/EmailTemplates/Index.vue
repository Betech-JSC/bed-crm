<template>
  <div>
    <Head title="Email Templates" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Email Templates</h1>
        <p class="mt-1 text-gray-600">Create and manage email templates for campaigns and automations</p>
      </div>
      <Link href="/email-templates/create">
        <Button label="Create Template" icon="pi pi-plus" />
      </Link>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="templates"
          :paginator="false"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No email templates found. Create your first template.</div>
          </template>

          <Column field="name" header="Name" sortable>
            <template #body="{ data }">
              <Link :href="`/email-templates/${data.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                {{ data.name }}
              </Link>
            </template>
          </Column>

          <Column field="subject" header="Subject">
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.subject }}</span>
            </template>
          </Column>

          <Column field="type" header="Type">
            <template #body="{ data }">
              <Tag :value="data.type" severity="info" />
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
                <Link :href="`/email-templates/${data.id}/edit`">
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

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    DataTable,
    Column,
    Tag,
  },
  layout: Layout,
  props: {
    templates: Array,
  },
  methods: {
    confirmDelete(id) {
      if (confirm('Are you sure you want to delete this template?')) {
        router.delete(`/email-templates/${id}`)
      }
    },
  },
}
</script>

