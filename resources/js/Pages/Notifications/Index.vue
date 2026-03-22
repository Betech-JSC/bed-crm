<template>
  <div>
    <Head :title="isVi ? 'Trung tâm thông báo' : 'Notification Center'" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-bell" style="color: #6366f1; margin-right: 0.5rem;" />
          {{ isVi ? 'Trung tâm thông báo' : 'Notification Center' }}
        </h1>
        <p class="page-subtitle">{{ isVi ? 'Theo dõi tất cả hoạt động & cập nhật' : 'Track all activities & updates' }}</p>
      </div>
      <div class="header-actions">
        <Button
          :label="isVi ? 'Đọc tất cả' : 'Mark All Read'"
          icon="pi pi-check-circle"
          severity="secondary"
          text
          @click="markAllRead"
          :disabled="unread_count === 0"
        />
        <Button
          :label="isVi ? 'Cài đặt' : 'Settings'"
          icon="pi pi-cog"
          severity="secondary"
          outlined
          @click="showPreferences = true"
        />
      </div>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-strip">
      <div class="kpi-chip" :class="{ 'kpi-active': filterRead === false }" @click="toggleReadFilter(false)">
        <div class="kpi-icon kpi-icon--indigo"><i class="pi pi-envelope" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ unread_count }}</span>
          <span class="kpi-lbl">{{ isVi ? 'Chưa đọc' : 'Unread' }}</span>
        </div>
      </div>
      <div class="kpi-chip" @click="toggleReadFilter(null)">
        <div class="kpi-icon kpi-icon--blue"><i class="pi pi-bell" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ notifications.total || 0 }}</span>
          <span class="kpi-lbl">{{ isVi ? 'Tổng cộng' : 'Total' }}</span>
        </div>
      </div>
      <div class="kpi-chip" @click="setFilterSeverity('warning')">
        <div class="kpi-icon kpi-icon--amber"><i class="pi pi-exclamation-triangle" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ countBySeverity('warning') }}</span>
          <span class="kpi-lbl">{{ isVi ? 'Cảnh báo' : 'Warnings' }}</span>
        </div>
      </div>
      <div class="kpi-chip" @click="setFilterSeverity('danger')">
        <div class="kpi-icon kpi-icon--red"><i class="pi pi-bolt" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ countBySeverity('danger') }}</span>
          <span class="kpi-lbl">{{ isVi ? 'Quan trọng' : 'Critical' }}</span>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <InputText v-model="searchQuery" :placeholder="isVi ? 'Tìm thông báo...' : 'Search notifications...'" class="w-full" @input="handleSearch" />
      </div>
      <div class="filter-chips">
        <button
          v-for="sev in severityTabs" :key="sev.value"
          class="sev-tab" :class="{ active: filterSeverity === sev.value }"
          @click="setFilterSeverity(sev.value === filterSeverity ? null : sev.value)"
        >
          <i :class="sev.icon" />
          {{ sev.label }}
        </button>
      </div>
      <Select
        v-model="filterEvent" :options="eventTypeOptions"
        optionLabel="label" optionValue="value"
        :placeholder="isVi ? 'Loại sự kiện' : 'Event Type'"
        showClear @change="applyFilters" class="filter-select"
      />
      <Button v-if="hasFilters" icon="pi pi-times" severity="secondary" text size="small" @click="clearFilters" v-tooltip="'Reset'" />
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedIds.length > 0" class="bulk-bar">
      <span class="bulk-count"><i class="pi pi-check-square" /> {{ selectedIds.length }} {{ isVi ? 'đã chọn' : 'selected' }}</span>
      <Button :label="isVi ? 'Đọc' : 'Read'" icon="pi pi-eye" text size="small" @click="bulkMarkRead" />
      <Button :label="isVi ? 'Xoá' : 'Delete'" icon="pi pi-trash" text severity="danger" size="small" @click="bulkDelete" />
      <Button :label="isVi ? 'Bỏ chọn' : 'Deselect'" text size="small" @click="selectedIds = []" />
    </div>

    <!-- Notification Groups -->
    <div class="notif-timeline">
      <template v-for="(group, label) in groupedNotifications" :key="label">
        <div class="group-header">
          <span class="group-label">{{ label }}</span>
          <span class="group-count">{{ group.length }}</span>
        </div>
        <div class="notif-list">
          <div
            v-for="notif in group" :key="notif.id"
            class="notif-card"
            :class="{
              unread: !notif.read_at,
              [`sev-${notif.severity}`]: true,
              selected: selectedIds.includes(notif.id),
            }"
          >
            <!-- Checkbox -->
            <div class="notif-check" @click.stop="toggleSelect(notif.id)">
              <i :class="selectedIds.includes(notif.id) ? 'pi pi-check-square' : 'pi pi-stop'" />
            </div>

            <!-- Icon -->
            <div class="notif-icon" :class="`icon-${notif.severity}`" @click="openNotif(notif)">
              <i :class="notif.icon || severityIcon(notif.severity)" />
            </div>

            <!-- Content -->
            <div class="notif-content" @click="openNotif(notif)">
              <div class="notif-top">
                <h3 class="notif-title">{{ notif.title }}</h3>
                <span class="notif-time">
                  <i class="pi pi-clock" />
                  {{ timeAgo(notif.created_at) }}
                </span>
              </div>
              <p v-if="notif.body" class="notif-body">{{ notif.body }}</p>
              <div class="notif-meta">
                <span class="notif-badge" :class="`badge-${notif.severity}`">
                  <i :class="notif.icon || severityIcon(notif.severity)" class="badge-icon" />
                  {{ getEventLabel(notif.event_type) }}
                </span>
                <span v-if="!notif.read_at" class="unread-dot" />
                <span v-if="notif.link" class="link-hint"><i class="pi pi-external-link" /></span>
              </div>
            </div>

            <!-- Actions -->
            <div class="notif-actions">
              <Button v-if="!notif.read_at" icon="pi pi-eye" text rounded size="small" v-tooltip.left="isVi ? 'Đánh dấu đã đọc' : 'Mark as read'" @click.stop="markSingleRead(notif)" />
              <Button icon="pi pi-trash" text rounded severity="danger" size="small" v-tooltip.left="isVi ? 'Xoá' : 'Delete'" @click.stop="deleteNotif(notif)" />
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Empty -->
    <div v-if="notifications.data.length === 0" class="empty-state">
      <div class="empty-icon-box">
        <i class="pi pi-bell-slash" />
      </div>
      <h3>{{ isVi ? 'Không có thông báo' : 'No notifications' }}</h3>
      <p>{{ isVi ? 'Bạn đã cập nhật mọi thứ rồi! 🎉' : "You're all caught up! 🎉" }}</p>
      <Button v-if="hasFilters" :label="isVi ? 'Xoá bộ lọc' : 'Clear filters'" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
    </div>

    <!-- Pagination -->
    <div v-if="notifications.last_page > 1" class="pagination-wrapper">
      <span class="pagination-info">
        {{ notifications.from }}–{{ notifications.to }} / {{ notifications.total }}
      </span>
      <Paginator
        :first="(notifications.current_page - 1) * notifications.per_page"
        :rows="notifications.per_page"
        :totalRecords="notifications.total"
        @page="onPageChange"
        template="PrevPageLink PageLinks NextPageLink"
      />
    </div>

    <!-- Preferences Dialog -->
    <Dialog v-model:visible="showPreferences" :header="isVi ? 'Cài đặt thông báo' : 'Notification Settings'" modal :style="{ width: '580px' }">
      <div class="pref-header-info">
        <i class="pi pi-info-circle" />
        <span>{{ isVi ? 'Chọn kênh nhận thông báo cho mỗi loại sự kiện' : 'Choose notification channels for each event type' }}</span>
      </div>

      <!-- Column Headers -->
      <div class="pref-col-header">
        <span class="pref-col-label">{{ isVi ? 'Loại sự kiện' : 'Event Type' }}</span>
        <div class="pref-col-channels">
          <span><i class="pi pi-desktop" /> {{ isVi ? 'Trong app' : 'In-App' }}</span>
          <span><i class="pi pi-envelope" /> Email</span>
        </div>
      </div>

      <div class="pref-list">
        <div v-for="et in event_types" :key="et.value" class="pref-row">
          <div class="pref-label">
            <div class="pref-icon-box" :class="`pref-sev-${et.severity}`">
              <i :class="et.icon" />
            </div>
            <div>
              <span class="pref-event-name">{{ isVi ? et.label_vi : et.label_en }}</span>
              <span class="pref-sev-badge" :class="`sev-text-${et.severity}`">{{ et.severity }}</span>
            </div>
          </div>
          <div class="pref-toggles">
            <label class="toggle-switch">
              <input type="checkbox" :checked="getPref(et.value, 'in_app')" @change="setPref(et.value, 'in_app', $event.target.checked)" />
              <span class="toggle-slider" />
            </label>
            <label class="toggle-switch">
              <input type="checkbox" :checked="getPref(et.value, 'email')" @change="setPref(et.value, 'email', $event.target.checked)" />
              <span class="toggle-slider" />
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showPreferences = false" />
        <Button :label="isVi ? 'Lưu cài đặt' : 'Save Settings'" icon="pi pi-check" @click="savePreferences" :loading="savingPrefs" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Paginator from 'primevue/paginator'
