<template>
  <div>
    <Head title="Wiki nội bộ" />

    <div class="wiki-layout">
      <!-- Sidebar -->
      <aside class="wiki-sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title"><i class="pi pi-book" /> Wiki</h2>
          <button class="btn-icon" title="Tạo danh mục" @click="showCategoryDialog = true">
            <i class="pi pi-folder-plus" />
          </button>
        </div>

        <!-- Search -->
        <div class="sidebar-search">
          <i class="pi pi-search" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm bài viết..."
            @input="onSearch"
          />
        </div>

        <!-- Categories tree -->
        <nav class="sidebar-nav">
          <a
            class="nav-item"
            :class="{ active: !filters.category_id }"
            href="#"
            @click.prevent="filterByCategory(null)"
          >
            <i class="pi pi-list" />
            <span>Tất cả</span>
            <span class="nav-count">{{ stats.total_articles }}</span>
          </a>
          <template v-for="cat in categories" :key="cat.id">
            <a
              class="nav-item"
              :class="{ active: filters.category_id == cat.id }"
              href="#"
              @click.prevent="filterByCategory(cat.id)"
            >
              <i :class="cat.icon || 'pi pi-folder'" />
              <span>{{ cat.name }}</span>
              <span class="nav-count">{{ cat.articles_count }}</span>
            </a>
            <template v-if="cat.children">
              <a
                v-for="child in cat.children"
                :key="child.id"
                class="nav-item nav-child"
                :class="{ active: filters.category_id == child.id }"
                href="#"
                @click.prevent="filterByCategory(child.id)"
              >
                <i :class="child.icon || 'pi pi-file'" />
                <span>{{ child.name }}</span>
                <span class="nav-count">{{ child.articles_count }}</span>
              </a>
            </template>
          </template>
        </nav>

        <!-- Drafts -->
        <div v-if="drafts.length" class="sidebar-section">
          <div class="section-label">Bản nháp của bạn</div>
          <Link
            v-for="draft in drafts"
            :key="draft.id"
            :href="`/wiki/${draft.id}/edit`"
            class="draft-item"
          >
            <i class="pi pi-file-edit" />
            <span>{{ draft.title }}</span>
          </Link>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="wiki-main">
        <div class="main-header">
          <div>
            <h1 class="main-title">
              {{ filters.category_id ? getCategoryName(filters.category_id) : 'Tất cả bài viết' }}
            </h1>
            <p class="main-subtitle">{{ stats.total_articles }} bài viết · {{ stats.total_categories }} danh mục</p>
          </div>
          <Link href="/wiki/create">
            <Button label="Viết bài" icon="pi pi-plus" />
          </Link>
        </div>

        <!-- Pinned articles -->
        <div v-if="pinnedArticles.length && !filters.category_id && !filters.search" class="pinned-section">
          <h3 class="section-heading"><i class="pi pi-bookmark-fill" /> Bài viết ghim</h3>
          <div class="pinned-grid">
            <Link
              v-for="article in pinnedArticles"
              :key="article.id"
              :href="`/wiki/${article.id}`"
              class="pinned-card"
            >
              <h4 class="pinned-title">{{ article.title }}</h4>
              <p class="pinned-excerpt">{{ article.excerpt }}</p>
              <div class="pinned-meta">
                <span v-if="article.category" class="cat-badge">
                  <i :class="article.category.icon || 'pi pi-folder'" />
                  {{ article.category.name }}
                </span>
                <span class="views"><i class="pi pi-eye" /> {{ article.views_count }}</span>
              </div>
            </Link>
          </div>
        </div>

        <!-- Article list -->
        <div class="article-list">
          <Link
            v-for="article in articles.data"
            :key="article.id"
            :href="`/wiki/${article.id}`"
            class="article-row"
          >
            <div class="article-info">
              <div class="article-title-row">
                <i v-if="article.is_pinned" class="pi pi-bookmark-fill pinned-icon" />
                <h3 class="article-title">{{ article.title }}</h3>
              </div>
              <p class="article-excerpt">{{ article.excerpt }}</p>
              <div class="article-meta">
                <span v-if="article.category" class="cat-tag">
                  <i :class="article.category.icon || 'pi pi-folder'" />
                  {{ article.category.name }}
                </span>
                <span v-if="article.author" class="author">{{ article.author.name }}</span>
                <span class="date">{{ article.updated_at }}</span>
                <span class="views"><i class="pi pi-eye" /> {{ article.views_count }}</span>
              </div>
            </div>
          </Link>

          <div v-if="articles.data.length === 0" class="empty-state">
            <i class="pi pi-file-edit" />
            <h3>Chưa có bài viết</h3>
            <p>Bắt đầu tạo bài viết đầu tiên cho Wiki nội bộ</p>
            <Link href="/wiki/create">
              <Button label="Viết bài" icon="pi pi-plus" size="small" />
            </Link>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="articles.links && articles.links.length > 3" class="pagination">
          <Link
            v-for="link in articles.links"
            :key="link.label"
            :href="link.url"
            class="page-link"
            :class="{ active: link.active, disabled: !link.url }"
            v-html="link.label"
          />
        </div>
      </main>
    </div>

    <!-- Category Dialog -->
    <Dialog v-model:visible="showCategoryDialog" header="Tạo danh mục" :modal="true" :style="{ width: '420px' }">
      <div class="form-group">
        <label>Tên danh mục <span class="required">*</span></label>
        <input v-model="categoryForm.name" type="text" class="form-control" placeholder="VD: Quy trình" />
      </div>
      <div class="form-group">
        <label>Danh mục cha</label>
        <select v-model="categoryForm.parent_id" class="form-control">
          <option :value="null">-- Không --</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
      <div class="form-group">
        <label>Icon (PrimeVue class)</label>
        <input v-model="categoryForm.icon" type="text" class="form-control" placeholder="pi pi-folder" />
      </div>
      <div class="form-group">
        <label>Mô tả</label>
        <textarea v-model="categoryForm.description" class="form-control" rows="2" />
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showCategoryDialog = false" />
        <Button label="Tạo" icon="pi pi-check" :loading="categoryForm.processing" @click="submitCategory" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

