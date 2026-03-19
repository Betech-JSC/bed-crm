<template>
  <div>
    <Head :title="isVi ? 'Lịch sử cơ cấu' : 'Org Snapshots'" />

    <div class="page-header">
      <div class="page-header-left">
        <Button icon="pi pi-arrow-left" text rounded @click="$inertia.visit('/org-structure')" />
        <div>
          <h1 class="page-title">{{ isVi ? 'Lịch sử cơ cấu tổ chức' : 'Organization Structure History' }}</h1>
          <p class="page-subtitle">{{ snapshots.data?.length || 0 }} {{ isVi ? 'phiên bản đã lưu' : 'saved versions' }}</p>
        </div>
      </div>
    </div>

    <div v-if="!snapshots.data || snapshots.data.length === 0" class="empty-state">
      <div class="empty-illustration"><i class="pi pi-camera" /></div>
      <h3>{{ isVi ? 'Chưa có snapshot nào' : 'No snapshots taken yet' }}</h3>
      <p>{{ isVi ? 'Snapshot giúp bạn theo dõi thay đổi cơ cấu theo thời gian' : 'Snapshots help you track structural changes over time' }}</p>
    </div>

    <div v-else class="timeline">
      <div v-for="(snap, idx) in snapshots.data" :key="snap.id" class="timeline-item">
        <div class="timeline-dot" :class="{ first: idx === 0 }"><i class="pi pi-circle-fill" /></div>
        <div class="timeline-line" v-if="idx < snapshots.data.length - 1" />
        <div class="snapshot-card">
          <div class="snap-header">
            <div class="snap-icon"><i class="pi pi-history" /></div>
            <div class="snap-info">
              <h3 class="snap-title">{{ snap.name }}</h3>
              <p class="snap-desc" v-if="snap.description">{{ snap.description }}</p>
            </div>
            <div class="snap-date-badge">
              <i class="pi pi-calendar" />
              <span>{{ formatDate(snap.snapshot_date) }}</span>
            </div>
          </div>
          <div class="snap-footer" v-if="snap.created_by_user || snap.snapshot_data">
            <span v-if="snap.created_by_user" class="snap-author">
              <i class="pi pi-user" /> {{ snap.created_by_user.first_name }} {{ snap.created_by_user.last_name }}
            </span>
            <div v-if="snap.snapshot_data" class="snap-stats">
              <span v-if="snap.snapshot_data.total_departments"><i class="pi pi-building" /> {{ snap.snapshot_data.total_departments }}</span>
              <span v-if="snap.snapshot_data.total_teams"><i class="pi pi-users" /> {{ snap.snapshot_data.total_teams }}</span>
              <span v-if="snap.snapshot_data.total_headcount"><i class="pi pi-user" /> {{ snap.snapshot_data.total_headcount }}</span>
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
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'
export default {
  components: { Head, Button },
  layout: Layout,
  props: { snapshots: Object },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  computed: { isVi() { return this.locale === 'vi' } },
  methods: {
    formatDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString(this.isVi ? 'vi-VN' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.page-header-left { display: flex; align-items: center; gap: 0.5rem; }
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 2px dashed #e2e8f0; }
.empty-illustration { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #eef2ff, #e0e7ff); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-illustration i { font-size: 1.75rem; color: #6366f1; }
.empty-state h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.timeline { position: relative; padding-left: 2.5rem; }
.timeline-item { position: relative; margin-bottom: 1rem; }
.timeline-dot { position: absolute; left: -2.5rem; top: 1.25rem; width: 20px; height: 20px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; z-index: 1; }
.timeline-dot i { font-size: 0.5rem; color: #94a3b8; }
.timeline-dot.first { background: #eef2ff; }
.timeline-dot.first i { color: #6366f1; }
.timeline-line { position: absolute; left: calc(-2.5rem + 9px); top: 2.75rem; bottom: -1rem; width: 2px; background: #f1f5f9; }
.snapshot-card { background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 1.15rem 1.35rem; transition: all 0.25s; }
.snapshot-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.snap-header { display: flex; align-items: flex-start; gap: 0.75rem; }
.snap-icon { width: 36px; height: 36px; border-radius: 10px; background: #eef2ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.snap-icon i { font-size: 0.85rem; color: #6366f1; }
.snap-info { flex: 1; min-width: 0; }
.snap-title { font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0; }
.snap-desc { font-size: 0.78rem; color: #64748b; margin: 0.2rem 0 0; }
.snap-date-badge { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #94a3b8; background: #f8fafc; padding: 0.25rem 0.65rem; border-radius: 20px; white-space: nowrap; }
.snap-date-badge i { font-size: 0.65rem; }
.snap-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f8fafc; }
.snap-author { font-size: 0.72rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem; }
.snap-author i { font-size: 0.65rem; }
.snap-stats { display: flex; gap: 0.75rem; }
.snap-stats span { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem; font-weight: 600; }
.snap-stats i { font-size: 0.62rem; }
</style>
