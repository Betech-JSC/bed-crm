<template>
  <div>
    <Head title="System Logs" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-list" style="color: #f59e0b; margin-right: 0.5rem;" />
          System Logs
        </h1>
        <p class="page-subtitle">Theo dõi log hệ thống theo thời gian thực</p>
      </div>
      <div class="header-actions">
        <Button
          :label="isLive ? 'Dừng live' : 'Live'"
          :icon="isLive ? 'pi pi-pause' : 'pi pi-play'"
          :severity="isLive ? 'danger' : 'success'"
          size="small"
          outlined
          @click="toggleLive"
        />
        <Button label="Tải về" icon="pi pi-download" severity="secondary" size="small" outlined @click="downloadLog" />
        <Button label="Xóa log" icon="pi pi-trash" severity="danger" size="small" text @click="showClearDialog = true" />
      </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar" v-if="stats.total">
      <div class="stat-mini" @click="setLevel('all')" :class="{ active: activeLevel === 'all' }">
        <span class="stat-num">{{ stats.total }}</span>
        <span>Tổng</span>
      </div>
      <div class="stat-mini stat-mini--error" @click="setLevel('error')" :class="{ active: activeLevel === 'error' }">
        <span class="stat-num">{{ stats.error }}</span>
        <span>Error</span>
      </div>
      <div class="stat-mini stat-mini--warning" @click="setLevel('warning')" :class="{ active: activeLevel === 'warning' }">
        <span class="stat-num">{{ stats.warning }}</span>
        <span>Warning</span>
      </div>
      <div class="stat-mini stat-mini--info" @click="setLevel('info')" :class="{ active: activeLevel === 'info' }">
        <span class="stat-num">{{ stats.info }}</span>
        <span>Info</span>
      </div>
      <div class="stat-mini stat-mini--debug" @click="setLevel('debug')" :class="{ active: activeLevel === 'debug' }">
        <span class="stat-num">{{ stats.debug }}</span>
        <span>Debug</span>
      </div>
      <div class="stat-mini stat-mini--critical" @click="setLevel('critical')" :class="{ active: activeLevel === 'critical' }">
        <span class="stat-num">{{ (stats.critical || 0) + (stats.emergency || 0) + (stats.alert || 0) }}</span>
        <span>Critical</span>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-left">
        <!-- File Selector -->
        <select v-model="selectedFile" class="tool-select" @change="fetchLogs">
          <option v-for="f in logFiles" :key="f.name" :value="f.name">
            {{ f.name }} ({{ f.size }})
          </option>
        </select>

        <!-- Search -->
        <div class="search-box">
          <i class="pi pi-search" />
          <input v-model="searchQuery" placeholder="Tìm kiếm log..." @input="debouncedFetch" />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''; fetchLogs()">
            <i class="pi pi-times" />
          </button>
        </div>
      </div>
      <div class="toolbar-right">
        <span class="result-count">{{ total }} kết quả</span>
        <div class="live-indicator" :class="{ active: isLive }">
          <span class="live-dot" />
          {{ isLive ? 'LIVE' : 'PAUSED' }}
        </div>
      </div>
    </div>

    <!-- Log Entries -->
    <div class="log-container" ref="logContainer">
      <div v-if="isLoading" class="log-loading">
        <i class="pi pi-spin pi-spinner" /> Đang tải log...
      </div>

      <div v-else-if="!entries.length" class="log-empty">
        <div class="empty-icon"><i class="pi pi-check-circle" /></div>
        <h3>Không có log nào</h3>
        <p>Hệ thống đang hoạt động bình thường</p>
      </div>

      <div v-else class="log-entries">
        <div
          v-for="entry in entries"
          :key="entry.id"
          class="log-entry"
          :class="`log-entry--${entry.level.toLowerCase()}`"
          @click="toggleExpand(entry.id)"
        >
          <!-- Level Badge -->
          <div class="entry-level">
            <span class="level-badge" :class="`badge--${entry.level.toLowerCase()}`">
              {{ entry.level }}
            </span>
          </div>

          <!-- Timestamp -->
          <div class="entry-time">{{ entry.timestamp }}</div>

          <!-- Message -->
          <div class="entry-message">
            <span class="message-text">{{ entry.message }}</span>
            <span v-if="entry.has_stacktrace" class="stacktrace-icon" title="Có stacktrace">
              <i class="pi pi-code" />
            </span>
          </div>

          <!-- Expanded Content -->
          <div v-if="expandedEntry === entry.id" class="entry-expanded" @click.stop>
            <!-- Context -->
            <div v-if="entry.context" class="expanded-section">
              <h4><i class="pi pi-info-circle" /> Context</h4>
              <pre class="code-block">{{ formatJson(entry.context) }}</pre>
            </div>

            <!-- Stacktrace -->
            <div v-if="entry.stacktrace" class="expanded-section">
              <h4><i class="pi pi-code" /> Stacktrace</h4>
              <pre class="code-block code-block--stack">{{ entry.stacktrace }}</pre>
            </div>

            <!-- Actions -->
            <div class="expanded-actions">
              <button class="exp-action" @click.stop="copyEntry(entry)">
                <i class="pi pi-copy" /> Copy
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="total > entries.length" class="load-more">
        <Button label="Tải thêm" icon="pi pi-chevron-down" text size="small" @click="loadMore" :loading="isLoadingMore" />
        <span class="load-info">{{ entries.length }} / {{ total }}</span>
      </div>
    </div>

    <!-- Live Stream Overlay -->
    <div v-if="isLive && liveLines.length" class="live-overlay" ref="liveContainer">
      <div class="live-header">
        <span class="live-dot active" /> Live Stream — {{ selectedFile }}
        <button class="live-close" @click="toggleLive"><i class="pi pi-times" /></button>
      </div>
      <pre class="live-content" ref="liveContent">{{ liveLines.join('') }}</pre>
    </div>

    <!-- Clear Dialog -->
    <Dialog v-model:visible="showClearDialog" modal header="Xóa log" :style="{ width: '420px' }" :draggable="false">
      <div class="clear-content">
        <div class="clear-icon"><i class="pi pi-exclamation-triangle" /></div>
        <p>Bạn có chắc muốn xóa file <strong>{{ selectedFile }}</strong>?</p>
        <p class="clear-sub">Hành động này không thể hoàn tác.</p>
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showClearDialog = false" />
        <Button label="Xóa log" severity="danger" icon="pi pi-trash" @click="clearLog" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

