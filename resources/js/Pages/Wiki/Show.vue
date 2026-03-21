<template>
  <div>
    <Head :title="article.title" />

    <div class="wiki-layout">
      <!-- Sidebar -->
      <aside class="wiki-sidebar">
        <div class="sidebar-header">
          <Link href="/wiki" class="back-link"><i class="pi pi-arrow-left" /> Wiki</Link>
        </div>

        <!-- Related articles -->
        <div v-if="relatedArticles.length" class="sidebar-section">
          <div class="section-label">Cùng danh mục</div>
          <Link
            v-for="rel in relatedArticles"
            :key="rel.id"
            :href="`/wiki/${rel.id}`"
            class="related-item"
            :class="{ active: rel.id === article.id }"
          >
            <i class="pi pi-file" />
            <span>{{ rel.title }}</span>
          </Link>
        </div>

        <!-- Versions -->
        <div v-if="versions.length" class="sidebar-section">
          <div class="section-label">Lịch sử phiên bản</div>
          <div
            v-for="v in versions"
            :key="v.id"
            class="version-item"
          >
            <div class="version-number">v{{ v.version_number }}</div>
            <div class="version-detail">
              <span class="version-who">{{ v.editor?.name || '—' }}</span>
              <span class="version-when">{{ v.created_at }}</span>
              <span v-if="v.change_summary" class="version-summary">{{ v.change_summary }}</span>
            </div>
          </div>
        </div>

        <!-- Categories tree -->
        <div class="sidebar-section">
          <div class="section-label">Danh mục</div>
          <Link
            v-for="cat in categories"
            :key="cat.id"
            :href="`/wiki?category_id=${cat.id}`"
            class="related-item"
          >
            <i :class="cat.icon || 'pi pi-folder'" />
            <span>{{ cat.name }}</span>
          </Link>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="wiki-main">
        <!-- Trashed banner -->
        <div v-if="article.deleted_at" class="trashed-banner">
          <i class="pi pi-exclamation-triangle" />
          <span>Bài viết này đã bị xóa.</span>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
          <Link href="/wiki" class="crumb">Wiki</Link>
          <span class="crumb-sep">›</span>
          <Link v-if="article.category" :href="`/wiki?category_id=${article.category.id}`" class="crumb">
            {{ article.category.name }}
          </Link>
          <span v-if="article.category" class="crumb-sep">›</span>
          <span class="crumb-current">{{ article.title }}</span>
        </div>

        <!-- Article Header -->
        <div class="article-header">
          <div class="header-left">
            <div class="title-row">
              <i v-if="article.is_pinned" class="pi pi-bookmark-fill pin-icon" />
              <h1 class="article-title">{{ article.title }}</h1>
            </div>
            <div class="article-meta">
              <span v-if="article.category" class="cat-badge">
                <i :class="article.category.icon || 'pi pi-folder'" />
                {{ article.category.name }}
              </span>
              <span class="meta-item"><i class="pi pi-user" /> {{ article.author?.name }}</span>
              <span class="meta-item"><i class="pi pi-calendar" /> {{ formatDate(article.created_at) }}</span>
              <span v-if="article.updated_at !== article.created_at" class="meta-item">
                <i class="pi pi-pencil" /> Sửa: {{ formatDate(article.updated_at) }}
                <template v-if="article.editor"> bởi {{ article.editor.name }}</template>
              </span>
              <span class="meta-item"><i class="pi pi-eye" /> {{ article.views_count }} lượt xem</span>
              <span v-if="article.status === 'draft'" class="status-draft">Bản nháp</span>
            </div>
          </div>
          <div class="header-actions">
            <Button
              :icon="article.is_pinned ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'"
              :severity="article.is_pinned ? 'warning' : 'secondary'"
              size="small"
              text
              rounded
              :title="article.is_pinned ? 'Bỏ ghim' : 'Ghim'"
              @click="togglePin"
            />
            <Link :href="`/wiki/${article.id}/edit`">
              <Button icon="pi pi-pencil" severity="secondary" size="small" text rounded title="Chỉnh sửa" />
            </Link>
            <Button
              v-if="!article.deleted_at"
              icon="pi pi-trash"
              severity="danger"
              size="small"
              text
              rounded
              title="Xóa"
              @click="destroy"
            />
            <Button
              v-else
              icon="pi pi-replay"
              severity="warning"
              size="small"
              text
              rounded
              title="Khôi phục"
              @click="restore"
            />
          </div>
        </div>

        <!-- Article Content -->
        <div class="article-content" v-html="article.content" />
      </main>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: {
    article: Object,
    versions: Array,
    categories: Array,
    relatedArticles: Array,
  },
  methods: {
    formatDate(d) {
      if (!d) return ''
      return new Date(d).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric' })
    },
    togglePin() {
      router.post(`/wiki/${this.article.id}/toggle-pin`, {}, { preserveScroll: true })
    },
    destroy() {
      if (confirm('Bạn có chắc muốn xóa bài viết này?')) {
        router.delete(`/wiki/${this.article.id}`)
      }
    },
    restore() {
      router.put(`/wiki/${this.article.id}/restore`, {}, { preserveScroll: true })
    },
  },
}
</script>

<style scoped>
.wiki-layout { display: flex; gap: 1.5rem; min-height: calc(100vh - 140px); }

