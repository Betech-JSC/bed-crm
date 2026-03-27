<template>
  <div>
    <Head title="Content Templates" />
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-palette" /></div>
        <div><h1 class="page-title">Content Templates</h1><p class="page-subtitle">Mẫu nội dung AI · {{ stats.total }} templates</p></div>
      </div>
      <Link href="/content-templates/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Template</button></Link>
    </div>

    <div class="stats-row">
      <div v-for="s in statCards" :key="s.label" class="stat-card" :class="{ active: form.category === s.filterKey }" @click="filterBy(s.filterKey)">
        <div class="stat-icon" :style="{ background: s.bg, color: s.color }"><i :class="s.icon" /></div>
        <div><span class="stat-value">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-box"><i class="pi pi-search" /><input v-model="form.search" placeholder="Tìm template..." class="search-input" @input="handleSearch" /></div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <div class="data-card">
      <div v-if="!templates.data || templates.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-palette" /></div>
        <h3>Chưa có template nào</h3>
        <p>Tạo template AI để bắt đầu sinh nội dung</p>
        <Link href="/content-templates/create"><button class="btn-primary sm"><i class="pi pi-plus" /> Tạo Template</button></Link>
      </div>
      <table v-else class="data-table">
        <thead><tr><th>Tên</th><th>Danh mục</th><th>Sử dụng</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead>
        <tbody>
          <tr v-for="tpl in templates.data" :key="tpl.id" class="table-row">
            <td>
              <Link :href="`/content-templates/${tpl.id}/edit`" class="name-link">
                <div class="tpl-avatar"><i class="pi pi-palette" /></div>
                <div><span class="tpl-name">{{ tpl.name }}</span><span v-if="tpl.description" class="tpl-desc">{{ tpl.description }}</span></div>
              </Link>
            </td>
            <td><span v-if="tpl.category" class="cat-badge" :class="`cat-${tpl.category}`">{{ catLabel(tpl.category) }}</span><span v-else class="text-sub">—</span></td>
            <td><span class="usage-badge"><i class="pi pi-chart-line" /> {{ tpl.usage_count }}</span></td>
            <td><span class="status-badge" :class="tpl.is_active ? 'st-active' : 'st-inactive'"><span class="status-dot" /> {{ tpl.is_active ? 'Active' : 'Inactive' }}</span></td>
            <td><span class="text-sub">{{ tpl.created_at }}</span></td>
            <td>
              <div class="action-buttons">
                <Link :href="`/content-templates/${tpl.id}/edit`" class="action-btn edit"><i class="pi pi-pencil" /></Link>
                <button class="action-btn delete" @click="confirmDelete(tpl.id)"><i class="pi pi-trash" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="templates.total > 0" class="pagination">
        <span class="page-info">{{ templates.from }}–{{ templates.to }} / {{ templates.total }}</span>
        <div class="page-btns"><button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === templates.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button></div>
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
  props: { templates: Object, stats: Object, filters: Object },
  data() { return { form: { search: this.filters?.search || null, category: this.filters?.category || null } } },
  computed: {
    statCards() {
      const s = this.stats || {}
      return [
        { label: 'Tổng', value: s.total || 0, icon: 'pi pi-palette', color: '#8b5cf6', bg: '#f5f3ff', filterKey: null },
        { label: 'Blog', value: s.blog || 0, icon: 'pi pi-book', color: '#3b82f6', bg: '#eff6ff', filterKey: 'blog' },
        { label: 'Social', value: s.social || 0, icon: 'pi pi-share-alt', color: '#ec4899', bg: '#fdf2f8', filterKey: 'social' },
        { label: 'Email', value: s.email || 0, icon: 'pi pi-envelope', color: '#10b981', bg: '#ecfdf5', filterKey: 'email' },
        { label: 'Quảng cáo', value: s.ad || 0, icon: 'pi pi-megaphone', color: '#f59e0b', bg: '#fffbeb', filterKey: 'ad' },
      ]
    },
    pageNumbers() { const t = this.templates.last_page, c = this.templates.current_page, p = []; if (t <= 7) { for (let i = 1; i <= t; i++) p.push(i) } else { p.push(1); if (c > 3) p.push('...'); for (let i = Math.max(2, c - 1); i <= Math.min(t - 1, c + 1); i++) p.push(i); if (c < t - 2) p.push('...'); p.push(t) } return p },
  },
  methods: {
    handleSearch: throttle(function () { router.get('/content-templates', pickBy(this.form), { preserveState: true }) }, 300),
    filterBy(key) { this.form.category = this.form.category === key ? null : key; router.get('/content-templates', pickBy(this.form), { preserveState: true }) },
    reset() { this.form = mapValues(this.form, () => null); router.get('/content-templates', {}, { preserveState: true }) },
    catLabel(c) { return { blog: 'Blog', social: 'Social', email: 'Email', ad: 'Ads', other: 'Khác' }[c] || c },
    confirmDelete(id) { if (confirm('Xóa template này?')) router.delete(`/content-templates/${id}`) },
    goToPage(pg) { const u = new URL(window.location.href); u.searchParams.set('page', pg); router.visit(u.pathname + u.search, { preserveState: true, preserveScroll: true }) },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem}.header-left{display:flex;align-items:center;gap:.85rem}.header-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;box-shadow:0 4px 14px rgba(139,92,246,.3)}.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.1rem 0 0}
