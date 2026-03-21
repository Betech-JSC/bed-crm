<template>
  <div>
    <Head title="Đề xuất báo giá" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-file-edit" style="color: #6366f1; margin-right: 0.5rem;" />
          {{ t('common.proposals') }}
        </h1>
        <p class="page-subtitle">Quản lý đề xuất & báo giá</p>
      </div>
      <Link href="/proposals/create">
        <Button label="Tạo báo giá" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card stat-card--purple">
        <div class="stat-card-icon"><i class="pi pi-file-edit" /></div>
        <div class="stat-card-content">
          <span class="stat-value">{{ proposalStats.total }}</span>
          <span class="stat-label">Tổng báo giá</span>
        </div>
      </div>
      <div class="stat-card stat-card--blue">
        <div class="stat-card-icon"><i class="pi pi-send" /></div>
        <div class="stat-card-content">
          <span class="stat-value">{{ proposalStats.sent }}</span>
          <span class="stat-label">Đã gửi</span>
        </div>
      </div>
      <div class="stat-card stat-card--green">
        <div class="stat-card-icon"><i class="pi pi-check-circle" /></div>
        <div class="stat-card-content">
          <span class="stat-value">{{ proposalStats.accepted }}</span>
          <span class="stat-label">Đã duyệt</span>
        </div>
      </div>
      <div class="stat-card stat-card--amber">
        <div class="stat-card-icon"><i class="pi pi-dollar" /></div>
        <div class="stat-card-content">
          <span class="stat-value">{{ formatCurrencyShort(proposalStats.totalAmount) }}</span>
          <span class="stat-label">Tổng giá trị</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrapper">
        <i class="pi pi-search search-icon" />
        <InputText v-model="form.search" placeholder="Tìm kiếm báo giá..." class="search-input" @input="handleSearch" />
      </div>
      <Select
        v-model="form.status"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Trạng thái"
        class="filter-select"
        :showClear="true"
        @change="handleFilter"
      />
      <Select
        v-model="form.deal_id"
        :options="dealOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Deal"
        class="filter-select"
        :showClear="true"
        @change="handleFilter"
      />
      <Button
        v-if="hasActiveFilters"
        icon="pi pi-times"
        label="Xoá lọc"
        severity="secondary"
        text
        size="small"
        @click="resetFilters"
      />
    </div>

    <!-- Proposals List -->
    <div v-if="proposals.data && proposals.data.length" class="proposals-list">
      <div
        v-for="proposal in proposals.data"
        :key="proposal.id"
        class="proposal-card"
        @click="goToProposal(proposal)"
      >
        <!-- Status Indicator -->
        <div class="proposal-status-bar" :style="{ background: getStatusColor(proposal.status) }" />

        <div class="proposal-card-body">
          <!-- Header Row -->
          <div class="proposal-header">
            <div class="proposal-title-group">
              <h3 class="proposal-title">{{ proposal.title }}</h3>
              <span v-if="proposal.version > 1" class="version-badge">v{{ proposal.version }}</span>
            </div>
            <div class="proposal-status-tag" :class="`status--${proposal.status}`">
              <i :class="getStatusIcon(proposal.status)" />
              {{ proposal.status_label }}
            </div>
          </div>

          <!-- Info Row -->
          <div class="proposal-info">
            <!-- Deal -->
            <div class="info-item" v-if="proposal.deal">
              <i class="pi pi-briefcase" />
              <span>{{ proposal.deal.title }}</span>
            </div>

            <!-- Amount -->
            <div class="info-item info-item--amount" v-if="proposal.amount">
              <i class="pi pi-dollar" />
              <span>{{ formatCurrency(proposal.amount) }}</span>
            </div>

            <!-- Creator -->
            <div class="info-item" v-if="proposal.creator">
              <i class="pi pi-user" />
              <span>{{ proposal.creator.name }}</span>
            </div>

            <!-- Date -->
            <div class="info-item">
              <i class="pi pi-calendar" />
              <span>{{ formatDate(proposal.created_at) }}</span>
            </div>
          </div>

          <!-- Tracking Row -->
          <div class="proposal-tracking" v-if="proposal.sent_at || proposal.viewed_at || proposal.accepted_at">
            <div class="tracking-step" :class="{ active: proposal.sent_at }">
              <div class="tracking-dot" />
              <span>Gửi</span>
              <span class="tracking-date" v-if="proposal.sent_at">{{ formatShortDate(proposal.sent_at) }}</span>
            </div>
            <div class="tracking-line" :class="{ active: proposal.viewed_at }" />
            <div class="tracking-step" :class="{ active: proposal.viewed_at }">
              <div class="tracking-dot" />
              <span>Xem</span>
              <span class="tracking-date" v-if="proposal.viewed_at">
                {{ proposal.view_count }}x
              </span>
            </div>
            <div class="tracking-line" :class="{ active: proposal.accepted_at || proposal.rejected_at }" />
            <div class="tracking-step" :class="{ active: proposal.accepted_at, rejected: proposal.rejected_at }">
              <div class="tracking-dot" />
              <span>{{ proposal.rejected_at ? 'Từ chối' : 'Duyệt' }}</span>
            </div>
          </div>
        </div>

        <!-- Arrow -->
        <div class="proposal-arrow">
          <i class="pi pi-chevron-right" />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-illustration">
        <i class="pi pi-file-edit" />
      </div>
      <h3>Chưa có đề xuất báo giá</h3>
      <p>Tạo báo giá đầu tiên để bắt đầu</p>
      <Link href="/proposals/create">
        <Button label="Tạo báo giá" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Pagination -->
    <div v-if="proposals.total > 0" class="pagination-wrapper">
      <Paginator
        :first="(proposals.current_page - 1) * proposals.per_page"
        :rows="proposals.per_page"
        :totalRecords="proposals.total"
        @page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import throttle from 'lodash/throttle'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, InputText, Select, Paginator },
  layout: Layout,
  props: {
    filters: Object,
    proposals: Object,
    statuses: Object,
    deals: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: {
        search: this.filters?.search || '',
        status: this.filters?.status || null,
        deal_id: this.filters?.deal_id || null,
      },
    }
  },
  computed: {
    statusOptions() {
      return [
        { label: 'Tất cả trạng thái', value: null },
        ...Object.entries(this.statuses).map(([value, label]) => ({ label, value })),
      ]
    },
    dealOptions() {
      return [
        { label: 'Tất cả deal', value: null },
        ...this.deals.map(deal => ({ label: deal.title, value: deal.id })),
      ]
    },
    hasActiveFilters() {
      return this.form.search || this.form.status || this.form.deal_id
    },
    proposalStats() {
      const data = this.proposals.data || []
      return {
        total: this.proposals.total || data.length,
        sent: data.filter(p => p.sent_at).length,
        accepted: data.filter(p => p.accepted_at).length,
        totalAmount: data.reduce((sum, p) => sum + (p.amount || 0), 0),
      }
    },
  },
  methods: {
    handleSearch: throttle(function () {
      this.handleFilter()
    }, 300),
    handleFilter() {
      const params = {}
      if (this.form.search) params.search = this.form.search
      if (this.form.status) params.status = this.form.status
      if (this.form.deal_id) params.deal_id = this.form.deal_id
      router.get('/proposals', params, { preserveState: true })
    },
    resetFilters() {
      this.form = { search: '', status: null, deal_id: null }
      router.get('/proposals', {}, { preserveState: true })
    },
    goToProposal(proposal) {
      router.visit(`/proposals/${proposal.id}`)
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
    },
    formatCurrencyShort(value) {
      if (!value) return '0'
      if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + ' tỷ'
      if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + ' tr'
      if (value >= 1_000) return (value / 1_000).toFixed(0) + 'k'
      return value.toString()
    },
    formatDate(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('vi-VN', {
        year: 'numeric', month: 'short', day: 'numeric',
      })
    },
    formatShortDate(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short' })
    },
    getStatusColor(status) {
      const map = { draft: '#94a3b8', sent: '#3b82f6', viewed: '#f59e0b', accepted: '#10b981', rejected: '#ef4444' }
      return map[status] || '#94a3b8'
    },
    getStatusIcon(status) {
      const map = {
        draft: 'pi pi-pencil', sent: 'pi pi-send', viewed: 'pi pi-eye',
        accepted: 'pi pi-check-circle', rejected: 'pi pi-times-circle',
      }
      return map[status] || 'pi pi-file'
    },
    onPageChange(event) {
      const page = event.page + 1
      router.get('/proposals', { ...this.form, page }, { preserveState: true })
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Stats Grid ===== */
.stats-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem;
}
.stat-card {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.85rem 1rem; border-radius: 12px;
  background: white; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s;
}
.stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

.stat-card-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
}
.stat-card--purple .stat-card-icon { background: #eef2ff; color: #6366f1; }
.stat-card--blue .stat-card-icon { background: #eff6ff; color: #3b82f6; }
.stat-card--green .stat-card-icon { background: #ecfdf5; color: #10b981; }
.stat-card--amber .stat-card-icon { background: #fffbeb; color: #f59e0b; }

.stat-card-content { display: flex; flex-direction: column; }
.stat-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.stat-label { font-size: 0.68rem; color: #94a3b8; font-weight: 500; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.85rem 1rem; background: white; border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem;
}
.search-wrapper { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem; }
.search-input { width: 100%; padding-left: 2.2rem !important; font-size: 0.82rem !important; border-radius: 8px !important; }
.filter-select { min-width: 150px; }

/* ===== Proposals List ===== */
.proposals-list {
  display: flex; flex-direction: column; gap: 0.55rem;
}

.proposal-card {
  display: flex; align-items: stretch;
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
  cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.proposal-card:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,0.07); border-color: #e2e8f0;
  transform: translateY(-1px);
}

.proposal-status-bar {
  width: 4px; flex-shrink: 0;
}

.proposal-card-body {
  flex: 1; padding: 0.85rem 1rem;
  display: flex; flex-direction: column; gap: 0.55rem;
  min-width: 0;
}

.proposal-header {
  display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
}
.proposal-title-group { display: flex; align-items: center; gap: 0.4rem; min-width: 0; }
.proposal-title {
  font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.proposal-card:hover .proposal-title { color: #6366f1; }
.version-badge {
  font-size: 0.58rem; font-weight: 700; color: #6366f1;
  background: #eef2ff; padding: 0.12rem 0.35rem; border-radius: 4px;
  flex-shrink: 0;
}

.proposal-status-tag {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.65rem; font-weight: 600; padding: 0.22rem 0.55rem;
  border-radius: 6px; flex-shrink: 0;
}
.proposal-status-tag i { font-size: 0.58rem; }
.status--draft { background: #f1f5f9; color: #64748b; }
.status--sent { background: #eff6ff; color: #3b82f6; }
.status--viewed { background: #fffbeb; color: #d97706; }
.status--accepted { background: #ecfdf5; color: #059669; }
.status--rejected { background: #fef2f2; color: #dc2626; }

/* Info Row */
.proposal-info {
  display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;
}
.info-item {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.72rem; color: #64748b;
}
.info-item i { font-size: 0.62rem; color: #94a3b8; }
.info-item--amount { font-weight: 700; color: #0f172a; }
.info-item--amount i { color: #10b981; }

/* Tracking Pipeline */
.proposal-tracking {
  display: flex; align-items: center; gap: 0;
  padding-top: 0.35rem;
}
.tracking-step {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.62rem; color: #cbd5e1;
}
.tracking-step.active { color: #10b981; }
.tracking-step.rejected { color: #ef4444; }
.tracking-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #e2e8f0; flex-shrink: 0;
}
.tracking-step.active .tracking-dot { background: #10b981; }
.tracking-step.rejected .tracking-dot { background: #ef4444; }
.tracking-date { color: #94a3b8; font-size: 0.58rem; }

.tracking-line {
  width: 28px; height: 2px; background: #e2e8f0;
  margin: 0 0.2rem;
}
.tracking-line.active { background: #10b981; }

/* Arrow */
.proposal-arrow {
  display: flex; align-items: center; padding: 0 0.85rem;
  color: #cbd5e1; font-size: 0.78rem; transition: all 0.2s;
}
.proposal-card:hover .proposal-arrow { color: #6366f1; }

/* ===== Empty State ===== */
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.65rem;
  padding: 4rem 2rem; color: #94a3b8;
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
}
.empty-illustration {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
}
.empty-illustration i { font-size: 1.8rem; color: #6366f1; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
.empty-state p { font-size: 0.82rem; margin: 0; }

/* ===== Pagination ===== */
.pagination-wrapper {
  display: flex; align-items: center; justify-content: center;
  padding: 0.85rem; margin-top: 1rem;
  background: white; border-radius: 12px; border: 1px solid #f1f5f9;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr; }
  .filter-bar { flex-wrap: wrap; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .proposal-info { flex-direction: column; align-items: flex-start; }
  .proposal-tracking { display: none; }
}
</style>
