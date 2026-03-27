<template>
  <div>
    <Head title="Tickets Hỗ trợ" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-ticket" style="color: #f59e0b; margin-right: 0.5rem;" />Tickets Hỗ trợ</h1>
        <p class="page-subtitle">{{ analytics.total }} tickets · {{ analytics.open }} đang mở · Quản lý yêu cầu hỗ trợ</p>
      </div>
      <a href="/support-tickets/create" class="btn-primary"><i class="pi pi-plus" /> Tạo Ticket</a>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-strip">
      <div class="kpi-chip" @click="setFilter('status', null)">
        <div class="kpi-icon kpi-icon--blue"><i class="pi pi-inbox" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.total }}</span>
          <span class="kpi-lbl">Tổng tickets</span>
        </div>
      </div>
      <div class="kpi-chip" @click="setFilter('status', 'open')">
        <div class="kpi-icon kpi-icon--amber"><i class="pi pi-clock" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.open }}</span>
          <span class="kpi-lbl">Đang mở</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--green"><i class="pi pi-check-circle" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.resolved_today }}</span>
          <span class="kpi-lbl">Resolved hôm nay</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--red"><i class="pi pi-bolt" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.urgent }}</span>
          <span class="kpi-lbl">Khẩn cấp</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--purple"><i class="pi pi-stopwatch" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.avg_response_hours }}h</span>
          <span class="kpi-lbl">TB phản hồi</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" type="text" placeholder="Tìm kiếm ticket..." class="search-input" @input="handleSearch" />
      </div>
      <select v-model="form.status" class="filter-select" @change="applyFilter">
        <option :value="null">Tất cả trạng thái</option>
        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
      </select>
      <select v-model="form.priority" class="filter-select" @change="applyFilter">
        <option :value="null">Tất cả ưu tiên</option>
        <option v-for="(label, key) in priorities" :key="key" :value="key">{{ label }}</option>
      </select>
      <select v-model="form.category" class="filter-select" @change="applyFilter">
        <option :value="null">Tất cả danh mục</option>
        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
      </select>
      <button class="btn-icon" @click="reset" title="Reset"><i class="pi pi-refresh" /></button>
    </div>

    <!-- Ticket List -->
    <div class="data-card">
      <table class="tickets-table">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>Khách hàng</th>
            <th>Ưu tiên</th>
            <th>Trạng thái</th>
            <th>Danh mục</th>
            <th>Phụ trách</th>
            <th>Ngày tạo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ticket in tickets.data" :key="ticket.id" class="ticket-row">
            <td>
              <a :href="`/support-tickets/${ticket.id}/edit`" class="ticket-link">
                <span class="ticket-subject">{{ ticket.subject }}</span>
                <span v-if="ticket.description" class="ticket-desc">{{ truncate(ticket.description, 60) }}</span>
              </a>
            </td>
            <td>
              <span v-if="ticket.customer" class="customer-badge"><i class="pi pi-building" /> {{ ticket.customer.name }}</span>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <span class="priority-badge" :class="`priority-${ticket.priority}`">
                <i :class="priorityIcon(ticket.priority)" /> {{ priorities[ticket.priority] }}
              </span>
            </td>
            <td>
              <span class="status-badge" :class="`status-${ticket.status}`">
                <i class="status-dot" /> {{ statuses[ticket.status] }}
              </span>
            </td>
            <td>
              <span v-if="ticket.category" class="category-chip">{{ categories[ticket.category] || ticket.category }}</span>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <div v-if="ticket.assigned_user" class="assigned-cell">
                <div class="mini-avatar">{{ initials(ticket.assigned_user.name) }}</div>
                <span>{{ ticket.assigned_user.name }}</span>
              </div>
              <span v-else class="text-muted">Chưa gán</span>
            </td>
            <td>
              <span class="date-text">{{ formatDate(ticket.created_at) }}</span>
            </td>
            <td>
              <a :href="`/support-tickets/${ticket.id}/edit`" class="btn-icon-sm" title="Chi tiết"><i class="pi pi-chevron-right" /></a>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="tickets.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-ticket" /></div>
        <h3>Chưa có ticket nào</h3>
        <p>Tạo ticket hỗ trợ đầu tiên</p>
        <a href="/support-tickets/create" class="btn-primary btn-sm"><i class="pi pi-plus" /> Tạo Ticket</a>
      </div>

      <!-- Pagination -->
      <div v-if="tickets.total > 0" class="pagination-bar">
        <span class="pagination-info">{{ tickets.from }}–{{ tickets.to }} / {{ tickets.total }}</span>
        <div class="pagination-btns">
          <button class="pg-btn" :disabled="!tickets.prev_page_url" @click="goPage(tickets.current_page - 1)"><i class="pi pi-chevron-left" /></button>
          <span class="pg-current">{{ tickets.current_page }}</span>
          <button class="pg-btn" :disabled="!tickets.next_page_url" @click="goPage(tickets.current_page + 1)"><i class="pi pi-chevron-right" /></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'

