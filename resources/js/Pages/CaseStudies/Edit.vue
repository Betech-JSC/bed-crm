<template>
  <div>
    <Head :title="t('common.edit_case_study')" />

    <div class="page-header">
      <div class="page-header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="$inertia.visit(`/case-studies/${caseStudy.id}`)" />
        <div>
          <h1 class="page-title">{{ t('common.edit_case_study') }}</h1>
          <p class="page-subtitle">{{ caseStudy.title }}</p>
        </div>
      </div>
      <div class="header-actions">
        <Button v-if="caseStudy.status !== 'published'" :label="isVi ? 'Xuất bản' : 'Publish'" icon="pi pi-send" severity="success" @click="publish" :loading="publishing" />
        <Button :label="isVi ? 'Xem' : 'View'" icon="pi pi-eye" severity="secondary" outlined @click="$inertia.visit(`/case-studies/${caseStudy.id}`)" />
        <Button :label="t('common.save')" icon="pi pi-check" @click="save" :loading="saving" />
      </div>
    </div>

    <!-- Tabs -->
    <div class="edit-tabs">
      <button v-for="(tab, i) in tabs" :key="i" class="edit-tab" :class="{ active: activeTab === i }" @click="activeTab = i">
        <i :class="tab.icon" /> {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Basic -->
    <Transition name="fade" mode="out-in">
      <div v-if="activeTab === 0" class="form-card" key="t0">
        <div class="form-grid-2">
          <div class="form-group full"><label>{{ t('common.title') }} <span class="req">*</span></label><InputText v-model="form.title" class="w-full" /></div>
          <div class="form-group full"><label>{{ t('common.summary') }}</label><Textarea v-model="form.summary" rows="2" class="w-full" autoResize /></div>
          <div class="form-group"><label>{{ t('common.service_category') }}</label>
            <Dropdown v-model="form.service_category" :options="categoryOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-group"><label>{{ isVi ? 'Hiển thị' : 'Visibility' }}</label>
            <Dropdown v-model="form.visibility" :options="visOptions" optionLabel="l" optionValue="v" class="w-full" />
          </div>
          <div class="form-group"><label>{{ t('common.status') }}</label>
            <Dropdown v-model="form.status" :options="statusOptions" optionLabel="l" optionValue="v" class="w-full" />
          </div>
          <div class="form-group"><label>{{ t('common.case_study_tags') }}</label>
            <MultiSelect v-model="form.tag_ids" :options="tags" optionLabel="name" optionValue="id" class="w-full" />
          </div>
        </div>
      </div>

      <!-- Tab: Client -->
      <div v-else-if="activeTab === 1" class="form-card" key="t1">
        <div class="form-grid-2">
          <div class="form-group"><label>{{ t('common.client_name') }} <span class="req">*</span></label><InputText v-model="form.client_name" class="w-full" /></div>
          <div class="form-group"><label>{{ t('common.client_industry') }}</label><InputText v-model="form.client_industry" class="w-full" /></div>
          <div class="form-group"><label>{{ t('common.client_company_size') }}</label>
            <Dropdown v-model="form.client_company_size" :options="sizeOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div class="form-group"><label>{{ t('common.client_website') }}</label><InputText v-model="form.client_website" class="w-full" /></div>
        </div>
      </div>

      <!-- Tab: Content -->
      <div v-else-if="activeTab === 2" class="form-card" key="t2">
        <div class="content-fields">
          <div class="form-group"><label><span class="label-icon" style="background:#fef2f2;color:#ef4444"><i class="pi pi-exclamation-circle" /></span> {{ t('common.problem') }}</label><Textarea v-model="form.problem" rows="4" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#ecfdf5;color:#10b981"><i class="pi pi-check-circle" /></span> {{ t('common.the_solution') }}</label><Textarea v-model="form.solution" rows="4" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#eef2ff;color:#6366f1"><i class="pi pi-cog" /></span> {{ t('common.the_approach') }}</label><Textarea v-model="form.approach" rows="4" class="w-full" autoResize /></div>
          <div class="form-group"><label><span class="label-icon" style="background:#fffbeb;color:#f59e0b"><i class="pi pi-chart-line" /></span> {{ t('common.the_result') }}</label><Textarea v-model="form.result" rows="4" class="w-full" autoResize /></div>
        </div>
      </div>

      <!-- Tab: Project -->
      <div v-else-if="activeTab === 3" class="form-card" key="t3">
        <div class="form-grid-2">
          <div class="form-group"><label>{{ isVi ? 'Ngày bắt đầu' : 'Start Date' }}</label><InputText v-model="form.project_start_date" type="date" class="w-full" /></div>
          <div class="form-group"><label>{{ isVi ? 'Ngày kết thúc' : 'End Date' }}</label><InputText v-model="form.project_end_date" type="date" class="w-full" /></div>
          <div class="form-group full"><label>{{ t('common.project_url') }}</label><InputText v-model="form.project_url" class="w-full" /></div>
        </div>
      </div>
    </Transition>

    <!-- Floating Save Bar -->
    <div class="save-bar">
      <span class="save-status" v-if="caseStudy.updated_at">{{ isVi ? 'Cập nhật lần cuối' : 'Last updated' }}: {{ formatDate(caseStudy.updated_at) }}</span>
      <Button :label="t('common.save')" icon="pi pi-check" @click="save" :loading="saving" />
    </div>
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
  props: { caseStudy: Object, tags: Array, serviceCategories: Object, clientSizes: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    const cs = this.caseStudy
    return {
      activeTab: 0, saving: false, publishing: false,
      form: {
        title: cs.title, summary: cs.summary, service_category: cs.service_category,
        client_name: cs.client_name, client_industry: cs.client_industry,
        client_company_size: cs.client_company_size, problem: cs.problem, solution: cs.solution,
        approach: cs.approach, result: cs.result, visibility: cs.visibility, status: cs.status,
        tag_ids: cs.tags?.map(t => t.id) || [],
        project_start_date: cs.project_start_date, project_end_date: cs.project_end_date,
        project_url: cs.project_url, client_website: cs.client_website,
      },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    tabs() {
      return [
        { icon: 'pi pi-file-edit', label: this.isVi ? 'Cơ bản' : 'Basic' },
        { icon: 'pi pi-user', label: this.isVi ? 'Khách hàng' : 'Client' },
        { icon: 'pi pi-align-left', label: this.isVi ? 'Nội dung' : 'Content' },
        { icon: 'pi pi-calendar', label: this.isVi ? 'Dự án' : 'Project' },
      ]
    },
    categoryOptions() { return Object.entries(this.serviceCategories || {}).map(([v, l]) => ({ value: v, label: l })) },
    sizeOptions() { return Object.entries(this.clientSizes || {}).map(([v, l]) => ({ value: v, label: l })) },
    visOptions() { return [{ v: 'private', l: this.isVi ? '🔒 Riêng tư' : '🔒 Private' }, { v: 'public', l: this.isVi ? '🌐 Công khai' : '🌐 Public' }, { v: 'unlisted', l: this.isVi ? '🔗 Không niêm yết' : '🔗 Unlisted' }] },
    statusOptions() { return [{ v: 'draft', l: this.isVi ? 'Bản nháp' : 'Draft' }, { v: 'published', l: this.isVi ? 'Đã xuất bản' : 'Published' }, { v: 'archived', l: this.isVi ? 'Lưu trữ' : 'Archived' }] },
  },
  methods: {
    save() { this.saving = true; router.put(`/case-studies/${this.caseStudy.id}`, this.form, { onFinish: () => { this.saving = false } }) },
    publish() { this.publishing = true; router.post(`/case-studies/${this.caseStudy.id}/publish`, {}, { onFinish: () => { this.publishing = false } }) },
    formatDate(d) { if (!d) return ''; return new Date(d).toLocaleString(this.isVi ? 'vi-VN' : 'en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-header-left { display: flex; align-items: center; gap: 0.5rem; }
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #6366f1; margin: 0; font-weight: 500; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.header-actions { display: flex; gap: 0.5rem; }

.edit-tabs { display: flex; gap: 0; background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 0.2rem; margin-bottom: 1rem; }
.edit-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.65rem 1rem; border: none; background: transparent; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-size: 0.78rem; font-weight: 600; color: #94a3b8; }
.edit-tab:hover { background: #f8fafc; color: #64748b; }
.edit-tab.active { background: #6366f1; color: white; box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
.edit-tab i { font-size: 0.82rem; }

.form-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 1.5rem 2rem; }
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

.save-bar { display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; padding: 0.85rem 1.25rem; background: white; border: 1px solid #f1f5f9; border-radius: 14px; position: sticky; bottom: 1rem; box-shadow: 0 -2px 12px rgba(0,0,0,0.06); z-index: 10; }
.save-status { font-size: 0.72rem; color: #94a3b8; margin-right: auto; }

.fade-enter-active { animation: fadeIn 0.2s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
