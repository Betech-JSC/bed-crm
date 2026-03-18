<template>
  <div>
    <Head :title="isVi ? 'Trung tâm thông báo' : 'Notification Center'" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isVi ? 'Trung tâm thông báo' : 'Notification Center' }}</h1>
        <p class="page-subtitle">{{ isVi ? `${unread_count} chưa đọc` : `${unread_count} unread` }}</p>
      </div>
      <div class="header-actions">
        <Button :label="isVi ? 'Đọc tất cả' : 'Mark All Read'" icon="pi pi-check-circle" severity="secondary" @click="markAllRead" :disabled="unread_count === 0" />
        <Button :label="isVi ? 'Cài đặt' : 'Settings'" icon="pi pi-cog" severity="secondary" outlined @click="showPreferences = true" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <SelectButton v-model="filterRead" :options="readOptions" optionLabel="label" optionValue="value" @change="applyFilters" />
      <Dropdown v-model="filterEvent" :options="eventTypeOptions" optionLabel="label" optionValue="value"
        :placeholder="isVi ? 'Loại sự kiện' : 'Event Type'" showClear @change="applyFilters" class="filter-dropdown" />
      <Dropdown v-model="filterSeverity" :options="severityOptions" optionLabel="label" optionValue="value"
        :placeholder="isVi ? 'Mức độ' : 'Severity'" showClear @change="applyFilters" class="filter-dropdown" />
    </div>

    <!-- Notification List -->
    <div class="notif-list">
      <div v-for="notif in notifications.data" :key="notif.id"
        class="notif-card" :class="{ unread: !notif.read_at, [`sev-${notif.severity}`]: true }"
        @click="openNotif(notif)">
        <div class="notif-icon" :class="`icon-${notif.severity}`">
          <i :class="notif.icon" />
        </div>
        <div class="notif-content">
          <div class="notif-top">
            <h3 class="notif-title">{{ notif.title }}</h3>
            <span class="notif-time">{{ timeAgo(notif.created_at) }}</span>
          </div>
          <p class="notif-body" v-if="notif.body">{{ notif.body }}</p>
          <div class="notif-meta">
            <span class="notif-event-badge" :class="`eb-${notif.severity}`">{{ getEventLabel(notif.event_type) }}</span>
            <span class="notif-unread-dot" v-if="!notif.read_at"></span>
          </div>
        </div>
        <div class="notif-actions">
          <Button icon="pi pi-trash" text rounded severity="secondary" size="small" @click.stop="deleteNotif(notif)" />
        </div>
      </div>

      <div v-if="notifications.data.length === 0" class="empty-state">
        <i class="pi pi-bell-slash" />
        <p>{{ isVi ? 'Không có thông báo nào' : 'No notifications' }}</p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar" v-if="notifications.last_page > 1">
      <Button v-for="page in notifications.last_page" :key="page"
        :label="String(page)" :severity="page === notifications.current_page ? 'primary' : 'secondary'"
        text size="small" @click="goToPage(page)" />
    </div>

    <!-- Preferences Dialog -->
    <Dialog v-model:visible="showPreferences" :header="isVi ? 'Cài đặt thông báo' : 'Notification Settings'" modal :style="{ width: '520px' }">
      <p class="pref-desc">{{ isVi ? 'Chọn kênh nhận thông báo cho mỗi loại sự kiện:' : 'Choose notification channels for each event type:' }}</p>
      <div class="pref-list">
        <div v-for="et in event_types" :key="et.value" class="pref-row">
          <div class="pref-label">
            <i :class="et.icon" class="pref-icon" />
            <span>{{ isVi ? et.label_vi : et.label_en }}</span>
          </div>
          <div class="pref-toggles">
            <div class="pref-toggle">
              <Checkbox :modelValue="getPref(et.value, 'in_app')" @update:modelValue="setPref(et.value, 'in_app', $event)" :binary="true" />
              <span class="pref-toggle-label">{{ isVi ? 'Trong app' : 'In-App' }}</span>
            </div>
            <div class="pref-toggle">
              <Checkbox :modelValue="getPref(et.value, 'email')" @update:modelValue="setPref(et.value, 'email', $event)" :binary="true" />
              <span class="pref-toggle-label">Email</span>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button :label="isVi ? 'Huỷ' : 'Cancel'" severity="secondary" text @click="showPreferences = false" />
        <Button :label="isVi ? 'Lưu' : 'Save'" icon="pi pi-check" @click="savePreferences" :loading="savingPrefs" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import SelectButton from 'primevue/selectbutton'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, Dropdown, SelectButton, Dialog, Checkbox },
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
      showPreferences: false,
      savingPrefs: false,
      localPrefs: { ...this.preferences },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    readOptions() {
      return [
        { label: this.isVi ? 'Tất cả' : 'All', value: null },
        { label: this.isVi ? 'Chưa đọc' : 'Unread', value: false },
        { label: this.isVi ? 'Đã đọc' : 'Read', value: true },
      ]
    },
    severityOptions() {
      return [
        { label: this.isVi ? 'Thông tin' : 'Info', value: 'info' },
        { label: this.isVi ? 'Thành công' : 'Success', value: 'success' },
        { label: this.isVi ? 'Cảnh báo' : 'Warning', value: 'warning' },
        { label: this.isVi ? 'Nguy hiểm' : 'Danger', value: 'danger' },
      ]
    },
    eventTypeOptions() {
      return this.event_types.map(et => ({
        value: et.value,
        label: this.isVi ? et.label_vi : et.label_en,
      }))
    },
  },
  methods: {
    getEventLabel(eventType) {
      const et = this.event_types.find(e => e.value === eventType)
      return et ? (this.isVi ? et.label_vi : et.label_en) : eventType
    },
    timeAgo(dateStr) {
      const ms = Date.now() - new Date(dateStr).getTime()
      const mins = Math.floor(ms / 60000)
      if (mins < 1) return this.isVi ? 'Vừa xong' : 'Just now'
      if (mins < 60) return `${mins}${this.isVi ? ' phút trước' : 'm ago'}`
      const hours = Math.floor(mins / 60)
      if (hours < 24) return `${hours}${this.isVi ? ' giờ trước' : 'h ago'}`
      const days = Math.floor(hours / 24)
      return `${days}${this.isVi ? ' ngày trước' : 'd ago'}`
    },
    applyFilters() {
      const params = {}
      if (this.filterRead !== null) params.read = this.filterRead
      if (this.filterEvent) params.event_type = this.filterEvent
      if (this.filterSeverity) params.severity = this.filterSeverity
      router.get('/notifications', params, { preserveState: true })
    },
    async openNotif(notif) {
      if (!notif.read_at) {
        await axios.post(`/notifications/${notif.id}/read`)
        notif.read_at = new Date().toISOString()
      }
      if (notif.link) {
        router.visit(notif.link)
      }
    },
    async markAllRead() {
      await axios.post('/notifications/read-all')
      router.reload()
    },
    async deleteNotif(notif) {
      await axios.delete(`/notifications/${notif.id}`)
      router.reload()
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
    goToPage(page) {
      router.get('/notifications', { ...this.filters, page }, { preserveState: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; gap: 0.5rem; }

.filter-bar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
.filter-dropdown { min-width: 160px; }

.notif-list { display: flex; flex-direction: column; gap: 0.5rem; }
.notif-card { display: flex; gap: 0.75rem; padding: 0.85rem 1rem; background: white; border-radius: 12px; border: 1px solid #f1f5f9; cursor: pointer; transition: all 0.2s; align-items: flex-start; }
.notif-card:hover { border-color: #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); transform: translateY(-1px); }
.notif-card.unread { background: #fafbff; border-left: 3px solid #6366f1; }
.notif-card.sev-success.unread { border-left-color: #10b981; }
.notif-card.sev-warning.unread { border-left-color: #f59e0b; }
.notif-card.sev-danger.unread { border-left-color: #ef4444; }

.notif-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem; }
.icon-info { background: #eef2ff; color: #6366f1; }
.icon-success { background: #ecfdf5; color: #10b981; }
.icon-warning { background: #fffbeb; color: #f59e0b; }
.icon-danger { background: #fef2f2; color: #ef4444; }

.notif-content { flex: 1; min-width: 0; }
.notif-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
.notif-title { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.3; }
.notif-time { font-size: 0.65rem; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }
.notif-body { font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0.4rem; line-height: 1.4; }
.notif-meta { display: flex; align-items: center; gap: 0.4rem; }
.notif-event-badge { font-size: 0.55rem; font-weight: 700; padding: 0.08rem 0.35rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.02em; }
.eb-info { background: #eef2ff; color: #6366f1; }
.eb-success { background: #ecfdf5; color: #059669; }
.eb-warning { background: #fffbeb; color: #d97706; }
.eb-danger { background: #fef2f2; color: #dc2626; }
.notif-unread-dot { width: 6px; height: 6px; border-radius: 50%; background: #6366f1; }

.notif-actions { flex-shrink: 0; opacity: 0; transition: opacity 0.2s; }
.notif-card:hover .notif-actions { opacity: 1; }

.empty-state { text-align: center; padding: 3rem; color: #cbd5e1; }
.empty-state i { font-size: 2.5rem; margin-bottom: 0.5rem; }
.empty-state p { font-size: 0.9rem; margin: 0; }

.pagination-bar { display: flex; justify-content: center; gap: 0.25rem; margin-top: 1rem; }

/* Preferences Dialog */
.pref-desc { font-size: 0.82rem; color: #64748b; margin: 0 0 1rem; }
.pref-list { display: flex; flex-direction: column; gap: 0.5rem; }
.pref-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #f8fafc; }
.pref-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; font-weight: 500; color: #334155; }
.pref-icon { font-size: 0.85rem; color: #6366f1; }
.pref-toggles { display: flex; gap: 1rem; }
.pref-toggle { display: flex; align-items: center; gap: 0.3rem; }
.pref-toggle-label { font-size: 0.72rem; color: #64748b; }
</style>
