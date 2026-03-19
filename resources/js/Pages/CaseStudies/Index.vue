<template>
  <div>
    <Head :title="t('common.case_studies')" />

    <!-- Hero Banner -->
    <div class="hero-banner">
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-icon-wrap"><i class="pi pi-trophy" /></div>
          <div>
            <h1 class="hero-title">{{ t('common.case_studies') }}</h1>
            <p class="hero-subtitle">{{ isVi ? 'Trình bày thành quả dự án, xây dựng uy tín thương hiệu' : 'Showcase your work, build credibility & win more clients' }}</p>
          </div>
        </div>
        <Button :label="t('common.create_case_study')" icon="pi pi-plus" class="hero-btn" @click="$inertia.visit('/case-studies/create')" />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card" v-for="s in statCards" :key="s.label">
        <div class="stat-icon-wrap" :style="{ background: s.bg }"><i :class="s.icon" :style="{ color: s.color }" /></div>
        <div class="stat-body"><span class="stat-number" :style="{ color: s.color }">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <span class="p-input-icon-left w-full"><i class="pi pi-search" /><InputText v-model="localFilters.search" :placeholder="t('common.search_case_studies')" class="w-full" @keyup.enter="applyFilters" /></span>
      </div>
      <div class="filter-selects">
        <Dropdown v-model="localFilters.category" :options="categoryOptions" optionLabel="label" optionValue="value" :placeholder="t('common.service_category')" showClear class="filter-select" @change="applyFilters" />
        <Dropdown v-model="localFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.status')" showClear class="filter-select" @change="applyFilters" />
      </div>
      <Button icon="pi pi-refresh" severity="secondary" text size="small" v-tooltip.top="isVi ? 'Đặt lại' : 'Reset'" @click="resetFilters" />
    </div>

    <!-- Service Category Chips -->
    <div class="category-chips">
      <button v-for="(count, cat) in categoryCounts" :key="cat" class="cat-chip" :class="{ active: localFilters.category === cat }" @click="toggleCategory(cat)">
        <i :class="catIcon(cat)" /> {{ serviceCategories[cat] || cat }}
        <span class="chip-count">{{ count }}</span>
      </button>
    </div>

    <!-- Grid -->
    <TransitionGroup name="grid" tag="div" class="cs-grid" v-if="caseStudies.data && caseStudies.data.length">
      <div v-for="cs in caseStudies.data" :key="cs.id" class="cs-card" @click="$inertia.visit(`/case-studies/${cs.id}`)">
        <div class="cs-image" :style="cs.featured_image_url ? { backgroundImage: `url(${cs.featured_image_url})` } : {}">
          <div class="cs-overlay">
            <div class="cs-badges">
              <span class="cs-cat-badge" :class="`cat-${cs.service_category}`"><i :class="catIcon(cs.service_category)" /> {{ serviceCategories[cs.service_category] || cs.service_category }}</span>
              <span v-if="cs.is_featured" class="cs-featured"><i class="pi pi-star-fill" /> {{ isVi ? 'Nổi bật' : 'Featured' }}</span>
            </div>
          </div>
        </div>
        <div class="cs-body">
          <h3 class="cs-title">{{ cs.title }}</h3>
          <div class="cs-client-row">
            <div class="cs-client-avatar" :style="{ background: clientColor(cs.client_name) }">{{ (cs.client_name || '?').charAt(0) }}</div>
            <div class="cs-client-info">
              <span class="cs-client-name">{{ cs.client_name }}</span>
              <span class="cs-client-industry" v-if="cs.client_industry">{{ cs.client_industry }}</span>
            </div>
          </div>
          <p class="cs-summary" v-if="cs.summary">{{ cs.summary }}</p>
          <div class="cs-footer">
            <span class="cs-status" :class="`status-${cs.status}`"><i class="status-dot" /> {{ cs.status }}</span>
            <span class="cs-visibility"><i :class="cs.visibility === 'public' ? 'pi pi-globe' : 'pi pi-lock'" /> {{ cs.visibility }}</span>
            <span class="cs-date">{{ formatDate(cs.created_at) }}</span>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <div v-else class="empty-state">
      <div class="empty-illustration"><i class="pi pi-trophy" /></div>
      <h3>{{ t('common.no_case_studies') }}</h3>
      <p>{{ isVi ? 'Tạo dự án điển hình đầu tiên để bắt đầu' : 'Create your first case study to get started' }}</p>
      <Button :label="t('common.create_case_study')" icon="pi pi-plus" @click="$inertia.visit('/case-studies/create')" />
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, InputText, Dropdown },
  layout: Layout,
  props: { caseStudies: Object, tags: Array, stats: Object, categoryCounts: Object, filters: Object, serviceCategories: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() { return { localFilters: { search: this.filters?.search || '', category: this.filters?.category || null, status: this.filters?.status || null } } },
  computed: {
    isVi() { return this.locale === 'vi' },
    categoryOptions() { return Object.entries(this.serviceCategories || {}).map(([v, l]) => ({ value: v, label: l })) },
    statusOptions() { return [{ value: 'draft', label: this.isVi ? 'Bản nháp' : 'Draft' }, { value: 'published', label: this.isVi ? 'Đã xuất bản' : 'Published' }, { value: 'archived', label: this.isVi ? 'Đã lưu trữ' : 'Archived' }] },
    statCards() {
      const s = this.stats || {}
      return [
        { label: this.isVi ? 'Tổng cộng' : 'Total', value: s.total || 0, icon: 'pi pi-folder', color: '#6366f1', bg: '#eef2ff' },
        { label: this.isVi ? 'Đã xuất bản' : 'Published', value: s.published || 0, icon: 'pi pi-check-circle', color: '#10b981', bg: '#ecfdf5' },
        { label: this.isVi ? 'Bản nháp' : 'Draft', value: s.draft || 0, icon: 'pi pi-pencil', color: '#f59e0b', bg: '#fffbeb' },
        { label: this.isVi ? 'Nổi bật' : 'Featured', value: s.featured || 0, icon: 'pi pi-star', color: '#ec4899', bg: '#fdf2f8' },
      ]
    },
  },
  methods: {
    applyFilters() { router.get('/case-studies', this.localFilters, { preserveState: true }) },
    toggleCategory(cat) { this.localFilters.category = this.localFilters.category === cat ? null : cat; this.applyFilters() },
    resetFilters() { this.localFilters = { search: '', category: null, status: null }; router.get('/case-studies') },
    catIcon(cat) { return { website: 'pi pi-globe', marketing: 'pi pi-megaphone', seo: 'pi pi-chart-line', branding: 'pi pi-palette', landing_page: 'pi pi-desktop', ai_agent: 'pi pi-microchip' }[cat] || 'pi pi-folder' },
    clientColor(name) { const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4']; if (!name) return colors[0]; return colors[name.charCodeAt(0) % colors.length] },
    formatDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString(this.isVi ? 'vi-VN' : 'en-US', { month: 'short', day: 'numeric' }) },
  },
}
</script>

