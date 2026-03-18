<template>
  <div>
    <Head :title="isVi ? 'OKR' : 'OKRs'" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isVi ? 'Mục tiêu & Kết quả then chốt' : 'Objectives & Key Results' }}</h1>
        <p class="page-subtitle">
          {{ isVi ? `${stats.total_objectives} mục tiêu · ${stats.total_key_results} KRs · ${stats.total_initiatives} sáng kiến` : `${stats.total_objectives} objectives · ${stats.total_key_results} KRs · ${stats.total_initiatives} initiatives` }}
        </p>
      </div>
      <div class="header-actions">
        <Dropdown v-model="selectedPeriod" :options="periodOptions" optionLabel="label" optionValue="value" :placeholder="isVi ? 'Chu kỳ' : 'Period'" showClear @change="filterByPeriod" class="period-filter" />
        <Button :label="isVi ? 'Tạo mục tiêu' : 'New Objective'" icon="pi pi-plus" @click="showCreateObj = true" />
      </div>
    </div>

    <!-- OKR Tree -->
    <div class="okr-tree">
      <div v-for="obj in tree" :key="obj.id" class="okr-node okr-company">
        <OKRCard :objective="obj" :isVi="isVi" :level="0" @check-in="openCheckIn" @cascade="openCascade" />

        <!-- Team level -->
        <div v-for="child in obj.children" :key="child.id" class="okr-child">
          <div class="okr-connector" />
          <OKRCard :objective="child" :isVi="isVi" :level="1" @check-in="openCheckIn" @cascade="openCascade" />

          <!-- Individual level -->
          <div v-for="grandchild in child.children" :key="grandchild.id" class="okr-grandchild">
            <div class="okr-connector" />
            <OKRCard :objective="grandchild" :isVi="isVi" :level="2" @check-in="openCheckIn" />
          </div>
        </div>
      </div>

      <div v-if="!tree.length" class="empty-state">
        <i class="pi pi-sitemap" />
        <p>{{ isVi ? 'Chưa có mục tiêu nào. Hãy tạo mục tiêu đầu tiên!' : 'No objectives yet. Create your first one!' }}</p>
        <Button :label="isVi ? 'Tạo mục tiêu' : 'Create Objective'" icon="pi pi-plus" @click="showCreateObj = true" />
      </div>
    </div>

    <!-- Create Objective Dialog -->
    <Dialog v-model:visible="showCreateObj" :header="isVi ? 'Tạo mục tiêu' : 'Create Objective'" modal :style="{ width: '520px' }">
      <div class="form-group">
        <label>{{ isVi ? 'Tiêu đề' : 'Title' }}</label>
        <InputText v-model="objForm.title" class="w-full" />
      </div>
      <div class="form-row">
        <div class="form-group flex-1">
          <label>{{ isVi ? 'Cấp độ' : 'Level' }}</label>
          <Dropdown v-model="objForm.level" :options="levelOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="form-group flex-1">
          <label>{{ isVi ? 'Chu kỳ' : 'Period' }}</label>
          <InputText v-model="objForm.period" placeholder="Q2-2026" class="w-full" />
        </div>
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Người sở hữu' : 'Owner' }}</label>
        <Dropdown v-model="objForm.owner_id" :options="users" optionLabel="name" optionValue="id" :placeholder="isVi ? 'Chọn...' : 'Select...'" class="w-full" showClear />
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Trụ cột chiến lược' : 'Strategic Theme' }}</label>
        <Dropdown v-model="objForm.strategic_theme_id" :options="themes" optionLabel="name" optionValue="id" :placeholder="isVi ? 'Không bắt buộc' : 'Optional'" class="w-full" showClear />
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showCreateObj = false" />
        <Button :label="isVi ? 'Tạo' : 'Create'" icon="pi pi-check" @click="createObjective" :loading="creating" />
      </template>
    </Dialog>

    <!-- Check-in Dialog -->
    <Dialog v-model:visible="showCheckIn" :header="isVi ? 'Check-in KR' : 'Key Result Check-in'" modal :style="{ width: '420px' }">
      <div v-if="checkInKR" class="check-in-info">
        <h4>{{ checkInKR.title }}</h4>
        <div class="check-in-progress">
          <span>{{ checkInKR.current_value }} → {{ checkInKR.target_value }}</span>
          <span class="check-in-pct">{{ checkInKR.progress }}%</span>
        </div>
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Giá trị mới' : 'New Value' }}</label>
        <InputText v-model.number="checkInValue" type="number" class="w-full" />
      </div>
      <div class="form-group">
        <label>{{ isVi ? 'Mức tự tin (0-100)' : 'Confidence (0-100)' }}</label>
        <InputText v-model.number="checkInConfidence" type="number" min="0" max="100" class="w-full" />
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showCheckIn = false" />
        <Button :label="isVi ? 'Cập nhật' : 'Update'" icon="pi pi-check" @click="submitCheckIn" :loading="submittingCheckIn" />
      </template>
    </Dialog>

    <!-- Cascade Dialog -->
    <Dialog v-model:visible="showCascade" :header="isVi ? 'Cascade mục tiêu' : 'Cascade Objective'" modal :style="{ width: '480px' }">
      <p class="cascade-hint">{{ isVi ? 'Tạo mục tiêu con kế thừa từ mục tiêu cha' : 'Create a child objective cascading from the parent' }}</p>
      <div class="form-group">
        <label>{{ isVi ? 'Tiêu đề' : 'Title' }}</label>
        <InputText v-model="cascadeForm.title" class="w-full" />
      </div>
      <div class="form-row">
        <div class="form-group flex-1">
          <label>{{ isVi ? 'Cấp độ' : 'Level' }}</label>
          <Dropdown v-model="cascadeForm.level" :options="childLevelOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="form-group flex-1">
          <label>{{ isVi ? 'Người sở hữu' : 'Owner' }}</label>
          <Dropdown v-model="cascadeForm.owner_id" :options="users" optionLabel="name" optionValue="id" class="w-full" showClear />
        </div>
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showCascade = false" />
        <Button :label="isVi ? 'Cascade' : 'Cascade'" icon="pi pi-arrow-down" @click="submitCascade" :loading="cascading" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import OKRCard from './OKRCard.vue'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, Dropdown, Dialog, InputText, OKRCard },
  layout: Layout,
  props: { tree: Array, periods: Array, stats: Object, themes: Array, users: Array, current_period: String, data_sources: Object },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      selectedPeriod: this.current_period,
      showCreateObj: false, creating: false,
      objForm: { title: '', level: 'company', period: '', owner_id: null, strategic_theme_id: null },
      showCheckIn: false, checkInKR: null, checkInValue: 0, checkInConfidence: 70, submittingCheckIn: false,
      showCascade: false, cascadeParent: null, cascadeForm: { title: '', level: 'team', owner_id: null }, cascading: false,
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    periodOptions() { return this.periods.map(p => ({ value: p, label: p })) },
    levelOptions() {
      return [
        { value: 'company', label: this.isVi ? '🏢 Công ty' : '🏢 Company' },
        { value: 'team', label: this.isVi ? '👥 Team' : '👥 Team' },
        { value: 'individual', label: this.isVi ? '👤 Cá nhân' : '👤 Individual' },
      ]
    },
    childLevelOptions() {
      return [
        { value: 'team', label: this.isVi ? '👥 Team' : '👥 Team' },
        { value: 'individual', label: this.isVi ? '👤 Cá nhân' : '👤 Individual' },
      ]
    },
  },
  methods: {
    filterByPeriod() { router.get('/okrs', this.selectedPeriod ? { period: this.selectedPeriod } : {}, { preserveState: true }) },
    async createObjective() {
      this.creating = true
      await axios.post('/okrs/objectives', this.objForm)
      this.creating = false
      this.showCreateObj = false
      this.objForm = { title: '', level: 'company', period: '', owner_id: null, strategic_theme_id: null }
      router.reload()
    },
    openCheckIn(kr) {
      this.checkInKR = kr
      this.checkInValue = kr.current_value
      this.checkInConfidence = 70
      this.showCheckIn = true
    },
    async submitCheckIn() {
      this.submittingCheckIn = true
      await axios.post(`/okrs/key-results/${this.checkInKR.id}/check-in`, { current_value: this.checkInValue, confidence: this.checkInConfidence })
      this.submittingCheckIn = false
      this.showCheckIn = false
      router.reload()
    },
    openCascade(obj) {
      this.cascadeParent = obj
      this.cascadeForm = { title: '', level: 'team', owner_id: null }
      this.showCascade = true
    },
    async submitCascade() {
      this.cascading = true
      await axios.post(`/okrs/objectives/${this.cascadeParent.id}/cascade`, this.cascadeForm)
      this.cascading = false
      this.showCascade = false
      router.reload()
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.72rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; gap: 0.5rem; }
.period-filter { min-width: 140px; }

.okr-tree { display: flex; flex-direction: column; gap: 0.5rem; }
.okr-child { margin-left: 2rem; position: relative; }
.okr-grandchild { margin-left: 2rem; position: relative; }
.okr-connector { position: absolute; left: -1rem; top: 0; bottom: 50%; width: 1rem; border-left: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; border-radius: 0 0 0 8px; }

.empty-state { text-align: center; padding: 3rem; color: #cbd5e1; background: white; border-radius: 12px; border: 1px dashed #e2e8f0; }
.empty-state i { font-size: 2.5rem; margin-bottom: 0.5rem; }
.empty-state p { font-size: 0.9rem; margin: 0 0 1rem; }

.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #334155; margin-bottom: 0.25rem; }
.form-row { display: flex; gap: 0.75rem; }
.flex-1 { flex: 1; }
.w-full { width: 100%; }
.cascade-hint { font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem; }

.check-in-info h4 { margin: 0 0 0.35rem; font-size: 0.85rem; color: #1e293b; }
.check-in-progress { display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; margin-bottom: 0.75rem; }
.check-in-pct { font-weight: 700; color: #6366f1; }
</style>
