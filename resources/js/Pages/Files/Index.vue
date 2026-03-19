<template>
  <div class="file-manager-container">
    <Head title="Files" />

    <!-- Sidebar Explorer -->
    <aside class="file-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-title">{{ isVi ? 'Kho tài liệu' : 'File Explorer' }}</h2>
        <Link href="/files/create">
          <Button icon="pi pi-plus" :label="isVi ? 'Tải lên' : 'Upload'" class="upload-btn-lg" />
        </Link>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="section-label">{{ isVi ? 'PHÂN LOẠI' : 'CATEGORIES' }}</div>
          <button 
            class="nav-item" 
            :class="{ active: selectedCategory === null }"
            @click="setCategory(null)"
          >
            <i class="pi pi-th-large" />
            <span>{{ isVi ? 'Tất cả file' : 'All Files' }}</span>
          </button>
          <button 
            v-for="(label, val) in categories" 
            :key="val"
            class="nav-item"
            :class="{ active: selectedCategory === val }"
            @click="setCategory(val)"
          >
            <i class="pi pi-folder" />
            <span>{{ label }}</span>
          </button>
        </div>

        <div class="nav-section mt-4">
          <div class="section-label">{{ isVi ? 'ĐỊNH DẠNG' : 'FILE TYPES' }}</div>
          <button 
            v-for="(label, val) in types" 
            :key="val"
            class="nav-item"
            :class="{ active: selectedType === val }"
            @click="setType(val)"
          >
            <i :class="getFileTypeIcon(val)" />
            <span>{{ label }}</span>
          </button>
        </div>
      </nav>

      <div class="sidebar-footer">
        <div class="storage-info">
          <div class="flex justify-between text-xs mb-1">
            <span>{{ isVi ? 'Dung lượng' : 'Storage' }}</span>
            <span>75%</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: 75%"></div>
          </div>
          <div class="text-[10px] text-gray-400 mt-2">1.5GB / 2GB used</div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="file-content">
      <header class="content-header">
        <div class="header-left">
          <div class="search-wrapper">
            <i class="pi pi-search" />
            <input 
              v-model="searchQuery" 
              :placeholder="isVi ? 'Tìm kiếm tài liệu...' : 'Search files...'"
              @input="handleSearch"
            />
          </div>
        </div>
        <div class="header-right">
          <div class="view-toggle">
            <button :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'" title="Grid view">
              <i class="pi pi-th-large" />
            </button>
            <button :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'" title="List view">
              <i class="pi pi-list" />
            </button>
          </div>
        </div>
      </header>

      <div class="content-body">
        <!-- Breadcrumb / Title -->
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-xl font-bold text-slate-800">
            {{ currentCategoryLabel }}
          </h1>
          <div class="text-sm text-slate-400">
            {{ files.total }} {{ isVi ? 'tài liệu' : 'items' }}
          </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="file-grid anim-fade-in">
          <div v-for="file in files.data" :key="file.id" class="file-card">
            <div class="file-card-preview">
              <i :class="file.icon" :style="{ color: getIconColor(file.extension) }" />
              <div class="file-card-actions">
                 <button @click="downloadFile(file.id)" class="action-btn"><i class="pi pi-download" /></button>
                 <Link :href="`/files/${file.id}`" class="action-btn"><i class="pi pi-external-link" /></Link>
              </div>
            </div>
            <div class="file-card-info">
              <Link :href="`/files/${file.id}`" class="file-name" :title="file.name">{{ file.name }}</Link>
              <div class="file-meta">
                <span>{{ file.extension.toUpperCase() }}</span>
                <span class="dot"></span>
                <span>{{ file.size }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- List View -->
        <div v-else class="file-list-view anim-fade-in">
          <DataTable :value="files.data" class="p-datatable-sm custom-table" responsiveLayout="scroll">
            <Column header="File">
              <template #body="{ data }">
                <div class="flex items-center gap-3 py-1">
                  <div class="file-list-icon" :style="{ backgroundColor: getIconBg(data.extension) }">
                    <i :class="data.icon" :style="{ color: getIconColor(data.extension) }" />
                  </div>
                  <div class="flex flex-col">
                    <Link :href="`/files/${data.id}`" class="font-bold text-slate-700 hover:text-indigo-600 transition-colors">
                      {{ data.name }}
                    </Link>
                    <span class="text-[10px] text-slate-400">{{ data.extension.toUpperCase() }}</span>
                  </div>
                </div>
              </template>
            </Column>
            <Column field="category" :header="isVi ? 'Danh mục' : 'Category'">
               <template #body="{ data }">
                 <Tag :value="data.category" severity="info" class="text-[10px] px-2" />
               </template>
            </Column>
            <Column field="size" :header="isVi ? 'Dung lượng' : 'Size'" />
            <Column field="uploader" :header="isVi ? 'Người tải' : 'Uploader'">
              <template #body="{ data }">{{ data.uploader?.name || '-' }}</template>
            </Column>
            <Column field="created_at" :header="isVi ? 'Ngày tạo' : 'Date'" />
            <Column style="width: 80px">
              <template #body="{ data }">
                <div class="flex gap-1">
                   <Button icon="pi pi-download" text rounded size="small" @click="downloadFile(data.id)" />
                   <Link :href="`/files/${data.id}`">
                      <Button icon="pi pi-chevron-right" text rounded size="small" />
                   </Link>
                </div>
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Empty State -->
        <div v-if="files.data.length === 0" class="empty-state">
           <div class="empty-icon-wrap">
              <i class="pi pi-folder-open" />
           </div>
           <h3>{{ isVi ? 'Chưa có tài liệu nào' : 'No files found' }}</h3>
           <p>{{ isVi ? 'Bắt đầu bằng cách tải lên tài liệu đầu tiên của bạn.' : 'Get started by uploading your first document.' }}</p>
           <Link href="/files/create">
              <Button :label="isVi ? 'Tải file ngay' : 'Upload now'" severity="indigo" class="mt-4" />
           </Link>
        </div>

        <!-- Paginator Placeholder -->
        <div class="mt-8 flex justify-center">
            <!-- primevue paginator or simple link based pagination could go here -->
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Button,
    DataTable,
    Column,
    Tag,
  },
  layout: Layout,
  props: {
    files: Object,
    categories: Object,
    types: Object,
    filters: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      viewMode: localStorage.getItem('fileViewMode') || 'grid',
      searchQuery: this.filters?.search || '',
      selectedCategory: this.filters?.category || null,
      selectedType: this.filters?.type || null,
      searchTimeout: null,
    }
  },
  watch: {
    viewMode(newMode) {
      localStorage.setItem('fileViewMode', newMode)
    }
  },
  computed: {
    isVi() {
      return this.$page.props.locale === 'vi'
    },
    currentCategoryLabel() {
      if (this.selectedCategory) return this.categories[this.selectedCategory]
      return this.isVi ? 'Tất cả file' : 'All Files'
    }
  },
  methods: {
    setCategory(val) {
      this.selectedCategory = val
      this.applyFilters()
    },
    setType(val) {
      this.selectedType = val
      this.applyFilters()
    },
    handleSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => this.applyFilters(), 400)
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
    downloadFile(id) {
      window.open(`/files/${id}/download`, '_blank')
    },
    getFileTypeIcon(type) {
        const icons = {
            'pdf': 'pi pi-file-pdf',
            'doc': 'pi pi-file-word',
            'excel': 'pi pi-file-excel',
            'image': 'pi pi-image',
            'video': 'pi pi-video'
        }
        return icons[type] || 'pi pi-file'
    },
    getIconColor(ext) {
        const colors = {
            'pdf': '#ef4444',
            'doc': '#3b82f6',
            'docx': '#3b82f6',
            'xls': '#10b981',
            'xlsx': '#10b981',
            'png': '#f59e0b',
            'jpg': '#f59e0b',
            'jpeg': '#f59e0b',
            'zip': '#8b5cf6'
        }
        return colors[ext.toLowerCase()] || '#64748b'
    },
    getIconBg(ext) {
        const color = this.getIconColor(ext)
        return color + '15' // 15% opacity
    }
  }
}
</script>

