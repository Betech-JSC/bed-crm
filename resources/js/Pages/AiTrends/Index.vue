<template>
  <div>
    <Head title="AI Trends Monitor" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-bolt" style="color: #6366f1; margin-right: 0.5rem;" />
          AI Trends Monitor
        </h1>
        <p class="page-subtitle">
          Theo dõi xu hướng AI từ GitHub, Hacker News, DEV.to &amp; nhiều nguồn khác
        </p>
      </div>
      <div class="header-actions">
        <Link href="/ai-trends/ecosystem">
          <Button label="Ecosystem Map" icon="pi pi-sitemap" severity="secondary" text size="small" />
        </Link>
        <Button
          label="Đánh dấu đã đọc"
          icon="pi pi-check-circle"
          severity="secondary"
          text
          size="small"
          @click="markAllRead"
          :disabled="stats.unreadItems === 0"
        />
        <Button
          label="Tạo Monitor"
          icon="pi pi-plus"
          size="small"
          @click="showCreateMonitor = true"
        />
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card stat-card--gradient-purple">
        <div class="stat-icon-wrapper"><i class="pi pi-bolt" /></div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.todayItems }}</span>
          <span class="stat-label">Mới hôm nay</span>
        </div>
        <div class="stat-sparkle" />
      </div>
      <div class="stat-card stat-card--gradient-blue">
        <div class="stat-icon-wrapper"><i class="pi pi-eye" /></div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.unreadItems }}</span>
          <span class="stat-label">Chưa đọc</span>
        </div>
      </div>
      <div class="stat-card stat-card--gradient-amber">
        <div class="stat-icon-wrapper"><i class="pi pi-bookmark" /></div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.pinnedItems }}</span>
          <span class="stat-label">Đã ghim</span>
        </div>
      </div>
      <div class="stat-card stat-card--gradient-green">
        <div class="stat-icon-wrapper"><i class="pi pi-sync" /></div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.activeMonitors }}</span>
          <span class="stat-label">Monitors hoạt động</span>
        </div>
      </div>
    </div>

    <!-- Main Layout: 2 columns -->
    <div class="trends-layout">
      <!-- Left: Trend Items Feed -->
      <div class="trends-feed">
        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="search-wrapper">
            <i class="pi pi-search search-icon" />
            <InputText
              v-model="filterForm.search"
              placeholder="Tìm kiếm trends..."
              class="search-input"
              @keyup.enter="applyFilters"
            />
          </div>
          <Select
            v-model="filterForm.source"
            :options="sourceOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Nguồn"
            class="filter-select"
            :showClear="true"
            @change="applyFilters"
          />
          <Select
            v-model="filterForm.language"
            :options="languageOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Ngôn ngữ"
            class="filter-select"
            :showClear="true"
            @change="applyFilters"
          />
          <div class="filter-chips">
            <button
              class="filter-chip"
              :class="{ active: filterForm.is_pinned }"
              @click="togglePinnedFilter"
            >
              <i class="pi pi-bookmark" /> Đã ghim
            </button>
            <button
              class="filter-chip"
              :class="{ active: filterForm.is_read === false }"
              @click="toggleUnreadFilter"
            >
              <i class="pi pi-eye" /> Chưa đọc
            </button>
          </div>
          <button
            v-if="hasActiveFilters"
            class="reset-btn"
            @click="resetFilters"
          >
            <i class="pi pi-times" /> Xoá bộ lọc
          </button>
        </div>

        <!-- Trend Items List -->
        <div v-if="items.data && items.data.length" class="items-list">
          <div
            v-for="item in items.data"
            :key="item.id"
            class="trend-item"
            :class="{ 'trend-item--unread': !item.is_read, 'trend-item--pinned': item.is_pinned }"
            @click="openItem(item)"
          >
            <!-- Source badge -->
            <div class="item-source-badge" :style="{ background: getSourceGradient(item.source) }">
              <i :class="getSourceIcon(item.source)" />
            </div>

            <!-- Content -->
            <div class="item-content">
              <div class="item-header">
                <h3 class="item-title">
                  <span v-if="item.is_pinned" class="pin-indicator">
                    <i class="pi pi-bookmark-fill" />
                  </span>
                  {{ item.title }}
                </h3>
                <div class="item-actions-inline">
                  <button class="action-icon" @click.stop="togglePin(item)" :title="item.is_pinned ? 'Bỏ ghim' : 'Ghim'">
                    <i :class="item.is_pinned ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'" />
                  </button>
                  <button class="action-icon action-delete" @click.stop="deleteItem(item)" title="Xoá">
                    <i class="pi pi-trash" />
                  </button>
                </div>
              </div>

              <p class="item-description" v-if="item.description">
                {{ truncate(item.description, 160) }}
              </p>

              <div class="item-meta">
                <!-- Author -->
                <span class="meta-tag" v-if="item.author">
                  <i class="pi pi-user" />
                  {{ item.author }}
                </span>

                <!-- Language -->
                <span class="meta-tag meta-tag--lang" v-if="item.language">
                  <span class="lang-dot" :style="{ background: getLangColor(item.language) }" />
                  {{ item.language }}
                </span>

                <!-- Stars -->
                <span class="meta-tag meta-tag--stars" v-if="item.stars > 0">
                  <i class="pi pi-star-fill" />
                  {{ formatNumber(item.stars) }}
                  <span v-if="item.stars_today > 0" class="stars-today">
                    +{{ formatNumber(item.stars_today) }}
                  </span>
                </span>

                <!-- Forks -->
                <span class="meta-tag" v-if="item.forks > 0">
                  <i class="pi pi-share-alt" />
                  {{ formatNumber(item.forks) }}
                </span>

                <!-- Score (HN) -->
                <span class="meta-tag meta-tag--score" v-if="item.source === 'hackernews' && item.score > 0">
                  <i class="pi pi-arrow-up" />
                  {{ item.score }} pts
                </span>

                <!-- Comments -->
                <span class="meta-tag" v-if="item.comments_count > 0">
                  <i class="pi pi-comments" />
                  {{ item.comments_count }}
                </span>

                <!-- Time -->
                <span class="meta-tag meta-tag--time">
                  <i class="pi pi-clock" />
                  {{ formatTimeAgo(item.created_at) }}
                </span>
              </div>

              <!-- Tags -->
              <div class="item-tags" v-if="item.tags && item.tags.length">
                <span v-for="(tag, idx) in item.tags.slice(0, 5)" :key="idx" class="tag-chip">
                  {{ typeof tag === 'string' ? tag : (tag.username || tag.name || '') }}
                </span>
              </div>
            </div>

            <!-- Image (if available) -->
            <div class="item-image" v-if="item.image_url">
              <img :src="item.image_url" :alt="item.title" loading="lazy" />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="empty-illustration">
            <i class="pi pi-sparkles" />
          </div>
          <h3>Chưa có xu hướng nào</h3>
          <p>Tạo monitor để bắt đầu theo dõi xu hướng AI mới nhất</p>
          <Button label="Tạo Monitor đầu tiên" icon="pi pi-plus" @click="showCreateMonitor = true" />
        </div>

        <!-- Pagination -->
        <Paginator
          v-if="items.data && items.data.length"
          :rows="items.per_page"
          :totalRecords="items.total"
          :first="(items.current_page - 1) * items.per_page"
          @page="onPageChange"
          class="pagination-wrapper"
        />
      </div>

      <!-- Right: Monitors Sidebar -->
      <div class="monitors-sidebar">
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-sync" /> Monitors</h3>
            <Button icon="pi pi-plus" text rounded size="small" @click="showCreateMonitor = true" />
          </div>

          <div v-if="monitors.length" class="monitors-list">
            <div
              v-for="monitor in monitors"
              :key="monitor.id"
              class="monitor-card"
              :class="{ 'monitor-card--inactive': !monitor.is_active }"
            >
              <div class="monitor-header">
                <div class="monitor-source-icon" :style="{ background: getSourceGradient(monitor.source) }">
                  <i :class="getSourceIcon(monitor.source)" />
                </div>
                <div class="monitor-info">
                  <span class="monitor-name">{{ monitor.name }}</span>
                  <span class="monitor-frequency">
                    <i class="pi pi-clock" />
                    {{ frequencies[monitor.schedule_frequency] || monitor.schedule_frequency }}
                  </span>
                </div>
                <div class="monitor-status">
                  <span class="status-dot" :class="monitor.is_active ? 'status-active' : 'status-inactive'" />
                </div>
              </div>

              <div class="monitor-meta" v-if="monitor.last_run_at">
                <span class="meta-label">Lần cuối:</span>
                <span class="meta-value">{{ formatTimeAgo(monitor.last_run_at) }}</span>
              </div>
              <div class="monitor-meta" v-if="monitor.next_run_at">
                <span class="meta-label">Lần tới:</span>
                <span class="meta-value">{{ formatTimeAgo(monitor.next_run_at) }}</span>
              </div>

              <div class="monitor-actions">
                <Button
                  icon="pi pi-play"
                  label="Fetch"
                  text
                  size="small"
                  severity="success"
                  @click="triggerFetch(monitor)"
                  :loading="fetchingMonitor === monitor.id"
                />
                <Button
                  :icon="monitor.is_active ? 'pi pi-pause' : 'pi pi-play'"
                  text
                  size="small"
                  :severity="monitor.is_active ? 'warning' : 'info'"
                  @click="toggleMonitor(monitor)"
                />
                <Button
                  icon="pi pi-trash"
                  text
                  size="small"
                  severity="danger"
                  @click="deleteMonitor(monitor)"
                />
              </div>
            </div>
          </div>

          <div v-else class="monitors-empty">
            <i class="pi pi-cog" />
            <p>Chưa có monitor nào</p>
          </div>
        </div>

        <!-- Source Stats Card -->
        <div class="sidebar-card" v-if="Object.keys(stats.sourceCounts).length">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-chart-pie" /> Theo nguồn (7 ngày)</h3>
          </div>
          <div class="source-stats">
            <div
              v-for="(count, source) in stats.sourceCounts"
              :key="source"
              class="source-stat-row"
            >
              <div class="source-stat-left">
                <span class="source-stat-dot" :style="{ background: getSourceColor(source) }" />
                <span class="source-stat-name">{{ getSourceLabel(source) }}</span>
              </div>
              <span class="source-stat-count">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Monitor Dialog -->
    <Dialog
      v-model:visible="showCreateMonitor"
      header="Tạo Monitor mới"
      :modal="true"
      :style="{ width: '560px' }"
      :draggable="false"
    >
      <div class="create-monitor-form">
        <!-- Name -->
        <div class="form-group">
          <label>Tên monitor <span class="required">*</span></label>
          <input
            v-model="monitorForm.name"
            class="form-control"
            placeholder="VD: GitHub AI Repos Trending"
          />
        </div>

        <!-- Source -->
        <div class="form-group">
          <label>Nguồn dữ liệu <span class="required">*</span></label>
          <div class="source-grid">
            <button
              v-for="(meta, key) in sources"
              :key="key"
              class="source-option"
              :class="{ 'source-option--selected': monitorForm.source === key }"
              @click="monitorForm.source = key"
            >
              <i :class="meta.icon" :style="{ color: meta.color }" />
              <span class="source-option-label">{{ meta.label }}</span>
              <span class="source-option-desc">{{ meta.description }}</span>
            </button>
          </div>
        </div>

        <!-- Source Config: GitHub -->
        <div v-if="monitorForm.source === 'github'" class="form-group">
          <label>Ngôn ngữ lập trình</label>
          <input
            v-model="monitorForm.source_config.language"
            class="form-control"
            placeholder="VD: python, javascript (để trống = tất cả)"
          />
          <div class="form-hint">Chỉ hiển thị repos viết bằng ngôn ngữ này</div>
        </div>

        <div v-if="monitorForm.source === 'github'" class="form-group">
          <label>Khoảng thời gian trending</label>
          <select v-model="monitorForm.source_config.since" class="form-control">
            <option value="daily">Hôm nay</option>
            <option value="weekly">Tuần này</option>
            <option value="monthly">Tháng này</option>
          </select>
        </div>

        <!-- Source Config: HackerNews -->
        <div v-if="monitorForm.source === 'hackernews'" class="form-group">
          <label>Loại story</label>
          <select v-model="monitorForm.source_config.type" class="form-control">
            <option value="top">Top Stories</option>
            <option value="new">New Stories</option>
            <option value="best">Best Stories</option>
          </select>
        </div>

        <!-- Source Config: DEV.to -->
        <div v-if="monitorForm.source === 'devto'" class="form-group">
          <label>Tag</label>
          <input
            v-model="monitorForm.source_config.tag"
            class="form-control"
            placeholder="VD: ai, machinelearning"
          />
        </div>

        <!-- Schedule -->
        <div class="form-row">
          <div class="form-group form-group--half">
            <label>Tần suất <span class="required">*</span></label>
            <select v-model="monitorForm.schedule_frequency" class="form-control">
              <option v-for="(label, val) in frequencies" :key="val" :value="val">{{ label }}</option>
            </select>
          </div>
          <div class="form-group form-group--half">
            <label>Thời gian chạy</label>
            <input
              v-model="monitorForm.schedule_time"
              type="time"
              class="form-control"
            />
          </div>
        </div>

        <div v-if="monitorForm.schedule_frequency === 'weekly'" class="form-group">
          <label>Ngày trong tuần</label>
          <select v-model="monitorForm.schedule_day" class="form-control">
            <option value="monday">Thứ 2</option>
            <option value="tuesday">Thứ 3</option>
            <option value="wednesday">Thứ 4</option>
            <option value="thursday">Thứ 5</option>
            <option value="friday">Thứ 6</option>
            <option value="saturday">Thứ 7</option>
            <option value="sunday">Chủ nhật</option>
          </select>
        </div>

        <!-- Notifications -->
        <div class="form-group">
          <label>Thông báo</label>
          <div class="notify-toggles">
            <label class="toggle-label">
              <input type="checkbox" v-model="monitorForm.notify_in_app" />
              <span class="toggle-slider" />
              <span>Thông báo trong app</span>
            </label>
            <label class="toggle-label">
              <input type="checkbox" v-model="monitorForm.notify_email" />
              <span class="toggle-slider" />
              <span>Gửi email</span>
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showCreateMonitor = false" />
        <Button
          label="Tạo Monitor"
          icon="pi pi-check"
          @click="createMonitor"
          :loading="creatingMonitor"
        />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'

