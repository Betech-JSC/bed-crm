<template>
  <div class="okr-card" :class="[`level-${level}`, `status-${objective.status}`]">
    <div class="okr-card-main">
      <div class="okr-level-badge" :class="`badge-${objective.level}`">
        {{ objective.level === 'company' ? '🏢' : objective.level === 'team' ? '👥' : '👤' }}
      </div>
      <div class="okr-card-body">
        <div class="okr-card-top">
          <h3 class="okr-title">{{ objective.title }}</h3>
          <div class="okr-actions">
            <Button v-if="objective.level !== 'individual'" icon="pi pi-arrow-down" text rounded size="small"
              v-tooltip="isVi ? 'Cascade' : 'Cascade'" @click="$emit('cascade', objective)" />
          </div>
        </div>
        <div class="okr-meta">
          <span v-if="objective.owner" class="okr-owner"><i class="pi pi-user" /> {{ objective.owner }}</span>
          <span v-if="objective.period" class="okr-period"><i class="pi pi-calendar" /> {{ objective.period }}</span>
          <span class="okr-status-badge" :class="`status-${objective.status}`">{{ statusLabel }}</span>
        </div>
        <div class="okr-progress-row">
          <div class="okr-progress-bar">
            <div class="okr-progress-fill" :class="progressColor" :style="{ width: Math.min(objective.progress, 100) + '%' }" />
          </div>
          <span class="okr-progress-pct">{{ objective.progress }}%</span>
          <span class="okr-confidence" v-tooltip="isVi ? 'Mức tự tin' : 'Confidence'">🎯 {{ objective.confidence }}%</span>
        </div>
      </div>
    </div>

    <!-- Key Results -->
    <div v-if="objective.key_results && objective.key_results.length" class="kr-list">
      <div v-for="kr in objective.key_results" :key="kr.id" class="kr-item" @click="$emit('check-in', kr)">
        <div class="kr-status-dot" :class="`dot-${kr.status}`" />
        <span class="kr-title">{{ kr.title }}</span>
        <div class="kr-values">
          <span class="kr-current">{{ formatValue(kr.current_value) }}</span>
          <span class="kr-sep">/</span>
          <span class="kr-target">{{ formatValue(kr.target_value) }}</span>
        </div>
        <div class="kr-progress-mini">
          <div class="kr-progress-mini-fill" :class="krProgressColor(kr.progress)" :style="{ width: Math.min(kr.progress, 100) + '%' }" />
        </div>
        <span class="kr-pct">{{ kr.progress }}%</span>
        <span v-if="kr.data_source !== 'manual'" class="kr-auto-badge" v-tooltip="isVi ? 'Tự động từ CRM' : 'Auto-tracked from CRM'">⚡</span>
      </div>
    </div>

    <div v-if="objective.initiatives_count" class="initiative-count">
      <i class="pi pi-flag" /> {{ objective.initiatives_count }} {{ isVi ? 'sáng kiến' : 'initiatives' }}
    </div>
  </div>
</template>

<script>
import Button from 'primevue/button'

export default {
  components: { Button },
  props: {
    objective: Object,
    isVi: Boolean,
    level: { type: Number, default: 0 },
  },
  emits: ['check-in', 'cascade'],
  computed: {
    statusLabel() {
      const map = {
        draft: this.isVi ? 'Nháp' : 'Draft',
        active: this.isVi ? 'Hoạt động' : 'Active',
        at_risk: this.isVi ? 'Có rủi ro' : 'At Risk',
        completed: this.isVi ? 'Hoàn thành' : 'Completed',
        cancelled: this.isVi ? 'Đã huỷ' : 'Cancelled',
      }
      return map[this.objective.status] || this.objective.status
    },
    progressColor() {
      const p = this.objective.progress
      if (p >= 70) return 'fill-green'
      if (p >= 40) return 'fill-amber'
      return 'fill-red'
    },
  },
  methods: {
    formatValue(v) {
      if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M'
      if (v >= 1000) return (v / 1000).toFixed(0) + 'K'
      return String(v)
    },
    krProgressColor(p) {
      if (p >= 70) return 'fill-green'
      if (p >= 40) return 'fill-amber'
      return 'fill-red'
    },
  },
}
</script>

<style scoped>
.okr-card { background: white; border: 1px solid #f1f5f9; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 0.5rem; transition: all 0.2s; }
.okr-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
.okr-card.level-0 { border-left: 3px solid #6366f1; }
.okr-card.level-1 { border-left: 3px solid #10b981; }
.okr-card.level-2 { border-left: 3px solid #f59e0b; }
.okr-card.status-completed { opacity: 0.7; }

.okr-card-main { display: flex; gap: 0.75rem; }
.okr-level-badge { font-size: 1.1rem; flex-shrink: 0; margin-top: 2px; }
.okr-card-body { flex: 1; min-width: 0; }
.okr-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
.okr-title { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.3; }
.okr-actions { flex-shrink: 0; }

.okr-meta { display: flex; gap: 0.5rem; margin: 0.25rem 0 0.4rem; flex-wrap: wrap; }
.okr-owner, .okr-period { font-size: 0.6rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.okr-status-badge { font-size: 0.55rem; font-weight: 700; padding: 0.05rem 0.3rem; border-radius: 4px; text-transform: uppercase; }
.okr-status-badge.status-active { background: #eef2ff; color: #6366f1; }
.okr-status-badge.status-at_risk { background: #fffbeb; color: #d97706; }
.okr-status-badge.status-completed { background: #ecfdf5; color: #059669; }
.okr-status-badge.status-draft { background: #f8fafc; color: #94a3b8; }

.okr-progress-row { display: flex; align-items: center; gap: 0.5rem; }
.okr-progress-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.okr-progress-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
.fill-green { background: #10b981; }
.fill-amber { background: #f59e0b; }
.fill-red { background: #ef4444; }
.okr-progress-pct { font-size: 0.7rem; font-weight: 700; color: #334155; min-width: 28px; }
.okr-confidence { font-size: 0.6rem; color: #94a3b8; }

/* Key Results */
.kr-list { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #f8fafc; }
.kr-item { display: flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0; cursor: pointer; border-radius: 4px; }
.kr-item:hover { background: #fafbff; }
.kr-status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.dot-on_track { background: #10b981; }
.dot-at_risk { background: #f59e0b; }
.dot-behind { background: #ef4444; }
.dot-completed { background: #6366f1; }
.kr-title { font-size: 0.72rem; color: #475569; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.kr-values { font-size: 0.6rem; color: #94a3b8; flex-shrink: 0; }
.kr-current { font-weight: 600; color: #334155; }
.kr-sep { margin: 0 0.1rem; }
.kr-target { color: #94a3b8; }
.kr-progress-mini { width: 40px; height: 3px; background: #f1f5f9; border-radius: 2px; overflow: hidden; flex-shrink: 0; }
.kr-progress-mini-fill { height: 100%; border-radius: 2px; }
.kr-pct { font-size: 0.55rem; font-weight: 600; color: #64748b; min-width: 24px; text-align: right; }
.kr-auto-badge { font-size: 0.6rem; flex-shrink: 0; }

.initiative-count { font-size: 0.62rem; color: #94a3b8; margin-top: 0.3rem; display: flex; align-items: center; gap: 0.25rem; }
</style>
