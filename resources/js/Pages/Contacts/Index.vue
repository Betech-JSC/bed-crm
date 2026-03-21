<template>
  <div>
    <Head :title="t('common.contacts')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-address-book" /></div>
        <div>
          <h1 class="page-title">{{ t('common.contacts') }}</h1>
          <p class="page-subtitle">{{ contacts.total || 0 }} {{ t('common.results') }}</p>
        </div>
      </div>
      <Link href="/contacts/create"><button class="btn-primary"><i class="pi pi-plus" /> {{ t('common.create_contact') }}</button></Link>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" :placeholder="t('common.search_contacts')" class="search-input" @input="handleSearch" />
      </div>
      <select v-model="form.trashed" class="filter-select" @change="handleFilter">
        <option v-for="o in trashedOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> {{ t('common.reset_filters') }}</button>
    </div>

    <!-- Table -->
    <div class="data-card">
      <div v-if="!contacts.data || contacts.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-users" /></div>
        <h3>{{ t('common.no_contacts') }}</h3>
        <Link href="/contacts/create"><button class="btn-primary sm"><i class="pi pi-plus" /> {{ t('common.create_contact') }}</button></Link>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>{{ t('common.name') }}</th>
            <th>{{ t('common.organization') }}</th>
            <th>{{ t('common.phone') }}</th>
            <th>{{ t('common.city') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in contacts.data" :key="c.id" class="table-row">
            <td>
              <Link :href="`/contacts/${c.id}/edit`" class="contact-link">
                <div class="contact-avatar" :style="{ background: avatarGradient(c.name) }">{{ initials(c.name) }}</div>
                <div class="contact-info">
                  <span class="contact-name">{{ c.name }}</span>
                  <span v-if="c.email" class="contact-sub">{{ c.email }}</span>
                </div>
                <i v-if="c.deleted_at" class="pi pi-trash deleted-icon" />
              </Link>
            </td>
            <td>
              <div v-if="c.organization" class="org-cell"><i class="pi pi-building" /> {{ c.organization.name }}</div>
              <span v-else class="muted">—</span>
            </td>
            <td>
              <span v-if="c.phone" class="info-cell"><i class="pi pi-phone" /> {{ c.phone }}</span>
              <span v-else class="muted">—</span>
            </td>
            <td>
              <span v-if="c.city" class="info-cell"><i class="pi pi-map-marker" /> {{ c.city }}</span>
              <span v-else class="muted">—</span>
            </td>
            <td><Link :href="`/contacts/${c.id}/edit`" class="row-arrow"><i class="pi pi-chevron-right" /></Link></td>
          </tr>
        </tbody>
      </table>

      <div v-if="contacts.total > 0" class="pagination">
        <span class="page-info">{{ t('common.showing') }} {{ contacts.from }}–{{ contacts.to }} {{ t('common.of') }} {{ contacts.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === contacts.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
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
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { filters: Object, contacts: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      form: { search: this.filters.search, trashed: this.filters.trashed },
      trashedOptions: [
        { label: this.t('common.active_only'), value: null },
        { label: this.t('common.with_trashed'), value: 'with' },
        { label: this.t('common.only_trashed'), value: 'only' },
      ],
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    pageNumbers() {
      const total = this.contacts.last_page, cur = this.contacts.current_page, p = []
      if (total <= 7) { for (let i = 1; i <= total; i++) p.push(i) }
      else { p.push(1); if (cur > 3) p.push('...'); for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) p.push(i); if (cur < total - 2) p.push('...'); p.push(total) }
      return p
    },
  },
  methods: {
    handleSearch: throttle(function () { this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true }) },
    reset() { this.form = mapValues(this.form, () => null); this.$inertia.get('/contacts', {}, { preserveState: true }) },
    initials(n) { if (!n) return '?'; return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    avatarGradient(name) {
      const c = [['#10b981','#14b8a6'],['#6366f1','#8b5cf6'],['#3b82f6','#06b6d4'],['#ec4899','#db2777'],['#f59e0b','#f97316']]
      const i = (name || '').charCodeAt(0) % c.length; return `linear-gradient(135deg, ${c[i][0]}, ${c[i][1]})`
    },
    goToPage(pg) { const u = new URL(window.location.href); u.searchParams.set('page', pg); router.visit(u.pathname + u.search, { preserveState: true, preserveScroll: true }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
.btn-primary.sm { font-size: 0.78rem; padding: 0.45rem 0.85rem; }
.btn-text { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.7rem; border-radius: 8px; border: none; background: transparent; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
.btn-text:hover { background: #f1f5f9; }

.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #10b981; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-select { padding: 0.4rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #475569; background: white; cursor: pointer; outline: none; }
.filter-select:focus { border-color: #10b981; }

.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: #fafbfe; }

.contact-link { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: inherit; }
.contact-avatar { width: 34px; height: 34px; border-radius: 50%; color: white; font-size: 0.6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.2s; }
.contact-link:hover .contact-avatar { transform: scale(1.08); }
.contact-info { display: flex; flex-direction: column; min-width: 0; }
.contact-name { font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.contact-link:hover .contact-name { color: #10b981; }
.contact-sub { font-size: 0.68rem; color: #94a3b8; }
.deleted-icon { font-size: 0.6rem; color: #cbd5e1; margin-left: auto; }

.org-cell { display: flex; align-items: center; gap: 0.35rem; font-size: 0.82rem; color: #334155; }
.org-cell i { font-size: 0.68rem; color: #94a3b8; }
.info-cell { display: flex; align-items: center; gap: 0.3rem; font-size: 0.82rem; color: #475569; }
.info-cell i { font-size: 0.65rem; color: #94a3b8; }
.muted { color: #cbd5e1; font-size: 0.78rem; }
.row-arrow { color: #cbd5e1; text-decoration: none; font-size: 0.72rem; }
.row-arrow:hover { color: #10b981; }

.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #10b981; color: #10b981; }
.page-btn.active { background: #10b981; color: white; border-color: #10b981; }
.page-btn.dots { border: none; cursor: default; }

.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.75rem; }

@media (max-width: 768px) {
  .filter-bar { flex-direction: column; }
  .data-table { display: block; overflow-x: auto; }
}
</style>
