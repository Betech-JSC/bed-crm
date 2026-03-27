<template>
  <div>
    <Head title="Văn hóa Doanh nghiệp" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-heart-fill" /></div>
        <div>
          <h1 class="page-title">Văn hóa Doanh nghiệp</h1>
          <p class="page-subtitle">Xây dựng và duy trì văn hóa công ty mạnh mẽ</p>
        </div>
      </div>
      <div class="header-actions">
        <Button v-if="!values.length && !initiatives.length" label="Tạo dữ liệu mẫu" icon="pi pi-database" severity="secondary" @click="$inertia.post('/culture/seed')" />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card sc-val"><div class="stat-icon"><i class="pi pi-heart" /></div><div class="stat-body"><span class="stat-value">{{ stats.values_count }}</span><span class="stat-label">Giá trị cốt lõi</span></div></div>
      <div class="stat-card sc-init"><div class="stat-icon"><i class="pi pi-bolt" /></div><div class="stat-body"><span class="stat-value">{{ stats.completed_initiatives }}/{{ stats.total_initiatives }}</span><span class="stat-label">Sáng kiến</span></div></div>
      <div class="stat-card sc-srv"><div class="stat-icon"><i class="pi pi-chart-bar" /></div><div class="stat-body"><span class="stat-value">{{ stats.active_surveys }}</span><span class="stat-label">Khảo sát đang mở</span></div></div>
      <div class="stat-card sc-res"><div class="stat-icon"><i class="pi pi-comments" /></div><div class="stat-body"><span class="stat-value">{{ stats.total_responses }}</span><span class="stat-label">Phản hồi</span></div></div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
      <button v-for="t in tabs" :key="t.key" class="tab-btn" :class="{ 'tab-active': activeTab === t.key }" @click="activeTab = t.key">
        <i :class="t.icon" /> {{ t.label }}
      </button>
    </div>

    <!-- ===== TAB 1: Core Values ===== -->
    <div v-if="activeTab === 'values'" class="tab-content">
      <div class="tab-header">
        <h2 class="tab-title">Giá trị cốt lõi</h2>
        <Button label="Thêm giá trị" icon="pi pi-plus" size="small" @click="openValueDialog()" />
      </div>
      <div v-if="values.length" class="values-grid">
        <div v-for="val in values" :key="val.id" class="value-card" :style="{ borderTopColor: val.color }">
          <div class="value-card-header">
            <div class="value-icon" :style="{ background: val.color + '18', color: val.color }"><i :class="val.icon" /></div>
            <div class="value-actions">
              <Button icon="pi pi-pencil" text rounded size="small" @click="openValueDialog(val)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteValue(val)" />
            </div>
          </div>
          <h3 class="value-title">{{ val.title }}</h3>
          <p class="value-desc">{{ val.description }}</p>
          <div v-if="val.behaviors && val.behaviors.length" class="value-behaviors">
            <div class="behavior-label"><i class="pi pi-check-square" /> Hành vi mẫu</div>
            <ul>
              <li v-for="(b, bi) in val.behaviors" :key="bi">{{ b }}</li>
            </ul>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-heart" /></div>
        <h3>Chưa có giá trị cốt lõi</h3>
        <p>Hãy định nghĩa các giá trị cốt lõi cho doanh nghiệp.</p>
      </div>
    </div>

    <!-- ===== TAB 2: Initiatives ===== -->
    <div v-if="activeTab === 'initiatives'" class="tab-content">
      <div class="tab-header">
        <h2 class="tab-title">Sáng kiến văn hóa</h2>
        <Button label="Thêm sáng kiến" icon="pi pi-plus" size="small" @click="openInitDialog()" />
      </div>
      <div v-if="initiatives.length" class="init-list">
        <div v-for="init in initiatives" :key="init.id" class="init-card">
          <div class="init-left">
            <span class="init-status" :class="'is-' + init.status">{{ statusLabel(init.status) }}</span>
            <h3 class="init-title">{{ init.title }}</h3>
            <p v-if="init.description" class="init-desc">{{ init.description }}</p>
            <div class="init-meta">
              <span v-if="init.category" class="init-cat"><i class="pi pi-tag" /> {{ categoryLabel(init.category) }}</span>
              <span v-if="init.assigned_to"><i class="pi pi-user" /> {{ init.assigned_to }}</span>
              <span v-if="init.start_date"><i class="pi pi-calendar" /> {{ init.start_date }}</span>
              <span class="init-impact" :class="'imp-' + init.impact">{{ init.impact }}</span>
            </div>
          </div>
          <div class="init-actions">
            <Button icon="pi pi-pencil" text rounded size="small" @click="openInitDialog(init)" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteInit(init)" />
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-bolt" /></div>
        <h3>Chưa có sáng kiến</h3>
        <p>Tạo sáng kiến văn hóa đầu tiên cho tổ chức.</p>
      </div>
    </div>

    <!-- ===== TAB 3: Surveys ===== -->
    <div v-if="activeTab === 'surveys'" class="tab-content">
      <div class="tab-header">
        <h2 class="tab-title">Khảo sát văn hóa</h2>
        <Button label="Tạo khảo sát" icon="pi pi-plus" size="small" @click="openSurveyDialog()" />
      </div>
      <div v-if="surveys.length" class="survey-list">
        <div v-for="srv in surveys" :key="srv.id" class="survey-card">
          <div class="survey-left">
            <div class="survey-status-row">
              <span class="survey-status" :class="'ss-' + srv.status">{{ surveyStatusLabel(srv.status) }}</span>
              <span v-if="srv.anonymous" class="anon-badge"><i class="pi pi-eye-slash" /> Ẩn danh</span>
            </div>
            <h3 class="survey-title">{{ srv.title }}</h3>
            <p v-if="srv.description" class="survey-desc">{{ srv.description }}</p>
            <div class="survey-meta">
              <span><i class="pi pi-list" /> {{ srv.questions.length }} câu hỏi</span>
              <span><i class="pi pi-users" /> {{ srv.responses_count }} phản hồi</span>
              <span><i class="pi pi-calendar" /> {{ srv.created_at }}</span>
            </div>
          </div>
          <div class="survey-actions">
            <Button v-if="srv.status === 'active' && !srv.user_responded" label="Tham gia" icon="pi pi-send" size="small" @click="openTakeSurvey(srv)" />
            <Button v-if="srv.user_responded" label="Đã trả lời" size="small" severity="success" text disabled />
            <Button v-if="srv.responses_count > 0" icon="pi pi-chart-bar" text rounded size="small" v-tooltip="'Xem kết quả'" @click="viewResults(srv)" />
            <Button icon="pi pi-pencil" text rounded size="small" @click="openSurveyDialog(srv)" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteSurvey(srv)" />
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-chart-bar" /></div>
        <h3>Chưa có khảo sát</h3>
        <p>Tạo khảo sát để đánh giá sức khỏe văn hóa tổ chức.</p>
      </div>
    </div>

    <!-- ===== VALUE DIALOG ===== -->
    <div v-if="valueDialog" class="dialog-overlay" @click.self="valueDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon di-pink"><i class="pi pi-heart" /></div>
            <h3>{{ valForm.id ? 'Sửa' : 'Thêm' }} giá trị cốt lõi</h3>
          </div>
          <button class="dialog-close" @click="valueDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitValue" class="dialog-body">
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="valForm.title" class="w-full" placeholder="VD: Chính trực" /></div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="valForm.description" class="form-control" rows="2" placeholder="Mô tả giá trị..." /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Icon</label><InputText v-model="valForm.icon" class="w-full" placeholder="pi pi-heart" /></div>
            <div class="form-group" style="width:100px"><label>Màu</label><InputText v-model="valForm.color" class="w-full" placeholder="#ec4899" /></div>
            <div class="form-group" style="width:80px"><label>Thứ tự</label><InputNumber v-model="valForm.sort_order" class="w-full" /></div>
          </div>
          <div class="form-group">
            <label>Hành vi mẫu (mỗi dòng 1 hành vi)</label>
            <textarea v-model="behaviorsText" class="form-control" rows="3" placeholder="Nói đúng sự thật&#10;Giữ lời hứa&#10;Chịu trách nhiệm" />
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="valueDialog = false" type="button" />
            <Button :label="valForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="valForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== INITIATIVE DIALOG ===== -->
    <div v-if="initDialog" class="dialog-overlay" @click.self="initDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon di-amber"><i class="pi pi-bolt" /></div>
            <h3>{{ initForm.id ? 'Sửa' : 'Thêm' }} sáng kiến</h3>
          </div>
          <button class="dialog-close" @click="initDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitInit" class="dialog-body">
          <div class="form-group"><label>Tên sáng kiến <span class="req">*</span></label><InputText v-model="initForm.title" class="w-full" /></div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="initForm.description" class="form-control" rows="2" /></div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Danh mục</label>
              <Select v-model="initForm.category" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="Chọn..." class="w-full" />
            </div>
            <div class="form-group flex-1">
              <label>Trạng thái</label>
              <Select v-model="initForm.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="form-group flex-1">
              <label>Mức ảnh hưởng</label>
              <Select v-model="initForm.impact" :options="impactOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Ngày bắt đầu</label><InputText v-model="initForm.start_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Ngày kết thúc</label><InputText v-model="initForm.end_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Người phụ trách</label><InputText v-model="initForm.assigned_to" class="w-full" /></div>
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="initDialog = false" type="button" />
            <Button :label="initForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="initForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== SURVEY DIALOG ===== -->
    <div v-if="surveyDialog" class="dialog-overlay" @click.self="surveyDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon di-blue"><i class="pi pi-chart-bar" /></div>
            <h3>{{ srvForm.id ? 'Sửa' : 'Tạo' }} khảo sát</h3>
          </div>
          <button class="dialog-close" @click="surveyDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitSurvey" class="dialog-body">
          <div class="form-row">
            <div class="form-group flex-1"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="srvForm.title" class="w-full" /></div>
            <div class="form-group" style="width:130px">
              <label>Trạng thái</label>
              <Select v-model="srvForm.status" :options="surveyStatuses" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="srvForm.description" class="form-control" rows="2" /></div>
          <div class="form-group">
            <div class="toggle-row"><div><label class="toggle-label">Ẩn danh</label><small class="toggle-desc">Không lưu người trả lời</small></div><InputSwitch v-model="srvForm.anonymous" /></div>
          </div>

          <div class="questions-editor">
            <div v-for="(q, qi) in editSurveyQs" :key="qi" class="edit-q">
              <div class="edit-q-header">
                <span class="edit-q-num">Câu {{ qi + 1 }}</span>
                <div class="edit-q-right">
                  <Select v-model="q.type" :options="qTypes" optionLabel="label" optionValue="value" class="q-type-select" />
                  <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="editSurveyQs.splice(qi, 1)" />
                </div>
              </div>
              <div class="form-group"><InputText v-model="q.q" class="w-full" placeholder="Nhập câu hỏi..." /></div>
              <div v-if="q.type === 'choice'" class="form-group">
                <label>Các lựa chọn (mỗi dòng 1 lựa chọn)</label>
                <textarea v-model="q.choicesText" class="form-control" rows="3" placeholder="Rất đồng ý&#10;Đồng ý&#10;Không đồng ý" />
              </div>
            </div>
            <Button label="Thêm câu hỏi" icon="pi pi-plus" severity="secondary" text @click="editSurveyQs.push({ q: '', type: 'rating', choicesText: '' })" />
          </div>

          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="surveyDialog = false" type="button" />
            <Button :label="srvForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="srvForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== TAKE SURVEY DIALOG ===== -->
    <div v-if="takeSurveyDialog" class="dialog-overlay" @click.self="takeSurveyDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon di-green"><i class="pi pi-send" /></div>
            <h3>{{ activeSurvey?.title }}</h3>
          </div>
          <button class="dialog-close" @click="takeSurveyDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <p v-if="activeSurvey?.description" class="survey-intro">{{ activeSurvey.description }}</p>
          <div v-for="(q, qi) in activeSurvey?.questions" :key="qi" class="take-q">
            <div class="take-q-label">Câu {{ qi + 1 }}: {{ q.q }}</div>
            <div v-if="q.type === 'rating'" class="rating-row">
              <button v-for="r in 5" :key="r" class="rating-btn" :class="{ 'rating-active': surveyAnswers[qi] === r }" @click="surveyAnswers[qi] = r" type="button">{{ r }}</button>
              <span class="rating-labels"><span>Rất không đồng ý</span><span>Rất đồng ý</span></span>
            </div>
            <div v-else-if="q.type === 'text'">
              <textarea v-model="surveyAnswers[qi]" class="form-control" rows="3" placeholder="Nhập câu trả lời..." />
            </div>
            <div v-else-if="q.type === 'choice' && q.choices" class="choice-list">
              <label v-for="(c, ci) in q.choices" :key="ci" class="choice-opt" :class="{ 'choice-selected': surveyAnswers[qi] === c }">
                <input type="radio" :name="'sq_' + qi" :value="c" v-model="surveyAnswers[qi]" /> {{ c }}
              </label>
            </div>
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="takeSurveyDialog = false" type="button" />
            <Button label="Gửi phản hồi" icon="pi pi-send" @click="submitSurveyResponse" />
          </div>
        </div>
      </div>
    </div>

    <!-- ===== RESULTS DIALOG ===== -->
    <div v-if="resultsDialog" class="dialog-overlay" @click.self="resultsDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon di-purple"><i class="pi pi-chart-bar" /></div>
            <h3>Kết quả khảo sát</h3>
          </div>
          <button class="dialog-close" @click="resultsDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <div class="results-summary">
            <span class="results-count"><i class="pi pi-users" /> {{ resultsData?.total_responses }} phản hồi</span>
          </div>
          <div v-for="(r, ri) in resultsData?.results" :key="ri" class="result-q">
            <div class="result-q-header"><span class="result-q-num">Câu {{ ri + 1 }}</span></div>
            <p class="result-q-text">{{ r.question }}</p>
            <div v-if="r.type === 'rating'" class="result-rating">
              <div class="result-avg-wrap">
                <span class="result-avg">{{ r.average }}</span><span class="result-avg-label">/5</span>
              </div>
              <div class="result-bar-track"><div class="result-bar-fill" :style="{ width: (r.average / 5 * 100) + '%' }"></div></div>
            </div>
            <div v-else-if="r.type === 'text'" class="result-texts">
              <div v-for="(a, ai) in r.answers" :key="ai" class="result-text-item">{{ a }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Select from 'primevue/select'

export default {
  components: { Head, Button, InputText, InputNumber, InputSwitch, Select },
  layout: Layout,
  props: { values: Array, initiatives: Array, surveys: Array, stats: Object, filters: Object },
  data() {
    return {
      activeTab: this.filters?.tab || 'values',
      tabs: [
        { key: 'values', label: 'Giá trị cốt lõi', icon: 'pi pi-heart' },
        { key: 'initiatives', label: 'Sáng kiến', icon: 'pi pi-bolt' },
        { key: 'surveys', label: 'Khảo sát', icon: 'pi pi-chart-bar' },
      ],
      // Value dialog
      valueDialog: false, valForm: this.emptyValForm(), behaviorsText: '',
      // Initiative dialog
      initDialog: false, initForm: this.emptyInitForm(),
      categoryOptions: [
        { label: 'Chung', value: 'general' }, { label: 'Học tập', value: 'learning' },
        { label: 'Onboarding', value: 'onboarding' }, { label: 'Đổi mới', value: 'innovation' },
        { label: 'Ghi nhận', value: 'recognition' }, { label: 'Team building', value: 'team_building' },
        { label: 'Sức khỏe', value: 'wellness' },
      ],
      statusOptions: [
        { label: 'Kế hoạch', value: 'planned' }, { label: 'Đang triển khai', value: 'in_progress' },
        { label: 'Hoàn thành', value: 'completed' }, { label: 'Hủy', value: 'cancelled' },
      ],
      impactOptions: [
        { label: 'Thấp', value: 'low' }, { label: 'Trung bình', value: 'medium' }, { label: 'Cao', value: 'high' },
      ],
      // Survey dialog
      surveyDialog: false, srvForm: this.emptySrvForm(), editSurveyQs: [],
      surveyStatuses: [
        { label: 'Nháp', value: 'draft' }, { label: 'Đang mở', value: 'active' }, { label: 'Đã đóng', value: 'closed' },
      ],
      qTypes: [
        { label: 'Đánh giá 1-5', value: 'rating' }, { label: 'Văn bản', value: 'text' }, { label: 'Trắc nghiệm', value: 'choice' },
      ],
      // Take survey
      takeSurveyDialog: false, activeSurvey: null, surveyAnswers: {},
      // Results
      resultsDialog: false, resultsData: null,
    }
  },
  methods: {
    // ── Values ──
    emptyValForm() { return this.$inertia.form({ id: null, title: '', description: '', icon: 'pi pi-heart', color: '#ec4899', behaviors: [], sort_order: 0 }) },
    openValueDialog(v = null) {
      if (v) {
        this.valForm = this.$inertia.form({ id: v.id, title: v.title, description: v.description || '', icon: v.icon, color: v.color, behaviors: v.behaviors || [], sort_order: v.sort_order })
        this.behaviorsText = (v.behaviors || []).join('\n')
      } else { this.valForm = this.emptyValForm(); this.behaviorsText = '' }
      this.valueDialog = true
    },
    submitValue() {
      this.valForm.behaviors = this.behaviorsText.split('\n').map(s => s.trim()).filter(Boolean)
      if (this.valForm.id) this.valForm.put(`/culture/values/${this.valForm.id}`, { preserveScroll: true, onSuccess: () => { this.valueDialog = false } })
      else this.valForm.post('/culture/values', { preserveScroll: true, onSuccess: () => { this.valueDialog = false } })
    },
    deleteValue(v) { if (confirm(`Xóa "${v.title}"?`)) this.$inertia.delete(`/culture/values/${v.id}`, { preserveScroll: true }) },

    // ── Initiatives ──
    emptyInitForm() { return this.$inertia.form({ id: null, title: '', description: '', category: 'general', status: 'planned', start_date: '', end_date: '', assigned_to: '', impact: 'medium' }) },
    openInitDialog(i = null) {
      if (i) { this.initForm = this.$inertia.form({ id: i.id, title: i.title, description: i.description || '', category: i.category, status: i.status, start_date: i.start_date || '', end_date: i.end_date || '', assigned_to: i.assigned_to || '', impact: i.impact }) }
      else { this.initForm = this.emptyInitForm() }
      this.initDialog = true
    },
    submitInit() {
      if (this.initForm.id) this.initForm.put(`/culture/initiatives/${this.initForm.id}`, { preserveScroll: true, onSuccess: () => { this.initDialog = false } })
      else this.initForm.post('/culture/initiatives', { preserveScroll: true, onSuccess: () => { this.initDialog = false } })
    },
    deleteInit(i) { if (confirm(`Xóa "${i.title}"?`)) this.$inertia.delete(`/culture/initiatives/${i.id}`, { preserveScroll: true }) },

    // ── Surveys ──
    emptySrvForm() { return this.$inertia.form({ id: null, title: '', description: '', questions: [], status: 'draft', anonymous: true }) },
    openSurveyDialog(s = null) {
      if (s) {
        this.srvForm = this.$inertia.form({ id: s.id, title: s.title, description: s.description || '', questions: s.questions, status: s.status, anonymous: s.anonymous })
        this.editSurveyQs = s.questions.map(q => ({ ...q, choicesText: (q.choices || []).join('\n') }))
      } else { this.srvForm = this.emptySrvForm(); this.editSurveyQs = [] }
      this.surveyDialog = true
    },
    submitSurvey() {
      this.srvForm.questions = this.editSurveyQs.map(q => {
        const out = { q: q.q, type: q.type }
        if (q.type === 'choice') out.choices = (q.choicesText || '').split('\n').map(s => s.trim()).filter(Boolean)
        return out
      })
      if (this.srvForm.id) this.srvForm.put(`/culture/surveys/${this.srvForm.id}`, { preserveScroll: true, onSuccess: () => { this.surveyDialog = false } })
      else this.srvForm.post('/culture/surveys', { preserveScroll: true, onSuccess: () => { this.surveyDialog = false } })
    },
    deleteSurvey(s) { if (confirm(`Xóa "${s.title}"?`)) this.$inertia.delete(`/culture/surveys/${s.id}`, { preserveScroll: true }) },

    // ── Take Survey ──
    openTakeSurvey(s) { this.activeSurvey = s; this.surveyAnswers = {}; this.takeSurveyDialog = true },
    submitSurveyResponse() {
      this.$inertia.post(`/culture/surveys/${this.activeSurvey.id}/submit`, { answers: this.surveyAnswers }, { preserveScroll: true, onSuccess: () => { this.takeSurveyDialog = false } })
    },

    // ── Results ──
    viewResults(s) {
      fetch(`/culture/surveys/${s.id}/results`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => { this.resultsData = data; this.resultsDialog = true })
    },

    // ── Helpers ──
    statusLabel(s) { return { planned: 'Kế hoạch', in_progress: 'Đang triển khai', completed: 'Hoàn thành', cancelled: 'Đã hủy' }[s] || s },
    categoryLabel(c) { return { general: 'Chung', learning: 'Học tập', onboarding: 'Onboarding', innovation: 'Đổi mới', recognition: 'Ghi nhận', team_building: 'Team building', wellness: 'Sức khỏe' }[c] || c },
    surveyStatusLabel(s) { return { draft: 'Nháp', active: 'Đang mở', closed: 'Đã đóng' }[s] || s },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#ec4899,#db2777); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(236,72,153,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; gap:.5rem }

/* ===== Stats ===== */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.25rem }
.stat-card { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; background:white; border-radius:14px; border:1.5px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:all .2s }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.06) }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.92rem }
.sc-val .stat-icon { background:#fce7f3; color:#db2777 }
.sc-init .stat-icon { background:#fef3c7; color:#d97706 }
.sc-srv .stat-icon { background:#dbeafe; color:#2563eb }
.sc-res .stat-icon { background:#f0fdf4; color:#16a34a }
.stat-body { display:flex; flex-direction:column }
.stat-value { font-size:1.15rem; font-weight:800; color:#0f172a }
.stat-label { font-size:.68rem; color:#94a3b8 }

/* ===== Tab Nav ===== */
.tab-nav { display:flex; gap:.35rem; margin-bottom:1.25rem; background:white; padding:.35rem; border-radius:12px; border:1px solid #f1f5f9 }
.tab-btn { padding:.5rem 1rem; border:none; background:transparent; border-radius:8px; font-size:.78rem; font-weight:600; color:#64748b; cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:.35rem; font-family:inherit }
.tab-btn:hover { color:#1e293b; background:#f8fafc }
.tab-btn i { font-size:.7rem }
.tab-active { background:linear-gradient(135deg,#ec4899,#db2777)!important; color:white!important; box-shadow:0 2px 8px rgba(236,72,153,.3) }
.tab-content { animation:fadeIn .2s ease }
@keyframes fadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:translateY(0)} }
.tab-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem }
.tab-title { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }

/* ===== Values Grid ===== */
.values-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:.75rem }
.value-card { background:white; border-radius:14px; padding:1.15rem; border:1.5px solid #f1f5f9; border-top:3px solid; transition:all .25s }
.value-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.06) }
.value-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.65rem }
.value-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem }
.value-actions { display:flex; gap:.125rem }
.value-title { font-size:.92rem; font-weight:700; color:#1e293b; margin:0 0 .25rem }
.value-desc { font-size:.75rem; color:#64748b; margin:0; line-height:1.5 }
.value-behaviors { margin-top:.65rem; padding:.65rem; background:#fafbfc; border-radius:8px }
.behavior-label { font-size:.62rem; font-weight:700; color:#ec4899; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; display:flex; align-items:center; gap:.25rem }
.behavior-label i { font-size:.55rem }
.value-behaviors ul { margin:0; padding-left:1.1rem; list-style:disc }
.value-behaviors li { font-size:.72rem; color:#475569; line-height:1.6 }

/* ===== Initiatives ===== */
.init-list { display:flex; flex-direction:column; gap:.5rem }
.init-card { display:flex; justify-content:space-between; align-items:flex-start; padding:.85rem 1rem; background:white; border-radius:12px; border:1.5px solid #f1f5f9; transition:all .2s; gap:.75rem }
.init-card:hover { border-color:#e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,.04) }
.init-left { flex:1; min-width:0 }
.init-status { font-size:.55rem; font-weight:700; padding:.12rem .45rem; border-radius:6px; text-transform:uppercase }
.is-planned { background:#eff6ff; color:#3b82f6 }
.is-in_progress { background:#fef3c7; color:#d97706 }
.is-completed { background:#d1fae5; color:#059669 }
.is-cancelled { background:#f1f5f9; color:#94a3b8 }
.init-title { font-size:.82rem; font-weight:700; color:#1e293b; margin:.3rem 0 .1rem }
.init-desc { font-size:.72rem; color:#64748b; margin:0 }
.init-meta { display:flex; gap:.65rem; margin-top:.35rem; flex-wrap:wrap }
.init-meta span { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.2rem }
.init-meta i { font-size:.55rem }
.init-cat { background:#f8fafc; padding:.1rem .35rem; border-radius:4px }
.init-impact { font-weight:700; text-transform:uppercase; letter-spacing:.04em }
.imp-high { color:#ef4444 }
.imp-medium { color:#f59e0b }
.imp-low { color:#94a3b8 }
.init-actions { display:flex; gap:.125rem; flex-shrink:0 }

/* ===== Surveys ===== */
.survey-list { display:flex; flex-direction:column; gap:.5rem }
.survey-card { display:flex; justify-content:space-between; align-items:flex-start; padding:.85rem 1rem; background:white; border-radius:12px; border:1.5px solid #f1f5f9; transition:all .2s; gap:.75rem }
.survey-card:hover { border-color:#e2e8f0 }
.survey-left { flex:1; min-width:0 }
.survey-status-row { display:flex; align-items:center; gap:.35rem }
.survey-status { font-size:.55rem; font-weight:700; padding:.12rem .45rem; border-radius:6px; text-transform:uppercase }
.ss-draft { background:#f1f5f9; color:#94a3b8 }
.ss-active { background:#d1fae5; color:#059669 }
.ss-closed { background:#fef2f2; color:#ef4444 }
.anon-badge { font-size:.52rem; font-weight:600; padding:.08rem .35rem; border-radius:4px; background:#f5f3ff; color:#8b5cf6; display:flex; align-items:center; gap:.15rem }
.anon-badge i { font-size:.48rem }
.survey-title { font-size:.82rem; font-weight:700; color:#1e293b; margin:.3rem 0 .1rem }
.survey-desc { font-size:.72rem; color:#64748b; margin:0 }
.survey-meta { display:flex; gap:.65rem; margin-top:.3rem }
.survey-meta span { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.2rem }
.survey-meta i { font-size:.55rem }
.survey-actions { display:flex; align-items:center; gap:.25rem; flex-shrink:0 }

/* ===== Take Survey ===== */
.survey-intro { font-size:.82rem; color:#64748b; margin:0 0 1rem; padding:.75rem; background:#fafbfc; border-radius:8px }
.take-q { margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid #f1f5f9 }
.take-q:last-child { border-bottom:none }
.take-q-label { font-size:.82rem; font-weight:600; color:#1e293b; margin-bottom:.5rem }
.rating-row { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center }
.rating-btn { width:42px; height:42px; border-radius:10px; border:2px solid #e2e8f0; background:white; font-size:.85rem; font-weight:700; color:#64748b; cursor:pointer; transition:all .2s }
.rating-btn:hover { border-color:#ec4899; color:#ec4899 }
.rating-active { border-color:#ec4899!important; background:#fce7f3!important; color:#db2777!important }
.rating-labels { display:flex; justify-content:space-between; width:100%; font-size:.55rem; color:#94a3b8; margin-top:.15rem }
.choice-list { display:flex; flex-direction:column; gap:.3rem }
.choice-opt { display:flex; align-items:center; gap:.5rem; padding:.45rem .65rem; border:1.5px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:.82rem; color:#334155; transition:all .2s }
.choice-opt:hover { border-color:#ec4899; background:#fdf2f8 }
.choice-opt input { display:none }
.choice-selected { border-color:#ec4899; background:#fce7f3; color:#db2777; font-weight:600 }

/* ===== Results ===== */
.results-summary { margin-bottom:1rem; padding:.5rem .75rem; background:#f8fafc; border-radius:8px }
.results-count { font-size:.78rem; font-weight:600; color:#475569; display:flex; align-items:center; gap:.3rem }
.results-count i { font-size:.7rem }
.result-q { margin-bottom:1rem; padding:.85rem; background:#fafbfc; border-radius:10px }
.result-q-header { margin-bottom:.2rem }
.result-q-num { font-size:.58rem; font-weight:700; color:#ec4899; text-transform:uppercase }
.result-q-text { font-size:.82rem; font-weight:600; color:#1e293b; margin:.15rem 0 .5rem }
.result-rating { display:flex; align-items:center; gap:.75rem }
.result-avg-wrap { display:flex; align-items:baseline; gap:.15rem }
.result-avg { font-size:1.5rem; font-weight:800; color:#1e293b }
.result-avg-label { font-size:.75rem; color:#94a3b8 }
.result-bar-track { flex:1; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden }
.result-bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#ec4899,#db2777); transition:width .4s }
.result-texts { display:flex; flex-direction:column; gap:.3rem }
.result-text-item { font-size:.75rem; color:#475569; padding:.4rem .65rem; background:white; border-radius:6px; border:1px solid #f1f5f9 }

/* ===== Dialogs ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:620px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-wide { width:780px }
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.di-pink { background:linear-gradient(135deg,#ec4899,#db2777) }
.di-amber { background:linear-gradient(135deg,#f59e0b,#d97706) }
.di-blue { background:linear-gradient(135deg,#3b82f6,#2563eb) }
.di-green { background:linear-gradient(135deg,#10b981,#059669) }
.di-purple { background:linear-gradient(135deg,#8b5cf6,#7c3aed) }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0 }

/* ===== Forms ===== */
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.req { color:#ef4444 }
.form-control { width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:8px; font-size:.85rem; color:#1e293b; background:white; transition:all .2s; outline:none; font-family:inherit; resize:vertical }
.form-control:focus { border-color:#ec4899; box-shadow:0 0 0 3px rgba(236,72,153,.1) }
.toggle-row { display:flex; justify-content:space-between; align-items:center }
.toggle-label { font-size:.82rem; font-weight:600; color:#1e293b }
.toggle-desc { display:block; font-size:.62rem; color:#94a3b8 }

/* ===== Questions Editor ===== */
.questions-editor { margin-top:.5rem }
.edit-q { padding:.85rem; background:#fafbfc; border-radius:10px; border:1px solid #f1f5f9; margin-bottom:.65rem }
.edit-q-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem }
.edit-q-num { font-size:.65rem; font-weight:700; color:#ec4899; text-transform:uppercase }
.edit-q-right { display:flex; align-items:center; gap:.3rem }
.q-type-select { width:130px; font-size:.75rem }

/* ===== Empty ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#fce7f3,#fbcfe8); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#db2777 }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }

/* ===== Responsive ===== */
@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .stats-grid { grid-template-columns:repeat(2,1fr) }
  .tab-nav { flex-wrap:wrap }
  .values-grid { grid-template-columns:1fr }
  .form-row { flex-direction:column }
  .dialog-overlay { padding:.75rem }
  .init-card, .survey-card { flex-direction:column }
  .rating-btn { width:36px; height:36px }
}
</style>