export default {
  components: { Head, Link, Button, Dialog },
  layout: Layout,
  props: {
    articles: Object,
    drafts: Array,
    categories: Array,
    filters: Object,
    stats: Object,
  },
  data() {
    return {
      searchQuery: this.filters?.search || '',
      showCategoryDialog: false,
      searchTimeout: null,
    }
  },
  setup() {
    const categoryForm = useForm({
      name: '',
      parent_id: null,
      icon: 'pi pi-folder',
      description: '',
    })
    return { categoryForm }
  },
  computed: {
    pinnedArticles() {
      return this.articles.data.filter(a => a.is_pinned)
    },
  },
  methods: {
    onSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/wiki', { search: this.searchQuery || undefined, category_id: this.filters.category_id }, {
          preserveState: true,
          replace: true,
        })
      }, 300)
    },
    filterByCategory(categoryId) {
      router.get('/wiki', { category_id: categoryId || undefined, search: this.filters.search }, {
        preserveState: true,
        replace: true,
      })
    },
    getCategoryName(id) {
      const find = (cats) => {
        for (const cat of cats) {
          if (cat.id == id) return cat.name
          if (cat.children) {
            const found = find(cat.children)
            if (found) return found
          }
        }
        return null
      }
      return find(this.categories) || 'Danh mục'
    },
    submitCategory() {
      this.categoryForm.post('/wiki-categories', {
        onSuccess: () => {
          this.showCategoryDialog = false
          this.categoryForm.reset()
        },
      })
    },
  },
}
</script>

<style scoped>
/* ===== Layout ===== */
.wiki-layout {
  display: flex;
  gap: 1.5rem;
  min-height: calc(100vh - 140px);
}

/* ===== Sidebar ===== */
.wiki-sidebar {
  width: 280px;
  flex-shrink: 0;
  background: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  position: sticky;
  top: 80px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}