/* Sidebar */
.wiki-sidebar {
  width: 280px; flex-shrink: 0; background: white;
  border-radius: 12px; padding: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
  position: sticky; top: 80px; max-height: calc(100vh - 100px); overflow-y: auto;
}
.sidebar-header { margin-bottom: 0.75rem; }
.back-link {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.82rem; font-weight: 600; color: #6366f1;
  text-decoration: none; transition: all 0.15s;
}
.back-link:hover { color: #4f46e5; }
.back-link i { font-size: 0.7rem; }

.sidebar-section { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; }
.section-label {
  font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.4rem;
}

.related-item {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.5rem; border-radius: 6px;
  font-size: 0.78rem; color: #475569; text-decoration: none; transition: all 0.15s;
}
.related-item:hover { background: #f8fafc; }
.related-item.active { background: #eef2ff; color: #4f46e5; font-weight: 600; }
.related-item i { font-size: 0.7rem; color: #94a3b8; flex-shrink: 0; }
.related-item span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.version-item {
  display: flex; gap: 0.5rem; padding: 0.4rem 0;
  border-bottom: 1px solid #f8fafc;
}
.version-number {
  font-size: 0.65rem; font-weight: 700; color: #6366f1;
  background: #eef2ff; padding: 0.1rem 0.35rem; border-radius: 4px;
  height: fit-content;
}
.version-detail { display: flex; flex-direction: column; gap: 0.1rem; }
.version-who { font-size: 0.72rem; color: #475569; font-weight: 500; }
.version-when { font-size: 0.65rem; color: #94a3b8; }
.version-summary { font-size: 0.65rem; color: #64748b; font-style: italic; }

/* Main */
.wiki-main { flex: 1; min-width: 0; }

.trashed-banner {
  background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
  padding: 0.65rem 1rem; border-radius: 10px; margin-bottom: 0.75rem;
  display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem;
}

.breadcrumb {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.75rem; margin-bottom: 1rem;
}
.crumb { color: #6366f1; text-decoration: none; transition: color 0.15s; }
.crumb:hover { color: #4f46e5; }
.crumb-sep { color: #cbd5e1; }
.crumb-current { color: #64748b; }

.article-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 1.5rem; padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}
.title-row { display: flex; align-items: center; gap: 0.5rem; }
.pin-icon { color: #f59e0b; font-size: 0.9rem; }
.article-title { font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3; }
.article-meta {
  display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
  margin-top: 0.5rem;
}
.cat-badge {
  font-size: 0.7rem; font-weight: 500; color: #6366f1;
  background: #eef2ff; padding: 0.2rem 0.5rem; border-radius: 6px;
  display: flex; align-items: center; gap: 0.25rem;
}
.cat-badge i { font-size: 0.6rem; }
.meta-item { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.meta-item i { font-size: 0.65rem; }
.status-draft {
  font-size: 0.65rem; font-weight: 600; color: #f59e0b;
  background: #fffbeb; padding: 0.15rem 0.45rem; border-radius: 4px;
}
.header-actions { display: flex; gap: 0.25rem; flex-shrink: 0; }

/* Article Content */
.article-content {
  background: white; border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
  font-size: 0.95rem; line-height: 1.7; color: #334155;
}
.article-content :deep(h1) { font-size: 1.5rem; font-weight: 700; margin: 1.5rem 0 0.75rem; color: #0f172a; }
.article-content :deep(h2) { font-size: 1.25rem; font-weight: 600; margin: 1.25rem 0 0.65rem; color: #1e293b; }
.article-content :deep(h3) { font-size: 1.1rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #334155; }
.article-content :deep(p) { margin: 0 0 0.75rem; }
.article-content :deep(ul), .article-content :deep(ol) { margin: 0 0 0.75rem; padding-left: 1.5rem; }
.article-content :deep(li) { margin-bottom: 0.35rem; }
.article-content :deep(code) {
  background: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 4px;
  font-size: 0.85em; color: #e11d48;
}
.article-content :deep(pre) {
  background: #1e293b; color: #e2e8f0; padding: 1rem;
  border-radius: 8px; overflow-x: auto; margin: 0 0 1rem;
}
.article-content :deep(pre code) { background: none; color: inherit; padding: 0; }
.article-content :deep(blockquote) {
  border-left: 3px solid #6366f1; padding: 0.5rem 1rem;
  margin: 0 0 1rem; background: #f8fafc; border-radius: 0 8px 8px 0;
  color: #475569;
}
.article-content :deep(table) { width: 100%; border-collapse: collapse; margin: 0 0 1rem; }
.article-content :deep(th), .article-content :deep(td) {
  border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; text-align: left;
}
.article-content :deep(th) { background: #f8fafc; font-weight: 600; font-size: 0.85rem; }
.article-content :deep(img) { max-width: 100%; height: auto; border-radius: 8px; margin: 0.5rem 0; }
.article-content :deep(a) { color: #6366f1; text-decoration: underline; }
.article-content :deep(a:hover) { color: #4f46e5; }
.article-content :deep(hr) { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }

@media (max-width: 768px) {
  .wiki-layout { flex-direction: column; }
  .wiki-sidebar { width: 100%; position: static; max-height: none; }
  .article-title { font-size: 1.35rem; }
  .article-content { padding: 1.25rem; }
}
</style>
