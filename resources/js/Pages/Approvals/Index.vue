<template>
  <div>
    <Head title="Phê duyệt" />
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-verified" /></div>
        <div><h1 class="page-title">Phê duyệt báo giá & hợp đồng</h1><p class="page-subtitle">{{ stats.total_pending }} yêu cầu đang chờ phê duyệt</p></div>
      </div>
      <div class="stat-chips">
        <span class="stat-chip s1"><i class="pi pi-file-edit" /> {{ stats.pending_quotations }} báo giá</span>
        <span class="stat-chip s2"><i class="pi pi-file-check" /> {{ stats.pending_contracts }} hợp đồng</span>
        <span class="stat-chip s3"><i class="pi pi-check-circle" /> {{ stats.approved_this_month }} duyệt tháng này</span>
      </div>
    </div>

    <!-- Pending Quotations -->
    <div v-if="pendingQuotations.length" class="section-block">
      <h2 class="section-label"><i class="pi pi-file-edit" /> Báo giá chờ duyệt ({{ pendingQuotations.length }})</h2>
      <div class="approval-list">
        <div v-for="q in pendingQuotations" :key="`q-${q.id}`" class="approval-card">
          <div class="card-left">
            <div class="card-icon quotation-icon"><i class="pi pi-file-edit" /></div>
            <div class="card-info">
              <div class="card-top-row">
                <span class="card-number">{{ q.number }}</span>
                <span class="type-badge quotation">{{ q.type_label }}</span>
              </div>
              <h3 class="card-title">{{ q.title }}</h3>
              <div class="card-meta">
                <span><i class="pi pi-building" /> {{ q.customer_name }}</span>
                <span><i class="pi pi-user" /> {{ q.creator_name }}</span>
                <span><i class="pi pi-calendar" /> {{ q.created_at }}</span>
                <span v-if="q.valid_until"><i class="pi pi-clock" /> HSD: {{ q.valid_until }}</span>
              </div>
            </div>
          </div>
          <div class="card-right">
            <div class="card-value">{{ formatPrice(q.total) }}</div>
            <div class="card-actions">
              <Button icon="pi pi-check" severity="success" size="small" rounded v-tooltip="'Phê duyệt'" @click="approve('quotation', q.id)" :loading="processing === `approve-q-${q.id}`" />
              <Button icon="pi pi-times" severity="danger" size="small" rounded outlined v-tooltip="'Từ chối'" @click="openReject('quotation', q.id, q.title)" :loading="processing === `reject-q-${q.id}`" />
              <Link :href="`/quotations/${q.id}/edit`"><Button icon="pi pi-eye" severity="secondary" size="small" rounded text v-tooltip="'Xem chi tiết'" /></Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Contracts -->
    <div v-if="pendingContracts.length" class="section-block">
      <h2 class="section-label"><i class="pi pi-file-check" /> Hợp đồng chờ duyệt ({{ pendingContracts.length }})</h2>
      <div class="approval-list">
        <div v-for="c in pendingContracts" :key="`c-${c.id}`" class="approval-card">
          <div class="card-left">
            <div class="card-icon contract-icon"><i class="pi pi-file-check" /></div>
            <div class="card-info">
              <div class="card-top-row">
                <span class="card-number contract-num">{{ c.number }}</span>
                <span class="type-badge contract">{{ c.type_label }}</span>
                <span class="contract-type-tag">{{ c.contract_type }}</span>
              </div>
              <h3 class="card-title">{{ c.title }}</h3>
              <div class="card-meta">
                <span><i class="pi pi-building" /> {{ c.customer_name }}</span>
                <span><i class="pi pi-user" /> {{ c.creator_name }}</span>
                <span><i class="pi pi-calendar" /> {{ c.created_at }}</span>
              </div>
            </div>
          </div>
          <div class="card-right">
            <div class="card-value">{{ formatPrice(c.total) }}</div>
            <div class="card-actions">
              <Button icon="pi pi-check" severity="success" size="small" rounded v-tooltip="'Phê duyệt'" @click="approve('contract', c.id)" :loading="processing === `approve-c-${c.id}`" />
              <Button icon="pi pi-times" severity="danger" size="small" rounded outlined v-tooltip="'Từ chối'" @click="openReject('contract', c.id, c.title)" :loading="processing === `reject-c-${c.id}`" />
              <Link :href="`/contracts/${c.id}/edit`"><Button icon="pi pi-eye" severity="secondary" size="small" rounded text v-tooltip="'Xem chi tiết'" /></Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No pending -->
    <div v-if="!pendingQuotations.length && !pendingContracts.length" class="empty-state">
      <div class="empty-icon"><i class="pi pi-check-circle" /></div>
      <h3>Không có yêu cầu chờ duyệt</h3>
      <p>Tất cả báo giá và hợp đồng đã được xử lý.</p>
    </div>

    <!-- Recently Processed -->
    <div v-if="recentlyProcessed.length" class="section-block recent">
      <h2 class="section-label"><i class="pi pi-history" /> Đã xử lý gần đây</h2>
      <div class="recent-list">
        <div v-for="r in recentlyProcessed" :key="`r-${r.type}-${r.id}`" class="recent-item">
          <div class="recent-dot" :class="r.status === 'approved' ? 'dot-approved' : 'dot-rejected'" />
          <div class="recent-info">
            <span class="recent-number">{{ r.number }}</span>
            <span class="recent-title">{{ r.title }}</span>
          </div>
          <span class="recent-status" :class="r.status === 'approved' ? 'approved' : 'rejected'">{{ r.status_label }}</span>
          <span class="recent-meta">{{ r.approved_by }} — {{ r.approved_at }}</span>
          <span class="recent-value">{{ formatPrice(r.total) }}</span>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <div v-if="rejectDialog" class="dialog-overlay" @click.self="rejectDialog = false" @keydown.esc="rejectDialog = false">
      <div class="dialog-card">
        <h3>Từ chối: {{ rejectTitle }}</h3>
        <div class="form-group"><label>Lý do từ chối</label><Textarea v-model="rejectReason" class="w-full" rows="3" placeholder="Nhập lý do..." /></div>
        <div class="dialog-actions">
          <Button label="Hủy" severity="secondary" outlined @click="rejectDialog = false" />
          <Button label="Từ chối" severity="danger" icon="pi pi-times" @click="confirmReject" :loading="!!processing" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'