<style scoped>
.hero-banner { background: linear-gradient(135deg, #581c87 0%, #7c3aed 50%, #a78bfa 100%); border-radius: 16px; padding: 1.5rem 2rem; margin-bottom: 1.25rem; position: relative; overflow: hidden; }
.hero-banner::before { content: ''; position: absolute; top: -50%; right: -15%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.08), transparent 70%); border-radius: 50%; }
.hero-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 1; }
.hero-left { display: flex; align-items: center; gap: 1rem; }
.hero-icon-wrap { width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; }
.hero-title { font-size: 1.35rem; font-weight: 700; color: white; margin: 0; }
.hero-subtitle { font-size: 0.78rem; color: rgba(255,255,255,0.7); margin: 0.15rem 0 0; }
.hero-btn { background: white !important; color: #7c3aed !important; border: none !important; font-weight: 600; }

.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
@media(max-width:768px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
.stat-card { background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.25s; }
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-1px); }
.stat-icon-wrap { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon-wrap i { font-size: 1rem; }
.stat-body { display: flex; flex-direction: column; }
.stat-number { font-size: 1.35rem; font-weight: 800; line-height: 1; letter-spacing: -0.02em; }
.stat-label { font-size: 0.65rem; color: #94a3b8; font-weight: 500; }

.filter-bar { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 0.75rem; flex-wrap: wrap; }
.filter-search { flex: 1; min-width: 220px; }
.filter-search :deep(.p-inputtext) { border-radius: 8px; }
.filter-selects { display: flex; gap: 0.5rem; }
.filter-select { min-width: 150px; }

.category-chips { display: flex; gap: 0.35rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.cat-chip { border: 1px solid #e2e8f0; background: white; padding: 0.35rem 0.85rem; border-radius: 99px; font-size: 0.72rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.25s; display: flex; align-items: center; gap: 0.35rem; }
.cat-chip i { font-size: 0.72rem; }
.cat-chip:hover { border-color: #a78bfa; color: #7c3aed; background: #f5f3ff; }
.cat-chip.active { background: #7c3aed; color: white; border-color: #7c3aed; }
.chip-count { font-weight: 800; opacity: 0.7; }

.cs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1rem; }
.cs-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; overflow: hidden; cursor: pointer; transition: all 0.3s; }
.cs-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); transform: translateY(-3px); }
.cs-image { height: 180px; background: linear-gradient(135deg, #8b5cf6, #a78bfa, #c4b5fd); background-size: cover; background-position: center; position: relative; }
.cs-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.4) 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 0.75rem; }
.cs-badges { display: flex; gap: 0.35rem; }
.cs-cat-badge { font-size: 0.62rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 6px; backdrop-filter: blur(8px); display: flex; align-items: center; gap: 0.25rem; text-transform: uppercase; letter-spacing: 0.03em; }
.cs-cat-badge i { font-size: 0.6rem; }
.cat-website { background: rgba(59,130,246,0.85); color: white; }
.cat-marketing { background: rgba(245,158,11,0.85); color: white; }
.cat-seo { background: rgba(16,185,129,0.85); color: white; }
.cat-branding { background: rgba(236,72,153,0.85); color: white; }
.cat-landing_page { background: rgba(99,102,241,0.85); color: white; }
.cat-ai_agent { background: rgba(139,92,246,0.85); color: white; }
.cs-featured { font-size: 0.6rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 6px; background: rgba(251,191,36,0.9); color: #78350f; display: flex; align-items: center; gap: 0.2rem; }
.cs-featured i { font-size: 0.55rem; }

.cs-body { padding: 1.15rem 1.25rem; }
.cs-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.65rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.cs-client-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.cs-client-avatar { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
.cs-client-info { display: flex; flex-direction: column; min-width: 0; }
.cs-client-name { font-size: 0.78rem; font-weight: 600; color: #334155; }
.cs-client-industry { font-size: 0.65rem; color: #94a3b8; }
.cs-summary { font-size: 0.75rem; color: #64748b; margin: 0 0 0.65rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }
.cs-footer { display: flex; align-items: center; gap: 0.5rem; padding-top: 0.65rem; border-top: 1px solid #f8fafc; }
.cs-status { font-size: 0.65rem; font-weight: 700; display: flex; align-items: center; gap: 0.3rem; text-transform: capitalize; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-published { color: #16a34a; } .status-published .status-dot { background: #16a34a; }
.status-draft { color: #94a3b8; } .status-draft .status-dot { background: #94a3b8; }
.status-archived { color: #d97706; } .status-archived .status-dot { background: #d97706; }
.cs-visibility { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.cs-visibility i { font-size: 0.6rem; }
.cs-date { font-size: 0.65rem; color: #cbd5e1; margin-left: auto; }

.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 2px dashed #e2e8f0; }
.empty-illustration { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #f5f3ff, #ede9fe); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-illustration i { font-size: 2rem; color: #7c3aed; }
.empty-state h3 { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

.w-full { width: 100%; }
.grid-enter-active { animation: fadeUp 0.3s ease; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
</style>
