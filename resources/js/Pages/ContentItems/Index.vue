<template>
  <div>
    <Head title="Nội Dung" />
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-file-edit" /></div>
        <div><h1 class="page-title">Nội Dung AI</h1><p class="page-subtitle">Quản lý nội dung sinh bởi AI · {{ stats.total }} items</p></div>
      </div>
      <Link href="/content-items/create"><button class="btn-primary"><i class="pi pi-sparkles" /> Tạo Nội Dung</button></Link>
    </div>

    <div class="stats-row">
      <div v-for="s in statCards" :key="s.label" class="stat-card" :class="{ active: form.status === s.filterKey }" @click="filterBy(s.filterKey)">
        <div class="stat-icon" :style="{ background: s.bg, color: s.color }"><i :class="s.icon" /></div>
        <div><span class="stat-value">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-box"><i class="pi pi-search" /><input v-model="form.search" placeholder="Tìm nội dung..." class="search-input" @input="handleSearch" /></div>
      <div class="filter-tabs">
        <button v-for="tp in typeOptions" :key="tp.value" class="filter-tab" :class="{ active: form.type === tp.value }" @click="filterType(tp.value)">{{ tp.label }}</button>
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <div class="data-card">
      <div v-if="!contentItems.data || contentItems.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-file-edit" /></div>
        <h3>Chưa có nội dung nào</h3>
        <p>Sinh nội dung bằng AI templates</p>
        <Link href="/content-items/create"><button class="btn-primary sm"><i class="pi pi-sparkles" /> Tạo Nội Dung</button></Link>
      </div>
      <table v-else class="data-table">
        <thead><tr><th>Tiêu đề</th><th>Loại</th><th>Trạng thái</th><th>AI Model</th><th>SD</th><th>Người tạo</th><th>Ngày</th><th></th></tr></thead>
        <tbody>
          <tr v-for="item in contentItems.data" :key="item.id" class="table-row">
            <td>
              <Link :href="`/content-items/${item.id}`" class="name-link">
                <span class="item-title">{{ item.title || 'Untitled' }}</span>
                <span class="item-preview">{{ item.content }}</span>
              </Link>
            </td>
            <td><span class="type-badge" :class="`tp-${item.type}`">{{ item.type }}</span></td>
            <td><span class="status-badge" :class="`st-${item.status}`"><span class="status-dot" /> {{ statusLabel(item.status) }}</span></td>
            <td><span class="ai-tag">{{ item.ai_model || '—' }}</span></td>
            <td><span class="usage-count">{{ item.usage_count }}</span></td>
            <td><span class="text-sub">{{ item.creator || '—' }}</span></td>
            <td><span class="text-sub">{{ item.created_at }}</span></td>
            <td>
              <div class="action-buttons">
                <Link :href="`/content-items/${item.id}`" class="action-btn view"><i class="pi pi-eye" /></Link>
                <Link :href="`/content-items/${item.id}/edit`" class="action-btn edit"><i class="pi pi-pencil" /></Link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="contentItems.total > 0" class="pagination">
        <span class="page-info">{{ contentItems.from }}–{{ contentItems.to }} / {{ contentItems.total }}</span>
        <div class="page-btns"><button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === contentItems.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button></div>
      </div>
    </div>
  </div>
