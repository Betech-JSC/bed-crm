<template>
  <div>
    <Head title="Files" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Files</h1>
        <p class="mt-1 text-gray-600">Manage your uploaded files</p>
      </div>
      <Link href="/files/create">
        <Button label="Upload File" icon="pi pi-upload" />
      </Link>
    </div>

    <Card>
      <template #content>
        <div class="mb-4 flex items-center gap-4">
          <div class="flex-1">
            <InputText
              v-model="searchQuery"
              placeholder="Search files..."
              class="w-full"
              @input="handleSearch"
            />
          </div>
          <Select
            v-model="selectedCategory"
            :options="categoryOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Categories"
            class="w-48"
            @change="handleFilter"
          />
          <Select
            v-model="selectedType"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Types"
            class="w-48"
            @change="handleFilter"
          />
        </div>

        <DataTable
          :value="files.data"
          :paginator="true"
          :rows="files.per_page"
          :totalRecords="files.total"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No files found. Upload your first file to get started.</div>
          </template>

          <Column header="File">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <i :class="data.icon" class="text-2xl text-primary-600"></i>
                <div>
                  <Link :href="`/files/${data.id}`" class="font-medium text-primary-600 hover:text-primary-800">
                    {{ data.name }}
                  </Link>
                  <div class="text-xs text-gray-500">{{ data.extension.toUpperCase() }} • {{ data.size }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="category" header="Category">
            <template #body="{ data }">
              <Tag :value="data.category" severity="info" />
            </template>
          </Column>

          <Column field="type" header="Type">
            <template #body="{ data }">
              <Tag v-if="data.type" :value="data.type" severity="secondary" />
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column field="download_count" header="Downloads" sortable>
            <template #body="{ data }">
              <Badge :value="data.download_count" severity="info" />
            </template>
          </Column>

          <Column field="uploader" header="Uploaded By">
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.uploader?.name || '-' }}</span>
            </template>
          </Column>

          <Column field="created_at" header="Uploaded" sortable>
            <template #body="{ data }">
              <span class="text-sm text-gray-600">{{ data.created_at }}</span>
            </template>
          </Column>

          <Column>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Link :href="`/files/${data.id}/download`" target="_blank">
                  <Button icon="pi pi-download" text rounded severity="info" v-tooltip.top="'Download'" />
                </Link>
                <Link :href="`/files/${data.id}`">
                  <Button icon="pi pi-arrow-right" text rounded />
                </Link>
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
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

export default {
  components: {
    Head,
    Link,
    Card,
    DataTable,
    Column,
    Tag,
    Badge,
    Button,
    InputText,
    Select,
  },
  layout: Layout,
  props: {
    files: Object,
    categories: Object,
    types: Object,
    filters: Object,
  },
  data() {
    return {
      searchQuery: this.filters?.search || '',
      selectedCategory: this.filters?.category || null,
      selectedType: this.filters?.type || null,
      categoryOptions: [
        { label: 'All Categories', value: null },
        ...Object.entries(this.categories).map(([value, label]) => ({ value, label })),
      ],
      typeOptions: [
        { label: 'All Types', value: null },
        ...Object.entries(this.types).map(([value, label]) => ({ value, label })),
      ],
    }
  },
  methods: {
    handleSearch() {
      this.debounceSearch()
    },
    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.applyFilters()
      }, 500)
    },
    handleFilter() {
      this.applyFilters()
    },
    applyFilters() {
      router.get('/files', {
        search: this.searchQuery || null,
        category: this.selectedCategory || null,
        type: this.selectedType || null,
      }, {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
}
</script>



