<template>
  <div>
    <Head title="Content Calendar" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-calendar" style="color:#ec4899;" /> Content Calendar</h1>
        <p class="page-subtitle">Lên lịch nội dung đa kênh — blog, social, video, email</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Thêm nội dung</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-file" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Tổng</span></div></div>
      <div class="stat-card"><div class="stat-icon si-pub"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.published }}</span><span class="stat-lbl">Published</span></div></div>
      <div class="stat-card"><div class="stat-icon si-prog"><i class="pi pi-pencil" /></div><div class="stat-body"><span class="stat-val">{{ stats.in_progress }}</span><span class="stat-lbl">Đang viết</span></div></div>
      <div class="stat-card"><div class="stat-icon si-plan"><i class="pi pi-clock" /></div><div class="stat-body"><span class="stat-val">{{ stats.planned }}</span><span class="stat-lbl">Planned</span></div></div>
      <div class="stat-card"><div class="stat-icon si-month"><i class="pi pi-calendar" /></div><div class="stat-body"><span class="stat-val">{{ stats.this_month }}</span><span class="stat-lbl">Tháng này</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'"><i class="pi pi-list" /> Danh sách</button>
      <button class="tab" :class="{ active: viewMode === 'board' }" @click="viewMode = 'board'"><i class="pi pi-th-large" /> Board</button>
      <button class="tab" :class="{ active: viewMode === 'calendar' }" @click="viewMode = 'calendar'"><i class="pi pi-calendar" /> Lịch</button>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm nội dung..." @input="doSearch" /></div>
      <select v-model="filterStatus" class="filter-select" @change="doSearch"><option value="">Status</option><option v-for="(info, key) in statuses" :key="key" :value="key">{{ info.label }}</option></select>
      <select v-model="filterType" class="filter-select" @change="doSearch"><option value="">Loại</option><option v-for="(info, key) in contentTypes" :key="key" :value="key">{{ info.label }}</option></select>
      <select v-model="filterChannel" class="filter-select" @change="doSearch"><option value="">Kênh</option><option v-for="(info, key) in channels" :key="key" :value="key">{{ info.label }}</option></select>
    </div>

    <!-- View: List -->
    <div v-show="viewMode === 'list'">
      <div v-if="items.data?.length" class="content-list">
        <div v-for="item in items.data" :key="item.id" class="content-row" @click="openEdit(item)">
          <div class="cr-date">{{ item.planned_display || '—' }}</div>
          <div class="cr-body">
            <div class="cr-title">{{ item.title }}</div>
            <div class="cr-meta">
              <span class="cr-type" :style="{ color: contentTypes[item.content_type]?.color }"><i :class="contentTypes[item.content_type]?.icon" /> {{ contentTypes[item.content_type]?.label }}</span>
              <span class="cr-channel"><i :class="channels[item.channel]?.icon" /> {{ channels[item.channel]?.label }}</span>
            </div>
          </div>
          <span class="cr-priority" :class="'cp-' + item.priority">{{ item.priority }}</span>
          <span class="cr-status" :style="{ background: statuses[item.status]?.color + '20', color: statuses[item.status]?.color }">{{ statuses[item.status]?.label }}</span>
          <div class="cr-perf">
            <span v-if="item.views_count"><i class="pi pi-eye" /> {{ item.views_count }}</span>
            <span v-if="item.clicks_count"><i class="pi pi-link" /> {{ item.clicks_count }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-calendar" /></div>
        <h3>Chưa có nội dung</h3>
        <p>Bắt đầu lên lịch content marketing</p>
      </div>
    </div>

    <!-- View: Board (Kanban) -->
    <div v-show="viewMode === 'board'" class="board-view">
      <div v-for="(info, status) in statuses" :key="status" class="board-col">
        <div class="bc-head" :style="{ borderTopColor: info.color }">
          <span>{{ info.label }}</span>
          <span class="bc-count">{{ boardItems(status).length }}</span>
        </div>
        <div class="bc-cards">
          <div v-for="item in boardItems(status)" :key="item.id" class="bc-card" @click="openEdit(item)">
            <div class="bcc-type" :style="{ color: contentTypes[item.content_type]?.color }"><i :class="contentTypes[item.content_type]?.icon" /> {{ contentTypes[item.content_type]?.label }}</div>
            <div class="bcc-title">{{ item.title }}</div>
            <div class="bcc-footer">
              <span class="bcc-channel"><i :class="channels[item.channel]?.icon" /> {{ channels[item.channel]?.label }}</span>
              <span class="bcc-date">{{ item.planned_display }}</span>
            </div>
          </div>
          <div v-if="!boardItems(status).length" class="bc-empty">Trống</div>
        </div>
      </div>
    </div>

    <!-- View: Calendar -->
    <div v-show="viewMode === 'calendar'" class="calendar-view">
      <div class="cal-header">
        <button class="cal-nav" @click="navMonth(-1)"><i class="pi pi-chevron-left" /></button>
        <span class="cal-month">{{ monthName }} {{ currentYear }}</span>
        <button class="cal-nav" @click="navMonth(1)"><i class="pi pi-chevron-right" /></button>
      </div>
      <div class="cal-grid">
        <div v-for="d in ['T2','T3','T4','T5','T6','T7','CN']" :key="d" class="cal-day-head">{{ d }}</div>
        <div v-for="day in calendarDays" :key="day.date" class="cal-cell" :class="{ 'cal-other': !day.isCurrentMonth, 'cal-today': day.isToday }">
          <span class="cal-date">{{ day.day }}</span>
          <div v-for="item in (calendarData[day.date] || [])" :key="item.id" class="cal-item" :style="{ borderLeftColor: contentTypes[item.content_type]?.color || '#94a3b8' }">
            {{ item.title }}
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreate || editItem" class="modal-overlay" @click.self="closeModal">
      <div class="modal-box modal-lg">
        <div class="modal-head">
          <h3>{{ editItem ? 'Sửa nội dung' : 'Tạo nội dung mới' }}</h3>
          <button class="modal-close" @click="closeModal"><i class="pi pi-times" /></button>
        </div>
        <div class="fm-group"><label>Tiêu đề <span class="req">*</span></label><input v-model="formData.title" type="text" class="fm-input" placeholder="Tiêu đề bài viết" /></div>
        <div class="fm-group"><label>Mô tả</label><textarea v-model="formData.description" rows="2" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Loại nội dung</label>
            <select v-model="formData.content_type" class="fm-input"><option v-for="(info, key) in contentTypes" :key="key" :value="key">{{ info.label }}</option></select>
          </div>
          <div class="fm-group flex-1"><label>Kênh</label>
            <select v-model="formData.channel" class="fm-input"><option v-for="(info, key) in channels" :key="key" :value="key">{{ info.label }}</option></select>
          </div>
        </div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Ngày dự kiến</label><input v-model="formData.planned_date" type="date" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Ưu tiên</label>
            <select v-model="formData.priority" class="fm-input"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
          </div>
          <div class="fm-group flex-1"><label>Status</label>
            <select v-model="formData.status" class="fm-input"><option v-for="(info, key) in statuses" :key="key" :value="key">{{ info.label }}</option></select>
          </div>
        </div>
        <div class="fm-group"><label>Nội dung</label><textarea v-model="formData.content_body" rows="4" class="fm-input" placeholder="Viết nội dung..." /></div>
        <div class="modal-footer">
          <button v-if="editItem" class="btn-del" @click="deleteItem"><i class="pi pi-trash" /></button>
          <div style="display:flex;gap:0.4rem;">
            <button class="btn-cancel" @click="closeModal">Hủy</button>
            <button class="btn-save" :disabled="!formData.title || saving" @click="saveItem">
              <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> {{ editItem ? 'Lưu' : 'Tạo' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { items: Object, calendarData: Object, stats: Object, contentTypes: Object, channels: Object, statuses: Object, users: Array, filters: Object, currentMonth: Number, currentYear: Number },
  data() {
    return {
      viewMode: 'list', search: this.filters?.search || '',
      filterStatus: this.filters?.status || '', filterType: this.filters?.content_type || '', filterChannel: this.filters?.channel || '',
      searchTimeout: null, showCreate: false, editItem: null, saving: false,
      formData: this.emptyForm(),
    }
  },
  computed: {
    monthName() {
      const names = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12']
      return names[this.currentMonth] || ''
    },
    calendarDays() {
      const days = []
      const first = new Date(this.currentYear, this.currentMonth - 1, 1)
      const startDay = (first.getDay() + 6) % 7 // Monday start
      const daysInMonth = new Date(this.currentYear, this.currentMonth, 0).getDate()
      const today = new Date().toISOString().slice(0, 10)

      // Previous month
      const prevMonth = new Date(this.currentYear, this.currentMonth - 1, 0)
      for (let i = startDay - 1; i >= 0; i--) {
        const d = prevMonth.getDate() - i
        const date = new Date(this.currentYear, this.currentMonth - 2, d)
        days.push({ day: d, date: date.toISOString().slice(0, 10), isCurrentMonth: false, isToday: false })
      }
      // Current month
      for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(this.currentYear, this.currentMonth - 1, d).toISOString().slice(0, 10)
        days.push({ day: d, date, isCurrentMonth: true, isToday: date === today })
      }
      // Fill to 42
      const remaining = 42 - days.length
      for (let d = 1; d <= remaining; d++) {
        const date = new Date(this.currentYear, this.currentMonth, d).toISOString().slice(0, 10)
        days.push({ day: d, date, isCurrentMonth: false, isToday: false })
      }
      return days
    },
  },
  methods: {
    emptyForm() {
      return { title: '', description: '', content_type: 'blog', channel: 'website', status: 'idea', priority: 'medium', planned_date: '', content_body: '', tags: [], seo_meta: {} }
    },
    boardItems(status) { return (this.items.data || []).filter(i => i.status === status) },
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/content-calendar', {
          search: this.search || undefined, status: this.filterStatus || undefined,
          content_type: this.filterType || undefined, channel: this.filterChannel || undefined,
        }, { preserveState: true, replace: true })
      }, 400)
    },
    navMonth(dir) {
      let m = this.currentMonth + dir, y = this.currentYear
      if (m < 1) { m = 12; y-- } else if (m > 12) { m = 1; y++ }
      router.get('/content-calendar', { cal_month: m, cal_year: y }, { preserveState: true, replace: true })
    },
    openEdit(item) {
      this.editItem = item
      this.formData = { ...item }
    },
    closeModal() { this.showCreate = false; this.editItem = null; this.formData = this.emptyForm() },
    saveItem() {
      this.saving = true
      if (this.editItem) {
        router.put(`/content-calendar/${this.editItem.id}`, this.formData, { onSuccess: () => this.closeModal(), onFinish: () => { this.saving = false } })
      } else {
        router.post('/content-calendar', this.formData, { onSuccess: () => this.closeModal(), onFinish: () => { this.saving = false } })
      }
    },
    deleteItem() {
      if (!confirm('Xóa nội dung này?')) return
      router.delete(`/content-calendar/${this.editItem.id}`, { onSuccess: () => this.closeModal() })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #db2777); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #fce7f3; color: #ec4899; }
.si-pub { background: #ecfdf5; color: #10b981; }
.si-prog { background: #fef3c7; color: #f59e0b; }
.si-plan { background: #eef2ff; color: #6366f1; }
.si-month { background: #ede9fe; color: #8b5cf6; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; }

.tabs-bar { display: flex; gap: 0; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.tab { padding: 0.5rem 0.9rem; border: none; background: transparent; font-size: 0.72rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; }
.tab.active { color: #ec4899; border-bottom-color: #ec4899; }

.filter-bar { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 240px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.68rem; color: #475569; font-family: inherit; outline: none; }

/* List view */
.content-list { display: flex; flex-direction: column; gap: 0.3rem; }
.content-row { display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.7rem; background: white; border-radius: 10px; border: 1.5px solid #f1f5f9; cursor: pointer; transition: all 0.1s; }
.content-row:hover { border-color: #ec4899; }
.cr-date { font-size: 0.72rem; font-weight: 800; color: #ec4899; min-width: 30px; text-align: center; }
.cr-body { flex: 1; }
.cr-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; }
.cr-meta { display: flex; gap: 0.5rem; margin-top: 0.1rem; }
.cr-type, .cr-channel { font-size: 0.58rem; font-weight: 600; display: flex; align-items: center; gap: 0.15rem; }
.cr-type i, .cr-channel i { font-size: 0.5rem; }
.cr-channel { color: #64748b; }
.cr-priority { padding: 0.1rem 0.3rem; border-radius: 4px; font-size: 0.5rem; font-weight: 700; text-transform: uppercase; }
.cp-low { background: #f1f5f9; color: #94a3b8; }
.cp-medium { background: #eef2ff; color: #6366f1; }
.cp-high { background: #fef3c7; color: #f59e0b; }
.cp-urgent { background: #fef2f2; color: #ef4444; }
.cr-status { padding: 0.1rem 0.35rem; border-radius: 5px; font-size: 0.55rem; font-weight: 700; flex-shrink: 0; }
.cr-perf { display: flex; gap: 0.4rem; font-size: 0.55rem; color: #94a3b8; min-width: 60px; }
.cr-perf i { font-size: 0.45rem; }

/* Board view */
.board-view { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.4rem; overflow-x: auto; }
.board-col { min-width: 160px; }
.bc-head { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: #475569; border-top: 3px solid #94a3b8; margin-bottom: 0.3rem; }
.bc-count { font-size: 0.55rem; color: #94a3b8; }
.bc-cards { display: flex; flex-direction: column; gap: 0.25rem; }
.bc-card { padding: 0.5rem; background: white; border-radius: 8px; border: 1px solid #f1f5f9; cursor: pointer; transition: all 0.1s; }
.bc-card:hover { border-color: #ec4899; }
.bcc-type { font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.15rem; margin-bottom: 0.15rem; }
.bcc-type i { font-size: 0.45rem; }
.bcc-title { font-size: 0.68rem; font-weight: 600; color: #0f172a; margin-bottom: 0.2rem; }
.bcc-footer { display: flex; justify-content: space-between; font-size: 0.5rem; color: #94a3b8; }
.bcc-channel { display: flex; align-items: center; gap: 0.1rem; }
.bcc-channel i { font-size: 0.4rem; }
.bc-empty { text-align: center; padding: 0.8rem; font-size: 0.6rem; color: #cbd5e1; }

/* Calendar view */
.cal-header { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 0.5rem; }
.cal-month { font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.cal-nav { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.6rem; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
.cal-day-head { padding: 0.35rem; text-align: center; font-size: 0.58rem; font-weight: 700; color: #64748b; background: #f8fafc; }
.cal-cell { min-height: 70px; padding: 0.25rem; background: white; }
.cal-other { background: #fafbfc; }
.cal-today { background: #fce7f3; }
.cal-date { font-size: 0.58rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.15rem; }
.cal-other .cal-date { color: #cbd5e1; }
.cal-item { font-size: 0.48rem; padding: 0.12rem 0.2rem; border-radius: 3px; background: #f8fafc; border-left: 2px solid; margin-bottom: 0.1rem; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-lg { max-width: 560px; }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.modal-footer { display: flex; justify-content: space-between; margin-top: 0.6rem; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-input:focus { border-color: #ec4899; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.btn-del { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid #fca5a5; background: white; color: #ef4444; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; }
.btn-cancel { padding: 0.4rem 0.7rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-save { padding: 0.4rem 0.9rem; border-radius: 8px; background: linear-gradient(135deg, #ec4899, #db2777); color: white; font-size: 0.72rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.25rem; }

.empty-state { text-align: center; padding: 2.5rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #fce7f3; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #ec4899; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .board-view { grid-template-columns: repeat(3, 1fr); }
}
</style>
