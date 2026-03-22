<template>
  <div>
    <Head title="Báo giá" />
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-file-edit" /></div>
        <div><h1 class="page-title">Quản lý báo giá</h1><p class="page-subtitle">{{ stats.total }} báo giá — Tổng giá trị: {{ formatPrice(stats.total_value) }}</p></div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip draft"><i class="pi pi-pencil" /> {{ stats.draft }} nháp</span>
          <span class="stat-chip pending"><i class="pi pi-clock" /> {{ stats.pending }} chờ duyệt</span>
          <span class="stat-chip accepted"><i class="pi pi-check" /> {{ stats.accepted }} chấp nhận</span>
        </div>
        <Link href="/quotations/create"><Button label="Tạo báo giá" icon="pi pi-plus" /></Link>
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-box"><i class="pi pi-search" /><input v-model="form.search" placeholder="Tìm theo mã, tiêu đề..." class="search-input" /></div>
      <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Trạng thái" showClear class="filter-select" />
    </div>

    <div v-if="quotations.data.length" class="quote-list">
      <Link v-for="q in quotations.data" :key="q.id" :href="`/quotations/${q.id}/edit`" class="quote-card">
        <div class="quote-status-dot" :class="`dot-${q.status}`" />
        <div class="quote-info">
          <div class="quote-top">
            <span class="quote-number">{{ q.quote_number }}</span>
            <span class="status-badge" :class="`s-${q.status}`">{{ q.status_label }}</span>
            <span v-if="q.is_expired" class="expired-badge">Hết hạn</span>
          </div>
          <h3 class="quote-title">{{ q.title }}</h3>
          <div class="quote-meta">
            <span><i class="pi pi-building" /> {{ q.customer_name }}</span>
            <span><i class="pi pi-user" /> {{ q.creator_name }}</span>
            <span v-if="q.valid_until"><i class="pi pi-calendar" /> HSD: {{ q.valid_until }}</span>
          </div>
        </div>
        <div class="quote-total">
          <span class="total-amount">{{ formatPrice(q.total) }}</span>
          <span class="total-date">{{ q.created_at }}</span>
        </div>
        <div class="quote-arrow"><i class="pi pi-chevron-right" /></div>
      </Link>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-file-edit" /></div>
      <h3>Chưa có báo giá</h3>
      <p>Tạo báo giá đầu tiên để gửi cho khách hàng.</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Link, Button, Select },
  layout: Layout,
  props: { quotations: Object, filters: Object, stats: Object },
  data() {
    return {
      form: { search: this.filters.search, status: this.filters.status },
      statusOptions: [
        { label: 'Nháp', value: 'draft' }, { label: 'Chờ duyệt', value: 'pending_approval' },
        { label: 'Đã duyệt', value: 'approved' }, { label: 'Đã gửi', value: 'sent' },
        { label: 'Chấp nhận', value: 'accepted' }, { label: 'Từ chối', value: 'rejected' },
      ],
    }
  },
  watch: { form: { deep: true, handler: throttle(function () { this.$inertia.get('/quotations', pickBy(this.form), { preserveState: true }) }, 300) } },
  methods: { formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' } },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.85rem}
.header-icon-wrapper{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;box-shadow:0 4px 14px rgba(59,130,246,.3)}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.header-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
.stat-chips{display:flex;gap:.4rem}.stat-chip{display:flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:20px;font-size:.65rem;font-weight:600}.stat-chip i{font-size:.58rem}
.draft{background:#f1f5f9;color:#64748b}.pending{background:#fffbeb;color:#d97706}.accepted{background:#ecfdf5;color:#059669}
.filter-bar{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:white;border:1.5px solid #e2e8f0;border-radius:14px;margin-bottom:1.25rem;flex-wrap:wrap}
.search-box{display:flex;align-items:center;flex:1;min-width:200px;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden}
.search-box:focus-within{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.08)}
.search-box i{padding:0 .6rem;color:#94a3b8;font-size:.75rem}
.search-input{flex:1;border:none;outline:none;padding:.5rem .5rem .5rem 0;font-size:.8rem;color:#1e293b;font-family:inherit}
.search-input::placeholder{color:#cbd5e1}.filter-select{min-width:140px;font-size:.8rem}
.quote-list{display:flex;flex-direction:column;gap:.5rem}
.quote-card{display:flex;align-items:center;gap:.75rem;padding:.85rem 1.15rem;background:white;border:1.5px solid #f1f5f9;border-radius:14px;text-decoration:none;color:inherit;transition:all .25s}
.quote-card:hover{border-color:#3b82f6;box-shadow:0 4px 18px rgba(59,130,246,.08);transform:translateX(2px)}
.quote-status-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.dot-draft{background:#94a3b8}.dot-pending_approval{background:#f59e0b}.dot-approved{background:#6366f1}.dot-sent{background:#3b82f6}.dot-accepted{background:#059669}.dot-rejected{background:#ef4444}.dot-expired{background:#94a3b8}
.quote-info{flex:1;min-width:0}
.quote-top{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.quote-number{font-size:.62rem;font-weight:700;color:#3b82f6;font-family:monospace}
.status-badge{font-size:.5rem;font-weight:700;padding:.08rem .35rem;border-radius:4px;text-transform:uppercase}
.s-draft{background:#f1f5f9;color:#64748b}.s-pending_approval{background:#fffbeb;color:#d97706}.s-approved{background:#eef2ff;color:#6366f1}.s-sent{background:#eff6ff;color:#3b82f6}.s-accepted{background:#ecfdf5;color:#059669}.s-rejected{background:#fef2f2;color:#ef4444}
.expired-badge{font-size:.5rem;font-weight:700;padding:.08rem .35rem;border-radius:4px;background:#fef2f2;color:#ef4444}
.quote-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:.15rem 0}
.quote-meta{display:flex;gap:.6rem;flex-wrap:wrap}
.quote-meta span{font-size:.65rem;color:#94a3b8;display:flex;align-items:center;gap:.2rem}
.quote-meta i{font-size:.55rem}
.quote-total{text-align:right;flex-shrink:0}
.total-amount{font-size:.92rem;font-weight:800;color:#1e293b;display:block}
.total-date{font-size:.6rem;color:#cbd5e1}
.quote-arrow{color:#cbd5e1;font-size:.72rem;transition:all .2s}.quote-card:hover .quote-arrow{color:#3b82f6;transform:translateX(3px)}
.empty-state{text-align:center;padding:3rem 2rem;background:white;border-radius:16px;border:2px dashed #e2e8f0}
.empty-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#3b82f6}
.empty-state h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .35rem}.empty-state p{font-size:.82rem;color:#94a3b8;margin:0}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.filter-bar{flex-direction:column}.search-box{min-width:100%}.quote-card{flex-wrap:wrap}.quote-total{margin-left:auto}}
</style>
