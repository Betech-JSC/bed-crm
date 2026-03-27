<template>
  <div>
    <Head title="Customer Reviews" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-star-fill" style="color:#f59e0b;" /> Customer Reviews</h1>
        <p class="page-subtitle">Quản lý đánh giá từ khách hàng — tạo social proof cho doanh nghiệp</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Thêm Review</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-star" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Tổng Reviews</span></div></div>
      <div class="stat-card"><div class="stat-icon si-avg"><i class="pi pi-star-fill" /></div><div class="stat-body"><span class="stat-val stat-gold">{{ stats.avg_rating }}</span><span class="stat-lbl">Trung bình</span></div></div>
      <div class="stat-card"><div class="stat-icon si-approved"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.approved }}</span><span class="stat-lbl">Approved</span></div></div>
      <div class="stat-card"><div class="stat-icon si-featured"><i class="pi pi-crown" /></div><div class="stat-body"><span class="stat-val">{{ stats.featured }}</span><span class="stat-lbl">Featured</span></div></div>
      <div class="stat-card"><div class="stat-icon si-pending"><i class="pi pi-clock" /></div><div class="stat-body"><span class="stat-val">{{ stats.pending }}</span><span class="stat-lbl">Pending</span></div></div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm review..." @input="doSearch" /></div>
      <select v-model="filterStatus" class="filter-select" @change="doSearch"><option value="">Status</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="featured">Featured</option><option value="rejected">Rejected</option></select>
      <select v-model="filterPlatform" class="filter-select" @change="doSearch"><option value="">Platform</option><option v-for="(info, key) in platforms" :key="key" :value="key">{{ info.label }}</option></select>
      <div class="filter-stars">
        <button v-for="s in [5,4,3,2,1]" :key="s" class="star-btn" :class="{ active: filterRating == s }" @click="filterRating = filterRating == s ? '' : s; doSearch()">{{ s }}★</button>
      </div>
    </div>

    <!-- Reviews Grid -->
    <div v-if="reviews.data?.length" class="reviews-grid">
      <div v-for="review in reviews.data" :key="review.id" class="review-card" :class="{ 'rc-featured': review.status === 'featured' }">
        <div class="rc-header">
          <div class="rc-stars">
            <span v-for="s in 5" :key="s" class="star" :class="{ filled: s <= review.rating }">★</span>
          </div>
          <div class="rc-actions">
            <select :value="review.status" class="st-select" @change="changeStatus(review.id, $event.target.value)">
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="featured">Featured</option>
              <option value="rejected">Rejected</option>
            </select>
            <button class="act-del" @click="deleteReview(review.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
        <p class="rc-text">"{{ review.review_text }}"</p>
        <div class="rc-customer">
          <div class="rc-avatar">{{ review.customer_name.charAt(0) }}</div>
          <div class="rc-info">
            <span class="rc-name">{{ review.customer_name }}</span>
            <span class="rc-role">{{ review.customer_role }}{{ review.customer_company ? ` · ${review.customer_company}` : '' }}</span>
          </div>
        </div>
        <div class="rc-footer">
          <span class="rc-platform" :style="{ color: platforms[review.platform]?.color }"><i :class="platforms[review.platform]?.icon" /> {{ platforms[review.platform]?.label }}</span>
          <span v-if="review.service_type" class="rc-service">{{ serviceTypes[review.service_type] }}</span>
          <span class="rc-date">{{ review.review_date || review.created_at }}</span>
        </div>
      </div>
    </div>
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-star" /></div>
      <h3>Chưa có review</h3>
      <p>Thu thập và hiển thị đánh giá từ khách hàng</p>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Thêm Review</h3><button class="modal-close" @click="showCreate = false"><i class="pi pi-times" /></button></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Tên khách hàng <span class="req">*</span></label><input v-model="form.customer_name" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Email</label><input v-model="form.customer_email" type="email" class="fm-input" /></div>
        </div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Công ty</label><input v-model="form.customer_company" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Chức vụ</label><input v-model="form.customer_role" type="text" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Đánh giá <span class="req">*</span></label><textarea v-model="form.review_text" rows="3" class="fm-input" placeholder="Nội dung đánh giá..." /></div>
        <div class="fm-row">
          <div class="fm-group">
            <label>Rating</label>
            <div class="rating-pick">
              <button v-for="s in [1,2,3,4,5]" :key="s" class="rate-btn" :class="{ active: form.rating >= s }" @click="form.rating = s">★</button>
            </div>
          </div>
          <div class="fm-group flex-1"><label>Platform</label>
            <select v-model="form.platform" class="fm-input"><option v-for="(info, key) in platforms" :key="key" :value="key">{{ info.label }}</option></select>
          </div>
          <div class="fm-group flex-1"><label>Dịch vụ</label>
            <select v-model="form.service_type" class="fm-input"><option value="">—</option><option v-for="(label, key) in serviceTypes" :key="key" :value="key">{{ label }}</option></select>
          </div>
        </div>
        <div class="fm-group"><label>Video URL</label><input v-model="form.video_url" type="url" class="fm-input" placeholder="https://youtube.com/..." /></div>
        <button class="btn-save" :disabled="!form.customer_name || !form.review_text || saving" @click="saveReview">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Thêm
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { reviews: Object, stats: Object, platforms: Object, serviceTypes: Object, filters: Object },
  data() {
    return {
      search: this.filters?.search || '', filterStatus: this.filters?.status || '',
      filterPlatform: this.filters?.platform || '', filterRating: this.filters?.rating || '',
      searchTimeout: null, showCreate: false, saving: false,
      form: { customer_name: '', customer_email: '', customer_company: '', customer_role: '', review_text: '', rating: 5, platform: 'direct', service_type: '', video_url: '' },
    }
  },
  methods: {
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/customer-reviews', { search: this.search || undefined, status: this.filterStatus || undefined, platform: this.filterPlatform || undefined, rating: this.filterRating || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    saveReview() {
      this.saving = true
      router.post('/customer-reviews', this.form, {
        onSuccess: () => { this.form = { customer_name: '', customer_email: '', customer_company: '', customer_role: '', review_text: '', rating: 5, platform: 'direct', service_type: '', video_url: '' }; this.showCreate = false },
        onFinish: () => { this.saving = false },
      })
    },
    changeStatus(id, status) { router.post(`/customer-reviews/${id}/status`, { status }) },
    deleteReview(id) { if (confirm('Xóa review?')) router.delete(`/customer-reviews/${id}`) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #fef3c7; color: #f59e0b; }
.si-avg { background: #fff7ed; color: #ea580c; }
.si-approved { background: #ecfdf5; color: #10b981; }
.si-featured { background: #ede9fe; color: #8b5cf6; }
.si-pending { background: #eef2ff; color: #6366f1; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-gold { color: #f59e0b; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; }

.filter-bar { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; flex-wrap: wrap; align-items: center; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 240px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.68rem; color: #475569; font-family: inherit; outline: none; }
.filter-stars { display: flex; gap: 0.2rem; }
.star-btn { padding: 0.2rem 0.4rem; border-radius: 5px; border: 1px solid #e2e8f0; background: white; font-size: 0.6rem; cursor: pointer; color: #94a3b8; font-family: inherit; }
.star-btn.active { background: #fef3c7; border-color: #f59e0b; color: #f59e0b; }

/* Review Cards */
.reviews-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 0.65rem; }
.review-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; transition: all 0.15s; }
.review-card:hover { border-color: #f59e0b; box-shadow: 0 4px 14px rgba(245,158,11,0.08); }
.rc-featured { border-color: #f59e0b; background: linear-gradient(135deg, #fffbeb, #ffffff); }
.rc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.rc-stars .star { font-size: 0.72rem; color: #e2e8f0; }
.rc-stars .star.filled { color: #f59e0b; }
.rc-actions { display: flex; gap: 0.2rem; align-items: center; }
.st-select { padding: 0.1rem 0.2rem; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.5rem; color: #64748b; font-family: inherit; outline: none; }
.act-del { width: 22px; height: 22px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.5rem; }
.act-del:hover { color: #ef4444; }
.rc-text { font-size: 0.78rem; color: #475569; line-height: 1.5; font-style: italic; margin: 0 0 0.6rem; }
.rc-customer { display: flex; gap: 0.4rem; align-items: center; margin-bottom: 0.5rem; }
.rc-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; flex-shrink: 0; }
.rc-name { font-size: 0.72rem; font-weight: 700; color: #0f172a; display: block; }
.rc-role { font-size: 0.58rem; color: #94a3b8; }
.rc-footer { display: flex; gap: 0.5rem; align-items: center; font-size: 0.55rem; }
.rc-platform { font-weight: 600; display: flex; align-items: center; gap: 0.15rem; }
.rc-platform i { font-size: 0.5rem; }
.rc-service { padding: 0.08rem 0.25rem; border-radius: 3px; background: #f1f5f9; color: #64748b; font-weight: 600; }
.rc-date { color: #cbd5e1; margin-left: auto; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.rating-pick { display: flex; gap: 0.15rem; }
.rate-btn { width: 28px; height: 28px; border: none; background: transparent; font-size: 1.1rem; color: #e2e8f0; cursor: pointer; }
.rate-btn.active { color: #f59e0b; }
.btn-save { width: 100%; padding: 0.5rem; border-radius: 9px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 0.3rem; margin-top: 0.5rem; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

.empty-state { text-align: center; padding: 2.5rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #fef3c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #f59e0b; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }

@media (max-width: 768px) { .stats-row { grid-template-columns: repeat(2, 1fr); } .reviews-grid { grid-template-columns: 1fr; } }
</style>