.btn-primary{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s;text-decoration:none}.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(139,92,246,.3)}.btn-primary.sm{font-size:.78rem;padding:.45rem .85rem}
.btn-text{display:flex;align-items:center;gap:.3rem;padding:.4rem .7rem;border-radius:8px;border:none;background:transparent;color:#64748b;font-size:.78rem;font-weight:600;cursor:pointer}.btn-text:hover{background:#f1f5f9}
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1rem}
.stat-card{display:flex;align-items:center;gap:.6rem;padding:.7rem .85rem;background:#fff;border-radius:14px;border:1.5px solid #e2e8f0;cursor:pointer;transition:all .2s}.stat-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.05)}.stat-card.active{border-color:#8b5cf6;background:#faf5ff}
.stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}.stat-value{display:block;font-size:1.1rem;font-weight:800;color:#1e293b;line-height:1}.stat-label{font-size:.6rem;color:#94a3b8}
.filter-bar{display:flex;align-items:center;gap:.6rem;padding:.65rem .85rem;background:#fff;border-radius:12px;border:1.5px solid #e2e8f0;margin-bottom:1rem}
.search-box{display:flex;align-items:center;gap:.4rem;padding:.35rem .65rem;border:1.5px solid #e2e8f0;border-radius:8px;background:#f8fafc;flex:1;min-width:200px}.search-box:focus-within{border-color:#8b5cf6;background:#fff}.search-box i{color:#94a3b8;font-size:.78rem}.search-input{border:none;outline:none;font-size:.82rem;color:#334155;background:transparent;width:100%}
.data-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden}
.data-table{width:100%;border-collapse:collapse}.data-table th{padding:.65rem 1rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;text-align:left;background:#fafbfc;border-bottom:1px solid #f1f5f9}.data-table td{padding:.65rem 1rem;font-size:.82rem;color:#334155;vertical-align:middle;border-bottom:1px solid #f8fafc}.table-row{transition:background .15s}.table-row:hover{background:#fafbfe}
.name-link{display:flex;align-items:center;gap:.5rem;text-decoration:none;color:inherit}.name-link:hover .tpl-name{color:#8b5cf6}
.tpl-avatar{width:34px;height:34px;border-radius:10px;background:#f5f3ff;color:#8b5cf6;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.tpl-name{display:block;font-weight:600;color:#1e293b;transition:color .15s}.tpl-desc{display:block;font-size:.68rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px}
.cat-badge{font-size:.65rem;font-weight:600;padding:.12rem .4rem;border-radius:6px}.cat-blog{background:#dbeafe;color:#2563eb}.cat-social{background:#fce7f3;color:#db2777}.cat-email{background:#d1fae5;color:#059669}.cat-ad{background:#fef3c7;color:#d97706}.cat-other{background:#f1f5f9;color:#64748b}
.usage-badge{font-size:.78rem;font-weight:600;color:#334155;display:flex;align-items:center;gap:.25rem}.usage-badge i{font-size:.65rem;color:#94a3b8}
.status-badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.68rem;font-weight:600;padding:.18rem .55rem;border-radius:20px}.status-dot{width:6px;height:6px;border-radius:50%}.st-active{background:#d1fae5;color:#059669}.st-active .status-dot{background:#059669}.st-inactive{background:#f1f5f9;color:#64748b}.st-inactive .status-dot{background:#94a3b8}
.text-sub{font-size:.78rem;color:#64748b}
.action-buttons{display:flex;gap:.25rem}.action-btn{width:28px;height:28px;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.65rem;transition:all .15s;color:#94a3b8;text-decoration:none}.action-btn.edit:hover{border-color:#8b5cf6;color:#8b5cf6;background:#f5f3ff}.action-btn.delete:hover{border-color:#ef4444;color:#ef4444;background:#fef2f2}
.pagination{display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-top:1px solid #f1f5f9}.page-info{font-size:.72rem;color:#94a3b8}.page-btns{display:flex;gap:.2rem}.page-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}.page-btn:hover:not(.active):not(.dots){border-color:#8b5cf6;color:#8b5cf6}.page-btn.active{background:#8b5cf6;color:#fff;border-color:#8b5cf6}.page-btn.dots{border:none;cursor:default}
.empty-state{display:flex;flex-direction:column;align-items:center;padding:4rem 2rem}.empty-icon{width:64px;height:64px;border-radius:18px;background:#f5f3ff;color:#8b5cf6;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:.75rem}.empty-state h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .2rem}.empty-state p{font-size:.78rem;color:#94a3b8;margin:0 0 1rem}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}.data-table{display:block;overflow-x:auto}}
</style>
