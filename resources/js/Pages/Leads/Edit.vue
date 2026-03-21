<template>
  <div>
    <Head :title="lead.name" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <div class="breadcrumb-row">
          <Link href="/leads" class="breadcrumb-link">{{ t('common.leads') }}</Link>
          <i class="pi pi-angle-right breadcrumb-sep" />
          <span class="breadcrumb-current">{{ lead.name }}</span>
        </div>
        <h1 class="page-title">{{ lead.name }}</h1>
        <div class="header-badges">
          <span v-if="lead.status" class="status-badge" :class="`status-${lead.status}`">
            <i class="status-dot" />
            {{ statuses[lead.status] || lead.status }}
          </span>
          <span v-if="lead.score !== null && lead.score !== undefined" class="score-badge" :class="getScoreSeverityClass(lead.score)">
            <i class="pi pi-star" /> {{ lead.score }}/100
          </span>
          <span v-if="lead.priority_label" class="priority-badge" :class="`priority-${(lead.priority_label || '').toLowerCase()}`">
            {{ lead.priority_label }}
          </span>
        </div>
      </div>
      <div class="header-actions">
        <Button
          v-if="!lead.deleted_at"
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          text
          size="small"
          @click="destroy"
        />
      </div>
    </div>

    <!-- Trashed Banner -->
    <div v-if="lead.deleted_at" class="alert alert-warning">
      <i class="pi pi-exclamation-triangle" />
      <span>Lead này đã bị xóa.</span>
      <Button label="Khôi phục" size="small" severity="warning" text @click="restore" />
    </div>

    <div class="edit-layout">
      <!-- Main Form -->
      <div class="edit-main">
        <!-- Duplicate Warning -->
        <div v-if="form.errors.duplicate" class="alert alert-error">
          <i class="pi pi-exclamation-triangle" />
          <div class="alert-content">
            <span>{{ form.errors.duplicate }}</span>
            <Link v-if="form.errors.duplicate_id" :href="`/leads/${form.errors.duplicate_id}/edit`" class="alert-link">
              Xem lead hiện có →
            </Link>
          </div>
        </div>

        <form @submit.prevent="update">
          <!-- Contact Info -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-user" />
              <h2 class="card-title">Thông tin liên hệ</h2>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.name') }} <span class="required">*</span></label>
                  <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
                  <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>
                <div class="form-group">
                  <label>{{ t('common.company') }}</label>
                  <InputText v-model="form.company" :class="{ 'p-invalid': form.errors.company }" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.phone') }}</label>
                  <InputText v-model="form.phone" :class="{ 'p-invalid': form.errors.phone }" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.email') }}</label>
                  <InputText v-model="form.email" type="email" :class="{ 'p-invalid': form.errors.email }" />
                </div>
              </div>
            </div>
          </div>

          <!-- Classification -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-tag" />
              <h2 class="card-title">Phân loại</h2>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>{{ t('common.source') }}</label>
                  <Select v-model="form.source" :options="sourceOptions" optionLabel="label" optionValue="value" placeholder="Chọn nguồn" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.status') }} <span class="required">*</span></label>
                  <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.assigned_to') }}</label>
                  <Select v-model="form.assigned_to" :options="assignedOptions" optionLabel="label" optionValue="value" placeholder="Chưa phân công" />
                </div>
                <div class="form-group">
                  <label>{{ t('common.tags') }}</label>
                  <InputText v-model="tagsInput" placeholder="hot, vip, follow-up" />
                  <small class="hint">Phân cách bằng dấu phẩy</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-pencil" />
              <h2 class="card-title">Ghi chú</h2>
            </div>
            <div class="card-body">
              <Textarea v-model="form.notes" rows="5" :class="{ 'p-invalid': form.errors.notes }" placeholder="Ghi chú về lead..." />
            </div>
          </div>

          <!-- Scoring & Intelligence -->
          <div class="form-card">
            <div class="card-header">
              <i class="pi pi-chart-bar" />
              <h2 class="card-title">Lead Scoring & Intelligence</h2>
              <div class="card-header-actions">
                <Button label="Enrich" icon="pi pi-search" size="small" severity="secondary" text :loading="enriching" @click="enrichLead" />
                <Button label="Score" icon="pi pi-star" size="small" severity="secondary" text @click="scoreLead" />
              </div>
            </div>
            <div class="card-body">
              <!-- Score summary -->
              <div v-if="lead.score !== null && lead.score !== undefined" class="score-summary">
                <div class="score-circle" :class="getScoreSeverityClass(lead.score)">
                  <span class="score-number">{{ lead.score }}</span>
                  <span class="score-total">/100</span>
                </div>
                <div class="score-details">
                  <div class="score-detail-row">
                    <span>Ưu tiên:</span>
                    <span class="priority-tag" :class="`priority-${(lead.priority_label || '').toLowerCase()}`">{{ lead.priority_label || 'Cold' }}</span>
                  </div>
                  <div v-if="lead.icp" class="score-detail-row">
                    <span>ICP:</span>
                    <span class="icp-name">{{ lead.icp.name }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="no-score">
                <i class="pi pi-info-circle" />
                <span>Chưa được chấm điểm. Nhấn "Score" để bắt đầu.</span>
              </div>

              <!-- Scoring Breakdown -->
              <div v-if="lead.scoring_details && lead.scoring_details.details" class="scoring-breakdown">
                <h4 class="breakdown-title">Phân tích chi tiết</h4>
                <div v-for="(detail, key) in lead.scoring_details.details" :key="key" class="breakdown-item">
                  <div class="breakdown-header">
                    <span class="breakdown-label">{{ key.replace('_', ' ') }}</span>
                    <span class="breakdown-value">{{ detail.score }}/100 <span class="breakdown-weight">({{ detail.weight }}%)</span></span>
                  </div>
                  <div class="breakdown-bar-track">
                    <div class="breakdown-bar-fill" :style="{ width: `${detail.score}%` }" />
                  </div>
                  <p v-if="detail.explanation" class="breakdown-explain">{{ detail.explanation }}</p>
                </div>

                <!-- Formula -->
                <div v-if="lead.scoring_details.formula" class="formula-box">
                  <span class="formula-label">Công thức:</span>
                  <code class="formula-code">{{ lead.scoring_details.formula }}</code>
                </div>

                <!-- Suggested Action -->
                <div v-if="lead.scoring_details.suggested_action" class="suggested-action">
                  <i :class="lead.scoring_details.suggested_action.icon || 'pi pi-lightbulb'" />
                  <div>
                    <strong>{{ lead.scoring_details.suggested_action.label }}</strong>
                    <p>{{ lead.scoring_details.suggested_action.description }}</p>
                  </div>
                </div>
              </div>

              <!-- Engagement -->
              <div v-if="lead.email_opens > 0 || lead.website_visits > 0" class="engagement-grid">
                <h4 class="breakdown-title">Tương tác</h4>
                <div class="engagement-stats">
                  <div class="engage-item">
                    <i class="pi pi-envelope" />
                    <span class="engage-value">{{ lead.email_opens || 0 }}</span>
                    <span class="engage-label">Mở email</span>
                  </div>
                  <div class="engage-item">
                    <i class="pi pi-external-link" />
                    <span class="engage-value">{{ lead.email_clicks || 0 }}</span>
                    <span class="engage-label">Click email</span>
                  </div>
                  <div class="engage-item">
                    <i class="pi pi-globe" />
                    <span class="engage-value">{{ lead.website_visits || 0 }}</span>
                    <span class="engage-label">Website</span>
                  </div>
                  <div class="engage-item">
                    <i class="pi pi-file" />
                    <span class="engage-value">{{ lead.page_views || 0 }}</span>
                    <span class="engage-label">Trang xem</span>
                  </div>
                </div>
              </div>

              <!-- Enriched Data -->
              <div v-if="lead.enrichment_data" class="enrichment-data">
                <h4 class="breakdown-title">Dữ liệu bổ sung</h4>
                <div class="enrich-grid">
                  <div v-if="lead.enrichment_data.industry" class="enrich-item">
                    <span class="enrich-label">Ngành:</span>
                    <span class="enrich-value">{{ lead.enrichment_data.industry }}</span>
                  </div>
                  <div v-if="lead.enrichment_data.employees" class="enrich-item">
                    <span class="enrich-label">Nhân viên:</span>
                    <span class="enrich-value">{{ lead.enrichment_data.employees }}</span>
                  </div>
                  <div v-if="lead.enrichment_data.location" class="enrich-item">
                    <span class="enrich-label">Vị trí:</span>
                    <span class="enrich-value">{{ lead.enrichment_data.location }}</span>
                  </div>
                  <div v-if="lead.enrichment_data.job_title" class="enrich-item">
                    <span class="enrich-label">Chức danh:</span>
                    <span class="enrich-value">{{ lead.enrichment_data.job_title }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SLA Tracking -->
          <div v-if="lead.sla_setting" class="form-card sla-card">
            <div class="card-header">
              <i class="pi pi-clock" />
              <h2 class="card-title">SLA Response Time</h2>
              <span class="sla-status-tag" :class="`sla-${lead.sla_status}`">{{ getSLAStatusLabel(lead.sla_status) }}</span>
            </div>
            <div class="card-body">
              <div v-if="lead.sla_started_at && !lead.first_response_at" class="sla-progress-section">
                <div class="sla-meta">
                  <span>Ngưỡng: <b>{{ lead.sla_setting.first_response_threshold }} phút</b></span>
                  <span>Đã qua: <b>{{ getElapsedMinutes(lead.sla_started_at) }} phút</b></span>
                </div>
                <div class="sla-bar-track">
                  <div class="sla-bar-fill" :class="`sla-fill-${lead.sla_status}`" :style="{ width: `${getSLAProgress(lead)}%` }" />
                </div>
                <p v-if="lead.sla_status === 'breached'" class="sla-warning breached">
                  <i class="pi pi-exclamation-triangle" /> SLA đã vi phạm! Cần hành động ngay.
                </p>
                <p v-else-if="lead.sla_status === 'warning'" class="sla-warning warn">
                  <i class="pi pi-exclamation-circle" /> Sắp đến ngưỡng SLA.
                </p>
              </div>
              <div v-if="lead.first_response_at" class="sla-resolved">
                <i class="pi pi-check-circle" />
                <span>Phản hồi đầu tiên: {{ formatDateTime(lead.first_response_at) }}</span>
              </div>
              <div v-if="lead.response_time_minutes" class="sla-meta">
                Thời gian phản hồi: <b>{{ lead.response_time_minutes }} phút</b>
              </div>
            </div>
          </div>

          <!-- Convert / Deal Action -->
          <div class="form-card action-card" :class="lead.deal ? 'action-done' : 'action-ready'">
            <div class="card-body">
              <div v-if="!lead.deal" class="action-content">
                <div>
                  <h3 class="action-title"><i class="pi pi-arrow-right" /> Sẵn sàng chuyển đổi?</h3>
                  <p class="action-desc">Chuyển lead thành deal để bắt đầu theo dõi trong pipeline</p>
                </div>
                <Button label="Chuyển thành Deal" icon="pi pi-arrow-right" severity="success" @click="convertToDeal" />
              </div>
              <div v-else class="action-content">
                <div>
                  <h3 class="action-title"><i class="pi pi-check-circle" /> Đã chuyển đổi</h3>
                  <Link :href="`/deals/${lead.deal.id}/edit`" class="action-link">Xem Deal →</Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <Link href="/leads"><Button label="Hủy" severity="secondary" text /></Link>
            <Button label="Cập nhật Lead" icon="pi pi-check" :loading="form.processing" type="submit" />
          </div>
        </form>
      </div>

      <!-- Sidebar -->
      <div class="edit-sidebar">
        <!-- Add Note -->
        <div class="sidebar-card">
          <h3 class="sidebar-title"><i class="pi pi-comment" /> Thêm ghi chú</h3>
          <form @submit.prevent="addNote">
            <Textarea v-model="noteForm.note" rows="3" placeholder="Viết ghi chú..." class="w-full" :class="{ 'p-invalid': noteForm.errors.note }" />
            <small v-if="noteForm.errors.note" class="p-error">{{ noteForm.errors.note }}</small>
            <Button label="Thêm" icon="pi pi-plus" :loading="noteForm.processing" type="submit" size="small" class="w-full note-btn" />
          </form>
        </div>

        <!-- Notes History -->
        <div v-if="formattedNotes.length > 0" class="sidebar-card">
          <h3 class="sidebar-title"><i class="pi pi-history" /> Lịch sử ghi chú</h3>
          <div class="notes-list">
            <div v-for="(note, index) in formattedNotes" :key="index" class="note-item">
              <div v-if="note.date" class="note-date">{{ note.date }}</div>
              <div class="note-text">{{ note.text }}</div>
            </div>
          </div>
        </div>

        <!-- Playbook Suggestions -->
        <PlaybookSuggestions
          v-if="playbookSuggestions && playbookSuggestions.length > 0"
          :playbooks="playbookSuggestions"
          subject-type="lead"
          :subject-id="lead.id"
        />

        <!-- Activity Timeline -->
        <div class="sidebar-card">
          <ActivityTimeline
            :activities="activities"
            subject-type="App\Models\Lead"
            :subject-id="lead.id"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import ActivityTimeline from '@/Shared/ActivityTimeline.vue'
import PlaybookSuggestions from '@/Shared/PlaybookSuggestions.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, Textarea, Select, Button, ActivityTimeline, PlaybookSuggestions },
  layout: Layout,
  props: {
    lead: Object,
    activities: Array,
    statuses: Object,
    sources: Object,
    salesUsers: Array,
    playbookSuggestions: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  remember: 'form',
  data() {
    return {
      enriching: false,
      tagsInput: this.lead.tags ? this.lead.tags.join(', ') : '',
      form: this.$inertia.form({
        name: this.lead.name,
        phone: this.lead.phone,
        email: this.lead.email,
        company: this.lead.company,
        source: this.lead.source,
        status: this.lead.status,
        assigned_to: this.lead.assigned_to,
        notes: this.lead.notes,
        tags: this.lead.tags || [],
      }),
      noteForm: this.$inertia.form({
        note: '',
      }),
    }
  },
  computed: {
    statusOptions() {
      return Object.entries(this.statuses).map(([value, label]) => ({ label, value }))
    },
    sourceOptions() {
      return [{ label: 'Chọn nguồn', value: null }, ...Object.entries(this.sources).map(([value, label]) => ({ label, value }))]
    },
    assignedOptions() {
      return [{ label: 'Chưa phân công', value: null }, ...this.salesUsers.map(user => ({ label: user.name, value: user.id }))]
    },
    formattedNotes() {
      if (!this.lead.notes) return []
      const notes = this.lead.notes.split('\n\n').filter(n => n.trim())
      return notes.map(note => {
        const match = note.match(/^\[(.+?)\]\s*(.+)$/s)
        if (match) return { date: match[1], text: match[2].trim() }
        return { date: '', text: note.trim() }
      }).reverse()
    },
  },
  watch: {
    tagsInput(newVal) {
      this.form.tags = newVal.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0)
    },
  },
  methods: {
    update() {
      this.form.put(`/leads/${this.lead.id}`)
    },
    destroy() {
      if (confirm('Bạn có chắc muốn xóa lead này?')) {
        this.$inertia.delete(`/leads/${this.lead.id}`)
      }
    },
    restore() {
      if (confirm('Bạn có muốn khôi phục lead này?')) {
        this.$inertia.put(`/leads/${this.lead.id}/restore`)
      }
    },
    addNote() {
      this.noteForm.post(`/leads/${this.lead.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => {
          this.noteForm.reset()
          this.$inertia.reload({ only: ['lead'] })
        },
      })
    },
    convertToDeal() {
      this.$inertia.post(`/leads/${this.lead.id}/convert`, {}, { preserveScroll: true })
    },
    async enrichLead() {
      this.enriching = true
      try {
        const response = await fetch(`/leads/${this.lead.id}/enrich`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
          },
        })
        const data = await response.json()
        if (data.success) this.$inertia.reload({ only: ['lead'] })
      } catch (error) {
        console.error('Enrichment failed:', error)
      } finally {
        this.enriching = false
      }
    },
    scoreLead() {
      this.$inertia.post(`/leads/${this.lead.id}/score`, {}, { preserveScroll: true })
    },
    getScoreSeverityClass(score) {
      if (score >= 80) return 'severity-success'
      if (score >= 60) return 'severity-warning'
      if (score >= 40) return 'severity-info'
      return 'severity-danger'
    },
    getSLAStatusLabel(status) {
      return { pending: 'Chờ', on_time: 'Đúng hạn', warning: 'Cảnh báo', breached: 'Vi phạm', resolved: 'Đã xử lý' }[status] || status
    },
    getElapsedMinutes(startedAt) {
      if (!startedAt) return 0
      return Math.floor((new Date() - new Date(startedAt)) / 60000)
    },
    getSLAProgress(lead) {
      if (!lead.sla_setting || !lead.sla_started_at || lead.first_response_at) return 0
      return Math.min(100, (this.getElapsedMinutes(lead.sla_started_at) / lead.sla_setting.first_response_threshold) * 100)
    },
    formatDateTime(d) {
      if (!d) return ''
      return new Date(d).toLocaleString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }

.breadcrumb-row { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; }
.breadcrumb-link { font-size: 0.75rem; color: #6366f1; text-decoration: none; font-weight: 500; transition: color 0.15s; }
.breadcrumb-link:hover { color: #4f46e5; }
.breadcrumb-sep { font-size: 0.6rem; color: #cbd5e1; }
.breadcrumb-current { font-size: 0.75rem; color: #94a3b8; }

.header-badges { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.4rem; }
.status-badge {
  display: inline-flex; align-items: center; gap: 0.3rem;
  font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.55rem; border-radius: 20px;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-new { background: #eff6ff; color: #3b82f6; }
.status-new .status-dot { background: #3b82f6; }
.status-contacted { background: #fef3c7; color: #d97706; }
.status-contacted .status-dot { background: #d97706; }
.status-qualified { background: #d1fae5; color: #059669; }
.status-qualified .status-dot { background: #059669; }
.status-won { background: #d1fae5; color: #059669; }
.status-won .status-dot { background: #059669; }
.status-lost { background: #fee2e2; color: #dc2626; }
.status-lost .status-dot { background: #dc2626; }

.score-badge { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 6px; display: flex; align-items: center; gap: 0.2rem; }
.score-badge i { font-size: 0.55rem; }
.severity-success { background: #d1fae5; color: #059669; }
.severity-warning { background: #fef3c7; color: #d97706; }
.severity-info { background: #dbeafe; color: #2563eb; }
.severity-danger { background: #fee2e2; color: #dc2626; }

.priority-badge { font-size: 0.6rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
.priority-hot  { background: #fef2f2; color: #ef4444; }
.priority-warm { background: #fffbeb; color: #f59e0b; }
.priority-cold { background: #eff6ff; color: #3b82f6; }

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ===== Alerts ===== */
.alert {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem;
  font-size: 0.82rem;
}
.alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #d97706; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-content { display: flex; flex-direction: column; gap: 0.2rem; }
.alert-link { font-size: 0.75rem; color: #6366f1; text-decoration: underline; }

/* ===== Layout ===== */
.edit-layout { display: flex; gap: 1.25rem; align-items: flex-start; }
.edit-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1rem; }
.edit-main form { display: flex; flex-direction: column; gap: 1rem; }

/* ===== Form Card ===== */
.form-card {
  background: white; border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9; overflow: hidden;
}
.card-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.85rem 1.25rem;
  background: #fafbfc; border-bottom: 1px solid #f1f5f9;
}
.card-header i { font-size: 0.85rem; color: #6366f1; }
.card-title { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
.card-header-actions { display: flex; gap: 0.25rem; }
.card-body { padding: 1.25rem; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-size: 0.78rem; font-weight: 500; color: #475569; margin-bottom: 0.35rem; }
.required { color: #ef4444; }
.hint { font-size: 0.7rem; color: #94a3b8; margin-top: 0.2rem; }
.form-group :deep(.p-inputtext), .form-group :deep(.p-select), .form-group :deep(.p-textarea) { width: 100%; }

/* ===== Score Summary ===== */
.score-summary { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1rem; }
.score-circle {
  width: 64px; height: 64px; border-radius: 50%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  border: 3px solid; flex-shrink: 0;
}
.score-circle.severity-success { border-color: #10b981; background: #ecfdf5; }
.score-circle.severity-warning { border-color: #f59e0b; background: #fffbeb; }
.score-circle.severity-info { border-color: #3b82f6; background: #eff6ff; }
.score-circle.severity-danger { border-color: #ef4444; background: #fef2f2; }
.score-number { font-size: 1.25rem; font-weight: 700; line-height: 1; }
.score-total { font-size: 0.6rem; color: #94a3b8; }
.score-details { display: flex; flex-direction: column; gap: 0.35rem; }
.score-detail-row { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: #64748b; }
.priority-tag { font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: uppercase; }
.icp-name { font-weight: 500; color: #475569; }
.no-score { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; color: #94a3b8; padding: 0.5rem 0; }
.no-score i { font-size: 0.85rem; }

/* ===== Scoring Breakdown ===== */
.scoring-breakdown { border-top: 1px solid #f1f5f9; padding-top: 1rem; }
.breakdown-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin: 0 0 0.75rem; }
.breakdown-item { margin-bottom: 0.75rem; }
.breakdown-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem; }
.breakdown-label { font-size: 0.78rem; color: #475569; font-weight: 500; text-transform: capitalize; }
.breakdown-value { font-size: 0.72rem; font-weight: 600; color: #1e293b; }
.breakdown-weight { color: #94a3b8; font-weight: 400; }
.breakdown-bar-track { width: 100%; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.breakdown-bar-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.3s; }
.breakdown-explain { font-size: 0.7rem; color: #94a3b8; margin: 0.2rem 0 0; }

.formula-box {
  padding: 0.65rem; background: #f8fafc; border-radius: 8px;
  border: 1px solid #e2e8f0; margin-top: 0.75rem;
}
.formula-label { font-size: 0.7rem; font-weight: 600; color: #475569; }
.formula-code { font-size: 0.7rem; color: #64748b; font-family: 'Courier New', monospace; display: block; margin-top: 0.2rem; }

.suggested-action {
  display: flex; align-items: flex-start; gap: 0.5rem;
  padding: 0.75rem; background: #eef2ff; border-radius: 8px;
  margin-top: 0.75rem; border: 1px solid #c7d2fe;
}
.suggested-action i { color: #6366f1; font-size: 1rem; margin-top: 0.1rem; }
.suggested-action strong { font-size: 0.78rem; color: #1e293b; display: block; }
.suggested-action p { font-size: 0.72rem; color: #64748b; margin: 0.15rem 0 0; }

/* ===== Engagement ===== */
.engagement-grid { border-top: 1px solid #f1f5f9; padding-top: 1rem; margin-top: 1rem; }
.engagement-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
.engage-item {
  display: flex; flex-direction: column; align-items: center; gap: 0.15rem;
  padding: 0.6rem; background: #f8fafc; border-radius: 8px;
}
.engage-item i { font-size: 0.85rem; color: #6366f1; }
.engage-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.engage-label { font-size: 0.6rem; color: #94a3b8; text-align: center; }

/* ===== Enrichment ===== */
.enrichment-data { border-top: 1px solid #f1f5f9; padding-top: 1rem; margin-top: 1rem; }
.enrich-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
.enrich-item { font-size: 0.78rem; }
.enrich-label { color: #94a3b8; }
.enrich-value { color: #1e293b; font-weight: 500; margin-left: 0.25rem; }

/* ===== SLA ===== */
.sla-card .card-header { gap: 0.5rem; }
.sla-status-tag {
  font-size: 0.6rem; font-weight: 700; padding: 0.15rem 0.4rem;
  border-radius: 4px; text-transform: uppercase; letter-spacing: 0.04em;
}
.sla-pending { background: #dbeafe; color: #2563eb; }
.sla-on_time { background: #d1fae5; color: #059669; }
.sla-warning { background: #fef3c7; color: #d97706; }
.sla-breached { background: #fee2e2; color: #dc2626; }
.sla-resolved { background: #d1fae5; color: #059669; }

.sla-progress-section { margin-bottom: 0.5rem; }
.sla-meta { font-size: 0.78rem; color: #64748b; display: flex; gap: 1rem; margin-bottom: 0.5rem; }
.sla-meta b { color: #1e293b; }
.sla-bar-track { width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.sla-bar-fill { height: 100%; border-radius: 3px; transition: all 0.3s; }
.sla-fill-on_time { background: #10b981; }
.sla-fill-warning { background: #f59e0b; }
.sla-fill-breached { background: #ef4444; }
.sla-fill-pending { background: #3b82f6; }
.sla-warning { font-size: 0.75rem; font-weight: 600; margin: 0.4rem 0 0; display: flex; align-items: center; gap: 0.3rem; }
.sla-warning.breached { color: #dc2626; }
.sla-warning.warn { color: #d97706; }
.sla-resolved { display: flex; align-items: center; gap: 0.35rem; font-size: 0.82rem; color: #059669; font-weight: 500; }
.sla-resolved i { font-size: 0.85rem; }

/* ===== Action Card ===== */
.action-card.action-ready { border-color: #bbf7d0; }
.action-card.action-ready .card-body { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); }
.action-card.action-done { border-color: #bfdbfe; }
.action-card.action-done .card-body { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
.action-content { display: flex; align-items: center; justify-content: space-between; }
.action-title { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0 0 0.2rem; display: flex; align-items: center; gap: 0.3rem; }
.action-title i { font-size: 0.82rem; }
.action-desc { font-size: 0.75rem; color: #64748b; margin: 0; }
.action-link { font-size: 0.78rem; color: #6366f1; text-decoration: none; font-weight: 500; }
.action-link:hover { color: #4f46e5; text-decoration: underline; }

/* ===== Form Actions ===== */
.form-actions {
  display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;
  padding-top: 0.75rem; border-top: 1px solid #f1f5f9;
}

/* ===== Sidebar ===== */
.edit-sidebar {
  width: 320px; flex-shrink: 0;
  position: sticky; top: 80px;
  display: flex; flex-direction: column; gap: 0.75rem;
}
.sidebar-card {
  background: white; border-radius: 12px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}
.sidebar-title {
  font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem;
  display: flex; align-items: center; gap: 0.4rem;
}
.sidebar-title i { color: #6366f1; font-size: 0.82rem; }
.w-full { width: 100%; }
.note-btn { margin-top: 0.5rem; }

.notes-list { display: flex; flex-direction: column; gap: 0.5rem; max-height: 350px; overflow-y: auto; }
.note-item { padding: 0.65rem; background: #f8fafc; border-radius: 8px; }
.note-date { font-size: 0.65rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; }
.note-text { font-size: 0.8rem; color: #334155; white-space: pre-wrap; line-height: 1.5; }

@media (max-width: 768px) {
  .edit-layout { flex-direction: column; }
  .edit-sidebar { width: 100%; position: static; }
  .form-grid { grid-template-columns: 1fr; }
  .engagement-stats { grid-template-columns: repeat(2, 1fr); }
  .action-content { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
}
</style>
