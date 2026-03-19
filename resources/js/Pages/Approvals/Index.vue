<template>
  <div>
    <Head :title="t('common.approvals')" />

    <div class="page-header">
      <div class="page-header-left">
        <div class="header-icon"><i class="pi pi-verified" /></div>
        <div>
          <h1 class="page-title">{{ t('common.approvals') }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Quản lý yêu cầu duyệt' : 'Manage approval requests' }}</p>
        </div>
      </div>
      <div class="header-tabs">
        <button class="tab-btn" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
          {{ isVi ? 'Chờ duyệt' : 'Pending' }} <span class="tab-count" v-if="pendingCount">{{ pendingCount }}</span>
        </button>
        <button class="tab-btn" :class="{ active: activeTab === 'all' }" @click="activeTab = 'all'">
          {{ isVi ? 'Tất cả' : 'All' }}
        </button>
      </div>
    </div>

    <div v-if="filteredRequests.length === 0" class="empty-state">
      <div class="empty-illustration"><i class="pi pi-check-circle" /></div>
      <h3>{{ activeTab === 'pending' ? (isVi ? 'Không có yêu cầu chờ duyệt' : 'No pending requests') : (isVi ? 'Chưa có yêu cầu nào' : 'No requests yet') }}</h3>
      <p>{{ isVi ? 'Các yêu cầu duyệt sẽ xuất hiện ở đây khi được tạo' : 'Approval requests will appear here when created' }}</p>
    </div>

    <TransitionGroup name="list" tag="div" class="request-list" v-else>
      <div v-for="req in filteredRequests" :key="req.id" class="request-card">
        <div class="req-status-bar" :class="`bar-${req.status}`" />
        <div class="req-body">
          <div class="req-header">
            <div class="req-icon" :class="`icon-${req.status}`"><i :class="statusIcon(req.status)" /></div>
            <div class="req-info">
              <h3 class="req-title">{{ req.title }}</h3>
              <p class="req-desc" v-if="req.description">{{ req.description }}</p>
            </div>
            <span class="req-status-badge" :class="`status-${req.status}`">
              <i :class="statusIcon(req.status)" /> {{ statusLabel(req.status) }}
            </span>
          </div>
          <div class="req-meta">
            <span class="meta-item"><i class="pi pi-tag" /> {{ req.entity_type }}</span>
            <span class="meta-item"><i class="pi pi-arrow-right" /> {{ isVi ? 'Bước' : 'Step' }} {{ req.current_step }}</span>
            <span class="meta-item" v-if="req.requester"><i class="pi pi-user" /> {{ req.requester.first_name }} {{ req.requester.last_name }}</span>
            <span class="meta-item"><i class="pi pi-clock" /> {{ timeAgo(req.created_at) }}</span>
          </div>
          <div class="req-actions" v-if="req.status === 'pending'">
            <Button :label="isVi ? 'Duyệt' : 'Approve'" icon="pi pi-check" size="small" severity="success" @click="approve(req)" />
            <Button :label="isVi ? 'Từ chối' : 'Reject'" icon="pi pi-times" size="small" severity="danger" outlined @click="reject(req)" />
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'
export default {
  components: { Head, Button },
  layout: Layout,
  props: { requests: Array, workflows: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() { return { activeTab: 'pending' } },
  computed: {
    isVi() { return this.locale === 'vi' },
    pendingCount() { return (this.requests || []).filter(r => r.status === 'pending').length },
    filteredRequests() {
      if (!this.requests) return []
      if (this.activeTab === 'pending') return this.requests.filter(r => r.status === 'pending')
      return this.requests
    },
  },
  methods: {
    statusIcon(s) { return { pending: 'pi pi-clock', approved: 'pi pi-check-circle', rejected: 'pi pi-times-circle', cancelled: 'pi pi-ban' }[s] || 'pi pi-circle' },
    statusLabel(s) { const m = { pending: this.isVi ? 'Chờ duyệt' : 'Pending', approved: this.isVi ? 'Đã duyệt' : 'Approved', rejected: this.isVi ? 'Từ chối' : 'Rejected', cancelled: this.isVi ? 'Đã huỷ' : 'Cancelled' }; return m[s] || s },
    timeAgo(d) { if (!d) return ''; const now = new Date(), then = new Date(d), diff = Math.floor((now - then) / 60000); if (diff < 60) return `${diff}m`; if (diff < 1440) return `${Math.floor(diff / 60)}h`; return `${Math.floor(diff / 1440)}d` },
    approve(req) { router.post(`/approvals/${req.id}/approve`) },
    reject(req) { if (confirm(this.isVi ? 'Xác nhận từ chối?' : 'Confirm rejection?')) router.post(`/approvals/${req.id}/reject`) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.page-header-left { display: flex; align-items: center; gap: 0.75rem; }
.header-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #059669, #10b981); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.15rem; }
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-tabs { display: flex; gap: 0.25rem; background: #f1f5f9; padding: 0.2rem; border-radius: 10px; }
.tab-btn { border: none; background: transparent; padding: 0.45rem 1rem; border-radius: 8px; font-size: 0.78rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.35rem; }
.tab-btn.active { background: white; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.tab-count { background: #ef4444; color: white; font-size: 0.6rem; padding: 0.1rem 0.35rem; border-radius: 10px; min-width: 18px; text-align: center; }

.request-list { display: flex; flex-direction: column; gap: 0.75rem; }
.request-card { background: white; border: 1px solid #f1f5f9; border-radius: 14px; overflow: hidden; transition: all 0.25s; }
.request-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
.req-status-bar { height: 3px; }
.bar-pending { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.bar-approved { background: linear-gradient(90deg, #10b981, #34d399); }
.bar-rejected { background: linear-gradient(90deg, #ef4444, #f87171); }
.bar-cancelled { background: #e2e8f0; }
.req-body { padding: 1.15rem 1.35rem; }
.req-header { display: flex; align-items: flex-start; gap: 0.75rem; }
.req-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.95rem; }
.icon-pending { background: #fffbeb; color: #d97706; }
.icon-approved { background: #dcfce7; color: #16a34a; }
.icon-rejected { background: #fee2e2; color: #dc2626; }
.icon-cancelled { background: #f1f5f9; color: #94a3b8; }
.req-info { flex: 1; min-width: 0; }
.req-title { font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0; }
.req-desc { font-size: 0.78rem; color: #64748b; margin: 0.2rem 0 0; }
.req-status-badge { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 20px; display: flex; align-items: center; gap: 0.25rem; white-space: nowrap; }
.req-status-badge i { font-size: 0.58rem; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #dcfce7; color: #166534; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-cancelled { background: #f1f5f9; color: #64748b; }
.req-meta { display: flex; gap: 1rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f8fafc; flex-wrap: wrap; }
.meta-item { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem; }
.meta-item i { font-size: 0.62rem; }
.req-actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; }

.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 2px dashed #e2e8f0; }
.empty-illustration { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #dcfce7, #bbf7d0); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-illustration i { font-size: 1.75rem; color: #10b981; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

.list-enter-active { animation: slideIn 0.3s ease; }
@keyframes slideIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
