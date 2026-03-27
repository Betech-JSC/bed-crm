<template>
  <div>
    <Head title="Công việc cá nhân" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-check-square" style="color:#8b5cf6;" /> Công việc cá nhân</h1>
        <p class="page-subtitle">Quản lý công việc hàng ngày của bạn</p>
      </div>
      <div class="header-actions">
        <button class="btn-add" @click="openCreate">
          <i class="pi pi-plus" /> Thêm việc mới
        </button>
      </div>
    </div>

    <!-- Stats Strip -->
    <div class="stats-row">
      <div class="stat-card" :class="{ active: !activeFilter }" @click="setFilter('due', null)">
        <div class="stat-icon si-total"><i class="pi pi-list-check" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Tổng</span></div>
      </div>
      <div class="stat-card" :class="{ active: activeFilter === 'today' }" @click="setFilter('due', 'today')">
        <div class="stat-icon si-today"><i class="pi pi-sun" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.today }}</span><span class="stat-lbl">Hôm nay</span></div>
      </div>
      <div class="stat-card" :class="{ active: activeFilter === 'this_week' }" @click="setFilter('due', 'this_week')">
        <div class="stat-icon si-week"><i class="pi pi-calendar" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.this_week }}</span><span class="stat-lbl">Tuần này</span></div>
      </div>
      <div class="stat-card" :class="{ active: activeFilter === 'overdue' }" @click="setFilter('due', 'overdue')">
        <div class="stat-icon si-overdue"><i class="pi pi-exclamation-triangle" /></div>
        <div class="stat-body"><span class="stat-val">{{ stats.overdue }}</span><span class="stat-lbl">Quá hạn</span></div>
      </div>
      <div class="stat-card stat-progress">
        <div class="stat-icon si-done"><i class="pi pi-check-circle" /></div>
        <div class="stat-body">
          <span class="stat-val">{{ stats.done }}<small>/{{ stats.total }}</small></span>
          <span class="stat-lbl">Hoàn thành</span>
        </div>
        <div class="progress-ring">
          <svg width="32" height="32" viewBox="0 0 32 32">
            <circle cx="16" cy="16" r="14" fill="none" stroke="#f1f5f9" stroke-width="3" />
            <circle cx="16" cy="16" r="14" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"
              :stroke-dasharray="`${completionPercent * 0.88} 88`" transform="rotate(-90 16 16)" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <i class="pi pi-search" />
        <input v-model="searchQuery" type="text" placeholder="Tìm công việc..." @input="debouncedSearch" />
      </div>
      <div class="filter-pills">
        <button class="pill" :class="{ active: !filterStatus }" @click="setFilter('status', null)">Tất cả</button>
        <button v-for="(info, key) in statuses" :key="key" class="pill" :class="{ active: filterStatus === key }"
          @click="setFilter('status', key)">
          <i :class="info.icon" :style="{ color: info.color }" /> {{ info.label }}
        </button>
      </div>
      <div class="filter-pills">
        <button v-for="(info, key) in priorities" :key="key" class="pill pill-sm"
          :class="{ active: filterPriority === key }" @click="setFilter('priority', key)">
          <i :class="info.icon" :style="{ color: info.color }" />
        </button>
      </div>
    </div>

    <!-- Quick Add -->
    <div class="quick-add" v-if="showQuickAdd">
      <input v-model="quickTitle" ref="quickInput" type="text" class="quick-input"
        placeholder="Nhập tên công việc rồi Enter..." @keyup.enter="quickCreate" @keyup.escape="showQuickAdd = false" />
      <div class="quick-actions">
        <select v-model="quickPriority" class="quick-select">
          <option v-for="(info, key) in priorities" :key="key" :value="key">{{ info.label }}</option>
        </select>
        <select v-model="quickCategory" class="quick-select">
          <option v-for="(info, key) in categories" :key="key" :value="key">{{ info.label }}</option>
        </select>
        <input v-model="quickDue" type="date" class="quick-select" />
        <button class="quick-btn" @click="quickCreate" :disabled="!quickTitle"><i class="pi pi-plus" /></button>
        <button class="quick-cancel" @click="showQuickAdd = false"><i class="pi pi-times" /></button>
      </div>
    </div>

    <!-- Task List -->
    <div v-if="tasks.data?.length" class="task-list">
      <!-- Pinned -->
      <div v-if="pinnedTasks.length" class="task-section">
        <div class="section-label"><i class="pi pi-bookmark-fill" /> Ghim</div>
        <div v-for="task in pinnedTasks" :key="task.id" class="task-item" :class="taskClasses(task)" @click="openEdit(task)">
          <button class="task-check" :class="{ checked: task.status === 'done' }" @click.stop="toggleTask(task)">
            <i :class="task.status === 'done' ? 'pi pi-check' : ''" />
          </button>
          <div class="task-body">
            <div class="task-title" :class="{ done: task.status === 'done' }">
              <i v-if="task.is_pinned" class="pi pi-bookmark-fill pin-icon" />
              {{ task.title }}
            </div>
            <div class="task-meta">
              <span v-if="task.category_info" class="meta-chip" :style="{ color: task.category_info.color }">
                <i :class="task.category_info.icon" /> {{ task.category_info.label }}
              </span>
              <span v-if="task.due_date" class="meta-chip" :class="{ overdue: task.is_overdue }">
                <i class="pi pi-calendar" /> {{ task.due_date }}
              </span>
              <span v-if="task.checklist_progress.total" class="meta-chip">
                <i class="pi pi-check-square" /> {{ task.checklist_progress.done }}/{{ task.checklist_progress.total }}
              </span>
              <span v-for="tag in (task.tags || []).slice(0, 3)" :key="tag" class="tag-chip">{{ tag }}</span>
            </div>
          </div>
          <div class="task-right">
            <span class="priority-dot" :style="{ background: task.priority_info.color }" :title="task.priority_info.label" />
            <button class="task-action" @click.stop="togglePin(task)" :title="task.is_pinned ? 'Bỏ ghim' : 'Ghim'">
              <i :class="task.is_pinned ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'" />
            </button>
            <button class="task-action del" @click.stop="deleteTask(task.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>

      <!-- Regular tasks -->
      <div class="task-section">
        <div v-if="pinnedTasks.length" class="section-label"><i class="pi pi-list" /> Tất cả</div>
        <div v-for="task in regularTasks" :key="task.id" class="task-item" :class="taskClasses(task)" @click="openEdit(task)">
          <button class="task-check" :class="{ checked: task.status === 'done' }" @click.stop="toggleTask(task)">
            <i :class="task.status === 'done' ? 'pi pi-check' : ''" />
          </button>
          <div class="task-body">
            <div class="task-title" :class="{ done: task.status === 'done' }">{{ task.title }}</div>
            <div class="task-meta">
              <span v-if="task.category_info" class="meta-chip" :style="{ color: task.category_info.color }">
                <i :class="task.category_info.icon" /> {{ task.category_info.label }}
              </span>
              <span v-if="task.due_date" class="meta-chip" :class="{ overdue: task.is_overdue }">
                <i class="pi pi-calendar" /> {{ task.due_date }}
              </span>
              <span v-if="task.checklist_progress.total" class="meta-chip">
                <i class="pi pi-check-square" /> {{ task.checklist_progress.done }}/{{ task.checklist_progress.total }}
              </span>
              <span v-for="tag in (task.tags || []).slice(0, 3)" :key="tag" class="tag-chip">{{ tag }}</span>
            </div>
          </div>
          <div class="task-right">
            <span class="priority-dot" :style="{ background: task.priority_info.color }" :title="task.priority_info.label" />
            <button class="task-action" @click.stop="togglePin(task)"><i class="pi pi-bookmark" /></button>
            <button class="task-action del" @click.stop="deleteTask(task.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-check-square" /></div>
      <h3>Chưa có công việc nào</h3>
      <p>Thêm công việc mới để bắt đầu quản lý</p>
      <button class="btn-add" @click="openCreate"><i class="pi pi-plus" /> Thêm việc mới</button>
    </div>

    <!-- Pagination -->
    <div v-if="tasks.last_page > 1" class="pagination-bar">
      <span class="pg-info">{{ tasks.from }}–{{ tasks.to }} / {{ tasks.total }}</span>
      <div class="pg-btns">
        <button class="pg-btn" :disabled="!tasks.prev_page_url" @click="goPage(tasks.current_page - 1)"><i class="pi pi-chevron-left" /></button>
        <span class="pg-current">{{ tasks.current_page }}/{{ tasks.last_page }}</span>
        <button class="pg-btn" :disabled="!tasks.next_page_url" @click="goPage(tasks.current_page + 1)"><i class="pi pi-chevron-right" /></button>
      </div>
    </div>

    <!-- ═══ Create/Edit Modal ═══ -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i :class="editingTask ? 'pi pi-pencil' : 'pi pi-plus'" /> {{ editingTask ? 'Chỉnh sửa' : 'Thêm việc mới' }}</h3>
          <button class="modal-close" @click="showModal = false"><i class="pi pi-times" /></button>
        </div>

        <div class="modal-body">
          <div class="fm-group">
            <label>Tên công việc <span class="req">*</span></label>
            <input v-model="form.title" type="text" class="fm-input" placeholder="Nhập tên công việc..." />
          </div>

          <div class="fm-group">
            <label>Mô tả</label>
            <textarea v-model="form.description" rows="2" class="fm-input" placeholder="Mô tả chi tiết..." />
          </div>

          <div class="fm-row">
            <div class="fm-group">
              <label>Trạng thái</label>
              <div class="option-pills">
                <button v-for="(info, key) in statuses" :key="key" class="opt-pill" :class="{ active: form.status === key }" @click="form.status = key">
                  <i :class="info.icon" :style="{ color: form.status === key ? 'white' : info.color }" /> {{ info.label }}
                </button>
              </div>
            </div>
          </div>

          <div class="fm-row">
            <div class="fm-group">
              <label>Ưu tiên</label>
              <div class="option-pills">
                <button v-for="(info, key) in priorities" :key="key" class="opt-pill" :class="{ active: form.priority === key }"
                  :style="form.priority === key ? { background: info.color, borderColor: info.color } : {}" @click="form.priority = key">
                  <i :class="info.icon" /> {{ info.label }}
                </button>
              </div>
            </div>
          </div>

          <div class="fm-row">
            <div class="fm-group flex-1">
              <label>Danh mục</label>
              <select v-model="form.category" class="fm-input">
                <option v-for="(info, key) in categories" :key="key" :value="key">{{ info.label }}</option>
              </select>
            </div>
            <div class="fm-group flex-1">
              <label>Hạn chót</label>
              <input v-model="form.due_date" type="date" class="fm-input" />
            </div>
          </div>

          <!-- Checklist -->
          <div class="fm-group">
            <label><i class="pi pi-check-square" /> Checklist</label>
            <div class="checklist-editor">
              <div v-for="(item, i) in form.checklist" :key="i" class="cl-item">
                <input type="checkbox" v-model="item.done" />
                <input v-model="item.text" type="text" class="cl-input" placeholder="Hạng mục..." />
                <button class="cl-del" @click="form.checklist.splice(i, 1)"><i class="pi pi-times" /></button>
              </div>
              <button class="cl-add" @click="form.checklist.push({ text: '', done: false })">
                <i class="pi pi-plus" /> Thêm mục
              </button>
            </div>
          </div>

          <!-- Tags -->
          <div class="fm-group">
            <label><i class="pi pi-tag" /> Tags</label>
            <div class="tags-editor">
              <span v-for="(tag, i) in form.tags" :key="i" class="tag-item">
                {{ tag }} <button @click="form.tags.splice(i, 1)"><i class="pi pi-times" /></button>
              </span>
              <input v-model="tagInput" type="text" class="tag-input" placeholder="Nhập tag + Enter"
                @keyup.enter="addTag" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-cancel" @click="showModal = false">Hủy</button>
          <button class="btn-save" @click="saveTask" :disabled="!form.title || isSaving">
            <i :class="isSaving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" />
            {{ editingTask ? 'Cập nhật' : 'Tạo mới' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import axios from 'axios'

export default {
  components: { Head },
  layout: Layout,
  props: { tasks: Object, stats: Object, statuses: Object, priorities: Object, categories: Object, filters: Object },
  data() {
    return {
      searchQuery: this.filters?.search || '',
      filterStatus: this.filters?.status || null,
      filterPriority: this.filters?.priority || null,
      activeFilter: this.filters?.due || null,
      searchTimeout: null,

      // Quick add
      showQuickAdd: false,
      quickTitle: '',
      quickPriority: 'medium',
      quickCategory: 'work',
      quickDue: '',

      // Modal
      showModal: false,
      editingTask: null,
      isSaving: false,
      tagInput: '',
      form: this.emptyForm(),
    }
  },
  computed: {
    pinnedTasks() { return (this.tasks.data || []).filter(t => t.is_pinned) },
    regularTasks() { return (this.tasks.data || []).filter(t => !t.is_pinned) },
    completionPercent() {
      return this.stats.total > 0 ? Math.round((this.stats.done / this.stats.total) * 100) : 0
    },
  },
  methods: {
    emptyForm() {
      return {
        title: '', description: '', status: 'todo', priority: 'medium',
        category: 'work', due_date: '', checklist: [], tags: [],
      }
    },
    openCreate() {
      this.editingTask = null
      this.form = this.emptyForm()
      this.showModal = true
    },
    openEdit(task) {
      this.editingTask = task
      this.form = {
        title: task.title,
        description: task.description || '',
        status: task.status,
        priority: task.priority,
        category: task.category || 'work',
        due_date: task.due_date_iso || '',
        checklist: [...(task.checklist || [])],
        tags: [...(task.tags || [])],
      }
      this.showModal = true
    },
    async saveTask() {
      this.isSaving = true
      try {
        if (this.editingTask) {
          await axios.put(`/my-tasks/${this.editingTask.id}`, this.form)
        } else {
          await axios.post('/my-tasks', this.form)
        }
        this.showModal = false
        router.reload({ preserveState: false })
      } catch (e) {
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      }
      this.isSaving = false
    },
    async quickCreate() {
      if (!this.quickTitle) return
      try {
        await axios.post('/my-tasks', {
          title: this.quickTitle,
          priority: this.quickPriority,
          category: this.quickCategory,
          due_date: this.quickDue || null,
        })
        this.quickTitle = ''
        this.quickDue = ''
        router.reload({ preserveState: false })
      } catch (e) { alert('Lỗi tạo task') }
    },
    async toggleTask(task) {
      try {
        const { data } = await axios.post(`/my-tasks/${task.id}/toggle`)
        task.status = data.status
        task.status_info = this.statuses[data.status]
        router.reload({ preserveState: false })
      } catch (e) { console.error(e) }
    },
    async togglePin(task) {
      try {
        const { data } = await axios.post(`/my-tasks/${task.id}/pin`)
        task.is_pinned = data.is_pinned
        router.reload({ preserveState: false })
      } catch (e) { console.error(e) }
    },
    async deleteTask(id) {
      if (!confirm('Xóa công việc này?')) return
      try {
        await axios.delete(`/my-tasks/${id}`)
        router.reload({ preserveState: false })
      } catch (e) { console.error(e) }
    },
    addTag() {
      const tag = this.tagInput.trim()
      if (tag && !this.form.tags.includes(tag)) {
        this.form.tags.push(tag)
      }
      this.tagInput = ''
    },
    setFilter(key, val) {
      const params = { search: this.searchQuery || undefined }
      if (key === 'status') {
        this.filterStatus = val
        this.filterPriority = null
        this.activeFilter = null
      } else if (key === 'priority') {
        this.filterPriority = this.filterPriority === val ? null : val
      } else if (key === 'due') {
        this.activeFilter = this.activeFilter === val ? null : val
        this.filterStatus = null
      }
      if (this.filterStatus) params.status = this.filterStatus
      if (this.filterPriority) params.priority = this.filterPriority
      if (this.activeFilter) params.due = this.activeFilter
      router.get('/my-tasks', params, { preserveState: true, replace: true })
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => this.setFilter('search', null), 400)
    },
    goPage(page) {
      router.visit(`/my-tasks?page=${page}`, { preserveState: true })
    },
    taskClasses(task) {
      return {
        'task--done': task.status === 'done',
        'task--overdue': task.is_overdue,
        'task--pinned': task.is_pinned,
        [`task--${task.priority}`]: true,
      }
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-title i { font-size: 1.2rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; cursor: pointer; transition: all 0.15s; position: relative; }
.stat-card:hover { border-color: #e2e8f0; }
.stat-card.active { border-color: #8b5cf6; background: linear-gradient(135deg, #faf5ff 0%, white 30%); }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #f1f5f9; color: #64748b; }
.si-today { background: #fef3c7; color: #f59e0b; }
.si-week { background: #eff6ff; color: #3b82f6; }
.si-overdue { background: #fef2f2; color: #ef4444; }
.si-done { background: #ecfdf5; color: #10b981; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-val small { font-size: 0.65rem; font-weight: 500; color: #94a3b8; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; font-weight: 500; }
.stat-progress { gap: 0.4rem; }
.progress-ring { margin-left: auto; }

/* Filters */
.filter-bar { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; min-width: 180px; max-width: 280px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-pills { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.pill { padding: 0.28rem 0.6rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; transition: all 0.15s; font-family: inherit; }
.pill:hover { border-color: #8b5cf6; }
.pill.active { background: #8b5cf6; color: white; border-color: #8b5cf6; }
.pill i { font-size: 0.5rem; }
.pill-sm { padding: 0.25rem 0.45rem; }

/* Quick Add */
.quick-add { display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.75rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 0.75rem; flex-wrap: wrap; }
.quick-input { flex: 1; border: none; outline: none; font-size: 0.82rem; color: #1e293b; font-family: inherit; min-width: 200px; }
.quick-actions { display: flex; align-items: center; gap: 0.35rem; }
.quick-select { padding: 0.3rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.68rem; color: #475569; background: white; font-family: inherit; outline: none; }
.quick-btn { width: 30px; height: 30px; border-radius: 8px; background: #8b5cf6; border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; }
.quick-btn:disabled { opacity: 0.5; }
.quick-cancel { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; }

/* Task List */
.task-section { margin-bottom: 0.5rem; }
.section-label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.25rem; }
.section-label i { font-size: 0.58rem; color: #f59e0b; }

.task-item { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.6rem 0.75rem; background: white; border-radius: 11px; border: 1.5px solid #f1f5f9; margin-bottom: 0.3rem; cursor: pointer; transition: all 0.15s; }
.task-item:hover { border-color: #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
.task-item.task--done { opacity: 0.6; }
.task-item.task--overdue { border-left: 3px solid #ef4444; }
.task-item.task--pinned { background: linear-gradient(135deg, #fffbeb 0%, white 20%); border-color: #fde68a; }

/* Checkbox */
.task-check { width: 22px; height: 22px; border-radius: 6px; border: 2px solid #d1d5db; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 0.1rem; transition: all 0.15s; font-size: 0.6rem; color: transparent; }
.task-check:hover { border-color: #8b5cf6; }
.task-check.checked { background: #8b5cf6; border-color: #8b5cf6; color: white; }

/* Task body */
.task-body { flex: 1; min-width: 0; }
.task-title { font-size: 0.82rem; font-weight: 600; color: #1e293b; line-height: 1.35; }
.task-title.done { text-decoration: line-through; color: #94a3b8; }
.pin-icon { font-size: 0.55rem; color: #f59e0b; margin-right: 0.15rem; }
.task-meta { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.2rem; }
.meta-chip { display: inline-flex; align-items: center; gap: 0.15rem; font-size: 0.58rem; font-weight: 600; color: #94a3b8; }
.meta-chip i { font-size: 0.5rem; }
.meta-chip.overdue { color: #ef4444; font-weight: 700; }
.tag-chip { display: inline-block; padding: 0.05rem 0.3rem; border-radius: 4px; background: #f1f5f9; font-size: 0.52rem; font-weight: 600; color: #8b5cf6; }

/* Right actions */
.task-right { display: flex; align-items: center; gap: 0.2rem; flex-shrink: 0; }
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.task-action { width: 26px; height: 26px; border-radius: 6px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.62rem; transition: all 0.15s; opacity: 0; }
.task-item:hover .task-action { opacity: 1; }
.task-action:hover { background: #f1f5f9; color: #8b5cf6; }
.task-action.del:hover { color: #ef4444; background: #fef2f2; }

/* Empty */
.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #eef2ff, #faf5ff); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; }
.empty-icon i { font-size: 1.3rem; color: #8b5cf6; }
.empty-state h3 { font-size: 1rem; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

/* Pagination */
.pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; }
.pg-info { font-size: 0.72rem; color: #94a3b8; }
.pg-btns { display: flex; align-items: center; gap: 0.3rem; }
.pg-btn { width: 30px; height: 30px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; }
.pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pg-current { font-size: 0.72rem; font-weight: 700; color: #8b5cf6; min-width: 50px; text-align: center; }

/* ═══ Modal ═══ */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-card { background: white; border-radius: 18px; width: 520px; max-width: 92vw; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.modal-header h3 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.35rem; }
.modal-header h3 i { color: #8b5cf6; }
.modal-close { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-body { padding: 1rem 1.25rem; overflow-y: auto; flex: 1; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.75rem 1.25rem; border-top: 1px solid #f1f5f9; }

.fm-group { margin-bottom: 0.7rem; }
.fm-group label { display: block; font-size: 0.7rem; font-weight: 600; color: #475569; margin-bottom: 0.2rem; }
.fm-group label i { font-size: 0.62rem; color: #8b5cf6; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.45rem 0.7rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.8rem; color: #1e293b; outline: none; font-family: inherit; transition: border-color 0.15s; }
.fm-input:focus { border-color: #8b5cf6; }
textarea.fm-input { resize: vertical; min-height: 36px; }
.fm-row { display: flex; gap: 0.6rem; }
.flex-1 { flex: 1; }

/* Option pills */
.option-pills { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.opt-pill { padding: 0.25rem 0.55rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-family: inherit; transition: all 0.12s; }
.opt-pill:hover { border-color: #8b5cf6; }
.opt-pill.active { background: #8b5cf6; color: white; border-color: #8b5cf6; }
.opt-pill i { font-size: 0.5rem; }

/* Checklist editor */
.checklist-editor { display: flex; flex-direction: column; gap: 0.25rem; }
.cl-item { display: flex; align-items: center; gap: 0.35rem; }
.cl-item input[type=checkbox] { accent-color: #8b5cf6; }
.cl-input { flex: 1; padding: 0.3rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.75rem; outline: none; font-family: inherit; }
.cl-input:focus { border-color: #8b5cf6; }
.cl-del { width: 22px; height: 22px; border-radius: 5px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.55rem; }
.cl-del:hover { color: #ef4444; }
.cl-add { padding: 0.3rem 0.6rem; border-radius: 6px; border: 1.5px dashed #e2e8f0; background: transparent; font-size: 0.68rem; font-weight: 600; color: #94a3b8; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-family: inherit; }
.cl-add:hover { border-color: #8b5cf6; color: #8b5cf6; }

/* Tags editor */
.tags-editor { display: flex; flex-wrap: wrap; gap: 0.25rem; padding: 0.35rem; border: 1.5px solid #e2e8f0; border-radius: 9px; }
.tag-item { display: inline-flex; align-items: center; gap: 0.15rem; padding: 0.15rem 0.4rem; border-radius: 5px; background: #eef2ff; font-size: 0.65rem; font-weight: 600; color: #6366f1; }
.tag-item button { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 0.5rem; padding: 0; }
.tag-input { border: none; outline: none; font-size: 0.75rem; color: #1e293b; font-family: inherit; min-width: 80px; flex: 1; }

/* Buttons */
.btn-cancel { padding: 0.45rem 0.85rem; border-radius: 9px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-save { display: flex; align-items: center; gap: 0.3rem; padding: 0.45rem 1.1rem; border-radius: 9px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all 0.15s; }
.btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .fm-row { flex-direction: column; }
  .quick-add { flex-direction: column; }
}
</style>
