<template>
  <div>
    <Head title="Bài đăng mạng xã hội" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-send" style="color: #3b82f6; margin-right: 0.5rem;" />
          Bài đăng
        </h1>
        <p class="page-subtitle">Quản lý bài đăng trên các nền tảng mạng xã hội</p>
      </div>
      <div class="header-actions">
        <Link href="/content-studio"><button class="btn-secondary"><i class="pi pi-palette" /> Content Studio</button></Link>
        <Link href="/social-posts/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo bài đăng</button></Link>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab" :class="{ active: activeFilter === 'all' }" @click="applyFilter('all')">
        Tất cả
        <span class="tab-count">{{ totalItems }}</span>
      </button>
      <button class="filter-tab" :class="{ active: activeFilter === 'published' }" @click="applyFilter('published')">
        <i class="pi pi-check-circle" style="color: #10b981;" />
        Đã đăng
      </button>
      <button class="filter-tab" :class="{ active: activeFilter === 'scheduled' }" @click="applyFilter('scheduled')">
        <i class="pi pi-clock" style="color: #f59e0b;" />
        Lên lịch
      </button>
      <button class="filter-tab" :class="{ active: activeFilter === 'draft' }" @click="applyFilter('draft')">
        <i class="pi pi-file" style="color: #94a3b8;" />
        Nháp
      </button>
      <button class="filter-tab" :class="{ active: activeFilter === 'failed' }" @click="applyFilter('failed')">
        <i class="pi pi-exclamation-triangle" style="color: #ef4444;" />
        Lỗi
      </button>
    </div>

    <!-- Posts List -->
    <div v-if="posts.data && posts.data.length" class="posts-list">
      <div
        v-for="post in posts.data"
        :key="post.id"
        class="post-card"
        @click="goToPost(post)"
      >
        <div class="post-status-bar" :style="{ background: getStatusColor(post.status) }" />

        <div class="post-body">
          <!-- Top Row -->
          <div class="post-top">
            <div class="post-platform-icon" :style="{ background: getPlatformGradient(post.platform) }">
              <i :class="getPlatformIcon(post.platform)" />
            </div>
            <div class="post-platform-info">
              <span class="post-platform-name">{{ getPlatformLabel(post.platform) }}</span>
              <span class="post-account-name" v-if="post.social_account">@{{ post.social_account.username || post.social_account.name }}</span>
            </div>
            <div class="post-status-tag" :class="`status--${post.status}`">
              {{ getStatusLabel(post.status) }}
            </div>
          </div>

          <!-- Content Preview -->
          <p class="post-content">{{ post.content }}</p>

          <!-- Media Preview -->
          <div v-if="post.media_urls && post.media_urls.length" class="post-media">
            <img v-for="(url, i) in post.media_urls.slice(0, 2)" :key="i" :src="url" class="media-thumb" />
            <div v-if="post.media_urls.length > 2" class="media-more">+{{ post.media_urls.length - 2 }}</div>
          </div>

          <!-- Bottom Row -->
          <div class="post-bottom">
            <div class="post-dates">
              <span v-if="post.scheduled_at" class="date-item">
                <i class="pi pi-clock" /> Lên lịch: {{ post.scheduled_at }}
              </span>
              <span v-if="post.posted_at" class="date-item">
                <i class="pi pi-check" /> Đăng: {{ post.posted_at }}
              </span>
              <span v-if="!post.scheduled_at && !post.posted_at" class="date-item">
                <i class="pi pi-calendar" /> Tạo: {{ post.created_at }}
              </span>
            </div>

            <!-- Engagement -->
            <div v-if="post.analytics" class="post-engagement">
              <span v-if="post.analytics.likes !== undefined" class="engagement-item">
                <i class="pi pi-heart" /> {{ post.analytics.likes }}
              </span>
              <span v-if="post.analytics.comments !== undefined" class="engagement-item">
                <i class="pi pi-comment" /> {{ post.analytics.comments }}
              </span>
              <span v-if="post.analytics.shares !== undefined" class="engagement-item">
                <i class="pi pi-share-alt" /> {{ post.analytics.shares }}
              </span>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="post.error_message" class="post-error">
            <i class="pi pi-exclamation-circle" />
            <span>{{ post.error_message }}</span>
            <button class="retry-btn" @click.stop="retry(post.id)">Thử lại</button>
          </div>
        </div>

        <div class="post-arrow"><i class="pi pi-chevron-right" /></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-illustration"><i class="pi pi-send" /></div>
      <h3>Chưa có bài đăng nào</h3>
      <p>Sử dụng Content Studio để tạo & đăng bài tự động</p>
      <Link href="/content-studio"><button class="btn-primary"><i class="pi pi-palette" /> Content Studio</button></Link>
    </div>

    <!-- Pagination -->
    <div v-if="posts.total > posts.per_page" class="pagination-wrapper">
      <span class="page-info">{{ posts.from }}–{{ posts.to }} / {{ posts.total }}</span>
      <div class="page-btns"><button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === posts.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button></div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'


