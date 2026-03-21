<template>
  <div>
    <Head title="Chi tiết bài đăng" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/social-posts" class="breadcrumb-link">
        <i class="pi pi-arrow-left" /> Bài đăng
      </Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Chi tiết</span>
    </div>

    <!-- Hero -->
    <div class="post-hero">
      <div class="hero-bar" :style="{ background: getStatusColor(post.status) }" />
      <div class="hero-content">
        <div class="hero-left">
          <div class="hero-platform-icon" :style="{ background: getPlatformGradient(post.platform) }">
            <i :class="getPlatformIcon(post.platform)" />
          </div>
          <div>
            <h1 class="hero-title">{{ getPlatformLabel(post.platform) }}</h1>
            <div class="hero-meta">
              <span v-if="post.social_account" class="meta-chip">
                <i class="pi pi-user" /> {{ post.social_account.name }}
              </span>
              <span v-if="post.content_item" class="meta-chip meta-chip--link">
                <Link :href="`/content-items/${post.content_item.id}`">
                  <i class="pi pi-file" /> {{ post.content_item.title }}
                </Link>
              </span>
            </div>
          </div>
        </div>
        <div class="hero-right">
          <div class="status-tag" :class="`status--${post.status}`">
            {{ getStatusLabel(post.status) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Layout -->
    <div class="detail-layout">
      <!-- Main -->
      <div class="detail-main">
        <!-- Content Card -->
        <div class="section-card">
          <div class="section-header">
            <i class="pi pi-file-edit" />
            <h3>Nội dung bài đăng</h3>
          </div>
          <div class="content-body">
            <p class="post-text">{{ post.content }}</p>
          </div>

          <!-- Media -->
          <div v-if="post.media_urls && post.media_urls.length" class="media-section">
            <div class="media-header">
              <i class="pi pi-image" />
              <span>Media ({{ post.media_urls.length }})</span>
            </div>
            <div class="media-grid">
              <img
                v-for="(url, i) in post.media_urls"
                :key="i"
                :src="url"
                class="media-item"
              />
            </div>
          </div>

          <!-- Error -->
          <div v-if="post.error_message" class="error-box">
            <i class="pi pi-exclamation-triangle" />
            <div>
              <span class="error-title">Lỗi đăng bài</span>
              <p class="error-text">{{ post.error_message }}</p>
            </div>
          </div>
        </div>

        <!-- Analytics Card -->
        <div v-if="post.analytics" class="section-card">
          <div class="section-header">
            <i class="pi pi-chart-bar" />
            <h3>Thống kê tương tác</h3>
            <span v-if="post.analytics_synced_at" class="sync-info">
              <i class="pi pi-clock" /> {{ post.analytics_synced_at }}
            </span>
          </div>
          <div class="analytics-grid">
            <div v-if="post.analytics.likes !== undefined" class="analytics-item">
              <div class="analytics-icon analytics-icon--pink"><i class="pi pi-heart-fill" /></div>
              <span class="analytics-value">{{ formatNumber(post.analytics.likes) }}</span>
              <span class="analytics-label">Lượt thích</span>
            </div>
            <div v-if="post.analytics.comments !== undefined" class="analytics-item">
              <div class="analytics-icon analytics-icon--blue"><i class="pi pi-comment" /></div>
              <span class="analytics-value">{{ formatNumber(post.analytics.comments) }}</span>
              <span class="analytics-label">Bình luận</span>
            </div>
            <div v-if="post.analytics.shares !== undefined" class="analytics-item">
              <div class="analytics-icon analytics-icon--green"><i class="pi pi-share-alt" /></div>
              <span class="analytics-value">{{ formatNumber(post.analytics.shares) }}</span>
              <span class="analytics-label">Chia sẻ</span>
            </div>
            <div v-if="post.analytics.views !== undefined" class="analytics-item">
              <div class="analytics-icon analytics-icon--purple"><i class="pi pi-eye" /></div>
              <span class="analytics-value">{{ formatNumber(post.analytics.views) }}</span>
              <span class="analytics-label">Lượt xem</span>
            </div>
          </div>
        </div>

        <!-- Timeline Card -->
        <div class="section-card">
          <div class="section-header">
            <i class="pi pi-history" />
            <h3>Timeline</h3>
          </div>
          <div class="timeline">
            <div class="timeline-item timeline-item--active">
              <div class="timeline-dot" />
              <div class="timeline-body">
                <span class="timeline-title">Tạo bài đăng</span>
                <span class="timeline-date">{{ post.created_at }}</span>
              </div>
            </div>
            <div v-if="post.scheduled_at" class="timeline-item" :class="{ 'timeline-item--active': post.scheduled_at }">
              <div class="timeline-dot" />
              <div class="timeline-body">
                <span class="timeline-title">Lên lịch</span>
                <span class="timeline-date">{{ post.scheduled_at }}</span>
              </div>
            </div>
            <div v-if="post.posted_at" class="timeline-item timeline-item--active">
              <div class="timeline-dot" />
              <div class="timeline-body">
                <span class="timeline-title">Đã đăng</span>
                <span class="timeline-date">{{ post.posted_at }}</span>
              </div>
            </div>
            <div v-if="post.status === 'failed'" class="timeline-item timeline-item--error">
              <div class="timeline-dot" />
              <div class="timeline-body">
                <span class="timeline-title">Đăng thất bại</span>
                <span class="timeline-date">Đã thử {{ post.retry_count || 0 }} lần</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="detail-sidebar">
        <!-- Actions -->
        <div class="sidebar-card">
          <div class="sidebar-header"><h3><i class="pi pi-cog" /> Hành động</h3></div>
          <div class="action-list">
            <Button
              v-if="post.status === 'failed'"
              label="Thử lại"
              icon="pi pi-refresh"
              class="action-btn"
              @click="retry"
            />
            <Button
              label="Đồng bộ thống kê"
              icon="pi pi-chart-bar"
              severity="secondary"
              outlined
              class="action-btn"
              @click="syncAnalytics"
            />
            <div class="action-divider" />
            <Button
              label="Xóa bài đăng"
              icon="pi pi-trash"
              severity="danger"
              text
              size="small"
              class="action-btn"
              @click="deletePost"
            />
          </div>
        </div>

        <!-- Info -->
        <div class="sidebar-card">
          <div class="sidebar-header"><h3><i class="pi pi-info-circle" /> Thông tin</h3></div>
          <div class="info-list">
            <div class="info-row">
              <span class="info-label">Nền tảng</span>
              <span class="info-value">{{ getPlatformLabel(post.platform) }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Tài khoản</span>
              <span class="info-value">{{ post.social_account?.name || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Trạng thái</span>
              <span class="info-value">
                <span class="status-tag status-tag--sm" :class="`status--${post.status}`">{{ getStatusLabel(post.status) }}</span>
              </span>
            </div>
            <div class="info-row" v-if="post.platform_post_id">
              <span class="info-label">Platform ID</span>
              <span class="info-value info-value--mono">{{ post.platform_post_id }}</span>
            </div>
            <div class="info-row" v-if="post.creator">
              <span class="info-label">Người tạo</span>
              <span class="info-value">{{ post.creator.name }}</span>
            </div>
          </div>
        </div>
      </div>
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
  props: { post: Object },
  methods: {
    retry() { router.post(`/social-posts/${this.post.id}/retry`, {}, { preserveScroll: true }) },
    syncAnalytics() { router.post(`/social-posts/${this.post.id}/sync-analytics`, {}, { preserveScroll: true }) },
    deletePost() {
      if (confirm('Bạn có chắc muốn xóa bài đăng này?')) {
        router.delete(`/social-posts/${this.post.id}`)
      }
    },
    formatNumber(n) {
      if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
      if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
      return n.toString()
    },
    getStatusColor(s) { return { draft: '#94a3b8', scheduled: '#f59e0b', posting: '#3b82f6', published: '#10b981', failed: '#ef4444' }[s] || '#94a3b8' },
    getStatusLabel(s) { return { draft: 'Nháp', scheduled: 'Lên lịch', posting: 'Đang đăng', published: 'Đã đăng', failed: 'Lỗi' }[s] || s },
    getPlatformIcon(p) { return { facebook: 'pi pi-facebook', instagram: 'pi pi-instagram', linkedin: 'pi pi-linkedin', twitter: 'pi pi-twitter' }[p] || 'pi pi-globe' },
    getPlatformLabel(p) { return { facebook: 'Facebook', instagram: 'Instagram', linkedin: 'LinkedIn', twitter: 'Twitter / X' }[p] || p },
    getPlatformGradient(p) {
      return { facebook: 'linear-gradient(135deg, #1877F2, #0d65d9)', instagram: 'linear-gradient(135deg, #E4405F, #F77737)', linkedin: 'linear-gradient(135deg, #0A66C2, #004182)', twitter: 'linear-gradient(135deg, #1DA1F2, #0d8ecf)' }[p] || '#6366f1'
    },
  },
}
</script>

<style scoped>
/* ===== Breadcrumb ===== */
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* ===== Hero ===== */
.post-hero { background: white; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.25rem; }
.hero-bar { height: 4px; }
.hero-content { display: flex; align-items: center; justify-content: space-between; padding: 1.15rem 1.5rem; }
.hero-left { display: flex; align-items: center; gap: 0.85rem; }
.hero-platform-icon {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem; box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
.hero-title { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; }
.hero-meta { display: flex; gap: 0.65rem; margin-top: 0.3rem; }
.meta-chip { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; color: #64748b; }
.meta-chip i { font-size: 0.6rem; color: #94a3b8; }
.meta-chip--link a { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.25rem; }
.hero-right {}
.status-tag { font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 8px; }
.status-tag--sm { font-size: 0.6rem; padding: 0.15rem 0.4rem; }
.status--draft { background: #f1f5f9; color: #64748b; }
.status--scheduled { background: #fffbeb; color: #d97706; }
.status--posting { background: #eff6ff; color: #3b82f6; }
.status--published { background: #ecfdf5; color: #059669; }
.status--failed { background: #fef2f2; color: #dc2626; }

/* ===== Layout ===== */
.detail-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.25rem; }
.detail-main { display: flex; flex-direction: column; gap: 1rem; }
.detail-sidebar { display: flex; flex-direction: column; gap: 1rem; }

/* ===== Section Card ===== */
.section-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
.section-header {
  display: flex; align-items: center; gap: 0.45rem;
  padding: 0.85rem 1.15rem; border-bottom: 1px solid #f8fafc;
}
.section-header i { color: #6366f1; font-size: 0.85rem; }
.section-header h3 { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
.sync-info { font-size: 0.62rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.sync-info i { font-size: 0.55rem; }

.content-body { padding: 1.15rem; }
.post-text { font-size: 0.85rem; color: #475569; line-height: 1.65; margin: 0; white-space: pre-wrap; }

/* Media */
.media-section { padding: 0 1.15rem 1.15rem; }
.media-header { display: flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: #94a3b8; margin-bottom: 0.5rem; }
.media-header i { font-size: 0.65rem; }
.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; }
.media-item { width: 100%; height: 100px; object-fit: cover; border-radius: 10px; }

/* Error */
.error-box {
  display: flex; gap: 0.65rem; margin: 0 1.15rem 1.15rem; padding: 0.85rem;
  border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca;
}
.error-box i { color: #ef4444; font-size: 0.88rem; margin-top: 0.1rem; }
.error-title { font-size: 0.72rem; font-weight: 600; color: #dc2626; }
.error-text { font-size: 0.78rem; color: #7f1d1d; margin: 0.2rem 0 0; }

/* Analytics */
.analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; }
.analytics-item {
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
  padding: 1.25rem; border-right: 1px solid #f8fafc;
}
.analytics-item:last-child { border-right: none; }
.analytics-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.analytics-icon--pink { background: #fdf2f8; color: #ec4899; }
.analytics-icon--blue { background: #eff6ff; color: #3b82f6; }
.analytics-icon--green { background: #ecfdf5; color: #10b981; }
.analytics-icon--purple { background: #eef2ff; color: #6366f1; }
.analytics-value { font-size: 1.15rem; font-weight: 700; color: #0f172a; }
.analytics-label { font-size: 0.65rem; color: #94a3b8; }

/* Timeline */
.timeline { padding: 1rem 1.15rem; }
.timeline-item { display: flex; gap: 0.75rem; padding-bottom: 1rem; position: relative; }
.timeline-item:not(:last-child)::before {
  content: ''; position: absolute; left: 5px; top: 14px; bottom: -2px;
  width: 2px; background: #e2e8f0;
}
.timeline-item--active::before { background: #10b981; }
.timeline-item--error::before { background: #ef4444; }
.timeline-dot {
  width: 12px; height: 12px; border-radius: 50%; background: #e2e8f0;
  flex-shrink: 0; margin-top: 2px;
}
.timeline-item--active .timeline-dot { background: #10b981; }
.timeline-item--error .timeline-dot { background: #ef4444; }
.timeline-body {}
.timeline-title { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; }
.timeline-date { font-size: 0.68rem; color: #94a3b8; }

/* ===== Sidebar ===== */
.sidebar-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
.sidebar-header { padding: 0.75rem 1rem; border-bottom: 1px solid #f8fafc; }
.sidebar-header h3 { font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.35rem; }
.sidebar-header i { color: #6366f1; font-size: 0.78rem; }

.action-list { display: flex; flex-direction: column; gap: 0.35rem; padding: 0.65rem; }
.action-btn { width: 100%; justify-content: flex-start !important; }
.action-divider { height: 1px; background: #f1f5f9; margin: 0.25rem 0; }

.info-list { padding: 0.65rem 1rem; }
.info-row { display: flex; justify-content: space-between; padding: 0.35rem 0; border-bottom: 1px solid #f8fafc; }
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 0.72rem; color: #94a3b8; }
.info-value { font-size: 0.72rem; font-weight: 600; color: #1e293b; }
.info-value--mono { font-family: monospace; font-size: 0.65rem; }

/* ===== Responsive ===== */
@media (max-width: 1024px) { .detail-layout { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
  .hero-content { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .analytics-grid { grid-template-columns: repeat(2, 1fr); }
  .analytics-item { border-bottom: 1px solid #f8fafc; }
}
</style>
