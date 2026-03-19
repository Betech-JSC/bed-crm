<template>
  <div>
    <Head :title="t('common.create_case_study')" />

    <div class="page-header">
      <div class="page-header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="$inertia.visit('/case-studies')" />
        <div>
          <h1 class="page-title">{{ t('common.create_case_study') }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Thêm dự án điển hình mới' : 'Add a new portfolio case study' }}</p>
        </div>
      </div>
      <div class="header-actions">
        <Button :label="isVi ? 'Lưu nháp' : 'Save Draft'" icon="pi pi-save" severity="secondary" outlined @click="submit('draft')" :loading="saving" />
        <Button :label="isVi ? 'Lưu & Xuất bản' : 'Save & Publish'" icon="pi pi-send" @click="submit('published')" :loading="saving" />
      </div>
    </div>

    <!-- Stepper -->
    <div class="stepper">
      <button v-for="(step, i) in steps" :key="i" class="step-item" :class="{ active: activeStep === i, completed: activeStep > i }" @click="activeStep = i">
        <span class="step-num">{{ activeStep > i ? '✓' : i + 1 }}</span>
        <span class="step-label">{{ step }}</span>
      </button>
    </div>

    <!-- Step 1: Basic Info -->
    <Transition name="slide" mode="out-in">
      <div v-if="activeStep === 0" class="form-card" key="step0">
        <h2 class="card-title"><i class="pi pi-file-edit" /> {{ isVi ? 'Thông tin cơ bản' : 'Basic Information' }}</h2>
        <div class="form-grid-2">
          <div class="form-group full"><label>{{ t('common.title') }} <span class="req">*</span></label><InputText v-model="form.title" class="w-full" :placeholder="isVi ? 'VD: Tăng 300% chuyển đổi cho ABC Corp' : 'e.g. 300% Conversion Increase for ABC Corp'" /></div>
          <div class="form-group full"><label>{{ t('common.summary') }}</label><Textarea v-model="form.summary" rows="2" class="w-full" autoResize :placeholder="isVi ? 'Tóm tắt ngắn gọn...' : 'Brief summary...'" /></div>
          <div class="form-group"><label>{{ t('common.service_category') }} <span class="req">*</span></label>
            <Dropdown v-model="form.service_category" :options="categoryOptions" optionLabel="label" optionValue="value" :placeholder="t('common.select')" class="w-full" />
          </div>
          <div class="form-group"><label>{{ isVi ? 'Hiển thị' : 'Visibility' }}</label>
            <Dropdown v-model="form.visibility" :options="visOptions" optionLabel="l" optionValue="v" class="w-full" />
          </div>
        </div>
        <div class="step-nav"><Button :label="isVi ? 'Tiếp theo' : 'Next'" icon="pi pi-arrow-right" iconPos="right" @click="activeStep = 1" /></div>
      </div>

      <!-- Step 2: Client -->
      <div v-else-if="activeStep === 1" class="form-card" key="step1">
        <h2 class="card-title"><i class="pi pi-user" /> {{ isVi ? 'Thông tin khách hàng' : 'Client Information' }}</h2>
        <div class="form-grid-2">
          <div class="form-group"><label>{{ t('common.client_name') }} <span class="req">*</span></label><InputText v-model="form.client_name" class="w-full" /></div>
          <div class="form-group"><label>{{ t('common.client_industry') }}</label><InputText v-model="form.client_industry" class="w-full" /></div>
          <div class="form-group"><label>{{ t('common.client_company_size') }}</label>
            <Dropdown v-model="form.client_company_size" :options="sizeOptions" optionLabel="label" optionValue="value" :placeholder="t('common.select')" class="w-full" showClear />
          </div>
          <div class="form-group"><label>{{ t('common.client_website') }}</label><InputText v-model="form.client_website" class="w-full" placeholder="https://" /></div>
        </div>
        <div class="step-nav"><Button :label="isVi ? 'Quay lại' : 'Back'" icon="pi pi-arrow-left" severity="secondary" text @click="activeStep = 0" /><Button :label="isVi ? 'Tiếp theo' : 'Next'" icon="pi pi-arrow-right" iconPos="right" @click="activeStep = 2" /></div>
      </div>

      <!-- Step 3: Content -->
      <div v-else-if="activeStep === 2" class="form-card" key="step2">
        <h2 class="card-title"><i class="pi pi-align-left" /> {{ isVi ? 'Nội dung dự án' : 'Project Content' }}</h2>
        <div class="content-fields">
          <div class="form-group"><label><span class="label-icon" style="background:#fef2f2;color:#ef4444"><i class="pi pi-exclamation-circle" /></span> {{ t('common.problem') }}</label><Textarea v-model="form.problem" rows="3" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#ecfdf5;color:#10b981"><i class="pi pi-check-circle" /></span> {{ t('common.the_solution') }}</label><Textarea v-model="form.solution" rows="3" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#eef2ff;color:#6366f1"><i class="pi pi-cog" /></span> {{ t('common.the_approach') }}</label><Textarea v-model="form.approach" rows="3" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#fffbeb;color:#f59e0b"><i class="pi pi-chart-line" /></span> {{ t('common.the_result') }}</label><Textarea v-model="form.result" rows="3" class="w-full" autoResize /></div>
        </div>
        <div class="step-nav"><Button :label="isVi ? 'Quay lại' : 'Back'" icon="pi pi-arrow-left" severity="secondary" text @click="activeStep = 1" /><Button :label="isVi ? 'Tiếp theo' : 'Next'" icon="pi pi-arrow-right" iconPos="right" @click="activeStep = 3" /></div>
      </div>

      <!-- Step 4: Extras -->
      <div v-else-if="activeStep === 3" class="form-card" key="step3">
        <h2 class="card-title"><i class="pi pi-sliders-h" /> {{ isVi ? 'Thông tin bổ sung' : 'Additional Info' }}</h2>
        <div class="form-grid-2">
          <div class="form-group"><label>{{ isVi ? 'Ngày bắt đầu' : 'Start Date' }}</label><InputText v-model="form.project_start_date" type="date" class="w-full" /></div>
          <div class="form-group"><label>{{ isVi ? 'Ngày kết thúc' : 'End Date' }}</label><InputText v-model="form.project_end_date" type="date" class="w-full" /></div>
          <div class="form-group"><label>{{ t('common.project_url') }}</label><InputText v-model="form.project_url" class="w-full" placeholder="https://" /></div>
          <div class="form-group"><label>{{ t('common.case_study_tags') }}</label>
            <MultiSelect v-model="form.tag_ids" :options="tags" optionLabel="name" optionValue="id" :placeholder="t('common.select')" class="w-full" />
          </div>
        </div>
        <div class="step-nav"><Button :label="isVi ? 'Quay lại' : 'Back'" icon="pi pi-arrow-left" severity="secondary" text @click="activeStep = 2" /><Button :label="isVi ? 'Hoàn tất & Lưu' : 'Finish & Save'" icon="pi pi-check" @click="submit('draft')" :loading="saving" /></div>
      </div>
    </Transition>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, InputText, Textarea, Dropdown, MultiSelect },
  layout: Layout,
  props: { tags: Array, serviceCategories: Object, clientSizes: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      activeStep: 0, saving: false,
      form: { title: '', summary: '', service_category: null, client_name: '', client_industry: '', client_company_size: null, client_website: '', problem: '', solution: '', approach: '', result: '', visibility: 'private', status: 'draft', tag_ids: [], project_start_date: null, project_end_date: null, project_url: '' },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    steps() { return this.isVi ? ['Cơ bản', 'Khách hàng', 'Nội dung', 'Bổ sung'] : ['Basic', 'Client', 'Content', 'Extras'] },
    categoryOptions() { return Object.entries(this.serviceCategories || {}).map(([v, l]) => ({ value: v, label: l })) },
    sizeOptions() { return Object.entries(this.clientSizes || {}).map(([v, l]) => ({ value: v, label: l })) },
    visOptions() { return [{ v: 'private', l: this.isVi ? '🔒 Riêng tư' : '🔒 Private' }, { v: 'public', l: this.isVi ? '🌐 Công khai' : '🌐 Public' }, { v: 'unlisted', l: this.isVi ? '🔗 Không niêm yết' : '🔗 Unlisted' }] },
  },
  methods: {
    submit(status) { this.saving = true; this.form.status = status; router.post('/case-studies', this.form, { onFinish: () => { this.saving = false } }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-header-left { display: flex; align-items: center; gap: 0.5rem; }
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; gap: 0.5rem; }

.stepper { display: flex; gap: 0; background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 0.2rem; margin-bottom: 1.25rem; overflow: hidden; }
.step-item { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; border: none; background: transparent; border-radius: 10px; cursor: pointer; transition: all 0.25s; font-size: 0.82rem; font-weight: 600; color: #94a3b8; }
.step-item:hover { background: #f8fafc; }
.step-item.active { background: #6366f1; color: white; box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
.step-item.completed { color: #10b981; }
.step-num { width: 24px; height: 24px; border-radius: 50%; border: 2px solid currentColor; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 700; }
.step-item.active .step-num { border-color: white; background: rgba(255,255,255,0.2); }
.step-item.completed .step-num { border-color: #10b981; background: #dcfce7; font-size: 0.6rem; }
.step-label { display: none; }
@media(min-width:640px) { .step-label { display: inline; } }

.form-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 1.5rem 2rem; }
.card-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 1.25rem; display: flex; align-items: center; gap: 0.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f8fafc; }
.card-title i { color: #6366f1; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
@media(max-width:640px) { .form-grid-2 { grid-template-columns: 1fr; } }
.form-group { }
.form-group.full { grid-column: 1 / -1; }
.form-group label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.3rem; }
.req { color: #ef4444; }
.label-icon { width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.label-icon i { font-size: 0.68rem; }
.content-fields { display: flex; flex-direction: column; gap: 0.85rem; }
.w-full { width: 100%; }
.step-nav { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f8fafc; }

.slide-enter-active { animation: slideIn 0.3s ease; } .slide-leave-active { animation: slideOut 0.2s ease; }
@keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
@keyframes slideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(-20px); } }
</style>
