<template>
  <div>
    <Head title="Viết bài Wiki" />

    <div class="editor-layout">
      <div class="editor-header">
        <div>
          <h1 class="page-title">Viết bài mới</h1>
          <p class="page-subtitle">Tạo bài viết cho Wiki nội bộ</p>
        </div>
        <Link href="/wiki">
          <Button label="Quay lại" icon="pi pi-arrow-left" severity="secondary" text />
        </Link>
      </div>

      <form @submit.prevent="submit" class="editor-form">
        <div class="editor-main">
          <!-- Title -->
          <div class="form-group">
            <input
              v-model="form.title"
              type="text"
              class="title-input"
              placeholder="Tiêu đề bài viết..."
            />
            <span v-if="form.errors.title" class="error">{{ form.errors.title }}</span>
          </div>

          <!-- Excerpt -->
          <div class="form-group">
            <input
              v-model="form.excerpt"
              type="text"
              class="excerpt-input"
              placeholder="Mô tả ngắn (hiển thị trong danh sách)..."
            />
          </div>

          <!-- Content -->
          <div class="form-group">
            <label class="content-label">Nội dung</label>
            <div class="editor-toolbar">
              <button type="button" class="toolbar-btn" title="Bold" @click="insertTag('b')"><b>B</b></button>
              <button type="button" class="toolbar-btn" title="Italic" @click="insertTag('i')"><i>I</i></button>
              <button type="button" class="toolbar-btn" title="Heading 2" @click="insertHeading">H2</button>
              <button type="button" class="toolbar-btn" title="Link" @click="insertLink"><i class="pi pi-link" /></button>
              <button type="button" class="toolbar-btn" title="List" @click="insertList"><i class="pi pi-list" /></button>
              <button type="button" class="toolbar-btn" title="Code" @click="insertCode"><i class="pi pi-code" /></button>
              <button type="button" class="toolbar-btn" title="Quote" @click="insertQuote"><i class="pi pi-comment" /></button>
              <button type="button" class="toolbar-btn" title="Table" @click="insertTable"><i class="pi pi-table" /></button>
              <span class="toolbar-divider" />
              <button type="button" class="toolbar-btn" :class="{ active: previewMode }" @click="previewMode = !previewMode">
                <i class="pi pi-eye" /> Preview
              </button>
            </div>
            <div v-if="!previewMode">
              <textarea
                ref="contentEditor"
                v-model="form.content"
                class="content-editor"
                rows="20"
                placeholder="Viết nội dung bài viết ở đây... (hỗ trợ HTML)"
              />
            </div>
            <div v-else class="content-preview" v-html="form.content" />
          </div>
        </div>

        <!-- Sidebar settings -->
        <div class="editor-sidebar">
          <div class="sidebar-card">
            <h3 class="card-title"><i class="pi pi-cog" /> Cài đặt</h3>

            <div class="form-group">
              <label>Danh mục</label>
              <select v-model="form.category_id" class="form-control">
                <option :value="null">-- Không phân loại --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.parent_id ? '└ ' : '' }}{{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Trạng thái</label>
              <div class="status-selector">
                <button
                  type="button"
                  class="status-btn"
                  :class="{ active: form.status === 'draft' }"
                  @click="form.status = 'draft'"
                >
                  <i class="pi pi-file-edit" /> Bản nháp
                </button>
                <button
                  type="button"
                  class="status-btn published"
                  :class="{ active: form.status === 'published' }"
                  @click="form.status = 'published'"
                >
                  <i class="pi pi-check-circle" /> Xuất bản
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="form.is_pinned" />
                <span>Ghim bài viết</span>
              </label>
            </div>
          </div>

          <div class="sidebar-actions">
            <Button
              type="submit"
              :label="form.status === 'published' ? 'Xuất bản' : 'Lưu nháp'"
              :icon="form.status === 'published' ? 'pi pi-send' : 'pi pi-save'"
              :loading="form.processing"
              class="w-full"
            />
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: {
    categories: Array,
    statuses: Object,
  },
  data() {
    return {
      previewMode: false,
    }
  },
  setup() {
    const form = useForm({
      title: '',
      excerpt: '',
      content: '',
      category_id: null,
      status: 'draft',
      is_pinned: false,
    })
    return { form }
  },
  methods: {
    submit() {
      this.form.post('/wiki')
    },
    insertTag(tag) {
      const el = this.$refs.contentEditor
      const start = el.selectionStart
      const end = el.selectionEnd
      const selected = this.form.content.substring(start, end) || 'text'
      const replacement = `<${tag}>${selected}</${tag}>`
      this.form.content = this.form.content.substring(0, start) + replacement + this.form.content.substring(end)
    },
    insertHeading() {
      const el = this.$refs.contentEditor
      const start = el.selectionStart
      const end = el.selectionEnd
      const selected = this.form.content.substring(start, end) || 'Tiêu đề'
      this.form.content = this.form.content.substring(0, start) + `<h2>${selected}</h2>\n` + this.form.content.substring(end)
    },
    insertLink() {
      const url = prompt('Nhập URL:', 'https://')
      if (url) {
        const el = this.$refs.contentEditor
        const start = el.selectionStart
        const end = el.selectionEnd
        const selected = this.form.content.substring(start, end) || 'link text'
        this.form.content = this.form.content.substring(0, start) + `<a href="${url}">${selected}</a>` + this.form.content.substring(end)
      }
    },
    insertList() {
      const el = this.$refs.contentEditor
      const pos = el.selectionStart
      this.form.content = this.form.content.substring(0, pos) + `<ul>\n  <li>Mục 1</li>\n  <li>Mục 2</li>\n  <li>Mục 3</li>\n</ul>\n` + this.form.content.substring(pos)
    },
    insertCode() {
      const el = this.$refs.contentEditor
      const start = el.selectionStart
      const end = el.selectionEnd
      const selected = this.form.content.substring(start, end) || 'code'
      this.form.content = this.form.content.substring(0, start) + `<code>${selected}</code>` + this.form.content.substring(end)
    },
    insertQuote() {
      const el = this.$refs.contentEditor
      const pos = el.selectionStart
      this.form.content = this.form.content.substring(0, pos) + `<blockquote>Trích dẫn...</blockquote>\n` + this.form.content.substring(pos)
    },
    insertTable() {
      const el = this.$refs.contentEditor
      const pos = el.selectionStart
      this.form.content = this.form.content.substring(0, pos) +
        `<table>\n  <tr><th>Cột 1</th><th>Cột 2</th><th>Cột 3</th></tr>\n  <tr><td></td><td></td><td></td></tr>\n  <tr><td></td><td></td><td></td></tr>\n</table>\n` +
        this.form.content.substring(pos)
    },
  },
}
</script>

<style scoped>
.editor-layout { max-width: 1100px; }
.editor-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }

