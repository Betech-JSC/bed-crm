<template>
  <div class="edit-page-container anim-fade-in">
    <Head :title="isVi ? `Chỉnh sửa: ${file.name}` : `Edit: ${file.name}`" />
    
    <div class="max-w-3xl mx-auto">
      <header class="page-header">
         <Link :href="`/files/${file.id}`" class="back-btn">
            <i class="pi pi-arrow-left" />
            <span>{{ isVi ? 'Quay lại chi tiết' : 'Back to Details' }}</span>
         </Link>
         <h1 class="text-2xl font-extrabold text-slate-800 mt-4">
           {{ isVi ? 'Chỉnh sửa cài đặt tài liệu' : 'Edit Document Settings' }}
         </h1>
      </header>

      <Card class="premium-form-card">
        <template #content>
          <form @submit.prevent="update" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              
              <!-- File Name -->
              <div class="flex flex-col md:col-span-2">
                <label class="form-label mb-2">{{ isVi ? 'Tên tài liệu' : 'Document Name' }}</label>
                <div class="input-with-icon">
                   <i class="pi pi-file text-slate-400" />
                   <InputText v-model="form.name" class="premium-input w-full" :class="{ 'p-invalid': form.errors.name }" />
                </div>
                <small v-if="form.errors.name" class="p-error mt-1">{{ form.errors.name }}</small>
              </div>

              <!-- Type Selection -->
              <div class="flex flex-col">
                <label class="form-label mb-2">{{ isVi ? 'Định dạng' : 'File Type' }}</label>
                <Select
                  v-model="form.type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="premium-select"
                />
              </div>

              <!-- Access Level -->
              <div class="flex flex-col">
                <label class="form-label mb-2">{{ isVi ? 'Mức độ truy cập' : 'Access Level' }}</label>
                <Select
                  v-model="form.access_level"
                  :options="accessLevelOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="premium-select"
                />
              </div>

              <!-- Description -->
              <div class="flex flex-col md:col-span-2">
                <label class="form-label mb-2">{{ isVi ? 'Mô tả chi tiết' : 'Detailed Description' }}</label>
                <Textarea
                  v-model="form.description"
                  rows="4"
                  class="premium-input w-full"
                  :placeholder="isVi ? 'Mô tả nội dung hoặc mục đích của tài liệu này...' : 'Describe the content or purpose of this document...'"
                />
              </div>

              <!-- Permissions Toggle -->
              <div class="md:col-span-2 p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-700">{{ isVi ? 'Công khai tài liệu' : 'Public Availability' }}</span>
                  <span class="text-[11px] text-slate-400">{{ isVi ? 'Cho phép người dùng ngoài hệ thống truy cập bằng liên kết.' : 'Allows external users to access via link.' }}</span>
                </div>
                <Checkbox v-model="form.is_public" :binary="true" />
              </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
               <Link :href="`/files/${file.id}`" class="cancel-link">
                  {{ isVi ? 'Huỷ thay đổi' : 'Cancel' }}
               </Link>
               <Button 
                type="submit" 
                :label="isVi ? 'Lưu thay đổi' : 'Save Changes'" 
                icon="pi pi-check" 
                class="save-btn" 
                :loading="form.processing" 
               />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
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
    InputText,
    Textarea,
    Select,
    Checkbox,
    Button,
  },
  layout: Layout,
  props: {
    file: Object,
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
        name: this.file.name,
        description: this.file.description || '',
        type: this.file.type,
        is_public: this.file.is_public,
        access_level: this.file.access_level,
      }),
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
    update() {
      this.form.put(`/files/${this.file.id}`, {
        preserveScroll: true,
      })
    },
  },
}
</script>

<style scoped>
.edit-page-container {
  padding: 2rem 1rem;
}

.page-header {
  margin-bottom: 2rem;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.back-btn:hover { color: #1e293b; transform: translateX(-4px); }

.premium-form-card {
  border-radius: 28px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  overflow: hidden;
}

.form-label {
  font-size: 0.75rem;
  font-weight: 800;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-icon i {
  position: absolute;
  left: 1rem;
  z-index: 10;
}

.input-with-icon :deep(.premium-input) {
  padding-left: 2.75rem;
}

.premium-input, .premium-select {
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  background: white;
  transition: all 0.2s;
}

.premium-input:hover, .premium-select:hover { border-color: #cbd5e1; }
.premium-input:focus, .premium-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.save-btn {
  background: #6366f1;
  border: none;
  font-weight: 700;
  border-radius: 14px;
  padding: 0.75rem 1.75rem;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.cancel-link {
  color: #64748b;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  padding: 0.5rem 1rem;
  transition: color 0.2s;
}

.cancel-link:hover { color: #1e293b; }

.anim-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>



