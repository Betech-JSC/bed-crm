<template>
  <div>
    <Head title="Email Templates" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-file-edit" /></div>
        <div>
          <h1 class="page-title">Email Templates</h1>
          <p class="page-subtitle">Quản lý mẫu email cho chiến dịch và automation</p>
        </div>
      </div>
      <Link href="/email-templates/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Template</button></Link>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div v-for="s in statCards" :key="s.label" class="stat-card" :class="{ active: form.type === s.filterKey }" @click="filterByType(s.filterKey)">
        <div class="stat-icon" :style="{ background: s.bg, color: s.color }"><i :class="s.icon" /></div>
        <div><span class="stat-value">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" placeholder="Tìm template..." class="search-input" @input="handleSearch" />
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <!-- Table -->
    <div class="data-card">
      <div v-if="!templates.data || templates.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-file-edit" /></div>
        <h3>Chưa có template nào</h3>
        <p>Tạo template đầu tiên để bắt đầu gửi email</p>
        <Link href="/email-templates/create"><button class="btn-primary sm"><i class="pi pi-plus" /> Tạo Template</button></Link>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Tên template</th>
            <th>Subject</th>
            <th>Loại</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tpl in templates.data" :key="tpl.id" class="table-row">
            <td>
              <Link :href="`/email-templates/${tpl.id}/edit`" class="name-link">
                <div class="tpl-avatar"><i class="pi pi-file" /></div>
                <span class="tpl-name">{{ tpl.name }}</span>
              </Link>
            </td>
            <td><span class="text-sub">{{ tpl.subject || '—' }}</span></td>
            <td>
              <span class="type-badge" :class="`tp-${tpl.type}`">{{ typeLabel(tpl.type) }}</span>
            </td>
            <td>
              <span class="status-badge" :class="tpl.is_active ? 'st-active' : 'st-inactive'">
                <span class="status-dot" /> {{ tpl.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td><span class="text-sub">{{ tpl.created_at }}</span></td>
            <td>
              <div class="action-buttons">
                <Link :href="`/email-templates/${tpl.id}/edit`" class="action-btn edit"><i class="pi pi-pencil" /></Link>
                <button class="action-btn delete" @click="confirmDelete(tpl.id)"><i class="pi pi-trash" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="templates.total > 0" class="pagination">
        <span class="page-info">Hiển thị {{ templates.from }}–{{ templates.to }} / {{ templates.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === templates.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
        </div>
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
  components: { Head, Link },
  layout: Layout,
  props: { templates: Object, filters: Object, stats: Object },
  data() {
    return {
      form: { search: this.filters?.search || null, type: this.filters?.type || null },
    }
  },
  computed: {
    statCards() {
      const s = this.stats || {}
      return [
        { label: 'Tổng', value: s.total || 0, icon: 'pi pi-file-edit', color: '#6366f1', bg: '#eef2ff', filterKey: null },
        { label: 'Campaign', value: s.campaign || 0, icon: 'pi pi-megaphone', color: '#3b82f6', bg: '#eff6ff', filterKey: 'campaign' },
        { label: 'Automation', value: s.automation || 0, icon: 'pi pi-bolt', color: '#f59e0b', bg: '#fffbeb', filterKey: 'automation' },
        { label: 'Transactional', value: s.transactional || 0, icon: 'pi pi-envelope', color: '#10b981', bg: '#ecfdf5', filterKey: 'transactional' },
      ]
    },
    pageNumbers() {
      const total = this.templates.last_page, cur = this.templates.current_page, pages = []
      if (total <= 7) { for (let i = 1; i <= total; i++) pages.push(i) } else {
        pages.push(1); if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...'); pages.push(total)
      }
      return pages
    },
  },
  methods: {
    handleSearch: throttle(function () { router.get('/email-templates', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { router.get('/email-templates', pickBy(this.form), { preserveState: true }) },
    filterByType(type) { this.form.type = this.form.type === type ? null : type; this.handleFilter() },
    reset() { this.form = mapValues(this.form, () => null); router.get('/email-templates', {}, { preserveState: true }) },
    typeLabel(t) { return { campaign: 'Campaign', automation: 'Automation', transactional: 'Transactional' }[t] || t },
    confirmDelete(id) { if (confirm('Bạn có chắc muốn xóa template này?')) router.delete(`/email-templates/${id}`) },
    goToPage(pg) {
      const url = new URL(window.location.href); url.searchParams.set('page', pg)
      router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-primary.sm { font-size: 0.78rem; padding: 0.45rem 0.85rem; }
.btn-text { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.7rem; border-radius: 8px; border: none; background: transparent; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
.btn-text:hover { background: #f1f5f9; }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.stat-card.active { border-color: #6366f1; background: #fafafe; }
.stat-icon { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-label { font-size: 0.65rem; color: #94a3b8; }

.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #6366f1; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }

.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: #fafbfe; }

.name-link { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: inherit; }
.name-link:hover .tpl-name { color: #6366f1; }
.tpl-avatar { width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0; }
.tpl-name { font-weight: 600; color: #1e293b; transition: color 0.15s; }
.text-sub { font-size: 0.78rem; color: #64748b; }

.type-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 6px; }
.tp-campaign { background: #dbeafe; color: #2563eb; }
.tp-automation { background: #fef3c7; color: #d97706; }
.tp-transactional { background: #d1fae5; color: #059669; }

.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.55rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; }
.st-active { background: #d1fae5; color: #059669; } .st-active .status-dot { background: #059669; }
.st-inactive { background: #f1f5f9; color: #64748b; } .st-inactive .status-dot { background: #94a3b8; }

.action-buttons { display: flex; gap: 0.25rem; }
.action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.65rem; transition: all 0.15s; color: #94a3b8; text-decoration: none; }
.action-btn.edit:hover { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
.action-btn.delete:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #6366f1; color: #6366f1; }
.page-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-btn.dots { border: none; cursor: default; }

.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .data-table { display: block; overflow-x: auto; }
}
</style>
