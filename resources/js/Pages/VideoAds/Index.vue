<template>
  <div>
    <Head title="Video Ads AI" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-video" /></div>
        <div>
          <h1 class="page-title">Video Ads AI</h1>
          <p class="page-subtitle">Tạo video quảng cáo bán hàng với AI Agent</p>
        </div>
      </div>
      <div class="header-right">
        <Link href="/video-ads/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Video mới</button></Link>
      </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-card"><div class="stat-icon all"><i class="pi pi-film" /></div><div><span class="stat-val">{{ stats.total }}</span><span class="stat-label">Tổng dự án</span></div></div>
      <div class="stat-card"><div class="stat-icon draft"><i class="pi pi-file" /></div><div><span class="stat-val">{{ stats.draft }}</span><span class="stat-label">Bản nháp</span></div></div>
      <div class="stat-card"><div class="stat-icon prod"><i class="pi pi-cog" /></div><div><span class="stat-val">{{ stats.producing }}</span><span class="stat-label">Đang sản xuất</span></div></div>
      <div class="stat-card"><div class="stat-icon pub"><i class="pi pi-check-circle" /></div><div><span class="stat-val">{{ stats.published }}</span><span class="stat-label">Đã đăng</span></div></div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="searchQuery" type="text" placeholder="Tìm kiếm video..." @input="debouncedSearch" />
      </div>
      <div class="filter-group">
        <select v-model="filterStatus" @change="applyFilters" class="filter-select">
          <option value="">Tất cả trạng thái</option>
          <option v-for="(info, key) in statuses" :key="key" :value="key">{{ info.label }}</option>
        </select>
        <select v-model="filterType" @change="applyFilters" class="filter-select">
          <option value="">Tất cả loại</option>
          <option v-for="(info, key) in videoTypes" :key="key" :value="key">{{ info.icon }} {{ info.label }}</option>
        </select>
      </div>
    </div>

    <!-- Project Grid -->
    <div v-if="projects.data.length" class="project-grid">
      <div v-for="project in projects.data" :key="project.id" class="project-card">
        <!-- Thumbnail / Preview -->
        <div class="card-visual" :class="project.video_type">
          <div class="visual-type-icon">{{ getTypeIcon(project.video_type) }}</div>
          <div class="visual-platforms">
            <span v-for="p in (project.target_platforms || [])" :key="p" class="platform-dot" :style="{ background: getPlatformColor(p) }" :title="p" />
          </div>
          <div class="visual-duration" v-if="project.duration_seconds">{{ project.duration_seconds }}s</div>
        </div>

        <!-- Info -->
        <div class="card-body">
          <Link :href="`/video-ads/${project.id}/edit`" class="card-title-link">
            <h3 class="card-title">{{ project.title }}</h3>
          </Link>
          <p v-if="project.product_name" class="card-product">
            <i class="pi pi-tag" /> {{ project.product_name }}
            <span v-if="project.product_price" class="product-price">{{ formatCurrency(project.product_price) }}</span>
          </p>
          <p v-if="project.description" class="card-desc">{{ truncate(project.description, 80) }}</p>

          <div class="card-meta">
            <span class="status-badge" :class="project.status">
              <i :class="project.status_info.icon" /> {{ project.status_info.label }}
            </span>
            <span v-if="project.scene_count" class="scene-count"><i class="pi pi-images" /> {{ project.scene_count }} cảnh</span>
          </div>

          <div class="card-footer">
            <div class="card-creator" v-if="project.creator">
              <div class="creator-avatar">{{ initials(project.creator.name) }}</div>
              <span>{{ project.updated_at }}</span>
            </div>
            <div class="card-actions">
              <Link :href="`/video-ads/${project.id}/edit`"><button class="action-btn"><i class="pi pi-pencil" /></button></Link>
              <button class="action-btn danger" @click="deleteProject(project.id)"><i class="pi pi-trash" /></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-video" /></div>
      <h3>Chưa có dự án video nào</h3>
      <p>Tạo video quảng cáo đầu tiên với AI Agent — chỉ cần nhập thông tin sản phẩm và AI sẽ viết kịch bản cho bạn!</p>
      <Link href="/video-ads/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Video đầu tiên</button></Link>
    </div>

    <!-- Pagination -->
    <div v-if="projects.links && projects.links.length > 3" class="pagination">
      <Link
        v-for="link in projects.links" :key="link.label"
        :href="link.url || '#'"
        class="page-link" :class="{ active: link.active, disabled: !link.url }"
        v-html="link.label"
        preserve-state
      />
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

