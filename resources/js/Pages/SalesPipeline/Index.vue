<template>
  <div>
    <Head :title="isVi ? 'Quy trình bán hàng' : 'Sales Pipeline'" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-chart-line" /></div>
        <div>
          <h1 class="page-title">{{ isVi ? 'Quy trình bán hàng' : 'Sales Pipeline' }}</h1>
          <div class="header-badges">
            <span class="h-badge open"><i class="pi pi-circle-fill" /> {{ stats.total_open }} {{ isVi ? 'đang mở' : 'open' }}</span>
            <span class="h-badge won"><i class="pi pi-check" /> {{ stats.closed_won }} {{ isVi ? 'thành công' : 'won' }}</span>
            <span class="h-badge lost"><i class="pi pi-times" /> {{ stats.closed_lost }} {{ isVi ? 'thất bại' : 'lost' }}</span>
          </div>
        </div>
      </div>
      <Link href="/sales-pipeline/create"><button class="btn-primary"><i class="pi pi-plus" /> {{ isVi ? 'Tạo mới' : 'Create' }}</button></Link>
    </div>

    <!-- Kanban -->
    <div class="kanban-board">
      <div v-for="[stageKey, stageLabel] in stageEntries" :key="stageKey" class="kanban-col">
        <div class="col-header">
          <div class="col-left">
            <span class="col-dot" :style="{ background: stageColor(stageKey) }" />
            <span class="col-title">{{ stageLabel }}</span>
          </div>
          <div class="col-right">
            <span class="col-total">{{ formatShort(stageValue(stageKey)) }}</span>
            <span class="col-count">{{ getStageCount(stageKey) }}</span>
          </div>
        </div>

        <draggable
          v-model="localPipelinesByStage[stageKey]"
          :group="{ name: 'pipelines', pull: true, put: true }"
          :animation="200"
          ghost-class="ghost-card"
          chosen-class="chosen-card"
          drag-class="dragging-card"
          :empty-insert-threshold="30"
          item-key="id"
          class="col-body"
          @change="onStageDrop(stageKey, $event)"
        >
          <template #item="{ element: pipeline }">
            <div :key="pipeline.id" class="pipe-card">
              <div class="pipe-top">
                <Link :href="`/sales-pipeline/${pipeline.id}/edit`" class="pipe-link">
                  <h3 class="pipe-company">{{ pipeline.company_name }}</h3>
                  <p class="pipe-contact">{{ pipeline.contact_name }}</p>
                </Link>
                <span class="priority-badge" :class="`pr-${pipeline.priority}`">{{ priorityLabel(pipeline.priority) }}</span>
              </div>

              <div v-if="pipeline.social_channel" class="pipe-social">
                <i :class="getSocialIcon(pipeline.social_channel)" />
                <span>{{ pipeline.social_account || pipeline.social_channel }}</span>
              </div>

              <div v-if="pipeline.quote_amount" class="pipe-value">
                <i class="pi pi-wallet" /> {{ formatCurrency(pipeline.quote_amount) }}
              </div>

              <div v-if="pipeline.audit_score > 0" class="pipe-audit">
                <div class="audit-track"><div class="audit-fill" :style="{ width: pipeline.audit_score + '%' }" :class="getAuditClass(pipeline.audit_score)" /></div>
                <span class="audit-label" :class="getAuditClass(pipeline.audit_score)">{{ pipeline.audit_score }}%</span>
              </div>

              <div class="pipe-bottom">
                <div v-if="pipeline.assigned_user" class="pipe-user">
                  <div class="pipe-avatar">{{ initials(pipeline.assigned_user.name) }}</div>
                  <span>{{ pipeline.assigned_user.name }}</span>
                </div>
                <div class="drag-handle"><i class="pi pi-bars" /></div>
              </div>
            </div>
          </template>
        </draggable>

        <div v-if="getStageCount(stageKey) === 0" class="col-empty">
          <i class="pi pi-inbox" /><span>{{ isVi ? 'Chưa có' : 'Empty' }}</span>
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
  props: { pipelinesByStage: Object, stages: Object, allStages: Object, priorities: Object, salesUsers: Array, stats: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() { return { localPipelinesByStage: {} } },
  computed: {
    isVi() { return this.locale === 'vi' },
    stageEntries() { return Object.entries(this.stages) },
  },
  watch: {
    pipelinesByStage: {
      immediate: true, deep: true,
      handler(v) { this.localPipelinesByStage = {}; Object.keys(this.stages).forEach(k => { this.localPipelinesByStage[k] = [...(v[k] || [])] }) },
    },
  },
  methods: {
    getStageCount(s) { return this.localPipelinesByStage[s]?.length || 0 },
    stageValue(s) { return (this.localPipelinesByStage[s] || []).reduce((sum, p) => sum + (p.quote_amount || 0), 0) },
    stageColor(s) { return { audit: '#6366f1', connect: '#06b6d4', propose: '#8b5cf6', discuss: '#f59e0b', quote: '#ef4444' }[s] || '#94a3b8' },
    initials(n) { if (!n) return '?'; return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    priorityLabel(p) { return { hot: '🔥 Hot', warm: '☀️ Warm', cold: '❄️ Cold' }[p] || p },
    getSocialIcon(ch) { return { zalo: 'pi pi-comment', facebook: 'pi pi-facebook' }[ch] || 'pi pi-globe' },
    getAuditClass(s) { return s >= 70 ? 'a-good' : s >= 40 ? 'a-warn' : 'a-low' },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    formatShort(v) { return v >= 1e9 ? (v/1e9).toFixed(1)+'B' : v >= 1e6 ? (v/1e6).toFixed(0)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : (v || 0).toString() },
    onStageDrop(target, event) {
      if (!event.added) return
      const p = event.added.element, old = p.stage
      if (old === target) return
      p.stage = target
      router.patch(`/sales-pipeline/${p.id}/stage`, { stage: target }, {
        preserveScroll: true, preserveState: true,
        onError: () => { p.stage = old; this.syncLocalState() },
      })
    },
    syncLocalState() { this.$nextTick(() => { Object.keys(this.stages).forEach(k => { this.localPipelinesByStage[k] = [...(this.pipelinesByStage[k] || [])] }) }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.header-badges { display: flex; gap: 0.35rem; margin-top: 0.15rem; }
.h-badge { font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.5rem; border-radius: 20px; display: flex; align-items: center; gap: 0.2rem; }
.h-badge i { font-size: 0.45rem; }
.h-badge.open { background: #eff6ff; color: #3b82f6; }
.h-badge.won { background: #ecfdf5; color: #10b981; }
.h-badge.lost { background: #fef2f2; color: #ef4444; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }

/* Kanban */
.kanban-board { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 1rem; min-height: calc(100vh - 180px); }
.kanban-col { flex-shrink: 0; width: 290px; display: flex; flex-direction: column; }
.col-header { display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.75rem; margin-bottom: 0.5rem; background: white; border-radius: 10px; border: 1.5px solid #e2e8f0; }
.col-left { display: flex; align-items: center; gap: 0.45rem; }
.col-dot { width: 8px; height: 8px; border-radius: 50%; }
.col-title { font-size: 0.78rem; font-weight: 700; color: #334155; }
.col-right { display: flex; align-items: center; gap: 0.35rem; }
.col-total { font-size: 0.6rem; font-weight: 700; color: #10b981; }
.col-count { font-size: 0.65rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.4rem; border-radius: 8px; }
.col-body { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; min-height: 200px; padding: 0.3rem; border-radius: 10px; background: #f8fafc; }

/* Card */
.pipe-card { background: white; border-radius: 12px; padding: 0.85rem; border: 1.5px solid #e2e8f0; cursor: grab; transition: all 0.2s; }
.pipe-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-color: #cbd5e1; transform: translateY(-1px); }
.pipe-card:active { cursor: grabbing; }
.pipe-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem; }
.pipe-link { text-decoration: none; flex: 1; min-width: 0; }
.pipe-company { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; }
.pipe-link:hover .pipe-company { color: #8b5cf6; }
.pipe-contact { font-size: 0.72rem; color: #64748b; margin: 0.15rem 0 0; }
.priority-badge { font-size: 0.55rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 6px; flex-shrink: 0; white-space: nowrap; }
.pr-hot { background: #fef2f2; color: #ef4444; } .pr-warm { background: #fffbeb; color: #f59e0b; } .pr-cold { background: #eff6ff; color: #3b82f6; }

.pipe-social { display: flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; color: #64748b; margin-bottom: 0.35rem; }
.pipe-social i { font-size: 0.75rem; color: #3b82f6; }

.pipe-value { display: flex; align-items: center; gap: 0.25rem; font-size: 0.82rem; font-weight: 800; color: #10b981; margin-bottom: 0.35rem; padding: 0.25rem 0.5rem; background: #ecfdf5; border-radius: 8px; }
.pipe-value i { font-size: 0.65rem; }

.pipe-audit { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; }
.audit-track { flex: 1; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.audit-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }
.a-good { background: #10b981; } .a-warn { background: #f59e0b; } .a-low { background: #ef4444; }
.audit-label { font-size: 0.6rem; font-weight: 700; }
.audit-label.a-good { color: #10b981; } .audit-label.a-warn { color: #f59e0b; } .audit-label.a-low { color: #ef4444; }

.pipe-bottom { display: flex; align-items: center; justify-content: space-between; }
.pipe-user { display: flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; color: #64748b; }
.pipe-avatar { width: 20px; height: 20px; border-radius: 6px; background: #f0e7ff; color: #8b5cf6; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.drag-handle { padding: 0.2rem; color: #cbd5e1; cursor: grab; }
.drag-handle:hover { color: #8b5cf6; }
.drag-handle i { font-size: 0.72rem; }

.col-empty { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 2rem; color: #cbd5e1; font-size: 0.72rem; }
.col-empty i { font-size: 1rem; }

.ghost-card { opacity: 0.4; background: #f1f5f9 !important; border: 2px dashed #94a3b8 !important; }
.chosen-card { cursor: grabbing !important; box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important; transform: rotate(1deg); }
.dragging-card { opacity: 0.9; }

.kanban-board::-webkit-scrollbar { height: 6px; }
.kanban-board::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.kanban-board::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
