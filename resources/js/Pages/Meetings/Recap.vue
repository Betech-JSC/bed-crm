<template>
  <div>
    <Head :title="`Recap: ${meeting.title}`" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/meetings"><button class="btn-back"><i class="pi pi-arrow-left" /></button></Link>
        <div>
          <h1 class="page-title"><i class="pi pi-sparkles" style="color:#8b5cf6;" /> AI Meeting Recap</h1>
          <p class="page-subtitle">{{ meeting.title }}</p>
        </div>
      </div>
      <div class="header-right">
        <button v-if="!meeting.ai_summary" class="btn-generate" @click="generateRecap" :disabled="generating">
          <i :class="generating ? 'pi pi-spin pi-spinner' : 'pi pi-sparkles'" />
          {{ generating ? 'Đang phân tích...' : 'Tạo AI Recap' }}
        </button>
        <button v-else class="btn-regen" @click="generateRecap" :disabled="generating">
          <i :class="generating ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" />
          Tạo lại
        </button>
      </div>
    </div>

    <!-- Meeting Info Bar -->
    <div class="info-bar">
      <div class="info-chip"><i class="pi pi-hashtag" /> {{ meeting.room_code }}</div>
      <div class="info-chip"><i class="pi pi-calendar" /> {{ meeting.scheduled_at }}</div>
      <div class="info-chip" v-if="meeting.duration_formatted"><i class="pi pi-stopwatch" /> {{ meeting.duration_formatted }}</div>
      <div class="info-chip"><i class="pi pi-users" /> {{ (meeting.participants || []).length }} người</div>
      <div class="info-chip info-chip--rec" v-if="meeting.recording_url"><i class="pi pi-video" /> Có recording</div>
    </div>

    <!-- Recap Content -->
    <div class="recap-layout">
      <!-- Main -->
      <div class="main-col">
        <!-- AI Summary -->
        <div class="recap-card summary-card" v-if="summary">
          <div class="card-header">
            <h2><i class="pi pi-align-left" /> Tóm tắt cuộc họp</h2>
          </div>
          <p class="summary-text">{{ summary }}</p>
        </div>

        <!-- Topics -->
        <div class="recap-card" v-if="topics.length">
          <div class="card-header">
            <h2><i class="pi pi-tags" /> Chủ đề thảo luận</h2>
          </div>
          <div class="topics-row">
            <span v-for="(topic, i) in topics" :key="i" class="topic-chip">{{ topic }}</span>
          </div>
        </div>

        <!-- Action Items -->
        <div class="recap-card" v-if="actionItems.length">
          <div class="card-header">
            <h2><i class="pi pi-check-square" /> Action Items ({{ actionItems.length }})</h2>
          </div>
          <div class="action-list">
            <div v-for="(item, i) in actionItems" :key="i" class="action-item" :class="`priority-${item.priority}`">
              <div class="action-check">
                <input type="checkbox" v-model="item.done" />
              </div>
              <div class="action-body">
                <div class="action-task" :class="{ done: item.done }">{{ item.task }}</div>
                <div class="action-meta">
                  <span class="action-assignee"><i class="pi pi-user" /> {{ item.assignee }}</span>
                  <span class="action-deadline"><i class="pi pi-calendar" /> {{ item.deadline }}</span>
                  <span class="action-priority" :class="`p-${item.priority}`">{{ item.priority }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Key Decisions -->
        <div class="recap-card" v-if="keyDecisions.length">
          <div class="card-header">
            <h2><i class="pi pi-verified" /> Quyết định quan trọng ({{ keyDecisions.length }})</h2>
          </div>
          <div class="decisions-list">
            <div v-for="(d, i) in keyDecisions" :key="i" class="decision-item">
              <div class="decision-icon"><i class="pi pi-check-circle" /></div>
              <div class="decision-body">
                <div class="decision-text">{{ d.decision }}</div>
                <div class="decision-context">{{ d.context }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Meeting Notes -->
        <div class="recap-card" v-if="meeting.meeting_notes">
          <div class="card-header">
            <h2><i class="pi pi-file-edit" /> Ghi chú cuộc họp</h2>
          </div>
          <pre class="notes-pre">{{ meeting.meeting_notes }}</pre>
        </div>

        <!-- Empty state -->
        <div v-if="!summary && !generating" class="empty-recap">
          <div class="empty-icon"><i class="pi pi-sparkles" /></div>
          <h3>Chưa có AI Recap</h3>
          <p>Nhấn "Tạo AI Recap" để AI phân tích cuộc họp và tạo tóm tắt tự động</p>
          <button class="btn-generate" @click="generateRecap">
            <i class="pi pi-sparkles" /> Tạo AI Recap
          </button>
        </div>

        <!-- Loading -->
        <div v-if="generating && !summary" class="generating-state">
          <div class="gen-spinner"><i class="pi pi-spin pi-spinner" /></div>
          <h3>AI đang phân tích cuộc họp...</h3>
          <p>Đang tóm tắt nội dung, trích xuất action items và quyết định</p>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="side-col">
        <!-- Participants -->
        <div class="side-card">
          <h3><i class="pi pi-users" /> Người tham gia</h3>
          <div class="participants-list">
            <div v-for="(p, i) in (meeting.participants || [])" :key="i" class="participant-row">
              <div class="p-avatar" :class="{ host: p.role === 'host' }">{{ (p.name || 'U').charAt(0) }}</div>
              <div class="p-info">
                <span class="p-name">{{ p.name }}</span>
                <span class="p-role">{{ p.role === 'host' ? 'Host' : 'Participant' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recording -->
        <div v-if="meeting.recording_url" class="side-card">
          <h3><i class="pi pi-video" /> Recording</h3>
          <a :href="meeting.recording_url" target="_blank" class="play-recording">
            <i class="pi pi-play" /> Xem lại recording
          </a>
        </div>

        <!-- Export -->
        <div class="side-card" v-if="summary">
          <h3><i class="pi pi-download" /> Xuất báo cáo</h3>
          <button class="export-btn" @click="copyRecap"><i class="pi pi-copy" /> Copy to clipboard</button>
        </div>

        <!-- Agenda -->
        <div class="side-card" v-if="meeting.agenda">
          <h3><i class="pi pi-list" /> Agenda</h3>
          <pre class="side-agenda">{{ meeting.agenda }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import axios from 'axios'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { meeting: Object },
  data() {
    return {
      generating: false,
      summary: this.meeting.ai_summary || '',
      actionItems: (this.meeting.ai_action_items || []).map(a => ({ ...a, done: false })),
      keyDecisions: this.meeting.ai_key_decisions || [],
      topics: this.meeting.ai_topics || [],
    }
  },
  methods: {
    async generateRecap() {
      this.generating = true
      try {
        const { data } = await axios.post(`/meetings/${this.meeting.id}/recap-generate`)
        if (data.success) {
          this.summary = data.summary || ''
          this.actionItems = (data.action_items || []).map(a => ({ ...a, done: false }))
          this.keyDecisions = data.key_decisions || []
          this.topics = data.topics || []
        }
      } catch (e) {
        alert('Lỗi tạo recap: ' + (e.response?.data?.message || e.message))
      }
      this.generating = false
    },
    copyRecap() {
      let text = `📋 MEETING RECAP: ${this.meeting.title}\n`
      text += `📅 ${this.meeting.scheduled_at} | ⏱ ${this.meeting.duration_formatted}\n\n`
      text += `📝 TÓM TẮT:\n${this.summary}\n\n`
      if (this.topics.length) text += `🏷 CHỦ ĐỀ: ${this.topics.join(', ')}\n\n`
      if (this.actionItems.length) {
        text += `✅ ACTION ITEMS:\n`
        this.actionItems.forEach((a, i) => { text += `${i + 1}. ${a.task} → ${a.assignee} (${a.deadline}) [${a.priority}]\n` })
        text += '\n'
      }
      if (this.keyDecisions.length) {
        text += `🎯 QUYẾT ĐỊNH:\n`
        this.keyDecisions.forEach((d, i) => { text += `${i + 1}. ${d.decision} — ${d.context}\n` })
      }
      navigator.clipboard.writeText(text)
      alert('Đã copy recap!')
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
.header-left { display: flex; align-items: center; gap: 0.65rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-back:hover { border-color: #8b5cf6; color: #8b5cf6; }
.page-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0; }
.btn-generate { display: flex; align-items: center; gap: 0.35rem; padding: 0.5rem 1rem; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all 0.15s; }
.btn-generate:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.btn-generate:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-regen { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.75rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-regen:hover { border-color: #8b5cf6; color: #8b5cf6; }

/* Info bar */
.info-bar { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 1rem; }
.info-chip { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.55rem; border-radius: 7px; background: #f1f5f9; font-size: 0.65rem; font-weight: 600; color: #64748b; }
.info-chip i { font-size: 0.58rem; color: #94a3b8; }
.info-chip--rec { background: #eff6ff; color: #3b82f6; }

/* Layout */
.recap-layout { display: grid; grid-template-columns: 1fr 280px; gap: 0.85rem; align-items: start; }
.recap-card { background: white; border-radius: 14px; padding: 1.1rem; border: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.card-header { margin-bottom: 0.65rem; }
.card-header h2 { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.3rem; }
.card-header h2 i { color: #8b5cf6; font-size: 0.82rem; }

/* Summary */
.summary-card { border-color: #ddd6fe; background: linear-gradient(135deg, #faf5ff 0%, white 25%); }
.summary-text { font-size: 0.82rem; color: #374151; line-height: 1.7; margin: 0; }

/* Topics */
.topics-row { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.topic-chip { padding: 0.2rem 0.55rem; border-radius: 7px; background: linear-gradient(135deg, #eef2ff, #faf5ff); font-size: 0.68rem; font-weight: 600; color: #6366f1; border: 1px solid #e0e7ff; }

/* Action Items */
.action-list { display: flex; flex-direction: column; gap: 0.4rem; }
.action-item { display: flex; gap: 0.5rem; padding: 0.55rem; border-radius: 10px; background: #fafbfc; border: 1px solid #f1f5f9; transition: all 0.15s; }
.action-item:hover { border-color: #e2e8f0; }
.action-check { padding-top: 0.1rem; }
.action-check input { accent-color: #8b5cf6; width: 16px; height: 16px; }
.action-task { font-size: 0.78rem; font-weight: 600; color: #1e293b; line-height: 1.4; }
.action-task.done { text-decoration: line-through; color: #94a3b8; }
.action-meta { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem; font-size: 0.6rem; color: #94a3b8; flex-wrap: wrap; }
.action-meta i { font-size: 0.5rem; }
.action-assignee, .action-deadline { display: flex; align-items: center; gap: 0.15rem; }
.action-priority { padding: 0.08rem 0.3rem; border-radius: 4px; font-size: 0.5rem; font-weight: 700; text-transform: uppercase; }
.p-high { background: #fef2f2; color: #ef4444; }
.p-medium { background: #fffbeb; color: #f59e0b; }
.p-low { background: #f0fdf4; color: #22c55e; }

/* Decisions */
.decisions-list { display: flex; flex-direction: column; gap: 0.4rem; }
.decision-item { display: flex; gap: 0.5rem; padding: 0.55rem; border-radius: 10px; background: #f0fdf4; border: 1px solid #dcfce7; }
.decision-icon { color: #10b981; font-size: 0.85rem; padding-top: 0.05rem; flex-shrink: 0; }
.decision-text { font-size: 0.78rem; font-weight: 600; color: #1e293b; line-height: 1.4; }
.decision-context { font-size: 0.65rem; color: #64748b; margin-top: 0.15rem; }

/* Notes */
.notes-pre { font-size: 0.75rem; color: #475569; line-height: 1.6; white-space: pre-wrap; font-family: inherit; margin: 0; padding: 0.65rem; background: #fafbfc; border-radius: 8px; border: 1px solid #f1f5f9; }

/* Loading / Empty */
.empty-recap { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #eef2ff, #faf5ff); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; }
.empty-icon i { font-size: 1.3rem; color: #8b5cf6; }
.empty-recap h3 { font-size: 1rem; color: #1e293b; margin: 0 0 0.25rem; }
.empty-recap p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

.generating-state { text-align: center; padding: 3rem 1rem; }
.gen-spinner { margin: 0 auto 0.75rem; }
.gen-spinner i { font-size: 2rem; color: #8b5cf6; }
.generating-state h3 { font-size: 1rem; color: #1e293b; margin: 0 0 0.25rem; }
.generating-state p { font-size: 0.78rem; color: #94a3b8; margin: 0; }

/* Sidebar */
.side-card { background: white; border-radius: 14px; padding: 0.85rem; border: 1.5px solid #f1f5f9; margin-bottom: 0.65rem; }
.side-card h3 { font-size: 0.78rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.3rem; }
.side-card h3 i { color: #8b5cf6; font-size: 0.72rem; }

/* Participants */
.participants-list { display: flex; flex-direction: column; gap: 0.3rem; }
.participant-row { display: flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0; }
.p-avatar { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.62rem; font-weight: 800; flex-shrink: 0; }
.p-avatar.host { background: linear-gradient(135deg, #f59e0b, #f97316); }
.p-name { font-size: 0.72rem; font-weight: 600; color: #1e293b; display: block; }
.p-role { font-size: 0.55rem; color: #94a3b8; }

/* Recording playback */
.play-recording { display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.45rem; border-radius: 8px; background: #eff6ff; color: #3b82f6; text-decoration: none; font-size: 0.72rem; font-weight: 600; transition: all 0.15s; }
.play-recording:hover { background: #dbeafe; }

/* Export */
.export-btn { width: 100%; padding: 0.4rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-family: inherit; transition: all 0.15s; }
.export-btn:hover { border-color: #8b5cf6; color: #8b5cf6; }

/* Agenda sidebar */
.side-agenda { font-size: 0.65rem; color: #64748b; line-height: 1.5; white-space: pre-wrap; font-family: inherit; margin: 0; }

@media (max-width: 768px) { .recap-layout { grid-template-columns: 1fr; } }
</style>
