<template>
  <div>
    <Head title="Tạo đề xuất báo giá" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/proposals" class="breadcrumb-link">
        <i class="pi pi-arrow-left" /> Báo giá
      </Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Tạo mới</span>
    </div>

    <div class="form-layout">
      <!-- Main Form -->
      <div class="form-main">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon">
              <i class="pi pi-file-edit" />
            </div>
            <div>
              <h2 class="form-card-title">Tạo đề xuất báo giá mới</h2>
              <p class="form-card-subtitle">Điền thông tin bên dưới để tạo báo giá</p>
            </div>
          </div>

          <form @submit.prevent="store">
            <!-- Title -->
            <div class="form-section">
              <div class="form-group form-group--full">
                <label>Tiêu đề <span class="required">*</span></label>
                <InputText v-model="form.title" placeholder="VD: Đề xuất phát triển phần mềm CRM" :class="{ 'p-invalid': form.errors.title }" />
                <small v-if="form.errors.title" class="field-error">{{ form.errors.title }}</small>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Deal liên kết</label>
                  <Select
                    v-model="form.deal_id"
                    :options="dealOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Chọn deal (tuỳ chọn)"
                    :class="{ 'p-invalid': form.errors.deal_id }"
                  />
                  <small v-if="form.errors.deal_id" class="field-error">{{ form.errors.deal_id }}</small>
                </div>

                <div class="form-group">
                  <label>Giá trị</label>
                  <InputNumber
                    v-model="form.amount"
                    mode="currency"
                    currency="VND"
                    locale="vi-VN"
                    :class="{ 'p-invalid': form.errors.amount }"
                  />
                  <small v-if="form.errors.amount" class="field-error">{{ form.errors.amount }}</small>
                </div>
              </div>

              <div class="form-group">
                <label>Hiệu lực đến</label>
                <Calendar
                  v-model="form.valid_until"
                  dateFormat="dd/mm/yy"
                  :minDate="minDate"
                  placeholder="Chọn ngày hết hạn"
                  :class="{ 'p-invalid': form.errors.valid_until }"
                />
                <small v-if="form.errors.valid_until" class="field-error">{{ form.errors.valid_until }}</small>
              </div>

              <div class="form-group form-group--full">
                <label>Mô tả</label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  class="form-textarea"
                  :class="{ 'form-textarea--error': form.errors.description }"
                  placeholder="Mô tả chi tiết nội dung báo giá..."
                />
                <small v-if="form.errors.description" class="field-error">{{ form.errors.description }}</small>
              </div>
            </div>

            <!-- File Upload -->
            <div class="form-section">
              <div class="form-group form-group--full">
                <label>File đề xuất <span class="required">*</span></label>
                <div class="upload-zone" @dragover.prevent @drop.prevent="onDrop">
                  <input type="file" ref="fileInput" accept=".pdf,.doc,.docx" class="upload-hidden" @change="onFileChange" />
                  <div v-if="!selectedFile" class="upload-placeholder" @click="$refs.fileInput.click()">
                    <div class="upload-icon"><i class="pi pi-cloud-upload" /></div>
                    <p class="upload-text">Kéo thả file hoặc click để chọn</p>
                    <p class="upload-hint">PDF, DOC, DOCX (Tối đa 10MB)</p>
                  </div>
                  <div v-else class="upload-preview">
                    <div class="upload-file-icon"><i class="pi pi-file-pdf" /></div>
                    <div class="upload-file-info">
                      <span class="upload-file-name">{{ selectedFile.name }}</span>
                      <span class="upload-file-size">{{ formatFileSize(selectedFile.size) }}</span>
                    </div>
                    <button type="button" class="upload-remove" @click="removeFile">
                      <i class="pi pi-times" />
                    </button>
                  </div>
                </div>
                <small v-if="form.errors.file" class="field-error">{{ form.errors.file }}</small>
              </div>
            </div>

            <!-- Footer -->
            <div class="form-footer">
              <Link href="/proposals">
                <Button label="Hủy" severity="secondary" text />
              </Link>
              <Button label="Tạo báo giá" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar Tips -->
      <div class="form-sidebar">
        <div class="tip-card">
          <div class="tip-icon"><i class="pi pi-lightbulb" /></div>
          <h4>Mẹo hay</h4>
          <ul class="tip-list">
            <li>Tiêu đề nên ngắn gọn và rõ ràng</li>
            <li>Liên kết với Deal để theo dõi hiệu quả</li>
            <li>Đặt ngày hết hạn để tạo urgency</li>
            <li>Upload file PDF để gửi trực tiếp cho khách</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, InputNumber, Select, Calendar, Button },
  layout: Layout,
  props: {
    deal_id: Number,
    deals: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      selectedFile: null,
      form: this.$inertia.form({
        deal_id: this.deal_id || null,
        title: '',
        description: '',
        amount: null,
        valid_until: null,
        file: null,
      }),
    }
  },
  computed: {
    dealOptions() {
      return this.deals.map(deal => ({ label: deal.title, value: deal.id }))
    },
    minDate() {
      const d = new Date()
      d.setDate(d.getDate() + 1)
      return d
    },
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files[0]
      if (file) { this.selectedFile = file; this.form.file = file }
    },
    onDrop(event) {
      const file = event.dataTransfer.files[0]
      if (file) { this.selectedFile = file; this.form.file = file }
    },
    removeFile() {
      this.selectedFile = null
      this.form.file = null
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },
    formatFileSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024, s = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + s[i]
    },
    store() {
      const formData = new FormData()
      formData.append('deal_id', this.form.deal_id || '')
      formData.append('title', this.form.title)
      formData.append('description', this.form.description || '')
      formData.append('amount', this.form.amount || '')
      formData.append('valid_until', this.form.valid_until || '')
      if (this.form.file) formData.append('file', this.form.file)
      this.$inertia.post('/proposals', formData, { forceFormData: true })
    },
  },
}
</script>