.editor-form { display: flex; gap: 1.25rem; align-items: flex-start; }
.editor-main { flex: 1; min-width: 0; }

/* Title & excerpt inputs */
.title-input {
  width: 100%; padding: 0.75rem 1rem; border: none; border-bottom: 2px solid #e2e8f0;
  font-size: 1.4rem; font-weight: 700; color: #0f172a; outline: none;
  background: transparent; font-family: inherit;
}
.title-input::placeholder { color: #cbd5e1; font-weight: 400; }
.title-input:focus { border-bottom-color: #6366f1; }

.excerpt-input {
  width: 100%; padding: 0.55rem 1rem; border: none; border-bottom: 1px solid #f1f5f9;
  font-size: 0.88rem; color: #64748b; outline: none;
  background: transparent; font-family: inherit;
}
.excerpt-input::placeholder { color: #cbd5e1; }
.excerpt-input:focus { border-bottom-color: #6366f1; }

.content-label {
  display: block; font-size: 0.72rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: 0.05em;
  color: #94a3b8; margin-bottom: 0.4rem;
}

/* Toolbar */
.editor-toolbar {
  display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap;
  padding: 0.5rem; background: #f8fafc; border-radius: 8px 8px 0 0;
  border: 1px solid #e2e8f0; border-bottom: none;
}
.toolbar-btn {
  padding: 0.35rem 0.5rem; border-radius: 6px; border: none;
  background: transparent; cursor: pointer; font-size: 0.78rem;
  color: #475569; transition: all 0.15s;
  display: flex; align-items: center; gap: 0.25rem;
}
.toolbar-btn:hover { background: white; color: #1e293b; }
.toolbar-btn.active { background: #6366f1; color: white; }
.toolbar-btn i { font-size: 0.78rem; }
.toolbar-divider { width: 1px; height: 20px; background: #e2e8f0; margin: 0 0.25rem; }

.content-editor {
  width: 100%; padding: 1rem; border: 1px solid #e2e8f0;
  border-radius: 0 0 8px 8px; font-size: 0.88rem; color: #1e293b;
  outline: none; font-family: 'Courier New', monospace; resize: vertical;
  min-height: 400px; line-height: 1.6;
}
.content-editor:focus { border-color: #6366f1; }

.content-preview {
  padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px;
  min-height: 400px; font-size: 0.92rem; line-height: 1.7; color: #334155;
}

.error { font-size: 0.72rem; color: #ef4444; margin-top: 0.2rem; display: block; }

/* Sidebar */
.editor-sidebar { width: 280px; flex-shrink: 0; position: sticky; top: 80px; }
.sidebar-card {
  background: white; border-radius: 12px; padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
}
.card-title {
  font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0 0 1rem;
  display: flex; align-items: center; gap: 0.4rem;
}
.card-title i { color: #6366f1; }

.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.75rem; font-weight: 500; color: #475569; margin-bottom: 0.3rem; }
.form-control {
  width: 100%; padding: 0.5rem 0.7rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.82rem; outline: none; font-family: inherit;
  transition: all 0.2s;
}
.form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
select.form-control { cursor: pointer; }

.status-selector { display: flex; gap: 0.5rem; }
.status-btn {
  flex: 1; padding: 0.45rem 0.5rem; border-radius: 8px;
  font-size: 0.75rem; font-weight: 600; border: 2px solid #e2e8f0;
  background: white; cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 0.3rem;
}
.status-btn:hover { border-color: #cbd5e1; }
.status-btn.active { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
.status-btn.published.active { border-color: #10b981; background: #ecfdf5; color: #10b981; }
.status-btn i { font-size: 0.7rem; }

.checkbox-label {
  display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem;
  color: #475569; cursor: pointer;
}
.checkbox-label input { accent-color: #6366f1; cursor: pointer; }

.sidebar-actions { margin-top: 1rem; }
.w-full { width: 100%; }

@media (max-width: 768px) {
  .editor-form { flex-direction: column; }
  .editor-sidebar { width: 100%; position: static; }
}
</style>
