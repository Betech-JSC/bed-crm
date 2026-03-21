<template>
  <div>
    <Head :title="proposal.title" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/proposals" class="breadcrumb-link">
        <i class="pi pi-arrow-left" /> Quay lại
      </Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ proposal.title }}</span>
    </div>

    <!-- Header Card -->
    <div class="proposal-hero">
      <div class="hero-status-bar" :style="{ background: getStatusColor(proposal.status) }" />
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon" :style="{ background: getStatusColor(proposal.status) + '18', color: getStatusColor(proposal.status) }">
            <i class="pi pi-file-edit" />
          </div>
          <div>
            <div class="hero-title-row">
              <h1 class="hero-title">{{ proposal.title }}</h1>
              <span v-if="proposal.version > 1" class="version-badge">v{{ proposal.version }}</span>
            </div>
            <div class="hero-meta">
              <span v-if="proposal.deal" class="meta-chip">
                <i class="pi pi-briefcase" /> {{ proposal.deal.title }}
              </span>
              <span v-if="proposal.creator" class="meta-chip">
                <i class="pi pi-user" /> {{ proposal.creator?.name }}
              </span>
              <span class="meta-chip">
                <i class="pi pi-calendar" /> {{ formatDateTime(proposal.created_at) }}
              </span>
            </div>
          </div>
        </div>
        <div class="hero-right">
          <div class="status-tag" :class="`status--${proposal.status}`">
            <i :class="getStatusIcon(proposal.status)" />
            {{ proposal.status_label }}
          </div>
          <div class="hero-amount" v-if="proposal.amount">
            {{ formatCurrency(proposal.amount) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Layout -->
    <div class="detail-layout">
      <!-- Left: Main Content -->
      <div class="detail-main">
        <!-- Tracking Timeline -->
        <div class="section-card" v-if="proposal.sent_at || proposal.viewed_at || proposal.accepted_at || proposal.rejected_at">
          <div class="section-header">
            <i class="pi pi-chart-line" />
            <h3>Theo dõi trạng thái</h3>
          </div>
          <div class="tracking-timeline">
            <div class="timeline-step" :class="{ active: true }">
              <div class="timeline-dot" />
              <div class="timeline-content">
                <span class="timeline-label">Tạo</span>
                <span class="timeline-date">{{ formatDateTime(proposal.created_at) }}</span>
              </div>
            </div>
            <div class="timeline-connector" :class="{ active: proposal.sent_at }" />

            <div class="timeline-step" :class="{ active: proposal.sent_at }">
              <div class="timeline-dot"><i class="pi pi-send" /></div>
              <div class="timeline-content">
                <span class="timeline-label">Đã gửi</span>
                <span class="timeline-date" v-if="proposal.sent_at">
                  {{ formatDateTime(proposal.sent_at) }}
                  <span v-if="proposal.sender" class="timeline-by">bởi {{ proposal.sender.name }}</span>
                </span>
                <span v-else class="timeline-pending">Chưa gửi</span>
              </div>
            </div>
            <div class="timeline-connector" :class="{ active: proposal.viewed_at }" />

            <div class="timeline-step" :class="{ active: proposal.viewed_at }">
              <div class="timeline-dot"><i class="pi pi-eye" /></div>
              <div class="timeline-content">
                <span class="timeline-label">Đã xem</span>
                <span class="timeline-date" v-if="proposal.viewed_at">
                  {{ formatDateTime(proposal.viewed_at) }}
                  <span class="view-count">({{ proposal.view_count }} lần)</span>
                </span>
                <span v-else class="timeline-pending">Chưa xem</span>
              </div>
            </div>
            <div class="timeline-connector" :class="{ active: proposal.accepted_at || proposal.rejected_at }" />

            <div class="timeline-step" :class="{ active: proposal.accepted_at, rejected: proposal.rejected_at }">
              <div class="timeline-dot">
                <i :class="proposal.rejected_at ? 'pi pi-times' : 'pi pi-check'" />
              </div>
              <div class="timeline-content">
                <span class="timeline-label">{{ proposal.rejected_at ? 'Từ chối' : 'Chấp nhận' }}</span>
                <span class="timeline-date" v-if="proposal.accepted_at">{{ formatDateTime(proposal.accepted_at) }}</span>
                <span class="timeline-date timeline-date--rejected" v-else-if="proposal.rejected_at">
                  {{ formatDateTime(proposal.rejected_at) }}
                </span>
                <span v-else class="timeline-pending">Đang chờ</span>
              </div>
            </div>
          </div>

          <!-- Rejection Reason -->
          <div v-if="proposal.rejection_reason" class="rejection-box">
            <i class="pi pi-exclamation-triangle" />
            <div>
              <span class="rejection-title">Lý do từ chối</span>
              <p class="rejection-text">{{ proposal.rejection_reason }}</p>
            </div>
          </div>
        </div>

        <!-- Details Card -->
        <div class="section-card">
          <div class="section-header">
            <i class="pi pi-info-circle" />
            <h3>Chi tiết báo giá</h3>
          </div>

          <!-- Description -->
          <div v-if="proposal.description" class="description-block">
            <p>{{ proposal.description }}</p>
          </div>

          <!-- Detail Grid -->
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Giá trị</span>
              <span class="detail-value detail-value--money">
                {{ proposal.amount ? formatCurrency(proposal.amount) : '—' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Hiệu lực đến</span>
              <span class="detail-value">
                {{ proposal.valid_until ? formatDate(proposal.valid_until) : 'Không giới hạn' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phiên bản</span>
              <span class="detail-value">v{{ proposal.version }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Ngày tạo</span>
              <span class="detail-value">{{ formatDateTime(proposal.created_at) }}</span>
            </div>
          </div>

          <!-- File Download -->
          <div v-if="proposal.file_name" class="file-download-card">
            <div class="file-info">
              <div class="file-icon-wrapper">
                <i class="pi pi-file-pdf" />
              </div>
              <div>
                <span class="file-name">{{ proposal.file_name }}</span>
                <span class="file-size">{{ formatFileSize(proposal.file_size) }}</span>
              </div>
            </div>
            <Link :href="`/proposals/${proposal.id}/download`">
              <Button label="Tải về" icon="pi pi-download" outlined size="small" />
            </Link>
          </div>
        </div>

        <!-- Version History -->
        <div v-if="versions.length > 1" class="section-card">
          <div class="section-header">
            <i class="pi pi-history" />
            <h3>Lịch sử phiên bản</h3>
          </div>
          <div class="versions-list">
            <div
              v-for="version in versions"
              :key="version.id"
              class="version-item"
              :class="{ 'version-item--current': version.id === proposal.id }"
            >
              <div class="version-left">
                <span class="version-num">v{{ version.version }}</span>
                <span class="version-date">{{ formatDateTime(version.created_at) }}</span>
                <div class="version-status-tag" :class="`status--${version.status}`">
                  {{ getStatusLabel(version.status) }}
                </div>
              </div>
              <div class="version-right">
                <span v-if="version.id === proposal.id" class="current-badge">Hiện tại</span>
                <Link v-else :href="`/proposals/${version.id}`">
                  <Button icon="pi pi-arrow-right" text rounded size="small" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Sidebar -->
      <div class="detail-sidebar">
        <!-- Actions -->
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-cog" /> Hành động</h3>
          </div>
          <div class="action-buttons">
            <Button
              v-if="proposal.can_be_edited"
              label="Chỉnh sửa"
              icon="pi pi-pencil"
              class="action-btn"
              @click="edit"
            />
            <Button
              v-if="proposal.can_be_sent"
              label="Gửi báo giá"
              icon="pi pi-send"
              severity="info"
              class="action-btn"
              @click="sendProposal"
            />
            <Button
              label="Tạo phiên bản mới"
              icon="pi pi-copy"
              severity="secondary"
              outlined
              class="action-btn"
              @click="createVersion"
            />
            <Button
              v-if="proposal.can_be_accepted"
              label="Duyệt"
              icon="pi pi-check"
              severity="success"
              class="action-btn"
              @click="acceptProposal"
            />
            <Button
              v-if="proposal.can_be_rejected"
              label="Từ chối"
              icon="pi pi-times"
              severity="danger"
              outlined
              class="action-btn"
              @click="showRejectDialog = true"
            />
            <div class="action-divider" />
            <Button
              label="Xóa"
              icon="pi pi-trash"
              severity="danger"
              text
              size="small"
              class="action-btn"
              @click="deleteProposal"
            />
          </div>
        </div>

        <!-- Linked Deal -->
        <div v-if="proposal.deal" class="sidebar-card">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-briefcase" /> Deal liên kết</h3>
          </div>
          <Link :href="`/deals/${proposal.deal.id}/edit`" class="deal-link-card">
            <div class="deal-link-icon">
              <i class="pi pi-briefcase" />
            </div>
            <div class="deal-link-info">
              <span class="deal-link-name">{{ proposal.deal.title }}</span>
              <span class="deal-link-arrow"><i class="pi pi-external-link" /></span>
            </div>
          </Link>
        </div>

        <!-- Creator -->
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-user" /> Người tạo</h3>
          </div>
          <div class="creator-info">
            <div class="creator-avatar">
              {{ (proposal.creator?.name || 'U')[0] }}
            </div>
            <div>
              <span class="creator-name">{{ proposal.creator?.name || 'Unknown' }}</span>
              <span class="creator-date">{{ formatDateTime(proposal.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <Dialog v-model:visible="showRejectDialog" modal header="Từ chối báo giá" :style="{ width: '480px' }" :draggable="false">
      <div class="reject-form">
        <div class="form-group">
          <label>Lý do từ chối (tuỳ chọn)</label>
          <textarea v-model="rejectionReason" rows="4" placeholder="Nhập lý do..." class="form-control" />
        </div>
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showRejectDialog = false" />
        <Button label="Từ chối báo giá" severity="danger" icon="pi pi-times" @click="rejectProposal" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, Dialog },
  layout: Layout,
  props: {
    proposal: Object,
    versions: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      showRejectDialog: false,
      rejectionReason: '',
    }
  },
  methods: {
    edit() { router.visit(`/proposals/${this.proposal.id}/edit`) },
    sendProposal() { router.post(`/proposals/${this.proposal.id}/send`, {}, { preserveScroll: true }) },
    createVersion() { router.post(`/proposals/${this.proposal.id}/version`, {}, { preserveScroll: true }) },
    acceptProposal() { router.post(`/proposals/${this.proposal.id}/accept`, {}, { preserveScroll: true }) },
    rejectProposal() {
      router.post(`/proposals/${this.proposal.id}/reject`, {
        rejection_reason: this.rejectionReason,
      }, {
        preserveScroll: true,
        onSuccess: () => { this.showRejectDialog = false; this.rejectionReason = '' },
      })
    },
    deleteProposal() {
      if (confirm('Bạn có chắc muốn xóa báo giá này?')) {
        router.delete(`/proposals/${this.proposal.id}`)
      }
    },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v) },
    formatDate(d) { return new Date(d).toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' }) },
    formatDateTime(d) { return new Date(d).toLocaleString('vi-VN', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) },
    formatFileSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024, s = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + s[i]
    },
    getStatusColor(status) {
      const m = { draft: '#94a3b8', sent: '#3b82f6', viewed: '#f59e0b', accepted: '#10b981', rejected: '#ef4444' }
      return m[status] || '#94a3b8'
    },
    getStatusIcon(status) {
      const m = { draft: 'pi pi-pencil', sent: 'pi pi-send', viewed: 'pi pi-eye', accepted: 'pi pi-check-circle', rejected: 'pi pi-times-circle' }
      return m[status] || 'pi pi-file'
    },
    getStatusLabel(status) {
      const m = { draft: 'Nháp', sent: 'Đã gửi', viewed: 'Đã xem', accepted: 'Đã duyệt', rejected: 'Từ chối' }
      return m[status] || status
    },
  },
}
</script>

<style scoped>
/* ===== Breadcrumb ===== */
.breadcrumb-bar {
  display: flex; align-items: center; gap: 0.5rem;
  margin-bottom: 1rem; font-size: 0.78rem;
}
.breadcrumb-link {
  color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem;
  transition: opacity 0.2s;
}
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* ===== Hero Card ===== */
.proposal-hero {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden;
  margin-bottom: 1.25rem;
}
.hero-status-bar { height: 4px; }
.hero-content {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.25rem 1.5rem; gap: 1rem;
}
.hero-left { display: flex; align-items: flex-start; gap: 0.85rem; min-width: 0; }
.hero-icon {
  width: 46px; height: 46px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.hero-title-row { display: flex; align-items: center; gap: 0.45rem; }
.hero-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; }
.version-badge {
  font-size: 0.6rem; font-weight: 700; color: #6366f1;
  background: #eef2ff; padding: 0.15rem 0.4rem; border-radius: 5px;
}
.hero-meta { display: flex; gap: 0.65rem; margin-top: 0.45rem; flex-wrap: wrap; }
.meta-chip {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.72rem; color: #64748b;
}
.meta-chip i { font-size: 0.6rem; color: #94a3b8; }

.hero-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.45rem; flex-shrink: 0; }
.status-tag {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 8px;
}
.status-tag i { font-size: 0.6rem; }
.status--draft { background: #f1f5f9; color: #64748b; }
.status--sent { background: #eff6ff; color: #3b82f6; }
.status--viewed { background: #fffbeb; color: #d97706; }
.status--accepted { background: #ecfdf5; color: #059669; }
.status--rejected { background: #fef2f2; color: #dc2626; }
.hero-amount { font-size: 1.35rem; font-weight: 800; color: #0f172a; }

/* ===== Layout ===== */
.detail-layout {
  display: grid; grid-template-columns: 1fr 320px; gap: 1.25rem;
}
.detail-main { display: flex; flex-direction: column; gap: 1rem; }
.detail-sidebar { display: flex; flex-direction: column; gap: 1rem; }

/* ===== Section Card ===== */
.section-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
}
.section-header {
  display: flex; align-items: center; gap: 0.45rem;
  padding: 0.85rem 1.15rem; border-bottom: 1px solid #f8fafc;
}
.section-header i { color: #6366f1; font-size: 0.88rem; }
.section-header h3 { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0; }

/* Tracking Timeline */
.tracking-timeline {
  display: flex; align-items: center; padding: 1.25rem;
  gap: 0;
}
.timeline-step {
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
  min-width: 80px;
}
.timeline-step.active .timeline-dot { background: #10b981; color: white; }
.timeline-step.rejected .timeline-dot { background: #ef4444; color: white; }
.timeline-dot {
  width: 32px; height: 32px; border-radius: 50%;
  background: #e2e8f0; color: #94a3b8;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; transition: all 0.3s;
}
.timeline-label { font-size: 0.7rem; font-weight: 600; color: #475569; }
.timeline-date { font-size: 0.6rem; color: #94a3b8; text-align: center; }
.timeline-date--rejected { color: #ef4444; }
.timeline-by { display: block; font-size: 0.55rem; }
.timeline-pending { font-size: 0.6rem; color: #cbd5e1; font-style: italic; }
.view-count { font-weight: 600; color: #6366f1; }

.timeline-connector {
  flex: 1; height: 3px; background: #e2e8f0;
  margin: 0 0.35rem; margin-bottom: 2rem;
  transition: background 0.3s;
}
.timeline-connector.active { background: #10b981; }

/* Rejection Box */
.rejection-box {
  display: flex; gap: 0.65rem; padding: 0.85rem 1.15rem;
  margin: 0 1.15rem 1.15rem; border-radius: 10px;
  background: #fef2f2; border: 1px solid #fecaca;
}
.rejection-box i { color: #ef4444; font-size: 0.88rem; margin-top: 0.1rem; }
.rejection-title { font-size: 0.72rem; font-weight: 600; color: #dc2626; }
.rejection-text { font-size: 0.78rem; color: #7f1d1d; margin: 0.2rem 0 0; }

/* Description */
.description-block {
  padding: 1rem 1.15rem; border-bottom: 1px solid #f8fafc;
}
.description-block p {
  font-size: 0.82rem; color: #475569; line-height: 1.65; margin: 0; white-space: pre-wrap;
}

/* Detail Grid */
.detail-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 0;
  padding: 0;
}
.detail-item {
  display: flex; flex-direction: column; gap: 0.2rem;
  padding: 0.85rem 1.15rem; border-bottom: 1px solid #f8fafc;
}
.detail-item:nth-child(odd) { border-right: 1px solid #f8fafc; }
.detail-label { font-size: 0.68rem; color: #94a3b8; font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
.detail-value { font-size: 0.88rem; font-weight: 600; color: #1e293b; }
.detail-value--money { color: #10b981; font-size: 1rem; }

/* File Download */
.file-download-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.85rem 1.15rem; margin: 0.85rem 1.15rem 1.15rem;
  background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;
}
.file-info { display: flex; align-items: center; gap: 0.65rem; }
.file-icon-wrapper {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
}
.file-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; }
.file-size { font-size: 0.68rem; color: #94a3b8; }

/* Versions */
.versions-list { padding: 0.65rem; display: flex; flex-direction: column; gap: 0.35rem; }
.version-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.55rem 0.75rem; border-radius: 8px;
  transition: background 0.2s;
}
.version-item:hover { background: #f8fafc; }
.version-item--current { background: #eef2ff; }
.version-left { display: flex; align-items: center; gap: 0.55rem; }
.version-num { font-size: 0.72rem; font-weight: 700; color: #6366f1; }
.version-date { font-size: 0.7rem; color: #64748b; }
.version-status-tag {
  font-size: 0.58rem; font-weight: 600; padding: 0.12rem 0.4rem; border-radius: 4px;
}
.version-right { display: flex; align-items: center; }
.current-badge {
  font-size: 0.6rem; font-weight: 600; color: #6366f1;
  background: #eef2ff; padding: 0.15rem 0.45rem; border-radius: 5px;
}

/* ===== Sidebar Card ===== */
.sidebar-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
}
.sidebar-card-header {
  display: flex; align-items: center; padding: 0.75rem 1rem;
  border-bottom: 1px solid #f8fafc;
}
.sidebar-card-header h3 {
  font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0;
  display: flex; align-items: center; gap: 0.35rem;
}
.sidebar-card-header i { color: #6366f1; font-size: 0.78rem; }

/* Action Buttons */
.action-buttons {
  display: flex; flex-direction: column; gap: 0.35rem;
  padding: 0.65rem;
}
.action-btn { width: 100%; justify-content: flex-start !important; }
.action-divider { height: 1px; background: #f1f5f9; margin: 0.25rem 0; }

/* Deal Link */
.deal-link-card {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem 1rem; text-decoration: none; transition: background 0.2s;
}
.deal-link-card:hover { background: #f8fafc; }
.deal-link-icon {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem;
}
.deal-link-info { flex: 1; display: flex; align-items: center; justify-content: space-between; }
.deal-link-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.deal-link-arrow { color: #94a3b8; font-size: 0.68rem; }

/* Creator */
.creator-info {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem 1rem;
}
.creator-avatar {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; font-weight: 700; text-transform: uppercase;
}
.creator-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; }
.creator-date { font-size: 0.68rem; color: #94a3b8; }

/* ===== Reject Form ===== */
.reject-form { padding: 0.5rem 0; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-size: 0.82rem; font-weight: 500; color: #475569; }
.form-control {
  width: 100%; padding: 0.65rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; font-size: 0.82rem; font-family: inherit;
  color: #1e293b; outline: none; resize: vertical; transition: border-color 0.2s;
}
.form-control:focus { border-color: #6366f1; }
.form-control::placeholder { color: #94a3b8; }

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .detail-layout { grid-template-columns: 1fr; }
  .detail-sidebar { order: -1; }
}
@media (max-width: 768px) {
  .hero-content { flex-direction: column; }
  .hero-right { align-items: flex-start; }
  .tracking-timeline { flex-direction: column; align-items: flex-start; }
  .timeline-step { flex-direction: row; }
  .timeline-connector { width: 3px; height: 24px; margin: 0.2rem 0 0.2rem 14px; }
  .detail-grid { grid-template-columns: 1fr; }
  .detail-item:nth-child(odd) { border-right: none; }
}
</style>
