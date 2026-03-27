<template>
  <div>
    <Head :title="automation.name" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-automations" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon" :class="automation.status === 'active' ? 'icon-active' : ''"><i class="pi pi-bolt" /></div>
        <div>
          <h1 class="page-title">{{ automation.name }}</h1>
          <p class="page-subtitle">
            <span class="status-badge" :class="`st-${automation.status}`"><span class="status-dot" /> {{ statusLabel }}</span>
            <span class="trigger-tag"><i class="pi pi-zap" /> {{ triggerLabel(automation.trigger_type) }}</span>
          </p>
        </div>
      </div>
      <div class="header-actions">
        <button v-if="automation.status === 'draft' || automation.status === 'paused'" class="btn-activate" @click="activate"><i class="pi pi-play" /> Kích hoạt</button>
        <button v-if="automation.status === 'active'" class="btn-pause" @click="pause"><i class="pi pi-pause" /> Tạm dừng</button>
        <Link :href="`/email-automations/${automation.id}/edit`"><button class="btn-edit"><i class="pi pi-pencil" /> Sửa</button></Link>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
      <div class="kpi-card"><div class="kpi-icon" style="background:#eef2ff;color:#6366f1"><i class="pi pi-users" /></div><div class="kpi-body"><span class="kpi-value">{{ automation.contacts_processed || 0 }}</span><span class="kpi-label">Đã xử lý</span></div></div>
      <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7;color:#f59e0b"><i class="pi pi-send" /></div><div class="kpi-body"><span class="kpi-value">{{ automation.emails_sent || 0 }}</span><span class="kpi-label">Emails gửi</span></div></div>
    </div>

    <!-- Steps -->
    <div class="steps-card">
      <div class="steps-header">
        <h3 class="section-title"><i class="pi pi-sitemap" /> Quy trình ({{ steps.length }} bước)</h3>
      </div>
      <div v-if="steps.length === 0" class="empty-mini">
        <i class="pi pi-info-circle" /> Automation chưa có bước nào. Hãy cấu hình trong phần chỉnh sửa.
      </div>
      <div v-else class="steps-timeline">
        <div v-for="(step, idx) in steps" :key="step.id" class="step-item">
          <div class="step-connector"><div class="step-dot" :class="{ active: step.is_active }" /></div>
          <div class="step-content">
            <div class="step-head">
              <span class="step-order">Bước {{ step.step_order }}</span>
              <span class="step-type">{{ stepTypeLabel(step.step_type) }}</span>
            </div>
            <div v-if="step.email_template_name" class="step-detail"><i class="pi pi-file" /> {{ step.email_template_name }}</div>
            <div v-if="step.wait_days" class="step-detail"><i class="pi pi-clock" /> Chờ {{ step.wait_days }} ngày</div>
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
  components: { Head, Link }, layout: Layout,
  props: { automation: Object, steps: Array, templates: Array },
  computed: {
    statusLabel() { return { draft: 'Nháp', active: 'Hoạt động', paused: 'Tạm dừng', completed: 'Hoàn tất' }[this.automation.status] || this.automation.status },
  },
  methods: {
    triggerLabel(t) { return { lead_created: 'Lead Created', contact_created: 'Contact Created', deal_won: 'Deal Won', tag_added: 'Tag Added' }[t] || t },
    stepTypeLabel(t) { return { send_email: 'Gửi Email', wait: 'Chờ', condition: 'Điều kiện', action: 'Action' }[t] || t },
    activate() { router.post(`/email-automations/${this.automation.id}/activate`) },
    pause() { router.post(`/email-automations/${this.automation.id}/pause`) },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#f59e0b;color:#d97706;background:#fffbeb}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.icon-active{background:linear-gradient(135deg,#16a34a,#15803d)!important;animation:pulse-icon 2s infinite}
@keyframes pulse-icon{0%,100%{box-shadow:0 0 0 0 rgba(22,163,106,.3)}50%{box-shadow:0 0 0 8px rgba(22,163,106,0)}}
.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{display:flex;align-items:center;gap:.5rem;margin:.15rem 0 0}
.status-badge{display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:600;padding:.15rem .5rem;border-radius:20px}.status-dot{width:6px;height:6px;border-radius:50%}
.st-draft{background:#f1f5f9;color:#64748b}.st-draft .status-dot{background:#94a3b8}.st-active{background:#dcfce7;color:#16a34a}.st-active .status-dot{background:#16a34a}.st-paused{background:#fef3c7;color:#d97706}.st-paused .status-dot{background:#d97706}.st-completed{background:#dbeafe;color:#2563eb}.st-completed .status-dot{background:#2563eb}
.trigger-tag{font-size:.68rem;color:#64748b;display:flex;align-items:center;gap:.2rem}.trigger-tag i{font-size:.6rem}
.header-actions{display:flex;gap:.5rem}
.btn-activate{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-activate:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(22,163,106,.3)}
.btn-pause{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-pause:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(245,158,11,.3)}
.btn-edit{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-edit:hover{border-color:#f59e0b;color:#d97706}

.kpi-row{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1rem}
.kpi-card{display:flex;align-items:center;gap:.6rem;padding:.75rem .85rem;background:#fff;border-radius:14px;border:1.5px solid #e2e8f0;transition:all .2s}.kpi-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.05)}
.kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}.kpi-body{display:flex;flex-direction:column}.kpi-value{font-size:1.15rem;font-weight:800;color:#1e293b;line-height:1.1}.kpi-label{font-size:.62rem;color:#94a3b8;margin-top:.1rem}

.steps-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.25rem}
.steps-header{margin-bottom:.75rem}
.section-title{font-size:.88rem;font-weight:700;color:#1e293b;margin:0;display:flex;align-items:center;gap:.4rem}.section-title i{color:#f59e0b;font-size:.85rem}
.empty-mini{padding:.75rem;background:#fffbeb;border-radius:10px;font-size:.78rem;color:#92400e;display:flex;align-items:center;gap:.4rem}.empty-mini i{font-size:.85rem}

.steps-timeline{display:flex;flex-direction:column;gap:0;padding-left:.5rem}
.step-item{display:flex;gap:.75rem;padding:.6rem 0}
.step-connector{display:flex;flex-direction:column;align-items:center;position:relative;min-width:16px}
.step-dot{width:12px;height:12px;border-radius:50%;background:#e2e8f0;border:2px solid #fff;box-shadow:0 0 0 2px #e2e8f0;z-index:1}
.step-dot.active{background:#16a34a;box-shadow:0 0 0 2px #dcfce7}
.step-item:not(:last-child) .step-connector::after{content:'';position:absolute;top:14px;left:50%;width:2px;height:calc(100% + 8px);background:#e2e8f0;transform:translateX(-50%)}
.step-content{flex:1;padding:.15rem 0}
.step-head{display:flex;align-items:center;gap:.5rem}
.step-order{font-size:.72rem;font-weight:700;color:#94a3b8}
.step-type{font-size:.78rem;font-weight:600;color:#1e293b}
.step-detail{font-size:.72rem;color:#64748b;margin-top:.25rem;display:flex;align-items:center;gap:.3rem}.step-detail i{font-size:.65rem;color:#94a3b8}

@media(max-width:768px){.kpi-row{grid-template-columns:1fr}}
</style>