export default {
  components: { Head, Link },
  layout: Layout,
  props: { posts: Object },

  data() {
    return { activeFilter: 'all' }
  },
  computed: {
    totalItems() { return this.posts.total || this.posts.data?.length || 0 },
    pageNumbers() { const t = this.posts.last_page, c = this.posts.current_page, p = []; if (!t) return p; if (t <= 7) { for (let i = 1; i <= t; i++) p.push(i) } else { p.push(1); if (c > 3) p.push('...'); for (let i = Math.max(2, c - 1); i <= Math.min(t - 1, c + 1); i++) p.push(i); if (c < t - 2) p.push('...'); p.push(t) } return p },
  },
  methods: {
    goToPost(post) { router.visit(`/social-posts/${post.id}`) },
    applyFilter(status) {
      this.activeFilter = status
      const params = status === 'all' ? {} : { status }
      router.get('/social-posts', params, { preserveState: true })
    },
    retry(id) { router.post(`/social-posts/${id}/retry`, {}, { preserveScroll: true }) },
    goToPage(pg) { const u = new URL(window.location.href); u.searchParams.set('page', pg); router.visit(u.pathname + u.search, { preserveState: true, preserveScroll: true }) },
      getStatusColor(s) {
      return { draft: '#94a3b8', scheduled: '#f59e0b', posting: '#3b82f6', published: '#10b981', failed: '#ef4444' }[s] || '#94a3b8'
    },
    getStatusLabel(s) {
      return { draft: 'Nháp', scheduled: 'Lên lịch', posting: 'Đang đăng', published: 'Đã đăng', failed: 'Lỗi' }[s] || s
    },
    getPlatformIcon(p) {
      return { facebook: 'pi pi-facebook', instagram: 'pi pi-instagram', linkedin: 'pi pi-linkedin', twitter: 'pi pi-twitter' }[p] || 'pi pi-globe'
    },
    getPlatformLabel(p) {
      return { facebook: 'Facebook', instagram: 'Instagram', linkedin: 'LinkedIn', twitter: 'Twitter / X' }[p] || p
    },
    getPlatformGradient(p) {
      return {
        facebook: 'linear-gradient(135deg, #1877F2, #0d65d9)',
        instagram: 'linear-gradient(135deg, #E4405F, #F77737)',
        linkedin: 'linear-gradient(135deg, #0A66C2, #004182)',
        twitter: 'linear-gradient(135deg, #1DA1F2, #0d8ecf)',
      }[p] || '#6366f1'
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* ===== Filter Tabs ===== */
.filter-tabs {
  display: flex; gap: 0.25rem; padding: 0.3rem;
  background: white; border-radius: 12px; border: 1px solid #f1f5f9;
  margin-bottom: 1rem; overflow-x: auto;
}
.filter-tab {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.5rem 0.85rem; border-radius: 8px; border: none; background: transparent;
  font-size: 0.78rem; color: #64748b; cursor: pointer; transition: all 0.2s;
  white-space: nowrap;
}
.filter-tab.active { background: #6366f1; color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(99,102,241,0.3); }
.filter-tab:hover:not(.active) { background: #f1f5f9; }
.filter-tab i { font-size: 0.68rem; }
.tab-count {
  font-size: 0.62rem; font-weight: 700; background: rgba(255,255,255,0.2);
  padding: 0.1rem 0.35rem; border-radius: 4px;
}
.filter-tab.active .tab-count { background: rgba(255,255,255,0.25); }

/* ===== Posts List ===== */
.posts-list { display: flex; flex-direction: column; gap: 0.55rem; }

.post-card {
  display: flex; align-items: stretch;
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
  cursor: pointer; transition: all 0.25s;
}
.post-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.07); transform: translateY(-1px); }

.post-status-bar { width: 4px; flex-shrink: 0; }

.post-body { flex: 1; padding: 0.85rem 1rem; min-width: 0; }

.post-top { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.55rem; }
.post-platform-icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.85rem; flex-shrink: 0;
}
.post-platform-info { flex: 1; min-width: 0; }
.post-platform-name { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; }
.post-account-name { font-size: 0.65rem; color: #94a3b8; }

.post-status-tag {
  font-size: 0.62rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 6px; flex-shrink: 0;
}
.status--draft { background: #f1f5f9; color: #64748b; }
.status--scheduled { background: #fffbeb; color: #d97706; }
.status--posting { background: #eff6ff; color: #3b82f6; }
.status--published { background: #ecfdf5; color: #059669; }
.status--failed { background: #fef2f2; color: #dc2626; }

.post-content {
  font-size: 0.78rem; color: #475569; line-height: 1.5; margin: 0;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}

.post-media { display: flex; gap: 0.35rem; margin-top: 0.5rem; }
.media-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; }
.media-more {
  width: 48px; height: 48px; border-radius: 8px; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 600; color: #64748b;
}

.post-bottom {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 0.5rem; padding-top: 0.45rem; border-top: 1px solid #f8fafc;
}
.post-dates { display: flex; gap: 0.75rem; }
.date-item { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.date-item i { font-size: 0.55rem; }

.post-engagement { display: flex; gap: 0.55rem; }
.engagement-item {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.68rem; font-weight: 600; color: #6366f1;
}
.engagement-item i { font-size: 0.58rem; }

.post-error {
  display: flex; align-items: center; gap: 0.4rem;
  margin-top: 0.45rem; padding: 0.4rem 0.6rem; border-radius: 8px;
  background: #fef2f2; font-size: 0.68rem; color: #dc2626;
}
.post-error i { font-size: 0.65rem; }
.post-error span { flex: 1; }
.retry-btn {
  font-size: 0.65rem; font-weight: 600; color: #6366f1; background: white;
  border: 1px solid #6366f1; border-radius: 5px; padding: 0.15rem 0.4rem;
  cursor: pointer;
}

.post-arrow {
  display: flex; align-items: center; padding: 0 0.75rem;
  color: #cbd5e1; transition: color 0.2s;
}
.post-card:hover .post-arrow { color: #6366f1; }

/* ===== Empty / Pagination ===== */
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.65rem;
  padding: 4rem; background: white; border-radius: 14px; border: 1px solid #f1f5f9;
}
.empty-illustration {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; color: #3b82f6;
}
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

.pagination-wrapper {
  display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem;
  margin-top: 1rem; background: white; border-radius: 12px; border: 1px solid #f1f5f9;
}
.page-info{font-size:.72rem;color:#94a3b8}.page-btns{display:flex;gap:.2rem}.page-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}.page-btn:hover:not(.active):not(.dots){border-color:#6366f1;color:#6366f1}.page-btn.active{background:#6366f1;color:#fff;border-color:#6366f1}.page-btn.dots{border:none;cursor:default}
.btn-primary{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s;text-decoration:none}.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(99,102,241,.3)}
.btn-secondary{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none}.btn-secondary:hover{border-color:#6366f1;color:#6366f1}

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .header-actions { width: 100%; }
  .filter-tabs { overflow-x: auto; }
}
</style>