export default {
  components: { Head, Link, Button, Dialog, InputText, Select, Paginator },
  layout: Layout,
  props: {
    items: Object,
    monitors: Array,
    stats: Object,
    languages: Array,
    sources: Object,
    frequencies: Object,
    filters: Object,
  },
  data() {
    return {
      showCreateMonitor: false,
      creatingMonitor: false,
      fetchingMonitor: null,
      filterForm: {
        search: this.filters?.search || '',
        source: this.filters?.source || null,
        language: this.filters?.language || null,
        is_read: this.filters?.is_read ?? null,
        is_pinned: this.filters?.is_pinned ?? null,
      },
      monitorForm: {
        name: '',
        source: 'github',
        source_config: { language: '', since: 'daily', type: 'top', tag: 'ai' },
        schedule_frequency: 'daily',
        schedule_time: '09:00',
        schedule_day: 'monday',
        notify_in_app: true,
        notify_email: false,
      },
    }
  },
  computed: {
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    sourceOptions() {
      const opts = [{ label: 'Tất cả nguồn', value: null }]
      Object.entries(this.sources).forEach(([key, meta]) => {
        opts.push({ label: meta.label, value: key })
      })
      return opts
    },
    languageOptions() {
      const opts = [{ label: 'Tất cả ngôn ngữ', value: null }]
      this.languages.forEach(lang => {
        opts.push({ label: lang, value: lang })
      })
      return opts
    },
    hasActiveFilters() {
      return this.filterForm.search || this.filterForm.source || this.filterForm.language
        || this.filterForm.is_read !== null || this.filterForm.is_pinned
    },
  },
  methods: {
    applyFilters() {
      const params = {}
      if (this.filterForm.search) params.search = this.filterForm.search
      if (this.filterForm.source) params.source = this.filterForm.source
      if (this.filterForm.language) params.language = this.filterForm.language
      if (this.filterForm.is_read !== null) params.is_read = this.filterForm.is_read ? 1 : 0
      if (this.filterForm.is_pinned) params.is_pinned = 1

      router.get('/ai-trends', params, { preserveState: true, replace: true })
    },
    resetFilters() {
      this.filterForm = { search: '', source: null, language: null, is_read: null, is_pinned: null }
      router.get('/ai-trends', {}, { preserveState: true, replace: true })
    },
    togglePinnedFilter() {
      this.filterForm.is_pinned = this.filterForm.is_pinned ? null : true
      this.applyFilters()
    },
    toggleUnreadFilter() {
      this.filterForm.is_read = this.filterForm.is_read === false ? null : false
      this.applyFilters()
    },
    onPageChange(event) {
      router.get('/ai-trends', { page: event.page + 1, ...this.filters }, { preserveState: true, replace: true })
    },

    openItem(item) {
      // Mark as read
      if (!item.is_read) {
        fetch(`/ai-trends/items/${item.id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' } })
        item.is_read = true
      }
      window.open(item.url, '_blank')
    },

    async togglePin(item) {
      try {
        const res = await fetch(`/ai-trends/items/${item.id}/pin`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
        })
        const data = await res.json()
        item.is_pinned = data.is_pinned
      } catch (e) {
        console.error(e)
      }
    },

    async deleteItem(item) {
      if (!confirm('Bạn có chắc muốn xoá mục này?')) return
      try {
        await fetch(`/ai-trends/items/${item.id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
        })
        router.reload({ only: ['items', 'stats'] })
      } catch (e) {
        console.error(e)
      }
    },

    async markAllRead() {
      try {
        await fetch('/ai-trends/items/read-all', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
        })
        router.reload({ only: ['items', 'stats'] })
      } catch (e) {
        console.error(e)
      }
    },

    async createMonitor() {
      this.creatingMonitor = true
      try {
        const res = await fetch('/ai-trends/monitors', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(this.monitorForm),
        })
        const data = await res.json()
        if (data.success) {
          this.showCreateMonitor = false
          this.resetMonitorForm()
          router.reload()
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.creatingMonitor = false
      }
    },

    async triggerFetch(monitor) {
      this.fetchingMonitor = monitor.id
      try {
        const res = await fetch(`/ai-trends/monitors/${monitor.id}/fetch`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
        })
        const data = await res.json()
        if (data.success) {
          router.reload()
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.fetchingMonitor = null
      }
    },

    async toggleMonitor(monitor) {
      try {
        await fetch(`/ai-trends/monitors/${monitor.id}`, {
          method: 'PUT',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ is_active: !monitor.is_active }),
        })
        router.reload({ only: ['monitors', 'stats'] })
      } catch (e) {
        console.error(e)
      }
    },

    async deleteMonitor(monitor) {
      if (!confirm(`Xoá monitor "${monitor.name}" và tất cả dữ liệu liên quan?`)) return
      try {
        await fetch(`/ai-trends/monitors/${monitor.id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
        })
        router.reload()
      } catch (e) {
        console.error(e)
      }
    },

    resetMonitorForm() {
      this.monitorForm = {
        name: '', source: 'github',
        source_config: { language: '', since: 'daily', type: 'top', tag: 'ai' },
        schedule_frequency: 'daily', schedule_time: '09:00',
        schedule_day: 'monday', notify_in_app: true, notify_email: false,
      }
    },

    // ── Formatting helpers ──
    truncate(str, len) {
      if (!str) return ''
      return str.length > len ? str.substring(0, len) + '…' : str
    },
    formatNumber(num) {
      if (num >= 1000) return (num / 1000).toFixed(1) + 'k'
      return num.toString()
    },
    formatTimeAgo(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      const now = new Date()
      const diff = Math.floor((now - date) / 1000)
      if (diff < 60) return 'Vừa xong'
      if (diff < 3600) return Math.floor(diff / 60) + ' phút trước'
      if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước'
      if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước'
      return date.toLocaleDateString('vi-VN', { day: 'numeric', month: 'short' })
    },

    getSourceIcon(source) {
      const map = { github: 'pi pi-github', hackernews: 'pi pi-bolt', producthunt: 'pi pi-megaphone', devto: 'pi pi-code' }
      return map[source] || 'pi pi-globe'
    },
    getSourceColor(source) {
      const map = { github: '#6e40c9', hackernews: '#ff6600', producthunt: '#da552f', devto: '#3b49df' }
      return map[source] || '#6366f1'
    },
    getSourceGradient(source) {
      const map = {
        github: 'linear-gradient(135deg, #6e40c9, #8b5cf6)',
        hackernews: 'linear-gradient(135deg, #ff6600, #ff8c42)',
        producthunt: 'linear-gradient(135deg, #da552f, #f56040)',
        devto: 'linear-gradient(135deg, #3b49df, #6366f1)',
      }
      return map[source] || 'linear-gradient(135deg, #6366f1, #8b5cf6)'
    },
    getSourceLabel(source) {
      return this.sources[source]?.label || source
    },
    getLangColor(lang) {
      const map = {
        Python: '#3572A5', JavaScript: '#f1e05a', TypeScript: '#3178c6',
        Rust: '#dea584', Go: '#00ADD8', Java: '#b07219',
        'C++': '#f34b7d', Ruby: '#701516', Swift: '#F05138',
        Kotlin: '#A97BFF', PHP: '#4F5D95', Dart: '#00B4AB',
      }
      return map[lang] || '#6366f1'
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
  display: flex;
  align-items: center;
}
.page-subtitle {
  font-size: 0.82rem;
  color: #94a3b8;
  margin: 0.15rem 0 0;
}
.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* ===== Stats Grid ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  border-radius: 14px;
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-card--gradient-purple {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}
.stat-card--gradient-blue {
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  color: white;
}
.stat-card--gradient-amber {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: white;
}
.stat-card--gradient-green {
  background: linear-gradient(135deg, #10b981, #34d399);
  color: white;
}

.stat-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(8px);
}
.stat-icon-wrapper i {
  font-size: 1.2rem;
  color: white;
}

.stat-content {
  display: flex;
  flex-direction: column;
}
.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.2;
}
.stat-label {
  font-size: 0.72rem;
  font-weight: 500;
  opacity: 0.85;
  letter-spacing: 0.02em;
}

.stat-sparkle {
  position: absolute;
  right: -10px;
  top: -10px;
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

/* ===== Layout ===== */
.trends-layout {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
}

.trends-feed {
  flex: 1;
  min-width: 0;
}

.monitors-sidebar {
  width: 300px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: 80px;
}

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.75rem 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.85rem;
}
.search-input {
  width: 100%;
  padding-left: 2.2rem !important;
  font-size: 0.82rem !important;
  border-radius: 8px !important;
}

.filter-select {
  font-size: 0.82rem !important;
  min-width: 140px;
}

.filter-chips {
  display: flex;
  gap: 0.35rem;
}

.filter-chip {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.65rem;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  background: white;
  font-size: 0.72rem;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-chip:hover {
  border-color: #6366f1;
  color: #6366f1;
}
.filter-chip.active {
  background: #eef2ff;
  border-color: #6366f1;
  color: #6366f1;
}
.filter-chip i {
  font-size: 0.7rem;
}

.reset-btn {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.65rem;
  border: none;
  background: #fef2f2;
  color: #ef4444;
  border-radius: 8px;
  font-size: 0.72rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.reset-btn:hover {
  background: #fee2e2;
}

/* ===== Items List ===== */
.items-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.trend-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.trend-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border-color: #e2e8f0;
  transform: translateY(-1px);
}

.trend-item--unread {
  border-left: 3px solid #6366f1;
}

.trend-item--pinned {
  background: linear-gradient(135deg, #fefce8, #fffbeb);
  border-color: #fde68a;
}

.item-source-badge {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.item-source-badge i {
  font-size: 0.9rem;
  color: white;
}

.item-content {
  flex: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.item-title {
  font-size: 0.88rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  line-height: 1.4;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.pin-indicator {
  color: #f59e0b;
  font-size: 0.72rem;
}

.item-actions-inline {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}
.trend-item:hover .item-actions-inline {
  opacity: 1;
}

.action-icon {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  font-size: 0.78rem;
}
.action-icon:hover {
  background: #f1f5f9;
  color: #6366f1;
}
.action-delete:hover {
  background: #fef2f2;
  color: #ef4444;
}

.item-description {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0 0 0.45rem;
  line-height: 1.5;
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.meta-tag {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 450;
}
.meta-tag i {
  font-size: 0.65rem;
}

.meta-tag--stars i {
  color: #f59e0b;
}
.stars-today {
  color: #10b981;
  font-weight: 600;
  font-size: 0.65rem;
}

.meta-tag--score i {
  color: #ff6600;
}

.meta-tag--lang {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.lang-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.item-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-top: 0.45rem;
}

.tag-chip {
  font-size: 0.62rem;
  font-weight: 500;
  padding: 0.12rem 0.45rem;
  border-radius: 4px;
  background: #eef2ff;
  color: #6366f1;
  text-transform: lowercase;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
}
.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== Empty State ===== */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 4rem 2rem;
  background: white;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
}
.empty-illustration {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex;
  align-items: center;
  justify-content: center;
}
.empty-illustration i {
  font-size: 2rem;
  color: #6366f1;
}
.empty-state h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}
.empty-state p {
  font-size: 0.82rem;
  color: #94a3b8;
  margin: 0;
}

/* ===== Pagination ===== */
.pagination-wrapper {
  margin-top: 1rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

/* ===== Monitors Sidebar ===== */
.sidebar-card {
  background: white;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.sidebar-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}
.sidebar-card-header h3 {
  font-size: 0.82rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.sidebar-card-header h3 i {
  font-size: 0.82rem;
  color: #6366f1;
}

.monitors-list {
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.monitor-card {
  padding: 0.75rem;
  border-radius: 10px;
  border: 1px solid #f1f5f9;
  background: #fafbfc;
  transition: all 0.2s;
}
.monitor-card:hover {
  border-color: #e2e8f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.monitor-card--inactive {
  opacity: 0.55;
}

.monitor-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.45rem;
}

.monitor-source-icon {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.monitor-source-icon i {
  font-size: 0.72rem;
  color: white;
}

.monitor-info {
  flex: 1;
  min-width: 0;
}
.monitor-name {
  font-size: 0.78rem;
  font-weight: 600;
  color: #1e293b;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.monitor-frequency {
  font-size: 0.65rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.2rem;
}
.monitor-frequency i {
  font-size: 0.55rem;
}

.monitor-status {
  flex-shrink: 0;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: block;
}
.status-active {
  background: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}
.status-inactive {
  background: #cbd5e1;
}

.monitor-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.65rem;
  padding: 0.1rem 0;
}
.meta-label {
  color: #94a3b8;
}
.meta-value {
  color: #64748b;
  font-weight: 500;
}

.monitor-actions {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.45rem;
  padding-top: 0.45rem;
  border-top: 1px solid #f1f5f9;
}

.monitors-empty {
  padding: 1.5rem;
  text-align: center;
  color: #94a3b8;
}
.monitors-empty i {
  font-size: 1.5rem;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
}
.monitors-empty p {
  font-size: 0.78rem;
  margin: 0;
}

/* ===== Source Stats ===== */
.source-stats {
  padding: 0.75rem 1rem;
}
.source-stat-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.45rem 0;
  border-bottom: 1px solid #f8fafc;
}
.source-stat-row:last-child {
  border: none;
}
.source-stat-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.source-stat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.source-stat-name {
  font-size: 0.75rem;
  color: #475569;
  font-weight: 500;
}
.source-stat-count {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
}

/* ===== Create Monitor Dialog ===== */
.create-monitor-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.form-group label {
  font-size: 0.78rem;
  font-weight: 500;
  color: #475569;
}
.required {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #1e293b;
  background: white;
  transition: all 0.2s;
  outline: none;
  font-family: inherit;
}
.form-control:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-hint {
  font-size: 0.68rem;
  color: #94a3b8;
}

.form-row {
  display: flex;
  gap: 0.75rem;
}
.form-group--half {
  flex: 1;
}

/* Source Grid */
.source-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.source-option {
  padding: 0.75rem;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.source-option:hover {
  border-color: #c7d2fe;
  background: #fefefe;
}
.source-option--selected {
  border-color: #6366f1;
  background: #eef2ff;
}
.source-option i {
  font-size: 1.1rem;
  margin-bottom: 0.15rem;
}
.source-option-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #1e293b;
}
.source-option-desc {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1.3;
}

/* Notify Toggles */
.notify-toggles {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.82rem;
  color: #475569;
}
.toggle-label input {
  appearance: none;
  width: 36px;
  height: 20px;
  border-radius: 10px;
  background: #cbd5e1;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
  flex-shrink: 0;
}
.toggle-label input::before {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: white;
  top: 2px;
  left: 2px;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
.toggle-label input:checked {
  background: #6366f1;
}
.toggle-label input:checked::before {
  left: 18px;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .trends-layout {
    flex-direction: column;
  }
  .monitors-sidebar {
    width: 100%;
    position: static;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .source-grid {
    grid-template-columns: 1fr;
  }
  .form-row {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
}
</style>
