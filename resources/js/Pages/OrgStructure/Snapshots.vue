<template>
  <div>
    <Head :title="isVi ? 'Lịch sử cơ cấu' : 'Org Snapshots'" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <button class="btn-back" @click="$inertia.visit('/org-structure')"><i class="pi pi-arrow-left" /></button>
        <div class="header-icon"><i class="pi pi-history" /></div>
        <div>
          <h1 class="page-title">{{ isVi ? 'Lịch sử cơ cấu tổ chức' : 'Organization History' }}</h1>
          <p class="page-subtitle">{{ snapshotsData.length }} {{ isVi ? 'phiên bản đã lưu' : 'saved versions' }}</p>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="snapshotsData.length === 0" class="empty-state">
      <div class="empty-icon"><i class="pi pi-camera" /></div>
      <h3>{{ isVi ? 'Chưa có snapshot nào' : 'No snapshots yet' }}</h3>
      <p>{{ isVi ? 'Snapshot giúp theo dõi thay đổi cơ cấu theo thời gian' : 'Snapshots help track changes over time' }}</p>
      <button class="btn-primary" @click="$inertia.visit('/org-structure')"><i class="pi pi-arrow-left" /> {{ isVi ? 'Quay lại' : 'Go Back' }}</button>
    </div>

    <!-- Timeline -->
    <div v-else class="timeline-wrap">
      <div class="timeline-line-bg" />
      <div v-for="(snap, idx) in snapshotsData" :key="snap.id" class="timeline-item" :style="{ animationDelay: idx * 0.05 + 's' }">
        <div class="tl-dot" :class="{ current: idx === 0 }">
          <div class="dot-inner" />
        </div>
        <div class="snap-card" :class="{ latest: idx === 0 }">
          <div v-if="idx === 0" class="snap-latest-badge">{{ isVi ? 'Mới nhất' : 'Latest' }}</div>
          <div class="snap-header">
            <div class="snap-icon"><i class="pi pi-history" /></div>
            <div class="snap-info">
              <h3 class="snap-title">{{ snap.name }}</h3>
              <p v-if="snap.description" class="snap-desc">{{ snap.description }}</p>
            </div>
            <div class="snap-date">
              <i class="pi pi-calendar" />
              <span>{{ formatDate(snap.snapshot_date) }}</span>
            </div>
          </div>

          <div class="snap-body">
            <!-- Stats -->
            <div v-if="snap.snapshot_data" class="snap-stats">
              <div v-if="snap.snapshot_data.total_departments" class="snap-stat">
                <i class="pi pi-building" />
                <span class="snap-stat-value">{{ snap.snapshot_data.total_departments }}</span>
                <span class="snap-stat-label">{{ isVi ? 'Phòng ban' : 'Depts' }}</span>
              </div>
              <div v-if="snap.snapshot_data.total_teams" class="snap-stat">
                <i class="pi pi-users" />
                <span class="snap-stat-value">{{ snap.snapshot_data.total_teams }}</span>
                <span class="snap-stat-label">{{ isVi ? 'Nhóm' : 'Teams' }}</span>
              </div>
              <div v-if="snap.snapshot_data.total_headcount" class="snap-stat">
                <i class="pi pi-user" />
                <span class="snap-stat-value">{{ snap.snapshot_data.total_headcount }}</span>
                <span class="snap-stat-label">{{ isVi ? 'Nhân viên' : 'People' }}</span>
              </div>
            </div>

            <!-- Change indicators vs previous snapshot -->
            <div v-if="idx < snapshotsData.length - 1 && snap.snapshot_data && snapshotsData[idx + 1].snapshot_data" class="snap-changes">
              <span v-for="c in getChanges(snap, snapshotsData[idx + 1])" :key="c.label" class="change-chip" :class="c.direction">
                <i :class="c.direction === 'up' ? 'pi pi-arrow-up' : c.direction === 'down' ? 'pi pi-arrow-down' : 'pi pi-minus'" />
                {{ c.diff }} {{ c.label }}
              </span>
            </div>

            <!-- Author -->
            <div v-if="snap.created_by_user" class="snap-author">
              <div class="author-avatar">{{ (snap.created_by_user.first_name || '?')[0] }}</div>
              <span>{{ snap.created_by_user.first_name }} {{ snap.created_by_user.last_name }}</span>
              <span class="snap-time">{{ formatTime(snap.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'
export default {
  components: { Head },
  layout: Layout,
  props: { snapshots: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  computed: {
    isVi() { return this.locale === 'vi' },
    snapshotsData() { return this.snapshots?.data || this.snapshots || [] },
  },
  methods: {
    formatDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString(this.isVi ? 'vi-VN' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' }) },
    formatTime(d) { if (!d) return ''; return new Date(d).toLocaleTimeString(this.isVi ? 'vi-VN' : 'en-US', { hour: '2-digit', minute: '2-digit' }) },
    getChanges(current, previous) {
      const changes = []
      const fields = [
        { key: 'total_departments', label: this.isVi ? 'Phòng ban' : 'Depts' },
        { key: 'total_teams', label: this.isVi ? 'Nhóm' : 'Teams' },
        { key: 'total_headcount', label: this.isVi ? 'Nhân viên' : 'People' },
      ]
      fields.forEach(f => {
        const curr = current.snapshot_data?.[f.key] || 0
        const prev = previous.snapshot_data?.[f.key] || 0
        const diff = curr - prev
        if (diff !== 0) {
          changes.push({
            label: f.label,
            diff: (diff > 0 ? '+' : '') + diff,
            direction: diff > 0 ? 'up' : 'down',
          })
        }
      })
      return changes
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; transition: all 0.2s; }
.btn-back:hover { border-color: #6366f1; color: #6366f1; }
.header-icon { width: 44px; height: 44px; border-radius: 13px; background: linear-gradient(135deg, #8b5cf6, #6366f1); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; box-shadow: 0 4px 14px rgba(99,102,241,0.25); }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.78rem; color: #64748b; margin: 0.1rem 0 0; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; background: white; border: 2px dashed #e2e8f0; border-radius: 20px; }
.empty-icon { width: 72px; height: 72px; border-radius: 20px; background: #f5f3ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }
.btn-primary { display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* Timeline */
.timeline-wrap { position: relative; padding-left: 3rem; }
.timeline-line-bg { position: absolute; left: 1.1rem; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #c7d2fe, #e2e8f0, transparent); border-radius: 2px; }

.timeline-item { position: relative; margin-bottom: 1.25rem; animation: tl-slide 0.3s ease both; }
@keyframes tl-slide { from { opacity: 0; transform: translateX(-10px); } }

.tl-dot { position: absolute; left: -3rem; top: 1.25rem; width: 22px; height: 22px; border-radius: 50%; background: white; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; z-index: 2; transition: all 0.2s; }
.tl-dot.current { border-color: #6366f1; background: white; }
.dot-inner { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; }
.tl-dot.current .dot-inner { background: #6366f1; animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.3); } }

.snap-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 16px; overflow: hidden; transition: all 0.25s; position: relative; }
.snap-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); border-color: #cbd5e1; }
.snap-card.latest { border-color: #c7d2fe; }

.snap-latest-badge { position: absolute; top: 0.75rem; right: 0.75rem; font-size: 0.6rem; font-weight: 700; color: #6366f1; background: #eef2ff; padding: 0.15rem 0.5rem; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }

.snap-header { display: flex; align-items: flex-start; gap: 0.75rem; padding: 1.15rem 1.35rem; }
.snap-icon { width: 38px; height: 38px; border-radius: 11px; background: #f0f0ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snap-icon i { font-size: 0.85rem; color: #6366f1; }
.snap-info { flex: 1; min-width: 0; }
.snap-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; }
.snap-desc { font-size: 0.78rem; color: #64748b; margin: 0.2rem 0 0; line-height: 1.4; }
.snap-date { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #94a3b8; background: #f8fafc; padding: 0.25rem 0.65rem; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.snap-date i { font-size: 0.65rem; }

.snap-body { padding: 0 1.35rem 1.15rem; }

/* Stats chips */
.snap-stats { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.65rem; }
.snap-stat { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.65rem; background: #f8fafc; border-radius: 8px; border: 1px solid #f1f5f9; }
.snap-stat i { font-size: 0.65rem; color: #94a3b8; }
.snap-stat-value { font-size: 0.82rem; font-weight: 800; color: #1e293b; }
.snap-stat-label { font-size: 0.65rem; color: #94a3b8; }

/* Changes */
.snap-changes { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.65rem; }
.change-chip { display: flex; align-items: center; gap: 0.2rem; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.65rem; font-weight: 700; }
.change-chip.up { background: #ecfdf5; color: #059669; }
.change-chip.down { background: #fef2f2; color: #dc2626; }
.change-chip i { font-size: 0.55rem; }

/* Author */
.snap-author { display: flex; align-items: center; gap: 0.4rem; padding-top: 0.65rem; border-top: 1px solid #f1f5f9; }
.author-avatar { width: 24px; height: 24px; border-radius: 6px; background: #6366f1; color: white; font-size: 0.55rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.snap-author span { font-size: 0.75rem; color: #475569; font-weight: 500; }
.snap-time { color: #94a3b8 !important; font-weight: 400 !important; margin-left: auto; }

@media (max-width: 768px) {
  .timeline-wrap { padding-left: 2rem; }
  .tl-dot { left: -2rem; }
  .snap-header { flex-wrap: wrap; }
}
</style>
