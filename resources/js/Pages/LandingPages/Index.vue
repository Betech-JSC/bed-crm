<template>
  <div>
    <Head title="Landing Pages" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-desktop" style="color:#8b5cf6;" /> Landing Pages</h1>
        <p class="page-subtitle">Tạo landing page chuyển đổi cao chỉ trong vài phút</p>
      </div>
      <Link href="/landing-pages/create" class="btn-add"><i class="pi pi-plus" /> Tạo Landing Page</Link>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-desktop" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Tổng Pages</span></div></div>
      <div class="stat-card"><div class="stat-icon si-pub"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.published }}</span><span class="stat-lbl">Published</span></div></div>
      <div class="stat-card"><div class="stat-icon si-visits"><i class="pi pi-eye" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_visits }}</span><span class="stat-lbl">Lượt truy cập</span></div></div>
      <div class="stat-card"><div class="stat-icon si-conv"><i class="pi pi-check" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_conversions }}</span><span class="stat-lbl">Conversions</span></div></div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm landing page..." @input="doSearch" /></div>
      <select v-model="statusFilter" class="filter-select" @change="doSearch">
        <option value="">Tất cả</option>
        <option value="draft">Draft</option>
        <option value="published">Published</option>
        <option value="archived">Archived</option>
      </select>
    </div>

    <!-- Pages Grid -->
    <div v-if="pages.data?.length" class="pages-grid">
      <div v-for="page in pages.data" :key="page.id" class="page-card">
        <div class="pc-header">
          <span class="pc-status" :class="'pcs-' + page.status">{{ page.status }}</span>
          <span class="pc-blocks">{{ page.blocks_count }} blocks</span>
        </div>
        <h3 class="pc-title">{{ page.title }}</h3>
        <div class="pc-url">{{ page.public_url }}</div>
        <div class="pc-stats">
          <div class="pcs-item"><span class="pcs-val">{{ page.visits_count }}</span><span class="pcs-lbl">Visits</span></div>
          <div class="pcs-item"><span class="pcs-val">{{ page.conversions_count }}</span><span class="pcs-lbl">Converts</span></div>
          <div class="pcs-item"><span class="pcs-val pcs-rate">{{ page.conversion_rate }}%</span><span class="pcs-lbl">Rate</span></div>
        </div>
        <div class="pc-footer">
          <span class="pc-date">{{ page.updated_at }}</span>
          <div class="pc-actions">
            <button class="pc-btn" @click="copyUrl(page.public_url)" title="Copy URL"><i class="pi pi-copy" /></button>
            <Link :href="`/landing-pages/${page.id}/edit`" class="pc-btn pc-edit" title="Sửa"><i class="pi pi-pencil" /></Link>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-desktop" /></div>
      <h3>Chưa có landing page</h3>
      <p>Tạo landing page với block builder — không cần code!</p>
      <Link href="/landing-pages/create" class="btn-add"><i class="pi pi-plus" /> Tạo Landing Page</Link>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { pages: Object, stats: Object, filters: Object },
  data() {
    return {
      search: this.filters?.search || '',
      statusFilter: this.filters?.status || '',
      searchTimeout: null,
    }
  },
  methods: {
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/landing-pages', { search: this.search || undefined, status: this.statusFilter || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    copyUrl(url) {
      navigator.clipboard.writeText(url)
      alert('Đã copy URL!')
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 1rem; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; text-decoration: none; transition: all 0.15s; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #ede9fe; color: #8b5cf6; }
.si-pub { background: #ecfdf5; color: #10b981; }
.si-visits { background: #eef2ff; color: #6366f1; }
.si-conv { background: #fef3c7; color: #f59e0b; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; font-weight: 500; }

.filter-bar { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 280px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.72rem; color: #475569; font-family: inherit; outline: none; }

.pages-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.65rem; }
.page-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; transition: all 0.15s; }
.page-card:hover { border-color: #8b5cf6; box-shadow: 0 4px 14px rgba(139,92,246,0.08); }
.pc-header { display: flex; justify-content: space-between; margin-bottom: 0.4rem; }
.pc-status { padding: 0.1rem 0.35rem; border-radius: 5px; font-size: 0.55rem; font-weight: 700; text-transform: uppercase; }
.pcs-draft { background: #fef3c7; color: #f59e0b; }
.pcs-published { background: #ecfdf5; color: #10b981; }
.pcs-archived { background: #f1f5f9; color: #94a3b8; }
.pc-blocks { font-size: 0.55rem; color: #94a3b8; font-weight: 600; }
.pc-title { font-size: 0.88rem; font-weight: 700; color: #0f172a; margin: 0 0 0.2rem; }
.pc-url { font-size: 0.58rem; color: #8b5cf6; margin-bottom: 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pc-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.25rem; margin-bottom: 0.5rem; }
.pcs-item { text-align: center; padding: 0.3rem; background: #fafbfc; border-radius: 6px; }
.pcs-val { font-size: 0.82rem; font-weight: 800; color: #0f172a; display: block; }
.pcs-rate { color: #10b981; }
.pcs-lbl { font-size: 0.45rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }
.pc-footer { display: flex; justify-content: space-between; align-items: center; }
.pc-date { font-size: 0.55rem; color: #94a3b8; }
.pc-actions { display: flex; gap: 0.2rem; }
.pc-btn { width: 26px; height: 26px; border-radius: 6px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; font-size: 0.55rem; text-decoration: none; }
.pc-btn:hover { border-color: #8b5cf6; color: #8b5cf6; }

.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #ede9fe; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #8b5cf6; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0 0 0.75rem; }
</style>
