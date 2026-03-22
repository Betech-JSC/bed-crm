<template>
  <div>
    <Head title="SLA Settings" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-clock" />
        </div>
        <div>
          <h1 class="page-title">{{ t('common.sla_settings') }}</h1>
          <p class="page-subtitle">Quản lý thỏa thuận mức dịch vụ (SLA) — thời gian phản hồi & cảnh báo</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip active">
            <i class="pi pi-check-circle" />
            {{ slaSettings.filter(s => s.is_active).length }} Active
          </span>
          <span class="stat-chip total">
            <i class="pi pi-list" />
            {{ slaSettings.length }} Tổng
          </span>
        </div>
        <Link href="/sla-settings/create">
          <Button label="Tạo SLA" icon="pi pi-plus" />
        </Link>
      </div>
    </div>

    <!-- SLA Cards Grid -->
    <div v-if="slaSettings.length" class="sla-grid">
      <div
        v-for="sla in slaSettings"
        :key="sla.id"
        class="sla-card"
        :class="{ 'is-default': sla.is_default, 'is-inactive': !sla.is_active }"
      >
        <!-- Card Accent -->
        <div class="card-accent" :class="sla.is_active ? 'accent-active' : 'accent-inactive'" />

        <div class="card-body">
          <!-- Header Row -->
          <div class="card-header">
            <div class="sla-info">
              <div class="sla-name-row">
                <Link :href="`/sla-settings/${sla.id}/edit`" class="sla-name">{{ sla.name }}</Link>
                <span v-if="sla.is_default" class="badge badge-default">
                  <i class="pi pi-star-fill" /> Mặc định
                </span>
                <span class="badge" :class="sla.is_active ? 'badge-active' : 'badge-inactive'">
                  {{ sla.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <p class="sla-desc">{{ sla.description || 'Chưa có mô tả' }}</p>
            </div>
            <Link :href="`/sla-settings/${sla.id}/edit`">
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Chỉnh sửa'" />
            </Link>
          </div>

          <!-- Thresholds -->
          <div class="thresholds">
            <div class="threshold-item">
              <div class="threshold-icon response">
                <i class="pi pi-bolt" />
              </div>
              <div class="threshold-info">
                <span class="threshold-label">Phản hồi đầu tiên</span>
                <span class="threshold-value">{{ formatMinutes(sla.first_response_threshold) }}</span>
              </div>
            </div>
            <div class="threshold-divider" />
            <div class="threshold-item">
              <div class="threshold-icon warning">
                <i class="pi pi-exclamation-triangle" />
              </div>
              <div class="threshold-info">
                <span class="threshold-label">Cảnh báo</span>
                <span class="threshold-value">{{ formatMinutes(sla.warning_threshold) }}</span>
              </div>
            </div>
          </div>

          <!-- Lead Stats -->
          <div class="lead-stats">
            <div class="lead-stat pending">
              <i class="pi pi-hourglass" />
              <span class="lead-stat-value">{{ sla.pending_leads_count || 0 }}</span>
              <span class="lead-stat-label">Đang chờ</span>
            </div>
            <div class="lead-stat breached">
              <i class="pi pi-exclamation-circle" />
              <span class="lead-stat-value">{{ sla.breached_leads_count || 0 }}</span>
              <span class="lead-stat-label">Vi phạm</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="pi pi-clock" />
      </div>
      <h3>Chưa có SLA nào</h3>
      <p>Tạo cấu hình SLA đầu tiên để theo dõi thời gian phản hồi lead.</p>
      <Link href="/sla-settings/create">
        <Button label="Tạo SLA đầu tiên" icon="pi pi-plus" />
      </Link>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: { slaSettings: Array },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  methods: {
    formatMinutes(minutes) {
      if (!minutes) return '—'
      if (minutes < 60) return `${minutes} phút`
      const hours = Math.floor(minutes / 60)
      const mins = minutes % 60
      return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-actions { display: flex; align-items: center; gap: 0.65rem; }

.stat-chips { display: flex; gap: 0.4rem; }
.stat-chip {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.65rem; border-radius: 20px;
  font-size: 0.68rem; font-weight: 600;
}
.stat-chip i { font-size: 0.6rem; }
.stat-chip.active { background: #ecfdf5; color: #059669; }
.stat-chip.total { background: #eef2ff; color: #6366f1; }

/* ===== SLA Grid ===== */
.sla-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1rem;
}

.sla-card {
  background: white; border-radius: 16px;
  border: 1.5px solid #e2e8f0; overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.sla-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 8px 25px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.sla-card.is-default {
  border-color: #6366f1;
  box-shadow: 0 4px 20px rgba(99,102,241,0.12);
}
.sla-card.is-inactive { opacity: 0.6; }

.card-accent { height: 3px; }
.accent-active { background: linear-gradient(90deg, #10b981, #34d399); }
.accent-inactive { background: #e2e8f0; }

.card-body { padding: 1.15rem; }

/* Card Header */
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.85rem; }
.sla-name-row { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.sla-name {
  font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0;
  text-decoration: none; transition: color 0.15s;
}
.sla-name:hover { color: #6366f1; }
.sla-desc { font-size: 0.72rem; color: #94a3b8; margin: 0.2rem 0 0; }

.badge {
  font-size: 0.55rem; font-weight: 700; padding: 0.12rem 0.4rem;
  border-radius: 5px; display: flex; align-items: center; gap: 0.15rem;
  text-transform: uppercase; letter-spacing: 0.04em;
}
.badge-default { background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; }
.badge-active { background: #ecfdf5; color: #059669; }
.badge-inactive { background: #f1f5f9; color: #94a3b8; }

/* Thresholds */
.thresholds {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem; background: #f8fafc;
  border-radius: 10px; margin-bottom: 0.75rem;
}
.threshold-item { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
.threshold-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem;
}
.threshold-icon.response { background: #eef2ff; color: #6366f1; }
.threshold-icon.warning { background: #fffbeb; color: #f59e0b; }
.threshold-info { display: flex; flex-direction: column; }
.threshold-label { font-size: 0.58rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.threshold-value { font-size: 0.85rem; font-weight: 700; color: #1e293b; }
.threshold-divider { width: 1px; height: 28px; background: #e2e8f0; }

/* Lead Stats */
.lead-stats { display: flex; gap: 0.5rem; }
.lead-stat {
  flex: 1; display: flex; align-items: center; gap: 0.35rem;
  padding: 0.55rem 0.65rem; border-radius: 8px;
  font-size: 0.72rem;
}
.lead-stat i { font-size: 0.65rem; }
.lead-stat-value { font-weight: 700; }
.lead-stat-label { font-size: 0.62rem; color: inherit; opacity: 0.7; }
.lead-stat.pending { background: #fffbeb; color: #d97706; }
.lead-stat.breached { background: #fef2f2; color: #dc2626; }

/* ===== Empty State ===== */
.empty-state {
  text-align: center; padding: 3rem 2rem;
  background: white; border-radius: 16px; border: 2px dashed #e2e8f0;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem; font-size: 1.5rem; color: #6366f1;
}
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0 0 1.25rem; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .sla-grid { grid-template-columns: 1fr; }
  .header-actions { flex-wrap: wrap; }
}
</style>
