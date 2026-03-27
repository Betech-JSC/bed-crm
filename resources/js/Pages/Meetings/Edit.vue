<template>
  <div>
    <Head :title="`Chỉnh sửa: ${meeting.title}`" />

    <div class="page-header">
      <div class="header-left">
        <Link href="/meetings"><button class="btn-back"><i class="pi pi-arrow-left" /></button></Link>
        <div>
          <h1 class="page-title"><i class="pi pi-video" style="color:#6366f1;" /> Chỉnh sửa cuộc họp</h1>
          <p class="page-subtitle">{{ meeting.room_code }} · Tạo {{ meeting.created_at }}</p>
        </div>
      </div>
      <div class="header-right">
        <Link v-if="meeting.status === 'scheduled' || meeting.status === 'live'" :href="`/meetings/${meeting.room_code}/room`">
          <button class="btn-join"><i class="pi pi-sign-in" /> Vào phòng</button>
        </Link>
        <Link v-if="meeting.has_recap" :href="`/meetings/${meeting.id}/recap`">
          <button class="btn-recap"><i class="pi pi-sparkles" /> Recap</button>
        </Link>
      </div>
    </div>

    <!-- Status & Info Band -->
    <div class="info-band">
      <div class="info-item">
        <span class="info-label">Trạng thái</span>
        <span class="status-badge" :class="`s-${meeting.status}`">
          <i :class="statusInfo.icon" /> {{ statusInfo.label }}
        </span>
      </div>
      <div class="info-item">
        <span class="info-label">Room Code</span>
        <span class="info-value room-code"><i class="pi pi-hashtag" /> {{ meeting.room_code }}</span>
      </div>
      <div v-if="meeting.started_at" class="info-item">
        <span class="info-label">Bắt đầu</span>
        <span class="info-value">{{ meeting.started_at }}</span>
      </div>
      <div v-if="meeting.ended_at" class="info-item">
        <span class="info-label">Kết thúc</span>
        <span class="info-value">{{ meeting.ended_at }}</span>
      </div>
      <div v-if="meeting.duration_formatted && meeting.duration_formatted !== '—'" class="info-item">
        <span class="info-label">Thời lượng</span>
        <span class="info-value">{{ meeting.duration_formatted }}</span>
      </div>
      <div class="info-item">
        <span class="info-label">Người tham gia</span>
        <span class="info-value">{{ (meeting.participants || []).length }}/{{ meeting.max_participants }}</span>
      </div>
    </div>

    <div class="edit-layout">
      <!-- Form Panel -->
      <div class="form-panel">
        <div class="form-card">
          <form @submit.prevent="submitUpdate">
            <div class="form-group">
              <label>Tên cuộc họp <span class="required">*</span></label>
              <input v-model="form.title" type="text" class="form-input" required />
            </div>

            <div class="form-group">
              <label>Mô tả</label>
              <textarea v-model="form.description" rows="2" class="form-input" />
            </div>

            <div class="form-group">
              <label>Loại cuộc họp</label>
              <div class="type-grid">
                <button v-for="(info, key) in types" :key="key" type="button" class="type-card" :class="{ active: form.type === key }" @click="form.type = key">
                  <i :class="info.icon" />
                  <span>{{ info.label }}</span>
                </button>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Thời gian bắt đầu</label>
                <input v-model="form.scheduled_at" type="datetime-local" class="form-input" />
              </div>
              <div class="form-group">
                <label>Max người tham gia</label>
                <input v-model.number="form.max_participants" type="number" min="2" max="100" class="form-input" />
              </div>
            </div>

            <div class="form-group">
              <label><i class="pi pi-list" /> Agenda</label>
              <textarea v-model="form.agenda" rows="4" class="form-input" placeholder="- Điểm 1&#10;- Điểm 2" />
            </div>

            <!-- Options -->
            <div class="options-section">
              <h4 class="options-title"><i class="pi pi-cog" /> Cài đặt</h4>
              <div class="options-grid">
                <label class="opt-toggle">
                  <input type="checkbox" v-model="form.record_enabled" />
                  <span class="toggle-slider" />
                  <div class="opt-info"><strong>Ghi hình</strong><span>Tự động ghi lại</span></div>
                </label>
                <label class="opt-toggle">
                  <input type="checkbox" v-model="form.is_public" />
                  <span class="toggle-slider" />
                  <div class="opt-info"><strong>Công khai</strong><span>Ai có link đều join</span></div>
                </label>
                <label class="opt-toggle">
                  <input type="checkbox" v-model="form.settings.mute_on_join" />
                  <span class="toggle-slider" />
                  <div class="opt-info"><strong>Tắt mic khi vào</strong><span>Mute lúc join</span></div>
                </label>
                <label class="opt-toggle">
                  <input type="checkbox" v-model="form.settings.waiting_room" />
                  <span class="toggle-slider" />
                  <div class="opt-info"><strong>Phòng chờ</strong><span>Approve mới vào</span></div>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label>Mật khẩu phòng</label>
              <input v-model="form.password" type="text" class="form-input" placeholder="Để trống = không cần" />
            </div>

            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="$inertia.visit('/meetings')">Hủy</button>
              <button type="submit" class="btn-save" :disabled="isSubmitting">
                <i :class="isSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-save'" />
                Lưu thay đổi
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Side Panel -->
      <div class="side-panel">
        <!-- Quick Actions -->
        <div class="side-card">
          <h4><i class="pi pi-bolt" /> Hành động nhanh</h4>
          <div class="quick-actions">
            <Link v-if="meeting.status === 'scheduled'" :href="`/meetings/${meeting.room_code}/room`">
              <button class="qa-btn qa-join"><i class="pi pi-play" /> Bắt đầu họp</button>
            </Link>
            <button class="qa-btn qa-copy" @click="copyLink"><i class="pi pi-link" /> Copy link phòng</button>
            <button class="qa-btn qa-dup" @click="duplicateMeeting"><i class="pi pi-copy" /> Nhân bản</button>
            <button v-if="meeting.status === 'scheduled'" class="qa-btn qa-cancel" @click="cancelMeeting"><i class="pi pi-times-circle" /> Hủy cuộc họp</button>
            <button class="qa-btn qa-delete" @click="deleteMeeting"><i class="pi pi-trash" /> Xóa</button>
          </div>
        </div>

        <!-- Participants -->
        <div class="side-card">
          <h4><i class="pi pi-users" /> Người tham gia ({{ (meeting.participants || []).length }})</h4>
          <div class="participants-list">
            <div v-for="(p, i) in (meeting.participants || [])" :key="i" class="p-row">
              <div class="p-avatar" :class="{ host: p.role === 'host' }">{{ (p.name || 'U').charAt(0) }}</div>
              <div class="p-info">
                <span class="p-name">{{ p.name }}</span>
                <span class="p-role">{{ p.role === 'host' ? 'Host' : 'Participant' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Meeting Notes -->
        <div v-if="meeting.meeting_notes" class="side-card">
          <h4><i class="pi pi-file-edit" /> Ghi chú</h4>
          <pre class="notes-content">{{ meeting.meeting_notes }}</pre>
        </div>

        <!-- Recording -->
        <div v-if="meeting.recording_url" class="side-card">
          <h4><i class="pi pi-video" /> Recording</h4>
          <a :href="meeting.recording_url" target="_blank" class="recording-link">
            <i class="pi pi-play" /> Xem lại recording
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import axios from 'axios'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { meeting: Object, types: Object, statuses: Object },
  data() {
    return {
      isSubmitting: false,
      form: {
        title: this.meeting.title,
        description: this.meeting.description,
        type: this.meeting.type,
        scheduled_at: this.meeting.scheduled_at || '',
        max_participants: this.meeting.max_participants,
        is_public: this.meeting.is_public,
        record_enabled: this.meeting.record_enabled,
        agenda: this.meeting.agenda || '',
        password: this.meeting.password || '',
        settings: { ...(this.meeting.settings || { mute_on_join: false, camera_off: false, waiting_room: false }) },
      },
    }
  },
  computed: {
    statusInfo() {
      return this.statuses[this.meeting.status] || { label: this.meeting.status, icon: 'pi pi-circle', color: '#94a3b8' }
    },
  },
  methods: {
    submitUpdate() {
      this.isSubmitting = true
      router.put(`/meetings/${this.meeting.id}`, this.form, {
        onFinish: () => { this.isSubmitting = false },
      })
    },
    copyLink() {
      const url = `${window.location.origin}/meetings/${this.meeting.room_code}/room`
      navigator.clipboard.writeText(url)
      alert('Đã copy link phòng!')
    },
    async cancelMeeting() {
      if (!confirm('Hủy cuộc họp này?')) return
      await axios.post(`/meetings/${this.meeting.id}/cancel`)
      router.reload()
    },
    duplicateMeeting() {
      router.post(`/meetings/${this.meeting.id}/duplicate`)
    },
    deleteMeeting() {
      if (!confirm('Xóa cuộc họp này?')) return
      router.delete(`/meetings/${this.meeting.id}`)
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
.header-left { display: flex; align-items: center; gap: 0.65rem; }
.header-right { display: flex; gap: 0.4rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-back:hover { border-color: #6366f1; color: #6366f1; }
.page-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.72rem; color: #94a3b8; margin: 0; }
.btn-join { display: flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.85rem; border-radius: 9px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-join:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-recap { display: flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.85rem; border-radius: 9px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

/* Info Band */
.info-band { display: flex; flex-wrap: wrap; gap: 1rem; padding: 0.75rem 1rem; background: white; border-radius: 12px; border: 1.5px solid #f1f5f9; margin-bottom: 0.85rem; }
.info-item { display: flex; flex-direction: column; gap: 0.15rem; }
.info-label { font-size: 0.58rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.info-value { font-size: 0.78rem; color: #334155; display: flex; align-items: center; gap: 0.2rem; }
.info-value i { font-size: 0.68rem; color: #94a3b8; }
.room-code { color: #6366f1; font-weight: 700; }
.status-badge { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.68rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; }
.status-badge i { font-size: 0.5rem; }
.s-scheduled { background: #eff6ff; color: #3b82f6; }
.s-live { background: #fef2f2; color: #ef4444; }
.s-ended { background: #ecfdf5; color: #10b981; }
.s-cancelled { background: #f1f5f9; color: #94a3b8; }

/* Layout */
.edit-layout { display: grid; grid-template-columns: 1fr 300px; gap: 0.85rem; align-items: start; }

/* Form */
.form-card { background: white; border-radius: 14px; padding: 1.25rem; border: 1.5px solid #f1f5f9; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem; }
.form-group label i { font-size: 0.65rem; color: #6366f1; }
.required { color: #ef4444; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.82rem; color: #1e293b; outline: none; font-family: inherit; transition: border-color 0.15s; }
.form-input:focus { border-color: #6366f1; }
textarea.form-input { resize: vertical; min-height: 40px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }

.type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 0.65rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.type-card:hover { border-color: #6366f1; }
.type-card.active { border-color: #6366f1; background: #eef2ff; }
.type-card i { font-size: 1rem; color: #6366f1; }
.type-card span { font-size: 0.68rem; font-weight: 600; color: #475569; }
.type-card.active span { color: #6366f1; }

.options-section { margin: 0.75rem 0; padding: 0.75rem; border-radius: 10px; background: #fafbfc; border: 1px solid #f1f5f9; }
.options-title { font-size: 0.78rem; font-weight: 700; color: #1e293b; margin: 0 0 0.55rem; display: flex; align-items: center; gap: 0.3rem; }
.options-title i { color: #6366f1; font-size: 0.72rem; }
.options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.opt-toggle { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.4rem; border-radius: 8px; }
.opt-toggle:hover { background: #f1f5f9; }
.opt-toggle input { display: none; }
.toggle-slider { width: 32px; height: 18px; border-radius: 9px; background: #e2e8f0; position: relative; transition: all 0.2s; flex-shrink: 0; }
.toggle-slider::after { content: ''; width: 14px; height: 14px; border-radius: 50%; background: white; position: absolute; top: 2px; left: 2px; transition: all 0.2s; }
.opt-toggle input:checked + .toggle-slider { background: #6366f1; }
.opt-toggle input:checked + .toggle-slider::after { left: 16px; }
.opt-info strong { display: block; font-size: 0.68rem; color: #1e293b; }
.opt-info span { font-size: 0.55rem; color: #94a3b8; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 9px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-save { display: flex; align-items: center; gap: 0.35rem; padding: 0.5rem 1.2rem; border-radius: 9px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all 0.15s; }
.btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Side */
.side-card { background: white; border-radius: 14px; padding: 0.85rem; border: 1.5px solid #f1f5f9; margin-bottom: 0.65rem; }
.side-card h4 { font-size: 0.78rem; font-weight: 700; color: #1e293b; margin: 0 0 0.55rem; display: flex; align-items: center; gap: 0.3rem; }
.side-card h4 i { color: #6366f1; font-size: 0.72rem; }

/* Quick Actions */
.quick-actions { display: flex; flex-direction: column; gap: 0.3rem; }
.qa-btn { width: 100%; padding: 0.45rem 0.65rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; font-family: inherit; transition: all 0.15s; color: #475569; }
.qa-btn:hover { border-color: #c4b5fd; }
.qa-btn i { font-size: 0.65rem; }
.qa-join { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
.qa-join:hover { background: #6366f1; color: white; }
.qa-copy { color: #6366f1; }
.qa-dup { color: #8b5cf6; }
.qa-cancel { color: #f59e0b; }
.qa-delete { color: #ef4444; border-color: #fecaca; }
.qa-delete:hover { background: #fef2f2; border-color: #ef4444; }

/* Participants */
.participants-list { display: flex; flex-direction: column; gap: 0.3rem; }
.p-row { display: flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0; }
.p-avatar { width: 26px; height: 26px; border-radius: 7px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.58rem; font-weight: 800; flex-shrink: 0; }
.p-avatar.host { background: linear-gradient(135deg, #f59e0b, #f97316); }
.p-name { font-size: 0.72rem; font-weight: 600; color: #1e293b; display: block; }
.p-role { font-size: 0.55rem; color: #94a3b8; }

/* Notes */
.notes-content { font-size: 0.68rem; color: #475569; line-height: 1.5; white-space: pre-wrap; font-family: inherit; margin: 0; padding: 0.5rem; background: #fafbfc; border-radius: 8px; border: 1px solid #f1f5f9; max-height: 150px; overflow-y: auto; }

/* Recording */
.recording-link { display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.45rem; border-radius: 8px; background: #eff6ff; color: #3b82f6; text-decoration: none; font-size: 0.72rem; font-weight: 600; transition: all 0.15s; }
.recording-link:hover { background: #dbeafe; }

@media (max-width: 768px) {
  .edit-layout { grid-template-columns: 1fr; }
  .options-grid { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; }
}
</style>
