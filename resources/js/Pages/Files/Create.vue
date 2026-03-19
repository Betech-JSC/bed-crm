<template>
  <div class="upload-page-container anim-fade-in">
    <Head :title="isVi ? 'Tải lên tài liệu' : 'Upload File'" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
          {{ isVi ? 'Tải lên tài liệu mới' : 'Upload New Document' }}
        </h1>
        <p class="text-slate-500 mt-2">
          {{ isVi ? 'Chia sẻ và lưu trữ tài liệu của bạn một cách an toàn.' : 'Securely share and store your professional documents.' }}
        </p>
      </div>

      <div class="upload-grid">
        <!-- Main Form -->
        <Card class="form-card">
          <template #content>
            <form @submit.prevent="store" class="space-y-8">
              
              <!-- Drag & Drop Zone -->
              <div class="upload-zone-wrapper">
                <label class="form-label mb-3 block">{{ isVi ? 'Tệp tin' : 'File' }} <span class="text-red-500">*</span></label>
                <div 
                  class="upload-dropzone" 
                  :class="{ 'dragging': isDragging, 'has-file': !!selectedFile }"
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="onFileDrop"
                  @click="$refs.fileInput.click()"
                >
                  <input type="file" ref="fileInput" @change="onFileChange" class="hidden" />
                  
                  <div v-if="!selectedFile" class="dropzone-empty">
                    <div class="icon-circle">
                      <i class="pi pi-cloud-upload" />
                    </div>
                    <div class="dropzone-text">
                      <span class="main-text">{{ isVi ? 'Kéo thả file vào đây hoặc click để chọn' : 'Drag & drop file here or click to browse' }}</span>
                      <span class="sub-text">Hỗ trợ tất cả định dạng • Tối đa 10MB</span>
                    </div>
                  </div>
                  
                  <div v-else class="dropzone-filled anim-scale-up">
                    <div class="file-preview-icon">
                       <i :class="getFileIcon(selectedFile)" />
                    </div>
                    <div class="file-details">
                      <span class="file-name">{{ selectedFile.name }}</span>
                      <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                    </div>
                    <button type="button" class="remove-btn" @click.stop="removeFile">
                      <i class="pi pi-times" />
                    </button>
                  </div>
                </div>
                <small v-if="form.errors.file" class="p-error block mt-2">{{ form.errors.file }}</small>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Access Level -->
                <div class="flex flex-col">
                  <label class="form-label mb-2">{{ isVi ? 'Quyền truy cập' : 'Access Level' }}</label>
                  <Select
                    v-model="form.access_level"
                    :options="accessLevelOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="premium-select"
                  />
                </div>

                <!-- Type -->
                <div class="flex flex-col">
                  <label class="form-label mb-2">{{ isVi ? 'Định dạng' : 'Type' }}</label>
                  <Select
                    v-model="form.type"
                    :options="typeOptions"
                    optionLabel="label"
                    optionValue="value"
                    :placeholder="isVi ? 'Chọn định dạng' : 'Select type'"
                    class="premium-select"
                  />
                </div>

                <!-- Description -->
                <div class="flex flex-col md:col-span-2">
                  <label class="form-label mb-2">{{ isVi ? 'Mô tả' : 'Description' }}</label>
                  <Textarea
                    v-model="form.description"
                    rows="3"
                    :placeholder="isVi ? 'Nhập mô tả ngắn gọn về tài liệu này...' : 'Enter a brief description for this document...'"
                    class="premium-input"
                  />
                </div>

                <!-- Toggle Public -->
                <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl md:col-span-2 border border-slate-100">
                  <Checkbox v-model="form.is_public" inputId="is_public" :binary="true" />
                  <div class="flex flex-col">
                    <label for="is_public" class="text-sm font-bold text-slate-700 leading-tight">
                      {{ isVi ? 'Công khai tài liệu' : 'Make file public' }}
                    </label>
                    <span class="text-[10px] text-slate-400">
                      {{ isVi ? 'Bất kỳ ai có đường dẫn đều có thể xem tài liệu này.' : 'Anyone with the link can access this document.' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <Link href="/files" class="cancel-link">
                  {{ isVi ? 'Huỷ bỏ' : 'Cancel' }}
                </Link>
                <Button 
                  type="submit" 
                  :label="isVi ? 'Bắt đầu tải lên' : 'Start Upload'" 
                  icon="pi pi-upload" 
                  class="upload-submit-btn" 
                  :loading="form.processing"
                  :disabled="!selectedFile"
                />
              </div>
            </form>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    Textarea,
    Select,
    Checkbox,
    Button,
  },
  layout: Layout,
  props: {
    categories: Object,
    types: Object,
    accessLevels: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        file: null,
        type: null,
        description: '',
        is_public: false,
        access_level: 'private',
      }),
      selectedFile: null,
      isDragging: false,
      typeOptions: [
        ...Object.entries(this.types).map(([value, label]) => ({ value, label })),
      ],
      accessLevelOptions: Object.entries(this.accessLevels).map(([value, label]) => ({ value, label })),
    }
  },
  computed: {
    isVi() {
      return this.$page.props.locale === 'vi'
    }
  },
  methods: {
    onFileChange(event) {
      this.processFile(event.target.files[0])
    },
    onFileDrop(event) {
      this.isDragging = false
      this.processFile(event.dataTransfer.files[0])
    },
    processFile(file) {
      if (file) {
        if (file.size > 10485760) {
          alert('File size exceeds 10MB limit')
          return
        }
        this.selectedFile = file
        this.form.file = file
      }
    },
    removeFile() {
      this.selectedFile = null
      this.form.file = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    getFileIcon(file) {
        const ext = file.name.split('.').pop().toLowerCase()
        const icons = {
            'pdf': 'pi pi-file-pdf',
            'doc': 'pi pi-file-word',
            'docx': 'pi pi-file-word',
            'xls': 'pi pi-file-excel',
            'xlsx': 'pi pi-file-excel',
            'png': 'pi pi-image',
            'jpg': 'pi pi-image',
            'jpeg': 'pi pi-image'
        }
        return icons[ext] || 'pi pi-file'
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
    store() {
      if (!this.selectedFile) return
      this.form.post('/files', { forceFormData: true })
    }
  }
}
</script>

<style scoped>
.upload-page-container {
  padding: 2rem 1rem;
}

.form-card {
  border-radius: 24px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
  overflow: hidden;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 800;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Dropzone Styling */
.upload-dropzone {
  border: 2px dashed #e2e8f0;
  border-radius: 20px;
  background: #f8fafc;
  padding: 2.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.upload-dropzone:hover {
  border-color: #6366f1;
  background: #f1f5f9;
}

.upload-dropzone.dragging {
  border-color: #6366f1;
  background: #eff6ff;
  transform: scale(1.02);
}

.upload-dropzone.has-file {
  border-style: solid;
  border-color: #818cf8;
  background: white;
}

.icon-circle {
  width: 64px;
  height: 64px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.icon-circle i {
  font-size: 1.5rem;
  color: #6366f1;
}

.dropzone-text .main-text {
  display: block;
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
}

.dropzone-text .sub-text {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.4rem;
}

/* Filled State */
.dropzone-filled {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  text-align: left;
}

.file-preview-icon {
  width: 54px;
  height: 54px;
  background: #eff6ff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-preview-icon i {
  font-size: 1.5rem;
  color: #3b82f6;
}

.file-details { flex: 1; }
.file-name {
  display: block;
  font-weight: 700;
  color: #1e293b;
  font-size: 0.95rem;
}
.file-size {
  font-size: 0.75rem;
  color: #94a3b8;
}

.remove-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: #fee2e2;
  color: #ef4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.remove-btn:hover { background: #fecaca; transform: rotate(90deg); }

/* Premium Inputs */
.premium-select, .premium-input {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: white;
  transition: all 0.2s;
}

.premium-select:hover, .premium-input:hover { border-color: #cbd5e1; }
.premium-select:focus, .premium-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.upload-submit-btn {
  background: #6366f1;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.cancel-link {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  padding: 0.5rem 1rem;
  transition: color 0.2s;
}

.cancel-link:hover { color: #1e293b; }

.anim-fade-in { animation: fadeIn 0.4s ease-out; }
.anim-scale-up { animation: scaleUp 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes scaleUp { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