<style scoped>
/* ===== Breadcrumb ===== */
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* ===== Layout ===== */
.form-layout { display: grid; grid-template-columns: 1fr 280px; gap: 1.25rem; }

/* ===== Form Card ===== */
.form-main { min-width: 0; }
.form-card {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden;
}
.form-card-header {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc;
}
.form-card-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: #6366f1; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.form-card-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.form-card-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Form Sections ===== */
.form-section { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.85rem; }
.form-group--full { grid-column: 1 / -1; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.required { color: #ef4444; }
.field-error { font-size: 0.7rem; color: #ef4444; }

.form-textarea {
  width: 100%; padding: 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.82rem; font-family: inherit; color: #1e293b; outline: none;
  resize: vertical; transition: border-color 0.2s; line-height: 1.6;
}
.form-textarea:focus { border-color: #6366f1; }
.form-textarea--error { border-color: #ef4444; }
.form-textarea::placeholder { color: #94a3b8; }

/* ===== Upload Zone ===== */
.upload-zone {
  border: 2px dashed #e2e8f0; border-radius: 12px;
  transition: border-color 0.2s; background: #fafbfc;
}
.upload-zone:hover { border-color: #6366f1; }
.upload-hidden { display: none; }
.upload-placeholder {
  display: flex; flex-direction: column; align-items: center;
  padding: 2rem; cursor: pointer; text-align: center;
}
.upload-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: #6366f1; margin-bottom: 0.65rem;
}
.upload-text { font-size: 0.82rem; font-weight: 500; color: #475569; margin: 0; }
.upload-hint { font-size: 0.68rem; color: #94a3b8; margin: 0.2rem 0 0; }

.upload-preview {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem 1rem;
}
.upload-file-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
}
.upload-file-info { flex: 1; }
.upload-file-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; }
.upload-file-size { font-size: 0.68rem; color: #94a3b8; }
.upload-remove {
  width: 28px; height: 28px; border-radius: 8px; border: none;
  background: #fef2f2; color: #ef4444; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 0.72rem;
  transition: all 0.2s;
}
.upload-remove:hover { background: #ef4444; color: white; }

/* ===== Footer ===== */
.form-footer {
  display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;
  padding: 1rem 1.5rem;
}

/* ===== Sidebar Tips ===== */
.form-sidebar { position: sticky; top: 1rem; }
.tip-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.15rem;
}
.tip-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  color: #f59e0b; display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem; margin-bottom: 0.65rem;
}
.tip-card h4 { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0 0 0.55rem; }
.tip-list { margin: 0; padding: 0; list-style: none; }
.tip-list li {
  position: relative; padding-left: 1rem; margin-bottom: 0.4rem;
  font-size: 0.72rem; color: #64748b; line-height: 1.5;
}
.tip-list li::before {
  content: ''; position: absolute; left: 0; top: 0.35rem;
  width: 5px; height: 5px; border-radius: 50%; background: #6366f1;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) { .form-layout { grid-template-columns: 1fr; } .form-sidebar { position: static; } }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>
