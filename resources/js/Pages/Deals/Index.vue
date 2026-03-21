<template>
  <div>
    <Head :title="t('common.deals')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-dollar" /></div>
        <div>
          <h1 class="page-title">{{ t('common.pipeline') }}</h1>
          <div class="header-badges">
            <span class="h-badge open">{{ totalDeals }} {{ isVi ? 'deal' : 'deals' }}</span>
            <span class="h-badge value"><i class="pi pi-wallet" /> {{ formatShort(totalValue) }}</span>
          </div>
        </div>
      </div>
      <Link href="/deals/create"><button class="btn-primary"><i class="pi pi-plus" /> {{ t('common.create_deal') }}</button></Link>
    </div>

    <!-- Kanban -->
    <div class="kanban-board">
      <div v-for="[stageKey, stageLabel] in stageEntries" :key="stageKey" class="kanban-col">
        <div class="col-header">
          <div class="col-header-left">
            <span class="col-dot" :style="{ background: stageColor(stageKey) }" />
            <span class="col-title">{{ stageLabel }}</span>
          </div>
          <div class="col-header-right">
            <span class="col-value">{{ formatShort(stageValue(stageKey)) }}</span>
            <span class="col-count">{{ getStageCount(stageKey) }}</span>
          </div>
        </div>

        <draggable
          v-model="localDealsByStage[stageKey]"
          :group="{ name: 'deals', pull: true, put: true }"
          :animation="200"
          ghost-class="ghost-card"
          chosen-class="chosen-card"
          drag-class="dragging-card"
          :empty-insert-threshold="30"
          item-key="id"
          class="col-body"
          @change="onStageDrop(stageKey, $event)"
        >
          <template #item="{ element: deal }">
            <div :key="deal.id" class="deal-card">
              <div class="deal-top">
                <Link :href="`/deals/${deal.id}/edit`" class="deal-link">
                  <h3 class="deal-title">{{ deal.title }}</h3>
                  <p v-if="deal.lead" class="deal-lead">{{ deal.lead.name }}</p>
                  <p v-if="deal.lead?.company" class="deal-company"><i class="pi pi-building" /> {{ deal.lead.company }}</p>
                </Link>
                <div class="drag-handle"><i class="pi pi-bars" /></div>
              </div>

              <div v-if="deal.value" class="deal-value">
                <i class="pi pi-wallet" /> {{ formatCurrency(deal.value) }}
              </div>

              <div class="deal-bottom">
                <div v-if="deal.assigned_user" class="deal-user">
                  <div class="deal-avatar">{{ initials(deal.assigned_user.name) }}</div>
                  <span>{{ deal.assigned_user.name }}</span>
                </div>
                <div v-if="deal.expected_close_date" class="deal-date" :class="{ overdue: isOverdue(deal.expected_close_date) }">
                  <i class="pi pi-calendar" /> {{ formatDate(deal.expected_close_date) }}
                </div>
              </div>
            </div>
          </template>
        </draggable>

        <div v-if="getStageCount(stageKey) === 0" class="col-empty">
          <i class="pi pi-inbox" /><span>{{ t('common.no_deals') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import draggable from 'vuedraggable'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, draggable },
  layout: Layout,
  props: { dealsByStage: Object, stages: Object, salesUsers: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() { return { localDealsByStage: {} } },
  computed: {
    isVi() { return this.locale === 'vi' },
    stageEntries() { return Object.entries(this.stages) },
    totalDeals() { return Object.values(this.localDealsByStage).reduce((s, a) => s + (a?.length || 0), 0) },
    totalValue() { return Object.values(this.localDealsByStage).flat().reduce((s, d) => s + (d?.value || 0), 0) },
  },
  watch: {
    dealsByStage: {
      immediate: true, deep: true,
      handler(v) { this.localDealsByStage = {}; Object.keys(this.stages).forEach(k => { this.localDealsByStage[k] = [...(v[k] || [])] }) },
    },
  },
  methods: {
    getStageCount(s) { return this.localDealsByStage[s]?.length || 0 },
    stageValue(s) { return (this.localDealsByStage[s] || []).reduce((sum, d) => sum + (d.value || 0), 0) },
    stageColor(s) { return { prospecting: '#6366f1', qualification: '#f59e0b', proposal: '#8b5cf6', negotiation: '#ef4444', closing: '#10b981' }[s] || '#94a3b8' },
    initials(n) { if (!n) return '?'; return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    isOverdue(d) { return new Date(d) < new Date() },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    formatShort(v) { return v >= 1e9 ? (v/1e9).toFixed(1)+'B' : v >= 1e6 ? (v/1e6).toFixed(0)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : (v || 0).toString() },
    formatDate(d) { return new Date(d).toLocaleDateString('vi-VN', { month: 'short', day: 'numeric' }) },
    onStageDrop(target, event) {
      if (!event.added) return
      const deal = event.added.element, old = deal.stage
      if (old === target) return
      deal.stage = target
      router.patch(`/deals/${deal.id}/stage`, { stage: target }, {
        preserveScroll: true, preserveState: true,
        onError: () => { deal.stage = old; this.syncLocalState() },
      })
    },
    syncLocalState() { this.$nextTick(() => { Object.keys(this.stages).forEach(k => { this.localDealsByStage[k] = [...(this.dealsByStage[k] || [])] }) }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.header-badges { display: flex; gap: 0.35rem; margin-top: 0.15rem; }
.h-badge { font-size: 0.65rem; font-weight: 700; padding: 0.12rem 0.5rem; border-radius: 20px; display: flex; align-items: center; gap: 0.2rem; }
.h-badge.open { background: #eef2ff; color: #6366f1; }
.h-badge.value { background: #ecfdf5; color: #10b981; }
.h-badge i { font-size: 0.55rem; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16,185,129,0.3); }

/* Kanban */
.kanban-board { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 1rem; min-height: calc(100vh - 180px); }
.kanban-col { flex-shrink: 0; width: 300px; display: flex; flex-direction: column; }

/* Column */
.col-header { display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.75rem; margin-bottom: 0.5rem; background: white; border-radius: 10px; border: 1.5px solid #e2e8f0; }
.col-header-left { display: flex; align-items: center; gap: 0.45rem; }
.col-dot { width: 8px; height: 8px; border-radius: 50%; }
.col-title { font-size: 0.78rem; font-weight: 700; color: #334155; }
.col-header-right { display: flex; align-items: center; gap: 0.35rem; }
.col-value { font-size: 0.62rem; font-weight: 700; color: #10b981; }
.col-count { font-size: 0.65rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.4rem; border-radius: 8px; min-width: 20px; text-align: center; }

/* Column Body */
.col-body { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; min-height: 200px; padding: 0.3rem; border-radius: 10px; background: #f8fafc; border: 2px dashed transparent; transition: all 0.2s; }

/* Deal Card */
.deal-card { background: white; border-radius: 12px; padding: 0.85rem; border: 1.5px solid #e2e8f0; cursor: grab; transition: all 0.2s; }
.deal-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-color: #cbd5e1; transform: translateY(-1px); }
.deal-card:active { cursor: grabbing; }
.deal-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.4rem; }
.deal-link { text-decoration: none; flex: 1; min-width: 0; }
.deal-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; }
.deal-link:hover .deal-title { color: #10b981; }
.deal-lead { font-size: 0.72rem; color: #64748b; margin: 0.15rem 0 0; }
.deal-company { font-size: 0.65rem; color: #94a3b8; margin: 0.1rem 0 0; display: flex; align-items: center; gap: 0.2rem; }
.deal-company i { font-size: 0.55rem; }
.drag-handle { padding: 0.2rem; color: #cbd5e1; cursor: grab; transition: color 0.15s; flex-shrink: 0; }
.drag-handle:hover { color: #6366f1; }
.drag-handle i { font-size: 0.72rem; }

.deal-value { display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; font-weight: 800; color: #10b981; margin-bottom: 0.4rem; padding: 0.3rem 0.5rem; background: #ecfdf5; border-radius: 8px; }
.deal-value i { font-size: 0.68rem; }

.deal-bottom { display: flex; align-items: center; justify-content: space-between; gap: 0.4rem; }
.deal-user { display: flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; color: #64748b; }
.deal-avatar { width: 20px; height: 20px; border-radius: 6px; background: #e0e7ff; color: #4f46e5; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.deal-date { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.deal-date.overdue { color: #ef4444; }
.deal-date i { font-size: 0.55rem; }

/* Empty */
.col-empty { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 2rem; color: #cbd5e1; font-size: 0.72rem; }
.col-empty i { font-size: 1rem; }

/* Drag States */
.ghost-card { opacity: 0.4; background: #f1f5f9 !important; border: 2px dashed #94a3b8 !important; box-shadow: none !important; }
.chosen-card { cursor: grabbing !important; box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important; transform: rotate(1deg); }
.dragging-card { opacity: 0.9; }

/* Scrollbar */
.kanban-board::-webkit-scrollbar { height: 6px; }
.kanban-board::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.kanban-board::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.kanban-board::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