</template>
<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
export default {
  components: { Head, Link }, layout: Layout,
  props: { contentItems: Object, stats: Object, filters: Object },
  data() {
    return {
      form: { search: this.filters?.search || null, status: this.filters?.status || null, type: this.filters?.type || null },
      typeOptions: [
        { label: 'Tất cả', value: null }, { label: 'Blog', value: 'blog' }, { label: 'Social', value: 'social' },
        { label: 'Email', value: 'email' }, { label: 'Ad', value: 'ad' },
      ],
    }
  },
  computed: {
    statCards() {
      const s = this.stats || {}
      return [
        { label: 'Tổng', value: s.total || 0, icon: 'pi pi-file-edit', color: '#8b5cf6', bg: '#f5f3ff', filterKey: null },
        { label: 'Nháp', value: s.draft || 0, icon: 'pi pi-pencil', color: '#94a3b8', bg: '#f8fafc', filterKey: 'draft' },
        { label: 'Đã duyệt', value: s.approved || 0, icon: 'pi pi-check-circle', color: '#10b981', bg: '#ecfdf5', filterKey: 'approved' },
        { label: 'Đã đăng', value: s.published || 0, icon: 'pi pi-globe', color: '#3b82f6', bg: '#eff6ff', filterKey: 'published' },
      ]
    },
    pageNumbers() { const t = this.contentItems.last_page, c = this.contentItems.current_page, p = []; if (t <= 7) { for (let i = 1; i <= t; i++) p.push(i) } else { p.push(1); if (c > 3) p.push('...'); for (let i = Math.max(2, c - 1); i <= Math.min(t - 1, c + 1); i++) p.push(i); if (c < t - 2) p.push('...'); p.push(t) } return p },
  },
  methods: {
    handleSearch: throttle(function () { router.get('/content-items', pickBy(this.form), { preserveState: true }) }, 300),
    filterBy(key) { this.form.status = this.form.status === key ? null : key; router.get('/content-items', pickBy(this.form), { preserveState: true }) },
    filterType(val) { this.form.type = this.form.type === val ? null : val; router.get('/content-items', pickBy(this.form), { preserveState: true }) },
    reset() { this.form = mapValues(this.form, () => null); router.get('/content-items', {}, { preserveState: true }) },
    statusLabel(s) { return { draft: 'Nháp', approved: 'Đã duyệt', published: 'Đã đăng', archived: 'Lưu trữ' }[s] || s },
    goToPage(pg) { const u = new URL(window.location.href); u.searchParams.set('page', pg); router.visit(u.pathname + u.search, { preserveState: true, preserveScroll: true }) },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem}.header-left{display:flex;align-items:center;gap:.85rem}.header-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#ec4899,#db2777);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;box-shadow:0 4px 14px rgba(236,72,153,.3)}.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.1rem 0 0}
.btn-primary{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#ec4899,#db2777);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s;text-decoration:none}.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(236,72,153,.3)}.btn-primary.sm{font-size:.78rem;padding:.45rem .85rem}
.btn-text{display:flex;align-items:center;gap:.3rem;padding:.4rem .7rem;border-radius:8px;border:none;background:transparent;color:#64748b;font-size:.78rem;font-weight:600;cursor:pointer}.btn-text:hover{background:#f1f5f9}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem}
.stat-card{display:flex;align-items:center;gap:.6rem;padding:.7rem .85rem;background:#fff;border-radius:14px;border:1.5px solid #e2e8f0;cursor:pointer;transition:all .2s}.stat-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.05)}.stat-card.active{border-color:#ec4899;background:#fdf2f8}
.stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}.stat-value{display:block;font-size:1.1rem;font-weight:800;color:#1e293b;line-height:1}.stat-label{font-size:.6rem;color:#94a3b8}
.filter-bar{display:flex;align-items:center;gap:.6rem;padding:.65rem .85rem;background:#fff;border-radius:12px;border:1.5px solid #e2e8f0;margin-bottom:1rem;flex-wrap:wrap}
.search-box{display:flex;align-items:center;gap:.4rem;padding:.35rem .65rem;border:1.5px solid #e2e8f0;border-radius:8px;background:#f8fafc;flex:1;min-width:200px}.search-box:focus-within{border-color:#ec4899;background:#fff}.search-box i{color:#94a3b8;font-size:.78rem}.search-input{border:none;outline:none;font-size:.82rem;color:#334155;background:transparent;width:100%}
.filter-tabs{display:flex;gap:.25rem}.filter-tab{padding:.3rem .55rem;border-radius:7px;border:1.5px solid transparent;background:transparent;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer;transition:all .15s}.filter-tab:hover{background:#f8fafc}.filter-tab.active{border-color:#ec4899;background:#fdf2f8;color:#db2777}
.data-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden}
.data-table{width:100%;border-collapse:collapse}.data-table th{padding:.65rem 1rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;text-align:left;background:#fafbfc;border-bottom:1px solid #f1f5f9}.data-table td{padding:.65rem 1rem;font-size:.82rem;color:#334155;vertical-align:middle;border-bottom:1px solid #f8fafc}.table-row{transition:background .15s}.table-row:hover{background:#fafbfe}
.name-link{display:flex;flex-direction:column;text-decoration:none;color:inherit;max-width:250px}.name-link:hover .item-title{color:#ec4899}.item-title{font-weight:600;color:#1e293b;transition:color .15s}.item-preview{font-size:.68rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:250px}
.type-badge{font-size:.65rem;font-weight:600;padding:.12rem .4rem;border-radius:6px}.tp-blog{background:#dbeafe;color:#2563eb}.tp-social{background:#fce7f3;color:#db2777}.tp-email{background:#d1fae5;color:#059669}.tp-ad{background:#fef3c7;color:#d97706}
.status-badge{display:inline-flex;align-items:center;gap:.25rem;font-size:.68rem;font-weight:600;padding:.15rem .5rem;border-radius:20px}.status-dot{width:6px;height:6px;border-radius:50%}.st-draft{background:#f1f5f9;color:#64748b}.st-draft .status-dot{background:#94a3b8}.st-approved{background:#d1fae5;color:#059669}.st-approved .status-dot{background:#059669}.st-published{background:#dbeafe;color:#2563eb}.st-published .status-dot{background:#2563eb}.st-archived{background:#fef3c7;color:#d97706}.st-archived .status-dot{background:#d97706}
.ai-tag{font-size:.68rem;color:#64748b;background:#f1f5f9;padding:.1rem .35rem;border-radius:5px}
.usage-count{font-size:.82rem;font-weight:700;color:#334155}
.text-sub{font-size:.78rem;color:#64748b}
.action-buttons{display:flex;gap:.25rem}.action-btn{width:28px;height:28px;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.65rem;transition:all .15s;color:#94a3b8;text-decoration:none}.action-btn.view:hover{border-color:#3b82f6;color:#3b82f6;background:#eff6ff}.action-btn.edit:hover{border-color:#8b5cf6;color:#8b5cf6;background:#f5f3ff}
.pagination{display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-top:1px solid #f1f5f9}.page-info{font-size:.72rem;color:#94a3b8}.page-btns{display:flex;gap:.2rem}.page-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}.page-btn:hover:not(.active):not(.dots){border-color:#ec4899;color:#ec4899}.page-btn.active{background:#ec4899;color:#fff;border-color:#ec4899}.page-btn.dots{border:none;cursor:default}
.empty-state{display:flex;flex-direction:column;align-items:center;padding:4rem 2rem}.empty-icon{width:64px;height:64px;border-radius:18px;background:#fdf2f8;color:#ec4899;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:.75rem}.empty-state h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .2rem}.empty-state p{font-size:.78rem;color:#94a3b8;margin:0 0 1rem}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}.data-table{display:block;overflow-x:auto}.filter-tabs{overflow-x:auto}}
</style>
