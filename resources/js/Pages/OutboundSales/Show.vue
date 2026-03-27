<template>
  <div>
    <Head :title="`Outbound — ${campaign.lead?.company || campaign.lead?.name || 'Campaign'}`" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/outbound-sales" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-send" /></div>
        <div>
          <h1 class="page-title">{{ campaign.lead?.company || campaign.lead?.name }}</h1>
          <p class="page-subtitle">
            <span class="funnel-badge" :class="`fl-${campaign.lead_status}`">
              <span class="funnel-badge-dot" /> {{ campaign.lead_status_label }}
            </span>
            · Campaign #{{ campaign.id }}
          </p>
        </div>
      </div>
      <div class="header-actions">
        <button v-if="campaign.status === 'active'" class="btn-secondary" @click="pauseCampaign"><i class="pi pi-pause" /> Pause</button>
        <button v-if="campaign.status === 'paused'" class="btn-primary" @click="resumeCampaign"><i class="pi pi-play" /> Resume</button>
        <button v-if="campaign.status === 'active' && !campaign.replied" class="btn-success" @click="markAsReplied"><i class="pi pi-reply" /> Mark Replied</button>
        <button v-if="campaign.status === 'active'" class="btn-danger" @click="cancelCampaign"><i class="pi pi-times" /> Cancel</button>
      </div>
    </div>

    <div class="detail-grid">
      <!-- Left: Lead Info + Automation Flow -->
      <div class="detail-left">
        <!-- Lead Card -->
        <div class="info-card">
          <div class="info-header">
            <div class="info-icon lead-bg"><i class="pi pi-user" /></div>
            <h3>Lead Information</h3>
          </div>
          <div class="info-body">
            <div class="info-row">
              <span class="info-label">Name</span>
              <span class="info-value">{{ campaign.lead?.name || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Company</span>
              <span class="info-value">{{ campaign.lead?.company || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Email</span>
              <span class="info-value email">{{ campaign.lead?.email || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Phone</span>
              <span class="info-value">{{ campaign.lead?.phone || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Industry</span>
              <span class="info-value">{{ campaign.lead?.industry || '—' }}</span>
            </div>
          </div>
        </div>

        <!-- Automation Flow -->
        <div class="info-card">
          <div class="info-header">
            <div class="info-icon flow-bg"><i class="pi pi-sitemap" /></div>
            <h3>Automation Flow</h3>
          </div>
          <div class="flow-steps">
            <div v-for="(step, idx) in automationSteps" :key="idx" class="flow-step" :class="{ completed: idx < campaign.current_step, active: idx === campaign.current_step && campaign.status === 'active', pending: idx > campaign.current_step }">
              <div class="flow-dot">
                <i v-if="idx < campaign.current_step" class="pi pi-check" />
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <div class="flow-line" v-if="idx < automationSteps.length - 1" />
              <div class="flow-content">
                <span class="flow-title">{{ step.title }}</span>
                <span class="flow-desc">{{ step.desc }}</span>
                <span v-if="step.delay" class="flow-delay"><i class="pi pi-clock" /> {{ step.delay }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Score Breakdown -->
        <div class="info-card">
          <div class="info-header">
            <div class="info-icon score-bg"><i class="pi pi-chart-line" /></div>
            <h3>Lead Score</h3>
            <div class="score-total" :class="scoreClass(campaign.lead_score)">{{ campaign.lead_score }}</div>
          </div>
          <div class="score-rules">
            <div class="score-rule">
              <span class="score-rule-label"><i class="pi pi-eye" /> Email Opened</span>
              <span class="score-rule-value" :class="{ earned: campaign.email_opened }">+10</span>
            </div>
            <div class="score-rule">
              <span class="score-rule-label"><i class="pi pi-external-link" /> Link Clicked</span>
              <span class="score-rule-value" :class="{ earned: campaign.link_clicked }">+20</span>
            </div>
            <div class="score-rule">
              <span class="score-rule-label"><i class="pi pi-reply" /> Replied</span>
              <span class="score-rule-value" :class="{ earned: campaign.replied }">+50</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Activity Timeline -->
      <div class="detail-right">
        <div class="info-card timeline-card">
          <div class="info-header">
            <div class="info-icon timeline-bg"><i class="pi pi-history" /></div>
            <h3>Activity Timeline</h3>
            <span class="timeline-count">{{ logs.length }} events</span>
          </div>
          <div class="timeline" v-if="logs.length">
            <div v-for="log in logs" :key="log.id" class="timeline-item">
              <div class="timeline-dot" :style="{ background: log.action_color }">
                <i :class="`pi ${log.action_icon}`" />
              </div>
              <div class="timeline-line" />
              <div class="timeline-content">
                <div class="timeline-head">
                  <span class="timeline-action">{{ log.action_label }}</span>
                  <span v-if="log.channel" class="timeline-channel" :class="`ch-${log.channel}`">{{ log.channel }}</span>
                </div>
                <p v-if="log.subject" class="timeline-subject"><i class="pi pi-envelope" /> {{ log.subject }}</p>
                <p v-if="log.content_preview" class="timeline-preview">{{ log.content_preview }}</p>
                <div v-if="log.metadata && log.metadata.points" class="timeline-score-change">
                  <span class="score-change-badge">+{{ log.metadata.points }} pts</span>
                  <span class="score-change-reason">{{ log.metadata.reason }}</span>
                </div>
                <span class="timeline-time">{{ formatDate(log.created_at) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-timeline">
            <i class="pi pi-inbox" />
            <p>No activity yet</p>
          </div>
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
  props: {
    campaign: Object,
    logs: Array,
  },
  data() {
    return {
      automationSteps: [
        { title: 'Intro Email + Zalo', desc: 'Personalized intro with portfolio & CTA', delay: null },
        { title: 'Follow-up Resend', desc: 'Resend with different subject if not opened', delay: 'After 2 days' },
        { title: 'Case Study Email', desc: 'Share relevant success story', delay: 'After 5 days' },
        { title: 'Final Offer', desc: 'Free mockup offer with urgency', delay: 'After 7 days' },
      ],
    }
  },
  methods: {
    pauseCampaign() { router.post(`/outbound-sales/${this.campaign.id}/pause`, {}, { preserveScroll: true }) },
    resumeCampaign() { router.post(`/outbound-sales/${this.campaign.id}/resume`, {}, { preserveScroll: true }) },
    cancelCampaign() {
      if (confirm('Cancel this campaign? This cannot be undone.')) {
        router.post(`/outbound-sales/${this.campaign.id}/cancel`, {}, { preserveScroll: true })
      }
    },
    markAsReplied() {
      if (confirm('Mark this lead as replied? This will qualify the lead and complete the campaign.')) {
        router.post(`/outbound-sales/${this.campaign.id}/mark-replied`, {}, { preserveScroll: true })
      }
    },
    scoreClass(s) { return s >= 50 ? 'sc-high' : s >= 20 ? 'sc-mid' : s >= 10 ? 'sc-low' : 'sc-cold' },
    formatDate(dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr)
      return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.back-btn { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; text-decoration: none; transition: all 0.2s; }
.back-btn:hover { border-color: #ef6820; color: #ef6820; background: #fef5f0; }
.header-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #ef6820, #e04f0f); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; box-shadow: 0 4px 14px rgba(239,104,32,0.25); }
.page-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.78rem; color: #64748b; margin: 0.1rem 0 0; display: flex; align-items: center; gap: 0.4rem; }
.header-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

/* Buttons */
.btn-primary { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.btn-secondary { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 8px; background: white; color: #475569; font-size: 0.78rem; font-weight: 600; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { border-color: #f59e0b; color: #f59e0b; }
.btn-success { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 8px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.78rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
.btn-danger { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 8px; background: white; color: #ef4444; font-size: 0.78rem; font-weight: 600; border: 1.5px solid #fecaca; cursor: pointer; transition: all 0.2s; }
.btn-danger:hover { background: #fef2f2; }

/* Detail Grid */
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; }

/* Info Cards */
.info-card { background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; overflow: hidden; margin-bottom: 1rem; }
.info-header { display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1.15rem; border-bottom: 1px solid #f1f5f9; }
.info-header h3 { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; flex: 1; }
.info-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0; }
.lead-bg { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.flow-bg { background: linear-gradient(135deg, #ef6820, #e04f0f); color: white; }
.score-bg { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.timeline-bg { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }

/* Info body */
.info-body { padding: 0.85rem 1.15rem; }
.info-row { display: flex; align-items: center; padding: 0.4rem 0; border-bottom: 1px solid #f8fafc; }
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 0.72rem; color: #94a3b8; font-weight: 500; width: 80px; flex-shrink: 0; }
.info-value { font-size: 0.82rem; color: #1e293b; font-weight: 500; }
.info-value.email { color: #6366f1; }

/* Funnel badge */
.funnel-badge { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.65rem; font-weight: 600; padding: 0.12rem 0.45rem; border-radius: 20px; }
.funnel-badge-dot { width: 5px; height: 5px; border-radius: 50%; }
.fl-new { background: #eff6ff; color: #3b82f6; } .fl-new .funnel-badge-dot { background: #3b82f6; }
.fl-contacted { background: #fef3c7; color: #d97706; } .fl-contacted .funnel-badge-dot { background: #d97706; }
.fl-engaged { background: #f5f3ff; color: #8b5cf6; } .fl-engaged .funnel-badge-dot { background: #8b5cf6; }
.fl-qualified { background: #d1fae5; color: #059669; } .fl-qualified .funnel-badge-dot { background: #059669; }

/* Flow steps */
.flow-steps { padding: 1rem 1.15rem; }
.flow-step { display: flex; gap: 0.75rem; position: relative; padding-bottom: 1.25rem; }
.flow-step:last-child { padding-bottom: 0; }
.flow-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; flex-shrink: 0; z-index: 1; transition: all 0.2s; }
.flow-step.completed .flow-dot { background: #10b981; color: white; }
.flow-step.active .flow-dot { background: #ef6820; color: white; box-shadow: 0 0 0 4px rgba(239,104,32,0.15); animation: pulse 2s infinite; }
.flow-step.pending .flow-dot { background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0; }
.flow-line { position: absolute; left: 13px; top: 28px; width: 2px; height: calc(100% - 28px); background: #e2e8f0; }
.flow-step.completed .flow-line { background: #10b981; }
.flow-step.active .flow-line { background: linear-gradient(180deg, #ef6820, #e2e8f0); }
.flow-content { flex: 1; }
.flow-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; }
.flow-desc { font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 0.1rem; }
.flow-delay { font-size: 0.65rem; color: #64748b; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.2rem; margin-top: 0.3rem; }
.flow-delay i { font-size: 0.55rem; }

@keyframes pulse { 0%, 100% { box-shadow: 0 0 0 4px rgba(239,104,32,0.15); } 50% { box-shadow: 0 0 0 8px rgba(239,104,32,0.05); } }

/* Score */
.score-total { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; border: 3px solid; }
.sc-high { border-color: #10b981; color: #059669; background: #ecfdf5; }
.sc-mid { border-color: #f59e0b; color: #d97706; background: #fffbeb; }
.sc-low { border-color: #ef4444; color: #dc2626; background: #fef2f2; }
.sc-cold { border-color: #94a3b8; color: #64748b; background: #f8fafc; }

.score-rules { padding: 0.75rem 1.15rem; }
.score-rule { display: flex; align-items: center; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px solid #f8fafc; }
.score-rule:last-child { border-bottom: none; }
.score-rule-label { font-size: 0.78rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; }
.score-rule-label i { font-size: 0.72rem; color: #94a3b8; }
.score-rule-value { font-size: 0.72rem; font-weight: 700; color: #cbd5e1; background: #f8fafc; padding: 0.15rem 0.45rem; border-radius: 6px; }
.score-rule-value.earned { color: #059669; background: #ecfdf5; }

/* Timeline */
.timeline-card { max-height: calc(100vh - 160px); overflow-y: auto; }
.timeline-count { font-size: 0.68rem; color: #94a3b8; background: #f1f5f9; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 600; }
.timeline { padding: 1rem 1.15rem; }
.timeline-item { display: flex; gap: 0.75rem; position: relative; padding-bottom: 1.25rem; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; color: white; flex-shrink: 0; z-index: 1; }
.timeline-dot i { font-size: 0.6rem; }
.timeline-line { position: absolute; left: 13px; top: 28px; width: 2px; height: calc(100% - 28px); background: #f1f5f9; }
.timeline-content { flex: 1; min-width: 0; }
.timeline-head { display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.2rem; }
.timeline-action { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.timeline-channel { font-size: 0.58rem; font-weight: 700; padding: 0.08rem 0.35rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.03em; }
.ch-email { background: #dbeafe; color: #2563eb; }
.ch-zalo { background: #dbeafe; color: #0068ff; }
.timeline-subject { font-size: 0.72rem; color: #475569; margin: 0.15rem 0; display: flex; align-items: center; gap: 0.25rem; }
.timeline-subject i { font-size: 0.6rem; color: #94a3b8; }
.timeline-preview { font-size: 0.68rem; color: #94a3b8; margin: 0.1rem 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.timeline-score-change { margin-top: 0.25rem; display: flex; align-items: center; gap: 0.35rem; }
.score-change-badge { font-size: 0.62rem; font-weight: 700; color: #059669; background: #ecfdf5; padding: 0.08rem 0.35rem; border-radius: 4px; }
.score-change-reason { font-size: 0.65rem; color: #94a3b8; }
.timeline-time { font-size: 0.65rem; color: #cbd5e1; margin-top: 0.2rem; display: block; }

.empty-timeline { display: flex; flex-direction: column; align-items: center; padding: 3rem; color: #94a3b8; }
.empty-timeline i { font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem; }
.empty-timeline p { font-size: 0.82rem; margin: 0; }

@media (max-width: 768px) {
  .detail-grid { grid-template-columns: 1fr; }
  .header-actions { flex-wrap: wrap; }
}
</style>