export default {
  components: { Head, Button, Dialog },
  layout: Layout,
  props: { logFiles: Array },
  data() {
    return {
      selectedFile: 'laravel.log',
      activeLevel: 'all',
      searchQuery: '',
      entries: [],
      total: 0,
      stats: {},
      page: 1,
      isLoading: false,
      isLoadingMore: false,
      isLive: false,
      liveLines: [],
      expandedEntry: null,
      showClearDialog: false,
      eventSource: null,
      debounceTimer: null,
    }
  },
  mounted() {
    this.fetchLogs()
  },
  beforeUnmount() {
    this.stopLive()
  },
  methods: {
    async fetchLogs() {
      this.isLoading = true
      this.page = 1
      try {
        const params = new URLSearchParams({
          file: this.selectedFile,
          level: this.activeLevel,
          search: this.searchQuery,
          limit: 100,
          page: this.page,
        })
        const res = await fetch(`/api/system-logs/fetch?${params}`, {
          headers: { 'Accept': 'application/json' },
        })
        const data = await res.json()
        this.entries = data.entries || []
        this.total = data.total || 0
        this.stats = data.stats || {}
      } catch (e) {
        console.error('Failed to fetch logs:', e)
      } finally {
        this.isLoading = false
      }
    },

    async loadMore() {
      this.isLoadingMore = true
      this.page++
      try {
        const params = new URLSearchParams({
          file: this.selectedFile,
          level: this.activeLevel,
          search: this.searchQuery,
          limit: 100,
          page: this.page,
        })
        const res = await fetch(`/api/system-logs/fetch?${params}`, {
          headers: { 'Accept': 'application/json' },
        })
        const data = await res.json()
        this.entries = [...this.entries, ...(data.entries || [])]
      } catch (e) {
        console.error(e)
      } finally {
        this.isLoadingMore = false
      }
    },

    setLevel(level) {
      this.activeLevel = level
      this.fetchLogs()
    },

    debouncedFetch() {
      clearTimeout(this.debounceTimer)
      this.debounceTimer = setTimeout(() => this.fetchLogs(), 400)
    },

    toggleLive() {
      if (this.isLive) {
        this.stopLive()
      } else {
        this.startLive()
      }
    },

    startLive() {
      this.isLive = true
      this.liveLines = []

      this.eventSource = new EventSource(`/api/system-logs/stream?file=${this.selectedFile}`)

      this.eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data)
        if (data.type === 'initial' || data.type === 'append') {
          this.liveLines.push(data.content)
          this.$nextTick(() => {
            const el = this.$refs.liveContent
            if (el) el.scrollTop = el.scrollHeight
          })
        }
        if (data.type === 'end') {
          // Reconnect
          this.stopLive()
          if (this.isLive) {
            setTimeout(() => this.startLive(), 1000)
          }
        }
      }

      this.eventSource.onerror = () => {
        this.stopLive()
        setTimeout(() => {
          if (this.isLive) this.startLive()
        }, 3000)
      }
    },

    stopLive() {
      this.isLive = false
      if (this.eventSource) {
        this.eventSource.close()
        this.eventSource = null
      }
    },

    toggleExpand(id) {
      this.expandedEntry = this.expandedEntry === id ? null : id
    },

    formatJson(str) {
      try {
        return JSON.stringify(JSON.parse(str), null, 2)
      } catch {
        return str
      }
    },

    copyEntry(entry) {
      const text = `[${entry.timestamp}] ${entry.level}: ${entry.message}\n${entry.context || ''}\n${entry.stacktrace || ''}`
      navigator.clipboard.writeText(text).then(() => {
        // Could show toast
      })
    },

    async clearLog() {
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || ''
        await fetch('/api/system-logs/clear', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ file: this.selectedFile }),
        })
        this.showClearDialog = false
        this.fetchLogs()
      } catch (e) {
        console.error(e)
      }
    },

    downloadLog() {
      window.location.href = `/api/system-logs/download?file=${this.selectedFile}`
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.4rem; }

