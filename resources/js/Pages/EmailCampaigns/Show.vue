<template>
  <div>
    <Head :title="campaign.name" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-campaigns" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon" :style="{ background: statusGradient }"><i class="pi pi-megaphone" /></div>
        <div>
          <h1 class="page-title">{{ campaign.name }}</h1>
          <p class="page-subtitle">
            <span class="campaign-badge" :class="`cb-${campaign.status}`"><span class="status-dot" /> {{ statusLabel }}</span>
            <span v-if="campaign.email_list_name" class="list-tag"><i class="pi pi-list" /> {{ campaign.email_list_name }}</span>
          </p>
        </div>
      </div>
      <div class="header-actions">
        <button v-if="canSend" class="btn-send" @click="sendCampaign"><i class="pi pi-send" /> Gửi ngay</button>
        <Link :href="`/email-campaigns/${campaign.id}/edit`"><button class="btn-edit"><i class="pi pi-pencil" /> Sửa</button></Link>
      </div>
    </div>

    <!-- Performance KPIs -->
    <div class="kpi-row">
      <div class="kpi-card"><div class="kpi-icon" style="background:#eef2ff;color:#6366f1"><i class="pi pi-users" /></div><div class="kpi-body"><span class="kpi-value">{{ campaign.total_recipients || 0 }}</span><span class="kpi-label">Recipients</span></div></div>
      <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5;color:#10b981"><i class="pi pi-check-circle" /></div><div class="kpi-body"><span class="kpi-value">{{ campaign.delivered_count || 0 }}</span><span class="kpi-label">Delivered</span></div></div>
      <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff;color:#8b5cf6"><i class="pi pi-eye" /></div><div class="kpi-body"><span class="kpi-value">{{ campaign.open_rate ? Number(campaign.open_rate).toFixed(1) + '%' : '0%' }}</span><span class="kpi-label">Open Rate ({{ campaign.opened_count || 0 }})</span></div></div>
      <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7;color:#f59e0b"><i class="pi pi-external-link" /></div><div class="kpi-body"><span class="kpi-value">{{ campaign.click_rate ? Number(campaign.click_rate).toFixed(1) + '%' : '0%' }}</span><span class="kpi-label">Click Rate ({{ campaign.clicked_count || 0 }})</span></div></div>
    </div>

    <!-- Details -->
    <div class="detail-grid">
      <div class="detail-card">
        <h3 class="detail-title"><i class="pi pi-info-circle" /> Thông tin</h3>
        <div class="detail-rows">
          <div class="detail-row"><span class="detail-key">Subject</span><span class="detail-val">{{ campaign.subject || '—' }}</span></div>
          <div class="detail-row"><span class="detail-key">Lên lịch</span><span class="detail-val">{{ campaign.scheduled_at || '—' }}</span></div>
          <div class="detail-row"><span class="detail-key">Đã gửi</span><span class="detail-val">{{ campaign.sent_at || '—' }}</span></div>
          <div class="detail-row"><span class="detail-key">Bounced</span><span class="detail-val">{{ campaign.bounced_count || 0 }}</span></div>
          <div class="detail-row"><span class="detail-key">Unsubscribed</span><span class="detail-val">{{ campaign.unsubscribed_count || 0 }}</span></div>
        </div>
      </div>

      <div class="detail-card">
        <h3 class="detail-title"><i class="pi pi-chart-bar" /> Funnel</h3>
        <div class="funnel-visual">
          <div v-for="f in funnelData" :key="f.label" class="funnel-item">
            <div class="funnel-bar-wrap"><div class="funnel-bar" :style="{ width: f.pct + '%', background: f.color }" /></div>
            <span class="funnel-label">{{ f.label }}</span>
            <span class="funnel-count">{{ f.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview -->
    <div v-if="campaign.body_html" class="preview-card">
      <h3 class="detail-title"><i class="pi pi-eye" /> Xem trước nội dung</h3>
      <div class="preview-frame" v-html="campaign.body_html" />
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link },
  layout: Layout,
  props: { campaign: Object },
  computed: {
    statusLabel() { return { draft: 'Nháp', scheduled: 'Lên lịch', sending: 'Đang gửi', sent: 'Đã gửi', paused: 'Tạm dừng', cancelled: 'Đã hủy' }[this.campaign.status] || this.campaign.status },
    statusGradient() { return { draft: '#94a3b8', scheduled: '#3b82f6', sending: '#f59e0b', sent: '#10b981' }[this.campaign.status] || '#94a3b8' },
    canSend() { return ['draft', 'scheduled'].includes(this.campaign.status) },
    funnelData() {
      const c = this.campaign, max = Math.max(c.total_recipients || 1, 1)
      return [
        { label: 'Sent', value: c.sent_count || 0, pct: ((c.sent_count || 0) / max) * 100, color: '#3b82f6' },
        { label: 'Delivered', value: c.delivered_count || 0, pct: ((c.delivered_count || 0) / max) * 100, color: '#10b981' },
        { label: 'Opened', value: c.opened_count || 0, pct: ((c.opened_count || 0) / max) * 100, color: '#8b5cf6' },
        { label: 'Clicked', value: c.clicked_count || 0, pct: ((c.clicked_count || 0) / max) * 100, color: '#f59e0b' },
      ]
    },
  },
  methods: {
    sendCampaign() { if (confirm('Bạn có chắc muốn gửi campaign này?')) router.post(`/email-campaigns/${this.campaign.id}/send`) },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#3b82f6;color:#3b82f6;background:#eff6ff}.header-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{display:flex;align-items:center;gap:.5rem;margin:.15rem 0 0}
.campaign-badge{display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:600;padding:.15rem .5rem;border-radius:20px}.status-dot{width:6px;height:6px;border-radius:50%}
.cb-draft{background:#f1f5f9;color:#64748b}.cb-draft .status-dot{background:#94a3b8}.cb-scheduled{background:#eff6ff;color:#3b82f6}.cb-scheduled .status-dot{background:#3b82f6}.cb-sending{background:#fef3c7;color:#d97706}.cb-sending .status-dot{background:#d97706}.cb-sent{background:#d1fae5;color:#059669}.cb-sent .status-dot{background:#059669}
.list-tag{font-size:.68rem;color:#64748b;display:flex;align-items:center;gap:.2rem}.list-tag i{font-size:.6rem}
.header-actions{display:flex;gap:.5rem}
.btn-send{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-send:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(16,185,129,.3)}
.btn-edit{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-edit:hover{border-color:#3b82f6;color:#3b82f6}

.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem}
.kpi-card{display:flex;align-items:center;gap:.6rem;padding:.75rem .85rem;background:#fff;border-radius:14px;border:1.5px solid #e2e8f0;transition:all .2s}.kpi-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.05)}
.kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}.kpi-body{display:flex;flex-direction:column}.kpi-value{font-size:1.15rem;font-weight:800;color:#1e293b;line-height:1.1}.kpi-label{font-size:.62rem;color:#94a3b8;margin-top:.1rem}

.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
.detail-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.25rem}
.detail-title{font-size:.88rem;font-weight:700;color:#1e293b;margin:0 0 .75rem;display:flex;align-items:center;gap:.4rem}.detail-title i{color:#94a3b8;font-size:.85rem}
.detail-rows{display:flex;flex-direction:column;gap:.5rem}
.detail-row{display:flex;align-items:center;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid #f8fafc}
.detail-key{font-size:.78rem;color:#64748b}.detail-val{font-size:.78rem;font-weight:600;color:#334155}

.funnel-visual{display:flex;flex-direction:column;gap:.6rem}
.funnel-item{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:.5rem}
.funnel-bar-wrap{height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.funnel-bar{height:100%;border-radius:4px;transition:width .5s ease}
.funnel-label{font-size:.72rem;color:#64748b;min-width:55px}.funnel-count{font-size:.72rem;font-weight:700;color:#334155;min-width:30px;text-align:right}

.preview-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.25rem}
.preview-frame{border:1px solid #e2e8f0;border-radius:10px;padding:1rem;max-height:400px;overflow-y:auto;font-size:.85rem;line-height:1.6;color:#334155}

@media(max-width:768px){.kpi-row{grid-template-columns:repeat(2,1fr)}.detail-grid{grid-template-columns:1fr}}
</style>
