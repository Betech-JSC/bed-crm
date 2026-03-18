<template>
  <div class="file-details-container anim-fade-in">
    <Head :title="file.name" />

    <header class="page-header">
       <Link href="/files" class="back-btn">
          <i class="pi pi-arrow-left" />
          <span>{{ isVi ? 'Quay lại' : 'Back to Explorer' }}</span>
       </Link>
       <div class="header-actions">
          <Link :href="`/files/${file.id}/edit`">
            <Button icon="pi pi-pencil" text rounded />
          </Link>
          <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteFile" />
       </div>
    </header>

    <div class="details-grid">
      <!-- Preview & Main Info -->
      <div class="main-content">
        <div class="preview-card">
          <div class="preview-header">
             <div class="file-icon-wrap" :style="{ backgroundColor: getIconBg(file.extension) }">
                <i :class="file.icon" :style="{ color: getIconColor(file.extension) }" />
             </div>
             <div class="file-main-info">
                <h1 class="file-title">{{ file.name }}</h1>
                <div class="file-tags">
                   <Tag :value="file.category" severity="info" />
                   <Tag v-if="file.type" :value="file.type" severity="secondary" />
                </div>
             </div>
          </div>

          <!-- Description Section -->
          <div v-if="file.description" class="description-box">
             <label>{{ isVi ? 'MÔ TẢ' : 'DESCRIPTION' }}</label>
             <p>{{ file.description }}</p>
          </div>

          <!-- Actual Preview (Images/PDF) -->
          <div class="preview-viewport">
             <div v-if="file.category === 'image'" class="image-wrapper">
                <img :src="`/files/${file.id}/preview`" :alt="file.name" />
             </div>
             <div v-else-if="file.mime_type === 'application/pdf'" class="pdf-notice">
                <i class="pi pi-file-pdf" />
                <p>{{ isVi ? 'Tài liệu PDF đã sẵn sàng để xem' : 'PDF document is ready for viewing' }}</p>
                <Link :href="`/files/${file.id}/preview`" target="_blank">
                   <Button :label="isVi ? 'Xem bản xem trước' : 'Open Preview'" severity="secondary" outlined />
                </Link>
             </div>
             <div v-else class="generic-notice">
                <i :class="file.icon" />
                <p>{{ isVi ? 'Không có bản xem trước cho định dạng này' : 'No preview available for this file type' }}</p>
             </div>
          </div>
        </div>
      </div>

      <!-- Sidebar Metadata & Actions -->
      <aside class="details-sidebar">
        <!-- Action Card -->
        <Card class="side-card action-card">
          <template #content>
            <div class="side-title">{{ isVi ? 'HÀNH ĐỘNG' : 'ACTIONS' }}</div>
            <div class="space-y-3">
               <Link :href="`/files/${file.id}/download`" target="_blank" class="block">
                  <Button :label="isVi ? 'Tải xuống tệp' : 'Download File'" icon="pi pi-download" class="w-full premium-button" />
               </Link>
               <Button 
                v-if="file.category === 'image' || file.mime_type === 'application/pdf'"
                :label="isVi ? 'Xem nhanh' : 'Quick Preview'" 
                icon="pi pi-eye" 
                class="w-full" 
                severity="secondary" 
                outlined 
               />
               <Button 
                :label="isVi ? 'Chia sẻ liên kết' : 'Share Link'" 
                icon="pi pi-share-alt" 
                class="w-full" 
                severity="secondary" 
                outlined 
               />
            </div>
          </template>
        </Card>

        <!-- Stats & Meta Card -->
        <Card class="side-card meta-card">
          <template #content>
            <div class="side-title">{{ isVi ? 'THÔNG TIN CHI TIẾT' : 'FILE SPECIFICATIONS' }}</div>
            <div class="meta-list">
               <div class="meta-item">
                  <span class="label">MIME:</span>
                  <span class="value">{{ file.mime_type }}</span>
               </div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Định dạng' : 'Extension' }}:</span>
                  <span class="value uppercase font-bold text-indigo-600">{{ file.extension }}</span>
               </div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Dung lượng' : 'Size' }}:</span>
                  <span class="value">{{ file.size }}</span>
               </div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Lượt tải' : 'Downloads' }}:</span>
                  <span class="value">{{ file.download_count }}</span>
               </div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Quyền truy cập' : 'Access' }}:</span>
                  <Tag :value="file.access_level" :severity="file.is_public ? 'success' : 'secondary'" class="text-[10px]" />
               </div>
               <div class="meta-divider"></div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Người tải' : 'Uploaded by' }}:</span>
                  <span class="value font-bold text-slate-700">{{ file.uploader?.name || '-' }}</span>
               </div>
               <div class="meta-item">
                  <span class="label">{{ isVi ? 'Ngày tải' : 'Date' }}:</span>
                  <span class="value">{{ file.created_at }}</span>
               </div>
            </div>
          </template>
        </Card>
      </aside>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Button,
  },
  layout: Layout,
  props: {
    file: Object,
  },
  computed: {
    isVi() {
      return this.$page.props.locale === 'vi'
    }
  },
  methods: {
    deleteFile() {
      if (confirm(this.isVi ? 'Bạn có chắc chắn muốn xoá tài liệu này?' : 'Are you sure you want to delete this file?')) {
        router.delete(`/files/${this.file.id}`)
      }
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
        return colors[(ext || '').toLowerCase()] || '#64748b'
    },
    getIconBg(ext) {
        return this.getIconColor(ext) + '15'
    }
  }
}
</script>

<style scoped>
.file-details-container {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.9rem;
  transition: color 0.2s;
}

.back-btn:hover { color: #1e293b; }

.details-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 2rem;
}

/* Main Content Card */
.preview-card {
  background: white;
  border-radius: 24px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 20px rgba(0,0,0,0.02);
  padding: 2rem;
}

.preview-header {
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.file-icon-wrap {
  width: 70px;
  height: 70px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-icon-wrap i { font-size: 2.25rem; }

.file-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.file-tags { display: flex; gap: 0.5rem; }

.description-box {
  background: #f8fafc;
  padding: 1.25rem;
  border-radius: 16px;
  margin-bottom: 2rem;
  border: 1px solid #f1f5f9;
}

.description-box label {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.description-box p {
  color: #475569;
  font-size: 0.9rem;
  line-height: 1.5;
}

.preview-viewport {
  background: #f1f5f9;
  border-radius: 16px;
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.image-wrapper img {
  max-width: 100%;
  max-height: 600px;
  display: block;
  border-radius: 8px;
}

.pdf-notice, .generic-notice {
  text-align: center;
  padding: 3rem;
}

.pdf-notice i, .generic-notice i {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.pdf-notice p, .generic-notice p {
  color: #64748b;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

/* Sidebar Styling */
.side-card {
  border-radius: 20px;
  border: 1px solid #f1f5f9;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}

.side-title {
  font-size: 0.7rem;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.05em;
  margin-bottom: 1.25rem;
}

.premium-button {
  background: #6366f1;
  border: none;
  font-weight: 700;
  border-radius: 12px;
  padding: 0.75rem;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.meta-list { display: flex; flex-direction: column; gap: 0.85rem; }
.meta-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.meta-item .label { color: #64748b; font-weight: 500; }
.meta-item .value { color: #1e293b; font-weight: 600; }

.meta-divider {
  height: 1px;
  background: #f1f5f9;
  margin: 0.25rem 0;
}

.anim-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 1024px) {
  .details-grid { grid-template-columns: 1fr; }
}
</style>