.sidebar-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 0.75rem;
}
.sidebar-title {
  font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0;
  display: flex; align-items: center; gap: 0.4rem;
}
.sidebar-title i { color: #6366f1; }
.btn-icon {
  width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: white; cursor: pointer; display: flex; align-items: center;
  justify-content: center; color: #64748b; transition: all 0.2s;
}
.btn-icon:hover { background: #f1f5f9; color: #1e293b; }

.sidebar-search {
  position: relative; margin-bottom: 0.75rem;
}
.sidebar-search i {
  position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%);
  font-size: 0.8rem; color: #94a3b8;
}
.sidebar-search input {
  width: 100%; padding: 0.5rem 0.6rem 0.5rem 2rem;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.8rem; outline: none; transition: all 0.2s;
}
.sidebar-search input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.sidebar-nav { display: flex; flex-direction: column; gap: 2px; }
.nav-item {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.65rem; border-radius: 8px;
  font-size: 0.8rem; color: #475569; text-decoration: none;
  transition: all 0.15s; cursor: pointer;
}
.nav-item:hover { background: #f8fafc; color: #1e293b; }
.nav-item.active { background: #eef2ff; color: #4f46e5; font-weight: 600; }
.nav-item i { font-size: 0.82rem; width: 18px; text-align: center; flex-shrink: 0; }
.nav-item span:first-of-type { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nav-count {
  font-size: 0.65rem; font-weight: 600; background: #f1f5f9;
  color: #94a3b8; padding: 0.1rem 0.35rem; border-radius: 6px;
}
.nav-child { padding-left: 1.5rem; }

.sidebar-section { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; }
.section-label {
  font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 0.4rem; padding: 0 0.4rem;
}
.draft-item {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.6rem; border-radius: 6px;
  font-size: 0.75rem; color: #f59e0b; text-decoration: none; transition: all 0.15s;
}
.draft-item:hover { background: #fffbeb; }
.draft-item i { font-size: 0.7rem; }

/* ===== Main Content ===== */
.wiki-main { flex: 1; min-width: 0; }
.main-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.25rem;
}
.main-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.main-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* Pinned section */
.pinned-section { margin-bottom: 1.5rem; }
.section-heading {
  font-size: 0.82rem; font-weight: 600; color: #64748b; margin: 0 0 0.75rem;
  display: flex; align-items: center; gap: 0.4rem;
}
.section-heading i { color: #f59e0b; font-size: 0.78rem; }
.pinned-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.75rem; }
.pinned-card {
  background: linear-gradient(135deg, #fefce8, #fffbeb);
  border: 1px solid #fde68a; border-radius: 10px;
  padding: 1rem; text-decoration: none; transition: all 0.2s;
}
.pinned-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.pinned-title { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0 0 0.35rem; }
.pinned-excerpt { font-size: 0.75rem; color: #64748b; margin: 0 0 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pinned-meta { display: flex; align-items: center; gap: 0.75rem; }
.cat-badge {
  font-size: 0.65rem; font-weight: 500; color: #6366f1;
  display: flex; align-items: center; gap: 0.25rem;
}
.cat-badge i { font-size: 0.6rem; }
.views { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.views i { font-size: 0.6rem; }

/* Article list */
.article-list { display: flex; flex-direction: column; gap: 0.5rem; }
.article-row {
  display: flex; align-items: flex-start;
  background: white; border-radius: 10px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9; text-decoration: none; transition: all 0.2s;
}
.article-row:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-color: #e2e8f0; }
.article-info { flex: 1; }
.article-title-row { display: flex; align-items: center; gap: 0.4rem; }
.pinned-icon { font-size: 0.7rem; color: #f59e0b; }
.article-title { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0; }
.article-row:hover .article-title { color: #4f46e5; }
.article-excerpt { font-size: 0.78rem; color: #64748b; margin: 0.3rem 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.article-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.cat-tag {
  font-size: 0.65rem; font-weight: 500; color: #6366f1;
  background: #eef2ff; padding: 0.15rem 0.4rem; border-radius: 4px;
  display: flex; align-items: center; gap: 0.2rem;
}
.cat-tag i { font-size: 0.55rem; }
.author { font-size: 0.7rem; color: #64748b; }
.date { font-size: 0.7rem; color: #94a3b8; }

/* Empty state */
.empty-state {
  text-align: center; padding: 3rem 1rem; color: #94a3b8;
}
.empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1; }
.empty-state h3 { font-size: 1.1rem; color: #475569; margin: 0 0 0.3rem; }
.empty-state p { font-size: 0.82rem; margin: 0 0 1rem; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 0.25rem; margin-top: 1.5rem; }
.page-link {
  padding: 0.4rem 0.7rem; border-radius: 6px;
  font-size: 0.78rem; text-decoration: none; color: #475569;
  border: 1px solid #e2e8f0; transition: all 0.15s;
}
.page-link:hover { background: #f1f5f9; }
.page-link.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-link.disabled { opacity: 0.4; pointer-events: none; }

/* Form */
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 500; color: #475569; margin-bottom: 0.3rem; }
.required { color: #ef4444; }
.form-control {
  width: 100%; padding: 0.5rem 0.7rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.82rem; outline: none; font-family: inherit;
  transition: all 0.2s;
}
.form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
select.form-control { cursor: pointer; }
textarea.form-control { resize: vertical; }

@media (max-width: 768px) {
  .wiki-layout { flex-direction: column; }
  .wiki-sidebar { width: 100%; position: static; max-height: none; }
  .pinned-grid { grid-template-columns: 1fr; }
}
</style>