export default {
  components: { Head },
  layout: Layout,
  props: { tickets: Object, analytics: Object, filters: Object, statuses: Object, priorities: Object, categories: Object, users: Array },
  data() {
    return {
      form: {
        search: this.filters.search || null,
        status: this.filters.status || null,
        priority: this.filters.priority || null,
        category: this.filters.category || null,
      },
    }
  },
  methods: {
    handleSearch: throttle(function () { this.applyFilter() }, 300),
    applyFilter() { router.get('/support-tickets', pickBy(this.form), { preserveState: true }) },
    setFilter(key, value) { this.form[key] = value; this.applyFilter() },
    reset() { this.form = { search: null, status: null, priority: null, category: null }; router.get('/support-tickets') },
    goPage(page) { router.visit(`/support-tickets?page=${page}`, { preserveState: true }) },
    formatDate(d) { return new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }) },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    truncate(s, len) { return s && s.length > len ? s.slice(0, len) + '…' : s },
    priorityIcon(p) {
      const map = { urgent: 'pi pi-bolt', high: 'pi pi-arrow-up', medium: 'pi pi-minus', low: 'pi pi-arrow-down' }
      return map[p] || 'pi pi-minus'
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; border-radius: 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(245,158,11,0.25); }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(245,158,11,0.35); }
.btn-sm { padding: 0.4rem 0.85rem; font-size: 0.78rem; }

/* KPI Strip */
.kpi-strip { display: flex; gap: 0.5rem; margin-bottom: 0.85rem; overflow-x: auto; }
.kpi-chip { display: flex; align-items: center; gap: 0.55rem; padding: 0.6rem 0.85rem; background: white; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); flex-shrink: 0; cursor: pointer; transition: all 0.2s; }
.kpi-chip:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px); }
.kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
.kpi-icon--blue { background: #dbeafe; color: #2563eb; }
.kpi-icon--amber { background: #fef3c7; color: #d97706; }
.kpi-icon--green { background: #d1fae5; color: #059669; }
.kpi-icon--red { background: #fee2e2; color: #dc2626; }
.kpi-icon--purple { background: #ede9fe; color: #7c3aed; }
.kpi-data { display: flex; flex-direction: column; }
.kpi-num { font-size: 0.95rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.kpi-lbl { font-size: 0.62rem; color: #94a3b8; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 0.85rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.35rem; flex: 1; min-width: 200px; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.search-input { border: none; outline: none; width: 100%; font-size: 0.85rem; color: #334155; background: transparent; }
.filter-select { padding: 0.4rem 0.65rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #475569; background: white; cursor: pointer; min-width: 130px; }
.btn-icon { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #94a3b8; transition: all 0.15s; }
.btn-icon:hover { background: #f8fafc; color: #475569; }

/* Table */
.data-card { background: white; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden; }
.tickets-table { width: 100%; border-collapse: collapse; }
.tickets-table th { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #f1f5f9; background: #fafbfc; }
.ticket-row { transition: background 0.15s; }
.ticket-row:hover { background: #f8fafc; }
.ticket-row td { padding: 0.7rem 1rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }

.ticket-link { display: flex; flex-direction: column; text-decoration: none; gap: 0.1rem; }
.ticket-subject { font-size: 0.85rem; font-weight: 600; color: #1e293b; transition: color 0.15s; }
.ticket-link:hover .ticket-subject { color: #f59e0b; }
.ticket-desc { font-size: 0.68rem; color: #94a3b8; }

/* Priority */
.priority-badge { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 6px; }
.priority-badge i { font-size: 0.55rem; }
.priority-urgent { background: #dc2626; color: white; }
.priority-high { background: #fef3c7; color: #d97706; }
.priority-medium { background: #eef2ff; color: #6366f1; }
.priority-low { background: #f1f5f9; color: #94a3b8; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.5rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-open { background: #fef3c7; color: #d97706; } .status-open .status-dot { background: #d97706; }
.status-in_progress { background: #dbeafe; color: #2563eb; } .status-in_progress .status-dot { background: #2563eb; }
.status-waiting { background: #ede9fe; color: #7c3aed; } .status-waiting .status-dot { background: #7c3aed; }
.status-resolved { background: #d1fae5; color: #059669; } .status-resolved .status-dot { background: #059669; }
.status-closed { background: #f1f5f9; color: #94a3b8; } .status-closed .status-dot { background: #94a3b8; }

/* Category */
.category-chip { font-size: 0.68rem; font-weight: 500; color: #64748b; background: #f1f5f9; padding: 0.12rem 0.45rem; border-radius: 5px; }

/* Customer */
.customer-badge { font-size: 0.78rem; color: #475569; display: flex; align-items: center; gap: 0.3rem; }
.customer-badge i { font-size: 0.65rem; color: #94a3b8; }

/* Assigned */
.assigned-cell { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: #334155; }
.mini-avatar { width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.date-text { font-size: 0.75rem; color: #64748b; }
.text-muted { color: #cbd5e1; font-size: 0.78rem; }

/* Actions */
.btn-icon-sm { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #94a3b8; transition: all 0.15s; text-decoration: none; }
.btn-icon-sm:hover { background: #f1f5f9; color: #f59e0b; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 3rem; text-align: center; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: #fef3c7; display: flex; align-items: center; justify-content: center; }
.empty-icon i { font-size: 1.5rem; color: #f59e0b; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #475569; margin: 0; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0; }

/* Pagination */
.pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
.pagination-btns { display: flex; align-items: center; gap: 0.35rem; }
.pg-btn { width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s; }
.pg-btn:hover:not(:disabled) { background: #f8fafc; color: #f59e0b; }
.pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pg-current { font-size: 0.82rem; font-weight: 600; color: #f59e0b; min-width: 28px; text-align: center; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .kpi-strip { flex-wrap: nowrap; }
  .filter-bar { flex-direction: column; }
  .tickets-table { font-size: 0.78rem; }
}
</style>
