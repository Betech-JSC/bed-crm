<template>
  <div>
    <Head title="Tạo cuộc họp" />

    <div class="page-header">
      <Link href="/meetings"><button class="btn-back"><i class="pi pi-arrow-left" /></button></Link>
      <div>
        <h1 class="page-title"><i class="pi pi-calendar-plus" style="color:#6366f1;" /> Tạo cuộc họp mới</h1>
        <p class="page-subtitle">Lên lịch video call, audio hoặc screen share</p>
      </div>
    </div>

    <div class="create-layout">
      <!-- Left: Form -->
      <div class="form-panel">
        <div class="form-card">
          <!-- Title -->
          <div class="form-group">
            <label>Tên cuộc họp <span class="required">*</span></label>
            <input v-model="form.title" type="text" class="form-input" placeholder="VD: Weekly Standup, Sprint Review..." />
          </div>

          <!-- Description -->
          <div class="form-group">
            <label>Mô tả <span class="optional">(tuỳ chọn)</span></label>
            <textarea v-model="form.description" rows="2" class="form-input" placeholder="Mô tả ngắn về cuộc họp..." />
          </div>

          <!-- Type -->
          <div class="form-group">
            <label>Loại cuộc họp</label>
            <div class="type-grid">
              <button v-for="(info, key) in types" :key="key" class="type-card" :class="{ active: form.type === key }" @click="form.type = key">
                <i :class="info.icon" />
                <span>{{ info.label }}</span>
              </button>
            </div>
          </div>

          <!-- Schedule -->
          <div class="form-row">
            <div class="form-group">
              <label>Thời gian bắt đầu</label>
              <input v-model="form.scheduled_at" type="datetime-local" class="form-input" :min="minDate" />
            </div>
            <div class="form-group">
              <label>Max người tham gia</label>
              <input v-model.number="form.max_participants" type="number" min="2" max="100" class="form-input" />
            </div>
          </div>

          <!-- Agenda -->
          <div class="form-group">
            <label><i class="pi pi-list" /> Agenda <span class="optional">(tuỳ chọn)</span></label>
            <textarea v-model="form.agenda" rows="4" class="form-input" placeholder="- Điểm 1: Check tiến độ &#10;- Điểm 2: Demo tính năng mới &#10;- Điểm 3: Q&A" />
          </div>

          <!-- Options -->
          <div class="options-section">
            <h4 class="options-title"><i class="pi pi-cog" /> Cài đặt</h4>
            <div class="options-grid">
              <label class="opt-toggle">
                <input type="checkbox" v-model="form.record_enabled" />
                <span class="toggle-slider" />
                <div class="opt-info"><strong>Ghi hình</strong><span>Tự động ghi lại cuộc họp</span></div>
              </label>
              <label class="opt-toggle">
                <input type="checkbox" v-model="form.is_public" />
                <span class="toggle-slider" />
                <div class="opt-info"><strong>Công khai</strong><span>Ai có link đều tham gia được</span></div>
              </label>
              <label class="opt-toggle">
                <input type="checkbox" v-model="form.settings.mute_on_join" />
                <span class="toggle-slider" />
                <div class="opt-info"><strong>Tắt mic khi vào</strong><span>Participant bị mute lúc join</span></div>
              </label>
              <label class="opt-toggle">
                <input type="checkbox" v-model="form.settings.waiting_room" />
                <span class="toggle-slider" />
                <div class="opt-info"><strong>Phòng chờ</strong><span>Host phải approve mới vào được</span></div>
              </label>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label>Mật khẩu phòng <span class="optional">(tuỳ chọn)</span></label>
            <input v-model="form.password" type="text" class="form-input" placeholder="Để trống = không cần mật khẩu" />
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <Link href="/meetings"><button class="btn-cancel">Hủy</button></Link>
            <button class="btn-create" @click="submitMeeting" :disabled="!form.title || isSubmitting">
              <i :class="isSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-video'" />
              {{ form.scheduled_at ? 'Lên lịch họp' : 'Bắt đầu ngay' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Right: Preview -->
      <div class="preview-panel">
        <div class="preview-card">
          <div class="preview-header">
            <i class="pi pi-eye" /> Xem trước
          </div>
          <div class="preview-meeting">
            <div class="preview-type-icon" :style="{ background: form.type === 'video' ? '#6366f1' : form.type === 'audio' ? '#10b981' : '#f59e0b' }">
              <i :class="types[form.type]?.icon || 'pi pi-video'" />
            </div>
            <h4>{{ form.title || 'Tên cuộc họp...' }}</h4>
            <p class="preview-desc">{{ form.description || 'Mô tả cuộc họp...' }}</p>
            <div class="preview-chips">
              <span class="prev-chip"><i class="pi pi-users" /> Max {{ form.max_participants }}</span>
              <span v-if="form.record_enabled" class="prev-chip prev-chip--rec"><i class="pi pi-circle-fill" /> REC</span>
              <span v-if="form.is_public" class="prev-chip"><i class="pi pi-globe" /> Công khai</span>
              <span v-if="form.password" class="prev-chip"><i class="pi pi-lock" /> Mật khẩu</span>
            </div>
            <div v-if="form.scheduled_at" class="preview-schedule">
              <i class="pi pi-calendar" /> {{ formatDate(form.scheduled_at) }}
            </div>
            <div v-if="form.agenda" class="preview-agenda">
              <strong><i class="pi pi-list" /> Agenda:</strong>
              <pre>{{ form.agenda }}</pre>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="tips-card">
          <h4><i class="pi pi-lightbulb" /> Mẹo</h4>
          <ul>
            <li>Bật <strong>Ghi hình</strong> để AI tạo recap sau cuộc họp</li>
            <li>Viết <strong>Agenda</strong> để người tham gia chuẩn bị trước</li>
            <li>Đặt <strong>mật khẩu</strong> cho cuộc họp bảo mật</li>
            <li>Dùng <strong>phòng chờ</strong> để kiểm soát ai vào phòng</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { types: Object },
  data() {
    return {
      isSubmitting: false,
      form: {
        title: '',
        description: '',
        type: 'video',
        scheduled_at: '',
        max_participants: 20,
        is_public: false,
        record_enabled: true,
        agenda: '',
        password: '',
        settings: { mute_on_join: false, camera_off: false, waiting_room: false },
      },
    }
  },
  computed: {
    minDate() {
      const d = new Date(); d.setMinutes(d.getMinutes() + 5)
      return d.toISOString().slice(0, 16)
    },
  },
  methods: {
    submitMeeting() {
      this.isSubmitting = true
      router.post('/meetings', this.form, { onFinish: () => { this.isSubmitting = false } })
    },
    formatDate(d) {
      if (!d) return ''
      return new Date(d).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-back:hover { border-color: #6366f1; color: #6366f1; }
.page-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0; }

.create-layout { display: grid; grid-template-columns: 1fr 320px; gap: 1rem; align-items: start; }
.form-card { background: white; border-radius: 14px; padding: 1.25rem; border: 1.5px solid #f1f5f9; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem; }
.form-group label i { font-size: 0.65rem; color: #6366f1; }
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; font-size: 0.62rem; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.82rem; color: #1e293b; outline: none; font-family: inherit; transition: border-color 0.15s; }
.form-input:focus { border-color: #6366f1; }
textarea.form-input { resize: vertical; min-height: 40px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }

/* Type cards */
.type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 0.75rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.type-card:hover { border-color: #6366f1; }
.type-card.active { border-color: #6366f1; background: #eef2ff; }
.type-card i { font-size: 1.1rem; color: #6366f1; }
.type-card span { font-size: 0.72rem; font-weight: 600; color: #475569; }
.type-card.active span { color: #6366f1; }

/* Options */
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
.opt-info span { font-size: 0.58rem; color: #94a3b8; }

/* Actions */
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 9px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-create { display: flex; align-items: center; gap: 0.35rem; padding: 0.5rem 1.2rem; border-radius: 9px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all 0.15s; }
.btn-create:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-create:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Preview */
.preview-card { background: white; border-radius: 14px; padding: 1rem; border: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.preview-header { font-size: 0.72rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 0.3rem; }
.preview-meeting { text-align: center; }
.preview-type-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; margin: 0 auto 0.55rem; }
.preview-meeting h4 { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0 0 0.15rem; }
.preview-desc { font-size: 0.68rem; color: #94a3b8; margin: 0 0 0.5rem; }
.preview-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.25rem; margin-bottom: 0.5rem; }
.prev-chip { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.12rem 0.4rem; border-radius: 5px; font-size: 0.58rem; font-weight: 600; background: #f1f5f9; color: #64748b; }
.prev-chip i { font-size: 0.5rem; }
.prev-chip--rec { background: #fef2f2; color: #ef4444; }
.prev-chip--rec i { font-size: 0.35rem; }
.preview-schedule { font-size: 0.72rem; color: #6366f1; font-weight: 600; margin-top: 0.3rem; }
.preview-schedule i { font-size: 0.65rem; }
.preview-agenda { text-align: left; margin-top: 0.65rem; padding: 0.5rem; background: #fafbfc; border-radius: 8px; border: 1px solid #f1f5f9; }
.preview-agenda strong { font-size: 0.65rem; color: #475569; display: flex; align-items: center; gap: 0.2rem; margin-bottom: 0.25rem; }
.preview-agenda strong i { font-size: 0.6rem; color: #6366f1; }
.preview-agenda pre { font-size: 0.65rem; color: #64748b; margin: 0; white-space: pre-wrap; font-family: inherit; line-height: 1.5; }

.tips-card { background: white; border-radius: 14px; padding: 1rem; border: 1.5px solid #f1f5f9; }
.tips-card h4 { font-size: 0.78rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.3rem; }
.tips-card h4 i { color: #f59e0b; }
.tips-card ul { padding: 0 0 0 1rem; margin: 0; }
.tips-card li { font-size: 0.68rem; color: #64748b; margin-bottom: 0.3rem; line-height: 1.4; }

@media (max-width: 768px) { .create-layout { grid-template-columns: 1fr; } .options-grid { grid-template-columns: 1fr; } }
</style>
