<template>
  <div>
    <Head title="Meetings" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-video" style="color:#6366f1;" /> Meetings</h1>
        <p class="page-subtitle">Video call, recording & AI recap</p>
      </div>
      <div class="header-actions">
        <button class="btn-instant" @click="showQuickMeeting = true">
          <i class="pi pi-bolt" /> Họp ngay
        </button>
        <Link href="/meetings/create">
          <button class="btn-schedule"><i class="pi pi-calendar-plus" /> Lên lịch</button>
        </Link>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card stat--live">
        <div class="stat-icon"><i class="pi pi-circle-fill" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.live }}</span><span class="stat-lbl">Đang live</span></div>
      </div>
      <div class="stat-card stat--scheduled">
        <div class="stat-icon"><i class="pi pi-clock" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.scheduled }}</span><span class="stat-lbl">Đã lên lịch</span></div>
      </div>
      <div class="stat-card stat--ended">
        <div class="stat-icon"><i class="pi pi-check-circle" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.ended }}</span><span class="stat-lbl">Đã kết thúc</span></div>
      </div>
      <div class="stat-card stat--hours">
        <div class="stat-icon"><i class="pi pi-stopwatch" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.total_recording_hours }}h</span><span class="stat-lbl">Tổng giờ họp</span></div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <i class="pi pi-search" />
        <input v-model="searchQuery" type="text" placeholder="Tìm cuộc họp..." @input="debouncedSearch" />
      </div>
      <div class="filter-pills">
        <button class="pill" :class="{ active: !filterStatus }" @click="filterBy('status', null)">Tất cả</button>
        <button v-for="(info, key) in statuses" :key="key" class="pill" :class="{ active: filterStatus === key }" @click="filterBy('status', key)">
          <i :class="info.icon" :style="{ color: info.color }" /> {{ info.label }}
        </button>
      </div>
    </div>

    <!-- Meetings Grid -->
    <div v-if="meetings.data?.length" class="meetings-grid">
      <div v-for="meeting in meetings.data" :key="meeting.id" class="meeting-card" :class="{ 'card--live': meeting.is_live }">
        <!-- Live badge -->
        <div v-if="meeting.is_live" class="live-badge"><span class="live-dot" /> LIVE</div>

        <!-- Card Header -->
        <div class="card-head">
          <div class="card-type-icon" :style="{ background: meeting.is_live ? '#ef4444' : '#6366f1' }">
            <i :class="meeting.type_info.icon || 'pi pi-video'" />
          </div>
          <div class="card-head-info">
            <Link :href="`/meetings/${meeting.id}/edit`" class="card-title-link">
              <h3 class="card-title">{{ meeting.title }}</h3>
            </Link>
            <div class="card-meta">
              <span class="room-code"><i class="pi pi-hashtag" /> {{ meeting.room_code }}</span>
              <span class="sep">·</span>
              <span>{{ meeting.scheduled_at }}</span>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div v-if="meeting.description" class="card-desc">{{ meeting.description }}</div>

        <!-- Info chips -->
        <div class="card-chips">
          <span class="chip" :style="{ background: meeting.status_info.color + '15', color: meeting.status_info.color }">
            <i :class="meeting.status_info.icon" /> {{ meeting.status_info.label }}
          </span>
          <span class="chip chip--neutral"><i class="pi pi-users" /> {{ meeting.participant_count }}/{{ meeting.max_participants }}</span>
          <span v-if="meeting.duration_formatted !== '—'" class="chip chip--neutral"><i class="pi pi-stopwatch" /> {{ meeting.duration_formatted }}</span>
          <span v-if="meeting.record_enabled" class="chip chip--rec"><i class="pi pi-circle-fill" /> REC</span>
        </div>

        <!-- Tags -->
        <div class="card-tags">
          <span v-if="meeting.has_recording" class="tag tag--recording"><i class="pi pi-play" /> Recording</span>
          <span v-if="meeting.has_recap" class="tag tag--recap"><i class="pi pi-sparkles" /> AI Recap</span>
        </div>

        <!-- Footer -->
        <div class="card-footer">
          <span class="card-creator" v-if="meeting.creator"><i class="pi pi-user" /> {{ meeting.creator.name }}</span>
          <span class="card-time">{{ meeting.created_at }}</span>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <Link v-if="meeting.is_live || meeting.status === 'scheduled'" :href="`/meetings/${meeting.room_code}/room`">
            <button class="act-btn act-join"><i class="pi pi-sign-in" /> {{ meeting.is_live ? 'Tham gia' : 'Vào phòng' }}</button>
          </Link>
          <Link :href="`/meetings/${meeting.id}/edit`">
            <button class="act-btn act-edit"><i class="pi pi-pencil" /> Sửa</button>
          </Link>
          <Link v-if="meeting.has_recap" :href="`/meetings/${meeting.id}/recap`">
            <button class="act-btn act-recap"><i class="pi pi-sparkles" /> Recap</button>
          </Link>
          <Link v-if="meeting.status === 'ended' && !meeting.has_recap" :href="`/meetings/${meeting.id}/recap`">
            <button class="act-btn act-recap"><i class="pi pi-sparkles" /> Tạo Recap</button>
          </Link>
          <button class="act-btn" @click="copyLink(meeting)" title="Copy link"><i class="pi pi-link" /></button>
          <button class="act-btn act-delete" @click="deleteMeeting(meeting.id)"><i class="pi pi-trash" /></button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meetings.last_page > 1" class="pagination-bar">
      <span class="pagination-info">{{ meetings.from }}–{{ meetings.to }} / {{ meetings.total }}</span>
      <div class="pagination-btns">
        <button class="pg-btn" :disabled="!meetings.prev_page_url" @click="goPage(meetings.current_page - 1)"><i class="pi pi-chevron-left" /></button>
        <span class="pg-current">{{ meetings.current_page }} / {{ meetings.last_page }}</span>
        <button class="pg-btn" :disabled="!meetings.next_page_url" @click="goPage(meetings.current_page + 1)"><i class="pi pi-chevron-right" /></button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-video" /></div>
      <h3>Chưa có cuộc họp nào</h3>
      <p>Tạo cuộc họp mới để bắt đầu video call với team</p>
      <Link href="/meetings/create"><button class="btn-schedule"><i class="pi pi-plus" /> Tạo cuộc họp</button></Link>
    </div>

    <!-- Quick Meeting Modal -->
    <div v-if="showQuickMeeting" class="modal-overlay" @click.self="showQuickMeeting = false">
      <div class="modal-card">
        <h3><i class="pi pi-bolt" /> Tạo họp nhanh</h3>
        <div class="modal-field">
          <label>Tên cuộc họp</label>
          <input v-model="quickForm.title" type="text" placeholder="VD: Daily standup..." class="modal-input" />
        </div>
        <div class="modal-field">
          <label>Loại</label>
          <div class="type-pills">
            <button v-for="(info, key) in types" :key="key" class="type-pill" :class="{ active: quickForm.type === key }" @click="quickForm.type = key">
              <i :class="info.icon" /> {{ info.label }}
            </button>
          </div>
        </div>
        <label class="modal-check"><input type="checkbox" v-model="quickForm.record_enabled" /> <span>Ghi hình cuộc họp</span></label>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showQuickMeeting = false">Hủy</button>
          <button class="btn-instant" @click="startQuickMeeting" :disabled="!quickForm.title"><i class="pi pi-bolt" /> Bắt đầu ngay</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { meetings: Object, stats: Object, statuses: Object, types: Object, filters: Object },
  data() {
    return {
      searchQuery: this.filters?.search || '',
      filterStatus: this.filters?.status || null,
      showQuickMeeting: false,
      quickForm: { title: '', type: 'video', record_enabled: false },
      searchTimeout: null,
    }
  },
  methods: {
    filterBy(key, val) {
      if (key === 'status') this.filterStatus = val
      router.get('/meetings', { search: this.searchQuery || undefined, status: this.filterStatus || undefined }, { preserveState: true, replace: true })
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => this.filterBy('search', this.searchQuery), 400)
    },
    startQuickMeeting() {
      router.post('/meetings', { ...this.quickForm, scheduled_at: null }, { onSuccess: () => { this.showQuickMeeting = false } })
    },
    deleteMeeting(id) {
      if (!confirm('Xóa cuộc họp này?')) return
      router.delete(`/meetings/${id}`)
    },
    copyLink(meeting) {
      const url = `${window.location.origin}/meetings/${meeting.room_code}/room`
      navigator.clipboard.writeText(url)
      alert('Đã copy link phòng!')
    },
    goPage(page) {
      router.visit(`/meetings?page=${page}`, { preserveState: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-title i { font-size: 1.2rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }
.btn-instant { display: flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1rem; border-radius: 10px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.btn-instant:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
.btn-instant:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-schedule { display: flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.btn-schedule:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.65rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; }
.stat--live .stat-icon { background: #fef2f2; color: #ef4444; }
.stat--scheduled .stat-icon { background: #eff6ff; color: #3b82f6; }
.stat--ended .stat-icon { background: #ecfdf5; color: #10b981; }
.stat--hours .stat-icon { background: #faf5ff; color: #8b5cf6; }
.stat-val { font-size: 1.1rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.62rem; color: #94a3b8; font-weight: 500; }

/* Filters */
.filter-bar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-wrap { display: flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; min-width: 200px; max-width: 320px; }
.search-wrap i { color: #94a3b8; font-size: 0.78rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-pills { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.pill { padding: 0.3rem 0.65rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.68rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.25rem; transition: all 0.15s; font-family: inherit; }
.pill:hover { border-color: #6366f1; }
.pill.active { background: #6366f1; color: white; border-color: #6366f1; }
.pill i { font-size: 0.55rem; }

/* Grid */
.meetings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.75rem; }
.meeting-card { background: white; border-radius: 14px; padding: 1rem; border: 1.5px solid #f1f5f9; transition: all 0.2s; position: relative; display: flex; flex-direction: column; gap: 0.55rem; }
.meeting-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
.meeting-card.card--live { border-color: #fecaca; background: linear-gradient(135deg, #fef2f2 0%, white 20%); }

/* Live badge */
.live-badge { position: absolute; top: 0.6rem; right: 0.6rem; display: flex; align-items: center; gap: 0.25rem; padding: 0.15rem 0.45rem; border-radius: 6px; background: #ef4444; color: white; font-size: 0.55rem; font-weight: 800; letter-spacing: 0.5px; animation: liveGlow 1.5s ease-in-out infinite; }
.live-dot { width: 6px; height: 6px; border-radius: 50%; background: white; animation: livePulse 1s ease-in-out infinite; }
@keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
@keyframes liveGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 50% { box-shadow: 0 0 8px 3px rgba(239,68,68,0.2); } }

/* Card head */
.card-head { display: flex; align-items: center; gap: 0.55rem; }
.card-type-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.85rem; flex-shrink: 0; }
.card-title { font-size: 0.88rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3; }
.card-meta { display: flex; align-items: center; gap: 0.3rem; font-size: 0.62rem; color: #94a3b8; margin-top: 0.1rem; }
.room-code { font-weight: 600; color: #6366f1; }
.sep { color: #e2e8f0; }
.card-desc { font-size: 0.72rem; color: #64748b; line-height: 1.4; }

/* Chips */
.card-chips { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.chip { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.12rem 0.45rem; border-radius: 6px; font-size: 0.6rem; font-weight: 600; }
.chip i { font-size: 0.5rem; }
.chip--neutral { background: #f1f5f9; color: #64748b; }
.chip--rec { background: #fef2f2; color: #ef4444; }
.chip--rec i { font-size: 0.35rem; animation: livePulse 1s ease-in-out infinite; }

/* Tags */
.card-tags { display: flex; gap: 0.25rem; }
.tag { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.12rem 0.4rem; border-radius: 5px; font-size: 0.58rem; font-weight: 600; }
.tag i { font-size: 0.5rem; }
.tag--recording { background: #eff6ff; color: #3b82f6; }
.tag--recap { background: #faf5ff; color: #8b5cf6; }

/* Footer */
.card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 0.35rem; border-top: 1px solid #f8fafc; }
.card-creator { font-size: 0.62rem; color: #64748b; display: flex; align-items: center; gap: 0.2rem; }
.card-creator i { font-size: 0.55rem; }
.card-time { font-size: 0.6rem; color: #cbd5e1; }

/* Actions */
.card-actions { display: flex; gap: 0.3rem; margin-top: auto; }
.act-btn { padding: 0.35rem 0.6rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; transition: all 0.15s; font-family: inherit; color: #64748b; }
.act-join { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
.act-join:hover { background: #6366f1; color: white; }
.act-recap { border-color: #c4b5fd; color: #8b5cf6; }
.act-recap:hover { background: #8b5cf6; color: white; }
.act-delete:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
.act-btn i { font-size: 0.6rem; }

/* Empty */
.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #eef2ff, #faf5ff); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.empty-icon i { font-size: 1.5rem; color: #6366f1; }
.empty-state h3 { font-size: 1rem; color: #1e293b; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

/* Quick Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-card { background: white; border-radius: 16px; padding: 1.5rem; width: 420px; max-width: 90vw; }
.modal-card h3 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.4rem; }
.modal-card h3 i { color: #ef4444; }
.modal-field { margin-bottom: 0.75rem; }
.modal-field label { display: block; font-size: 0.68rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem; }
.modal-input { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.82rem; outline: none; font-family: inherit; }
.modal-input:focus { border-color: #6366f1; }
.type-pills { display: flex; gap: 0.35rem; }
.type-pill { padding: 0.4rem 0.7rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.72rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: all 0.15s; font-family: inherit; }
.type-pill:hover { border-color: #6366f1; }
.type-pill.active { background: #6366f1; color: white; border-color: #6366f1; }
.type-pill i { font-size: 0.65rem; }
.modal-check { display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: #475569; margin: 0.75rem 0; cursor: pointer; }
.modal-check input { accent-color: #6366f1; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.btn-cancel { padding: 0.45rem 0.85rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .meetings-grid { grid-template-columns: 1fr; }
}

/* Title Link */
.card-title-link { text-decoration: none; }
.card-title-link:hover .card-title { color: #6366f1; }

/* Edit button */
.act-edit { border-color: #e2e8f0; color: #6366f1; }
.act-edit:hover { background: #eef2ff; border-color: #6366f1; }

/* Pagination */
.pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; margin-top: 0.5rem; }
.pagination-info { font-size: 0.75rem; color: #94a3b8; }
.pagination-btns { display: flex; align-items: center; gap: 0.35rem; }
.pg-btn { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s; }
.pg-btn:hover:not(:disabled) { border-color: #6366f1; color: #6366f1; }
.pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pg-current { font-size: 0.78rem; font-weight: 700; color: #6366f1; min-width: 60px; text-align: center; }
</style>