export default {
  components: { Head, Link, Button, Textarea },
  layout: Layout,
  props: { pendingQuotations: Array, pendingContracts: Array, recentlyProcessed: Array, stats: Object },
  data() {
    return {
      processing: null,
      rejectDialog: false, rejectType: null, rejectId: null, rejectTitle: '', rejectReason: '',
    }
  },
  mounted() {
    this._escHandler = (e) => { if (e.key === 'Escape') { this.rejectDialog = false } }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  methods: {
    formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' },
    approve(type, id) {
      this.processing = `approve-${type[0]}-${id}`
      this.$inertia.post('/approvals/approve', { type, id }, {
        preserveScroll: true, onFinish: () => { this.processing = null },
      })
    },
    openReject(type, id, title) {
      this.rejectType = type; this.rejectId = id; this.rejectTitle = title
      this.rejectReason = ''; this.rejectDialog = true
    },
    confirmReject() {
      this.processing = `reject-${this.rejectType[0]}-${this.rejectId}`
      this.$inertia.post('/approvals/reject', {
        type: this.rejectType, id: this.rejectId, reason: this.rejectReason,
      }, { preserveScroll: true, onFinish: () => { this.processing = null; this.rejectDialog = false } })
    },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.85rem}
.header-icon-wrapper{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;box-shadow:0 4px 14px rgba(139,92,246,.3)}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.stat-chips{display:flex;gap:.4rem;flex-wrap:wrap}
.stat-chip{display:flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:20px;font-size:.65rem;font-weight:600}.stat-chip i{font-size:.58rem}
.s1{background:#eff6ff;color:#3b82f6}.s2{background:#ecfdf5;color:#059669}.s3{background:#f5f3ff;color:#7c3aed}
.section-block{margin-bottom:1.5rem}
.section-label{font-size:.85rem;font-weight:700;color:#1e293b;margin:0 0 .65rem;display:flex;align-items:center;gap:.35rem}
.section-label i{font-size:.75rem;color:#8b5cf6}
.approval-list{display:flex;flex-direction:column;gap:.5rem}
.approval-card{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.15rem;background:white;border:1.5px solid #f1f5f9;border-radius:14px;transition:all .25s;gap:1rem}
.approval-card:hover{border-color:#8b5cf6;box-shadow:0 4px 18px rgba(139,92,246,.06)}
.card-left{display:flex;align-items:center;gap:.75rem;flex:1;min-width:0}
.card-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.quotation-icon{background:linear-gradient(135deg,#dbeafe,#eff6ff);color:#3b82f6}
.contract-icon{background:linear-gradient(135deg,#d1fae5,#ecfdf5);color:#059669}
.card-info{min-width:0;flex:1}
.card-top-row{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.card-number{font-size:.62rem;font-weight:700;color:#3b82f6;font-family:monospace}
.card-number.contract-num{color:#059669}
.type-badge{font-size:.48rem;font-weight:700;padding:.06rem .3rem;border-radius:4px;text-transform:uppercase}
.type-badge.quotation{background:#eff6ff;color:#3b82f6}.type-badge.contract{background:#ecfdf5;color:#059669}
.contract-type-tag{font-size:.48rem;font-weight:600;padding:.06rem .3rem;border-radius:4px;background:#f8fafc;color:#94a3b8}
.card-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:.1rem 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-meta{display:flex;gap:.6rem;flex-wrap:wrap}
.card-meta span{font-size:.62rem;color:#94a3b8;display:flex;align-items:center;gap:.15rem}
.card-meta i{font-size:.52rem}
.card-right{display:flex;align-items:center;gap:1rem;flex-shrink:0}
.card-value{font-size:1rem;font-weight:800;color:#1e293b;min-width:110px;text-align:right}
.card-actions{display:flex;gap:.25rem}
.section-block.recent{border-top:1px solid #f1f5f9;padding-top:1.25rem}
.recent-list{display:flex;flex-direction:column;gap:.3rem}
.recent-item{display:flex;align-items:center;gap:.6rem;padding:.45rem .75rem;background:white;border:1px solid #f8fafc;border-radius:10px;font-size:.72rem}
.recent-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.dot-approved{background:#059669}.dot-rejected{background:#ef4444}
.recent-info{flex:1;display:flex;align-items:center;gap:.4rem;min-width:0}
.recent-number{font-weight:700;color:#64748b;font-family:monospace;font-size:.62rem}
.recent-title{color:#1e293b;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.recent-status{font-size:.52rem;font-weight:700;padding:.06rem .3rem;border-radius:4px;text-transform:uppercase;flex-shrink:0}
.recent-status.approved{background:#ecfdf5;color:#059669}.recent-status.rejected{background:#fef2f2;color:#ef4444}
.recent-meta{color:#94a3b8;font-size:.62rem;flex-shrink:0}
.recent-value{font-weight:700;color:#1e293b;min-width:80px;text-align:right;flex-shrink:0}
.empty-state{text-align:center;padding:3rem 2rem;background:white;border-radius:16px;border:2px dashed #e2e8f0}
.empty-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#f5f3ff,#ede9fe);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#8b5cf6}
.empty-state h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .35rem}.empty-state p{font-size:.82rem;color:#94a3b8;margin:0}
.dialog-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(4px);padding:1.5rem}
.dialog-card{background:white;border-radius:16px;padding:1.5rem;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.15);animation:slideUp .25s ease-out}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.dialog-card h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1rem}
.form-group{margin-bottom:1rem}.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.w-full{width:100%}
.dialog-actions{display:flex;justify-content:flex-end;gap:.5rem}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.approval-card{flex-direction:column;align-items:flex-start}.card-right{width:100%;justify-content:space-between}.recent-item{flex-wrap:wrap}}
</style>