let searchTimer = null

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    projects: Object,
    filters: Object,
    stats: Object,
    statuses: Object,
    videoTypes: Object,
    platforms: Object,
  },
  data() {
    return {
      searchQuery: this.filters?.search || '',
      filterStatus: this.filters?.status || '',
      filterType: this.filters?.video_type || '',
    }
  },
  methods: {
    debouncedSearch() {
      clearTimeout(searchTimer)
      searchTimer = setTimeout(() => this.applyFilters(), 350)
    },
    applyFilters() {
      router.get('/video-ads', {
        search: this.searchQuery || undefined,
        status: this.filterStatus || undefined,
        video_type: this.filterType || undefined,
      }, { preserveState: true, replace: true })
    },
    getTypeIcon(type) {
      return { product: '🛍️', testimonial: '⭐', tutorial: '📖', promo: '🔥', story: '📺', ugc: '📱' }[type] || '🎬'
    },
    getPlatformColor(p) {
      return { tiktok: '#010101', facebook: '#1877f2', instagram: '#e4405f', youtube: '#ff0000', zalo: '#0068ff' }[p] || '#94a3b8'
    },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    truncate(str, len) { return str?.length > len ? str.slice(0, len) + '...' : str },
    initials(n) { if (!n) return '?'; return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    deleteProject(id) {
      if (!confirm('Xóa dự án video này?')) return
      router.delete(`/video-ads/${id}`)
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-right { display: flex; gap: 0.5rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #f43f5e, #e11d48); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(244,63,94,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(244,63,94,0.3); }

/* Stats */
.stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.65rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 12px; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
.stat-icon.all { background: #fef2f2; color: #f43f5e; }
.stat-icon.draft { background: #f8fafc; color: #64748b; }
.stat-icon.prod { background: #fffbeb; color: #f59e0b; }
.stat-icon.pub { background: #ecfdf5; color: #10b981; }
.stat-val { display: block; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.stat-label { font-size: 0.62rem; color: #94a3b8; }

/* Filters */
.filter-bar { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; flex: 1; max-width: 320px; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-box input { border: none; outline: none; font-size: 0.82rem; color: #1e293b; flex: 1; background: transparent; }
.filter-group { display: flex; gap: 0.4rem; }
.filter-select { padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.78rem; color: #475569; background: white; cursor: pointer; outline: none; }
.filter-select:focus { border-color: #f43f5e; }

/* Grid */
.project-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 0.85rem; }
.project-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; overflow: hidden; transition: all 0.2s; }
.project-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); border-color: #e2e8f0; transform: translateY(-2px); }

.card-visual { height: 100px; background: linear-gradient(135deg, #1e1b4b, #312e81); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.card-visual.product { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.card-visual.promo { background: linear-gradient(135deg, #f43f5e, #e11d48); }
.card-visual.tutorial { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
.card-visual.story { background: linear-gradient(135deg, #f59e0b, #d97706); }
.card-visual.ugc { background: linear-gradient(135deg, #10b981, #059669); }
.card-visual.testimonial { background: linear-gradient(135deg, #ec4899, #db2777); }

.visual-type-icon { font-size: 2rem; opacity: 0.9; }
.visual-platforms { position: absolute; top: 0.5rem; right: 0.5rem; display: flex; gap: 0.2rem; }
.platform-dot { width: 10px; height: 10px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.5); }
.visual-duration { position: absolute; bottom: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.5); color: white; font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 6px; }

.card-body { padding: 0.85rem; }
.card-title-link { text-decoration: none; }
.card-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; line-height: 1.3; }
.card-title-link:hover .card-title { color: #f43f5e; }
.card-product { font-size: 0.68rem; color: #64748b; margin: 0 0 0.25rem; display: flex; align-items: center; gap: 0.25rem; }
.card-product i { font-size: 0.6rem; color: #f43f5e; }
.product-price { font-weight: 700; color: #10b981; margin-left: 0.3rem; }
.card-desc { font-size: 0.68rem; color: #94a3b8; margin: 0 0 0.5rem; line-height: 1.4; }

.card-meta { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
.status-badge { font-size: 0.58rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.2rem; }
.status-badge i { font-size: 0.5rem; }
.status-badge.draft { background: #f8fafc; color: #64748b; }
.status-badge.scripting { background: #eff6ff; color: #3b82f6; }
.status-badge.producing { background: #fffbeb; color: #f59e0b; }
.status-badge.review { background: #faf5ff; color: #8b5cf6; }
.status-badge.published { background: #ecfdf5; color: #10b981; }
.status-badge.archived { background: #f1f5f9; color: #94a3b8; }
.scene-count { font-size: 0.6rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.scene-count i { font-size: 0.55rem; }

.card-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 0.45rem; border-top: 1px solid #f8fafc; }
.card-creator { display: flex; align-items: center; gap: 0.3rem; font-size: 0.62rem; color: #94a3b8; }
.creator-avatar { width: 18px; height: 18px; border-radius: 5px; background: #fef2f2; color: #f43f5e; font-size: 0.45rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.card-actions { display: flex; gap: 0.2rem; }
.action-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid #f1f5f9; background: white; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.action-btn i { font-size: 0.65rem; }
.action-btn:hover { border-color: #f43f5e; color: #f43f5e; }
.action-btn.danger:hover { background: #fef2f2; }

/* Empty */
.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 14px; border: 1.5px dashed #e2e8f0; }
.empty-icon { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #fef2f2, #fce7f3); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-icon i { font-size: 1.5rem; color: #f43f5e; }
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.4rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1rem; max-width: 420px; margin-left: auto; margin-right: auto; }

/* Pagination */
.pagination { display: flex; gap: 0.25rem; justify-content: center; margin-top: 1.25rem; }
.page-link { padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.72rem; color: #64748b; border: 1px solid #e2e8f0; background: white; text-decoration: none; transition: all 0.15s; }
.page-link.active { background: #f43f5e; color: white; border-color: #f43f5e; }
.page-link.disabled { opacity: 0.4; pointer-events: none; }
.page-link:hover:not(.active):not(.disabled) { border-color: #f43f5e; color: #f43f5e; }

@media (max-width: 768px) {
  .stats-bar { grid-template-columns: repeat(2, 1fr); }
  .filter-bar { flex-direction: column; }
  .search-box { max-width: 100%; }
}
</style>