/* ===== Stats Bar ===== */
.stats-bar {
  display: flex; gap: 0.35rem; margin-bottom: 0.75rem; flex-wrap: wrap;
}
.stat-mini {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.65rem; border-radius: 8px;
  font-size: 0.68rem; color: #64748b; background: white;
  border: 1.5px solid #f1f5f9; cursor: pointer; transition: all 0.2s;
}
.stat-mini:hover { border-color: #6366f1; }
.stat-mini.active { border-color: #6366f1; background: #eef2ff; color: #6366f1; font-weight: 600; }
.stat-num { font-weight: 700; font-size: 0.78rem; }
.stat-mini--error .stat-num { color: #ef4444; }
.stat-mini--warning .stat-num { color: #f59e0b; }
.stat-mini--info .stat-num { color: #3b82f6; }
.stat-mini--debug .stat-num { color: #94a3b8; }
.stat-mini--critical .stat-num { color: #dc2626; }

/* ===== Toolbar ===== */
.toolbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.5rem 0.75rem; background: white; border-radius: 12px;
  border: 1px solid #f1f5f9; margin-bottom: 0.65rem;
}
.toolbar-left { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
.toolbar-right { display: flex; align-items: center; gap: 0.75rem; }

.tool-select {
  padding: 0.4rem 0.55rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
  font-size: 0.72rem; color: #1e293b; background: white; outline: none;
}
.tool-select:focus { border-color: #6366f1; }

.search-box {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 0.55rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
  flex: 1; max-width: 320px;
}
.search-box i { color: #94a3b8; font-size: 0.72rem; }
.search-box input {
  border: none; outline: none; font-size: 0.72rem; color: #1e293b;
  flex: 1; background: transparent; font-family: inherit;
}
.search-box input::placeholder { color: #cbd5e1; }
.search-clear { border: none; background: none; cursor: pointer; color: #94a3b8; padding: 0; }

.result-count { font-size: 0.65rem; color: #94a3b8; }

.live-indicator {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.62rem; font-weight: 600; color: #94a3b8;
  padding: 0.2rem 0.5rem; border-radius: 6px; background: #f1f5f9;
}
.live-indicator.active { color: #ef4444; background: #fef2f2; }
.live-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #94a3b8;
}
.live-indicator.active .live-dot {
  background: #ef4444; animation: pulse 1.2s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

/* ===== Log Container ===== */
.log-container {
  background: #0d1117; border-radius: 14px;
  border: 1px solid #1e293b; overflow: hidden;
  min-height: 400px; max-height: calc(100vh - 320px);
  overflow-y: auto;
}
.log-container::-webkit-scrollbar { width: 6px; }
.log-container::-webkit-scrollbar-track { background: #161b22; }
.log-container::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }

.log-loading {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 3rem; color: #8b949e; font-size: 0.82rem;
}
.log-loading i { font-size: 1rem; }

.log-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 4rem; text-align: center;
}
.empty-icon { font-size: 2rem; color: #10b981; margin-bottom: 0.75rem; }
.empty-icon i { font-size: 2rem; }
.log-empty h3 { font-size: 0.95rem; font-weight: 600; color: #c9d1d9; margin: 0 0 0.25rem; }
.log-empty p { font-size: 0.78rem; color: #8b949e; margin: 0; }

/* ===== Log Entries ===== */
.log-entries { }
.log-entry {
  display: grid; grid-template-columns: 72px 145px 1fr;
  padding: 0.45rem 0.75rem; border-bottom: 1px solid #161b22;
  cursor: pointer; transition: background 0.15s; align-items: start;
  font-family: 'SF Mono', 'Fira Code', 'JetBrains Mono', monospace;
}
.log-entry:hover { background: #161b22; }

/* Level-based left accent */
.log-entry--error { border-left: 3px solid #f85149; }
.log-entry--warning { border-left: 3px solid #d29922; }
.log-entry--info { border-left: 3px solid #58a6ff; }
.log-entry--debug { border-left: 3px solid #484f58; }
.log-entry--critical, .log-entry--emergency, .log-entry--alert {
  border-left: 3px solid #ff7b72; background: rgba(248, 81, 73, 0.05);
}

.entry-level { padding-right: 0.35rem; }
.level-badge {
  font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em;
  padding: 0.12rem 0.35rem; border-radius: 4px;
}
.badge--error { background: #3d1a1a; color: #f85149; }
.badge--warning { background: #3d2e0a; color: #d29922; }
.badge--info { background: #0c2d6b; color: #58a6ff; }
.badge--debug { background: #1c1c1c; color: #8b949e; }
.badge--critical, .badge--emergency, .badge--alert { background: #5c1a1a; color: #ff7b72; }
.badge--notice { background: #1a3a2a; color: #56d364; }

.entry-time { font-size: 0.65rem; color: #484f58; padding-right: 0.5rem; padding-top: 0.1rem; }

.entry-message { min-width: 0; }
.message-text {
  font-size: 0.72rem; color: #c9d1d9; line-height: 1.45;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; word-break: break-word;
}
.stacktrace-icon {
  display: inline-flex; color: #f59e0b; margin-left: 0.35rem;
  font-size: 0.6rem; vertical-align: middle;
}

/* Expanded */
.entry-expanded {
  grid-column: 1 / -1;
  margin-top: 0.45rem; padding: 0.65rem; border-radius: 8px;
  background: #161b22; border: 1px solid #30363d;
}
.expanded-section { margin-bottom: 0.65rem; }
.expanded-section:last-of-type { margin-bottom: 0; }
.expanded-section h4 {
  font-size: 0.68rem; font-weight: 600; color: #8b949e;
  margin: 0 0 0.35rem; display: flex; align-items: center; gap: 0.3rem;
}
.expanded-section h4 i { font-size: 0.6rem; }

.code-block {
  background: #0d1117; border: 1px solid #21262d; border-radius: 6px;
  padding: 0.55rem 0.65rem; font-size: 0.65rem; color: #c9d1d9;
  overflow-x: auto; white-space: pre-wrap; word-break: break-all;
  max-height: 200px; overflow-y: auto; margin: 0;
  font-family: 'SF Mono', 'Fira Code', monospace;
}
.code-block--stack { max-height: 300px; color: #f85149; font-size: 0.6rem; }

.expanded-actions {
  display: flex; gap: 0.35rem; margin-top: 0.5rem;
  padding-top: 0.45rem; border-top: 1px solid #21262d;
}
.exp-action {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.62rem; color: #58a6ff; background: none; border: 1px solid #30363d;
  border-radius: 5px; padding: 0.2rem 0.45rem; cursor: pointer;
  transition: background 0.15s;
}
.exp-action:hover { background: #21262d; }
.exp-action i { font-size: 0.55rem; }

/* Load More */
.load-more {
  display: flex; align-items: center; justify-content: center; gap: 0.65rem;
  padding: 0.65rem; border-top: 1px solid #161b22;
}
.load-info { font-size: 0.62rem; color: #484f58; }

/* ===== Live Overlay ===== */
.live-overlay {
  position: fixed; bottom: 0; right: 0; width: 50vw; max-width: 700px;
  height: 350px; background: #0d1117; border: 1px solid #30363d;
  border-radius: 14px 0 0 0; box-shadow: 0 -4px 20px rgba(0,0,0,0.4);
  z-index: 1000; display: flex; flex-direction: column;
}
.live-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.55rem 0.85rem; border-bottom: 1px solid #21262d;
  font-size: 0.72rem; font-weight: 600; color: #c9d1d9;
}
.live-close { background: none; border: none; color: #8b949e; cursor: pointer; margin-left: auto; }
.live-content {
  flex: 1; padding: 0.65rem; font-size: 0.6rem; color: #8b949e;
  overflow-y: auto; margin: 0; white-space: pre-wrap; word-break: break-all;
  font-family: 'SF Mono', 'Fira Code', monospace;
}

/* Clear Dialog */
.clear-content { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0.5rem; }
.clear-icon { font-size: 2.5rem; color: #f59e0b; margin-bottom: 0.75rem; }
.clear-icon i { font-size: 2.5rem; }
.clear-content p { font-size: 0.82rem; color: #475569; margin: 0; }
.clear-sub { font-size: 0.72rem; color: #94a3b8; margin-top: 0.35rem !important; }

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .live-overlay { width: 100vw; max-width: 100%; border-radius: 14px 14px 0 0; }
}
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .header-actions { width: 100%; }
  .toolbar { flex-direction: column; gap: 0.5rem; }
  .toolbar-left { width: 100%; flex-wrap: wrap; }
  .search-box { max-width: 100%; }
  .log-entry { grid-template-columns: 60px 1fr; }
  .entry-time { display: none; }
  .stats-bar { overflow-x: auto; flex-wrap: nowrap; }
}
</style>