<style scoped>
.file-manager-container {
  display: flex;
  height: calc(100vh - 120px);
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0,0,0,0.03);
  border: 1px solid #f1f5f9;
}

/* Sidebar Explorer */
.file-sidebar {
  width: 260px;
  background: #f8fafc;
  border-right: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
}

.sidebar-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 1.5rem;
}

.upload-btn-lg {
  width: 100%;
  margin-bottom: 2rem;
  border-radius: 12px;
  background: #6366f1;
  border: none;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.section-label {
  font-size: 0.65rem;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.1em;
  margin-bottom: 0.75rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: none;
  background: transparent;
  border-radius: 10px;
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.nav-item i {
  font-size: 1rem;
}

.nav-item:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.nav-item.active {
  background: #eff6ff;
  color: #2563eb;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 2rem;
}

.storage-info {
  background: white;
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.progress-bar {
  height: 6px;
  background: #f1f5f9;
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill {
  background: #6366f1;
  height: 100%;
}

/* Main Content Area */
.file-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.content-header {
  height: 70px;
  padding: 0 2rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.search-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f1f5f9;
  padding: 0.6rem 1.25rem;
  border-radius: 12px;
  width: 380px;
}

.search-wrapper i { color: #94a3b8; }
.search-wrapper input {
  background: transparent;
  border: none;
  outline: none;
  font-size: 0.88rem;
  width: 100%;
}

.view-toggle {
  display: flex;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 8px;
}

.view-toggle button {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.view-toggle button.active {
  background: white;
  color: #1e293b;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.content-body {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}

/* Grid Layout */
.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.5rem;
}

.file-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.2s;
}

.file-card:hover {
  border-color: #6366f1;
  box-shadow: 0 10px 25px rgba(0,0,0,0.05);
  transform: translateY(-4px);
}

.file-card-preview {
  height: 120px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.file-card-preview i {
  font-size: 3rem;
  transition: all 0.3s;
}

.file-card-actions {
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  opacity: 0;
  transition: all 0.2s;
}

.file-card:hover .file-card-actions { opacity: 1; }

.action-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.file-card-info {
  padding: 1rem;
}

.file-name {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.4rem;
  text-decoration: none;
}

.file-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 500;
}

.dot {
  width: 3px;
  height: 3px;
  background: #cbd5e1;
  border-radius: 50%;
}

/* List Table Style */
.file-list-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-list-icon i {
  font-size: 1.1rem;
}

.custom-table :deep(.p-datatable-thead > tr > th) {
  background: #f8fafc;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem;
}

.custom-table :deep(.p-datatable-tbody > tr) {
  transition: all 0.2s;
}

.custom-table :deep(.p-datatable-tbody > tr:hover) {
  background: #f1f5f9;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon-wrap {
  width: 80px;
  height: 80px;
  background: #f1f5f9;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.empty-icon-wrap i {
  font-size: 2.5rem;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #94a3b8;
  font-size: 0.95rem;
}

.anim-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@media (max-width: 1024px) {
  .file-sidebar { display: none; }
}
</style>