import axios from 'axios'
import throttle from 'lodash/throttle'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, InputText, Select, Dialog, Paginator },
  layout: Layout,
  props: {
    notifications: Object,
    unread_count: Number,
    event_types: Array,
    preferences: Object,
    filters: Object,
  },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      filterRead: this.filters?.read ?? null,
      filterEvent: this.filters?.event_type ?? null,
      filterSeverity: this.filters?.severity ?? null,
      searchQuery: '',
      selectedIds: [],
      showPreferences: false,
      savingPrefs: false,
      localPrefs: JSON.parse(JSON.stringify(this.preferences || {})),
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    hasFilters() { return this.filterRead !== null || this.filterEvent || this.filterSeverity || this.searchQuery },
    severityTabs() {
      return [
        { value: 'info', label: 'Info', icon: 'pi pi-info-circle' },
        { value: 'success', label: this.isVi ? 'Hoàn thành' : 'Success', icon: 'pi pi-check-circle' },
        { value: 'warning', label: this.isVi ? 'Cảnh báo' : 'Warning', icon: 'pi pi-exclamation-triangle' },
        { value: 'danger', label: this.isVi ? 'Quan trọng' : 'Critical', icon: 'pi pi-bolt' },
      ]
    },
    eventTypeOptions() {
      return this.event_types.map(et => ({
        value: et.value,
        label: this.isVi ? et.label_vi : et.label_en,
      }))
    },
    groupedNotifications() {
      const groups = {}
      const now = new Date()
      const today = now.toDateString()
      const yesterday = new Date(now - 86400000).toDateString()

      for (const n of this.notifications.data) {
        const d = new Date(n.created_at)
        const ds = d.toDateString()
        let label
        if (ds === today) label = this.isVi ? '🔔 Hôm nay' : '🔔 Today'
        else if (ds === yesterday) label = this.isVi ? '📅 Hôm qua' : '📅 Yesterday'
        else if (now - d < 7 * 86400000) label = this.isVi ? '📋 Tuần này' : '📋 This Week'
        else label = this.isVi ? '📦 Trước đó' : '📦 Earlier'
        if (!groups[label]) groups[label] = []
        groups[label].push(n)
      }
      return groups
    },
  },
  methods: {
    getEventLabel(eventType) {
      const et = this.event_types.find(e => e.value === eventType)
      return et ? (this.isVi ? et.label_vi : et.label_en) : eventType
    },
    severityIcon(severity) {
      return { info: 'pi pi-info-circle', success: 'pi pi-check-circle', warning: 'pi pi-exclamation-triangle', danger: 'pi pi-bolt' }[severity] || 'pi pi-bell'
    },
    timeAgo(dateStr) {
      const ms = Date.now() - new Date(dateStr).getTime()
      const mins = Math.floor(ms / 60000)
      if (mins < 1) return this.isVi ? 'Vừa xong' : 'Just now'
      if (mins < 60) return `${mins}${this.isVi ? ' phút' : 'm'}`
      const hours = Math.floor(mins / 60)
      if (hours < 24) return `${hours}${this.isVi ? ' giờ' : 'h'}`
      const days = Math.floor(hours / 24)
      if (days < 7) return `${days}${this.isVi ? ' ngày' : 'd'}`
      return new Date(dateStr).toLocaleDateString(this.isVi ? 'vi-VN' : 'en-US', { month: 'short', day: 'numeric' })
    },
    countBySeverity(sev) {
      return this.notifications.data.filter(n => n.severity === sev).length
    },
    handleSearch: throttle(function () { this.applyFilters() }, 350),
    applyFilters() {
      const params = {}
      if (this.filterRead !== null) params.read = this.filterRead
      if (this.filterEvent) params.event_type = this.filterEvent
      if (this.filterSeverity) params.severity = this.filterSeverity
      if (this.searchQuery) params.search = this.searchQuery
      router.get('/notifications', params, { preserveState: true })
    },
    toggleReadFilter(val) {
      this.filterRead = this.filterRead === val ? null : val
      this.applyFilters()
    },
    setFilterSeverity(val) {
      this.filterSeverity = val
      this.applyFilters()
    },
    clearFilters() {
      this.filterRead = null; this.filterEvent = null; this.filterSeverity = null; this.searchQuery = ''
      router.get('/notifications', {}, { preserveState: true })
    },
    toggleSelect(id) {
      const idx = this.selectedIds.indexOf(id)
      if (idx >= 0) this.selectedIds.splice(idx, 1)
      else this.selectedIds.push(id)
    },
    async openNotif(notif) {
      if (!notif.read_at) {
        await axios.post(`/notifications/${notif.id}/read`)
        notif.read_at = new Date().toISOString()
      }
      if (notif.link) router.visit(notif.link)
    },
    async markSingleRead(notif) {
      await axios.post(`/notifications/${notif.id}/read`)
      notif.read_at = new Date().toISOString()
    },
    async markAllRead() {
      await axios.post('/notifications/read-all')
      router.reload()
    },
    async deleteNotif(notif) {
      await axios.delete(`/notifications/${notif.id}`)
      router.reload()
    },
    async bulkMarkRead() {
      for (const id of this.selectedIds) {
        await axios.post(`/notifications/${id}/read`)
      }
      this.selectedIds = []
      router.reload()
    },
    async bulkDelete() {
      for (const id of this.selectedIds) {
        await axios.delete(`/notifications/${id}`)
      }
      this.selectedIds = []
      router.reload()
    },
    onPageChange(e) {
      router.get('/notifications', { ...this.filters, page: e.page + 1 }, { preserveState: true })
    },
    getPref(eventType, channel) {
      const p = this.localPrefs[eventType]
      return p ? p[channel] : true
    },
    setPref(eventType, channel, value) {
      if (!this.localPrefs[eventType]) {
        this.localPrefs[eventType] = { event_type: eventType, in_app: true, email: true }
      }
      this.localPrefs[eventType][channel] = value
    },
    async savePreferences() {
      this.savingPrefs = true
      const prefs = Object.entries(this.localPrefs).map(([key, val]) => ({
        event_type: val.event_type || key,
        in_app: val.in_app ?? true,
        email: val.email ?? true,
      }))
      await axios.post('/notifications/preferences', { preferences: prefs })
      this.savingPrefs = false
      this.showPreferences = false
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* ===== KPI Strip ===== */
.kpi-strip { display: flex; gap: 0.5rem; margin-bottom: 1rem; overflow-x: auto; }
.kpi-chip {
  display: flex; align-items: center; gap: 0.55rem;
  padding: 0.6rem 0.85rem; background: white; border-radius: 12px;
  border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.kpi-chip:hover { border-color: #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px); }
.kpi-chip.kpi-active { border-color: #6366f1; background: #fafbff; }
.kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
.kpi-icon--indigo { background: #eef2ff; color: #6366f1; }
.kpi-icon--blue { background: #dbeafe; color: #3b82f6; }
.kpi-icon--amber { background: #fef3c7; color: #d97706; }
.kpi-icon--red { background: #fee2e2; color: #dc2626; }
.kpi-data { display: flex; flex-direction: column; }
.kpi-num { font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.kpi-lbl { font-size: 0.6rem; color: #94a3b8; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.85rem;
  background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9; margin-bottom: 1rem; flex-wrap: wrap;
}
.search-box { display: flex; align-items: center; gap: 0.35rem; flex: 1; min-width: 200px; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.filter-chips { display: flex; gap: 0.25rem; flex-shrink: 0; }
.sev-tab {
  display: flex; align-items: center; gap: 0.25rem;
  padding: 0.35rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px;
  background: white; font-size: 0.68rem; font-weight: 500; color: #64748b;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.sev-tab:hover { border-color: #cbd5e1; background: #f8fafc; }
.sev-tab.active { border-color: #6366f1; background: #eef2ff; color: #6366f1; font-weight: 600; }
.sev-tab i { font-size: 0.62rem; }
.filter-select { min-width: 155px; }

/* ===== Bulk Bar ===== */
.bulk-bar {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.85rem; background: #eef2ff; border-radius: 10px;
  border: 1px solid #c7d2fe; margin-bottom: 0.75rem;
  animation: slideDown 0.2s ease;
}
.bulk-count { font-size: 0.78rem; font-weight: 600; color: #4338ca; display: flex; align-items: center; gap: 0.3rem; flex: 1; }
.bulk-count i { font-size: 0.72rem; }

/* ===== Timeline Groups ===== */
.notif-timeline { display: flex; flex-direction: column; gap: 0.25rem; }
.group-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; }
.group-label { font-size: 0.78rem; font-weight: 600; color: #475569; }
.group-count { font-size: 0.6rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.4rem; border-radius: 10px; }

/* ===== Notification List ===== */
.notif-list { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.5rem; }
.notif-card {
  display: flex; align-items: flex-start; gap: 0.55rem;
  padding: 0.75rem 0.85rem; background: white; border-radius: 12px;
  border: 1px solid #f1f5f9; cursor: pointer; transition: all 0.2s;
  animation: fadeInUp 0.3s ease;
}
.notif-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.notif-card.unread { background: #fafbff; border-left: 3px solid #6366f1; }
.notif-card.unread.sev-success { border-left-color: #10b981; }
.notif-card.unread.sev-warning { border-left-color: #f59e0b; }
.notif-card.unread.sev-danger { border-left-color: #ef4444; }
.notif-card.selected { background: #eef2ff; border-color: #c7d2fe; }

/* Checkbox */
.notif-check {
  flex-shrink: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;
  color: #cbd5e1; font-size: 0.85rem; margin-top: 0.1rem; cursor: pointer; transition: color 0.15s;
}
.notif-check:hover { color: #6366f1; }
.notif-card.selected .notif-check { color: #6366f1; }

/* Icon */
.notif-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 0.85rem; transition: transform 0.15s;
}
.notif-card:hover .notif-icon { transform: scale(1.05); }
.icon-info { background: #eef2ff; color: #6366f1; }
.icon-success { background: #ecfdf5; color: #10b981; }
.icon-warning { background: #fffbeb; color: #f59e0b; }
.icon-danger { background: #fef2f2; color: #ef4444; }

/* Content */
.notif-content { flex: 1; min-width: 0; }
.notif-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.notif-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.35; }
.notif-card.unread .notif-title { font-weight: 700; }
.notif-time { font-size: 0.62rem; color: #94a3b8; white-space: nowrap; flex-shrink: 0; display: flex; align-items: center; gap: 0.2rem; }
.notif-time i { font-size: 0.55rem; }
.notif-body { font-size: 0.72rem; color: #64748b; margin: 0.2rem 0 0.35rem; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Meta */
.notif-meta { display: flex; align-items: center; gap: 0.4rem; }
.notif-badge {
  font-size: 0.55rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 5px;
  text-transform: uppercase; letter-spacing: 0.03em; display: flex; align-items: center; gap: 0.2rem;
}
.badge-icon { font-size: 0.5rem; }
.badge-info { background: #eef2ff; color: #6366f1; }
.badge-success { background: #ecfdf5; color: #059669; }
.badge-warning { background: #fffbeb; color: #d97706; }
.badge-danger { background: #fef2f2; color: #dc2626; }
.unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #6366f1; flex-shrink: 0; animation: pulse 2s infinite; }
.link-hint { color: #94a3b8; font-size: 0.6rem; }

/* Actions */
.notif-actions { flex-shrink: 0; display: flex; gap: 0.1rem; opacity: 0; transition: opacity 0.15s; }
.notif-card:hover .notif-actions { opacity: 1; }

/* ===== Empty State ===== */
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 4rem 2rem; text-align: center;
}
.empty-icon-box {
  width: 72px; height: 72px; border-radius: 20px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 0.5rem;
}
.empty-icon-box i { font-size: 1.8rem; color: #6366f1; }
.empty-state h3 { font-size: 1.05rem; font-weight: 600; color: #475569; margin: 0; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

/* ===== Pagination ===== */
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 0; margin-top: 0.5rem; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }

/* ===== Preferences Dialog ===== */
.pref-header-info {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.65rem 0.85rem; background: #f8fafc; border-radius: 10px;
  font-size: 0.78rem; color: #64748b; margin-bottom: 1rem;
}
.pref-header-info i { color: #6366f1; font-size: 0.82rem; }

.pref-col-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0 0 0.5rem; border-bottom: 1px solid #f1f5f9; margin-bottom: 0.25rem;
}
.pref-col-label { font-size: 0.72rem; font-weight: 600; color: #475569; }
.pref-col-channels { display: flex; gap: 1.25rem; }
.pref-col-channels span { font-size: 0.65rem; font-weight: 600; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.pref-col-channels i { font-size: 0.6rem; }

.pref-list { display: flex; flex-direction: column; max-height: 380px; overflow-y: auto; }
.pref-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.55rem 0; border-bottom: 1px solid #f8fafc; transition: background 0.15s;
}
.pref-row:hover { background: #fafbff; }
.pref-label { display: flex; align-items: center; gap: 0.55rem; }
.pref-icon-box {
  width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; font-size: 0.72rem;
}
.pref-sev-info { background: #eef2ff; color: #6366f1; }
.pref-sev-success { background: #ecfdf5; color: #10b981; }
.pref-sev-warning { background: #fffbeb; color: #f59e0b; }
.pref-sev-danger { background: #fef2f2; color: #ef4444; }
.pref-event-name { font-size: 0.78rem; font-weight: 600; color: #334155; display: block; }
.pref-sev-badge { font-size: 0.55rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
.sev-text-info { color: #6366f1; }
.sev-text-success { color: #10b981; }
.sev-text-warning { color: #f59e0b; }
.sev-text-danger { color: #ef4444; }

.pref-toggles { display: flex; gap: 1.75rem; padding-right: 0.5rem; }

/* Toggle Switch */
.toggle-switch { position: relative; display: inline-block; width: 36px; height: 20px; cursor: pointer; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  background: #e2e8f0; border-radius: 10px; transition: background 0.2s;
}
.toggle-slider::after {
  content: ''; position: absolute; width: 16px; height: 16px;
  border-radius: 50%; background: white; top: 2px; left: 2px;
  transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.toggle-switch input:checked + .toggle-slider { background: #6366f1; }
.toggle-switch input:checked + .toggle-slider::after { transform: translateX(16px); }

/* ===== Animations ===== */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes slideDown {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.w-full { width: 100%; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .kpi-strip { flex-wrap: nowrap; }
  .filter-bar { flex-direction: column; }
  .search-box { width: 100%; }
  .filter-chips { flex-wrap: wrap; }
  .bulk-bar { flex-wrap: wrap; }
}
</style>
