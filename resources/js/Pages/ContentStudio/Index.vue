<template>
  <div>
    <Head title="Content Studio" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-palette" style="color: #8b5cf6; margin-right: 0.5rem;" />
          Content Studio
        </h1>
        <p class="page-subtitle">AI tự động tạo bài viết + thumbnail → Đăng lên mạng xã hội</p>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-chip stat-chip--published">
        <i class="pi pi-check-circle" />
        <span class="stat-value">{{ postStats.published }}</span>
        <span class="stat-label">Đã đăng</span>
      </div>
      <div class="stat-chip stat-chip--scheduled">
        <i class="pi pi-clock" />
        <span class="stat-value">{{ postStats.scheduled }}</span>
        <span class="stat-label">Đã lên lịch</span>
      </div>
      <div class="stat-chip stat-chip--failed">
        <i class="pi pi-exclamation-triangle" />
        <span class="stat-value">{{ postStats.failed }}</span>
        <span class="stat-label">Lỗi</span>
      </div>
    </div>

    <!-- Main Layout -->
    <div class="studio-layout">
      <!-- Left: Generator Panel -->
      <div class="generator-panel">
        <!-- Step Indicator -->
        <div class="step-indicator">
          <div class="step" :class="{ active: currentStep >= 1, done: currentStep > 1 }">
            <div class="step-dot">1</div>
            <span>Cấu hình</span>
          </div>
          <div class="step-line" :class="{ active: currentStep >= 2 }" />
          <div class="step" :class="{ active: currentStep >= 2, done: currentStep > 2 }">
            <div class="step-dot">2</div>
            <span>AI tạo</span>
          </div>
          <div class="step-line" :class="{ active: currentStep >= 3 }" />
          <div class="step" :class="{ active: currentStep >= 3 }">
            <div class="step-dot">3</div>
            <span>Đăng bài</span>
          </div>
        </div>

        <!-- Step 1: Configuration -->
        <div v-if="currentStep === 1" class="step-content">
          <div class="config-card">
            <div class="config-header">
              <i class="pi pi-cog" />
              <h3>Cấu hình nội dung</h3>
            </div>

            <!-- Topic -->
            <div class="form-group">
              <label>Chủ đề / Prompt <span class="required">*</span></label>
              <textarea
                v-model="form.topic"
                rows="3"
                class="form-textarea"
                placeholder="VD: Xu hướng AI trong marketing 2026, Top 5 công cụ AI giúp tăng năng suất..."
              />
            </div>

            <!-- Content Type & Tone -->
            <div class="form-row">
              <div class="form-group">
                <label>Loại nội dung</label>
                <select v-model="form.content_type" class="form-select">
                  <option v-for="(label, key) in contentTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Phong cách</label>
                <select v-model="form.tone" class="form-select">
                  <option v-for="(label, key) in tones" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>

            <!-- Language -->
            <div class="form-row">
              <div class="form-group">
                <label>Ngôn ngữ</label>
                <div class="lang-toggle">
                  <button :class="{ active: form.language === 'vi' }" @click="form.language = 'vi'">
                    🇻🇳 Tiếng Việt
                  </button>
                  <button :class="{ active: form.language === 'en' }" @click="form.language = 'en'">
                    🇺🇸 English
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label>Kiểu thumbnail</label>
                <select v-model="form.thumbnail_style" class="form-select">
                  <option v-for="(label, key) in thumbnailStyles" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
            </div>

            <!-- Platforms Selection -->
            <div class="form-group">
              <label>Nền tảng đăng <span class="required">*</span></label>
              <div class="platform-grid">
                <button
                  v-for="(meta, key) in platforms"
                  :key="key"
                  class="platform-option"
                  :class="{ selected: form.platforms.includes(key) }"
                  @click="togglePlatform(key)"
                >
                  <i :class="meta.icon" :style="{ color: form.platforms.includes(key) ? 'white' : meta.color }" />
                  <span>{{ meta.label }}</span>
                  <i v-if="form.platforms.includes(key)" class="pi pi-check check-icon" />
                </button>
              </div>
            </div>

            <!-- Options -->
            <div class="form-row">
              <label class="toggle-label">
                <input type="checkbox" v-model="form.generate_thumbnail" />
                <span class="toggle-slider" />
                <span>Tạo thumbnail AI</span>
              </label>
              <label class="toggle-label">
                <input type="checkbox" v-model="form.hashtags" />
                <span class="toggle-slider" />
                <span>Tạo hashtags</span>
              </label>
            </div>

            <!-- Additional Instructions -->
            <div class="form-group">
              <label>Hướng dẫn bổ sung <span class="optional">(tuỳ chọn)</span></label>
              <textarea
                v-model="form.instructions"
                rows="2"
                class="form-textarea form-textarea--sm"
                placeholder="VD: Thêm emoji, nhấn mạnh sản phẩm X, tập trung vào đối tượng startup..."
              />
            </div>

            <!-- Generate Button -->
            <div class="generate-action">
              <Button
                label="✨ AI Tạo nội dung"
                icon="pi pi-sparkles"
                :loading="isGenerating"
                :disabled="!form.topic || form.platforms.length === 0"
                @click="generateContent"
                class="generate-btn"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Review & Edit Generated Content -->
        <div v-if="currentStep === 2" class="step-content">
          <div class="config-card">
            <div class="config-header">
              <i class="pi pi-eye" />
              <h3>Xem trước & chỉnh sửa</h3>
              <Button label="← Quay lại" text size="small" @click="currentStep = 1" />
            </div>

            <!-- Thumbnail Preview -->
            <div v-if="generatedData.thumbnail_url" class="thumbnail-preview">
              <img :src="generatedData.thumbnail_url" alt="Generated thumbnail" />
              <div class="thumbnail-overlay">
                <Button
                  icon="pi pi-refresh"
                  label="Tạo lại"
                  text
                  size="small"
                  @click="regenerateThumbnail"
                  :loading="isRegeneratingThumb"
                />
              </div>
            </div>

            <!-- Platform Content Tabs -->
            <div class="platform-tabs">
              <button
                v-for="platform in form.platforms"
                :key="platform"
                class="platform-tab"
                :class="{ active: activePreviewPlatform === platform }"
                @click="activePreviewPlatform = platform"
              >
                <i :class="platforms[platform]?.icon" :style="{ color: platforms[platform]?.color }" />
                {{ platforms[platform]?.label }}
              </button>
            </div>

            <!-- Content Editor -->
            <div v-if="generatedData.contents[activePreviewPlatform]" class="content-editor">
              <textarea
                v-model="generatedData.contents[activePreviewPlatform].content"
                rows="8"
                class="form-textarea"
              />
              <div class="char-count" :class="{ warning: getCharCount(activePreviewPlatform) > getCharLimit(activePreviewPlatform) }">
                {{ getCharCount(activePreviewPlatform) }} / {{ getCharLimit(activePreviewPlatform) }}
              </div>

              <!-- Hashtags -->
              <div v-if="generatedData.contents[activePreviewPlatform].hashtags?.length" class="hashtags-row">
                <span
                  v-for="(tag, idx) in generatedData.contents[activePreviewPlatform].hashtags"
                  :key="idx"
                  class="hashtag-chip"
                >
                  #{{ tag }}
                </span>
              </div>
            </div>

            <!-- AI Meta -->
            <div class="ai-meta">
              <span><i class="pi pi-cpu" /> {{ generatedData.ai_model }}</span>
              <span><i class="pi pi-bolt" /> {{ generatedData.tokens_used }} tokens</span>
            </div>

            <!-- Actions -->
            <div class="step-actions">
              <Button label="Lưu nháp" icon="pi pi-save" severity="secondary" outlined @click="saveAsDraft" :loading="isSaving" />
              <Button label="Tiếp: Chọn tài khoản →" icon="pi pi-arrow-right" iconPos="right" @click="currentStep = 3" />
            </div>
          </div>
        </div>

        <!-- Step 3: Select Accounts & Publish -->
        <div v-if="currentStep === 3" class="step-content">
          <div class="config-card">
            <div class="config-header">
              <i class="pi pi-send" />
              <h3>Đăng bài</h3>
              <Button label="← Quay lại" text size="small" @click="currentStep = 2" />
            </div>

            <!-- Select Social Accounts -->
            <div class="form-group">
              <label>Chọn tài khoản đăng</label>
              <div v-if="matchingAccounts.length" class="accounts-list">
                <label
                  v-for="account in matchingAccounts"
                  :key="account.id"
                  class="account-option"
                  :class="{ selected: selectedAccounts.includes(account.id), expired: account.is_token_expired }"
                >
                  <input type="checkbox" v-model="selectedAccounts" :value="account.id" :disabled="account.is_token_expired" />
                  <div class="account-icon" :style="{ background: platforms[account.platform]?.color }">
                    <i :class="platforms[account.platform]?.icon" />
                  </div>
                  <div class="account-info">
                    <span class="account-name">{{ account.name }}</span>
                    <span class="account-platform">@{{ account.username }} · {{ platforms[account.platform]?.label }}</span>
                  </div>
                  <span v-if="account.is_token_expired" class="token-expired">Hết hạn</span>
                </label>
              </div>
              <div v-else class="no-accounts">
                <i class="pi pi-exclamation-triangle" />
                <p>Chưa kết nối tài khoản nào phù hợp</p>
                <Link href="/social-accounts">
                  <Button label="Kết nối" icon="pi pi-link" size="small" />
                </Link>
              </div>
            </div>

            <!-- Schedule -->
            <div class="form-group">
              <label>Thời gian đăng</label>
              <div class="schedule-options">
                <button class="schedule-opt" :class="{ active: !scheduleMode }" @click="scheduleMode = false">
                  <i class="pi pi-bolt" /> Đăng ngay
                </button>
                <button class="schedule-opt" :class="{ active: scheduleMode }" @click="scheduleMode = true">
                  <i class="pi pi-clock" /> Lên lịch
                </button>
              </div>
              <input
                v-if="scheduleMode"
                v-model="scheduledAt"
                type="datetime-local"
                class="form-select"
                :min="minScheduleDate"
              />
            </div>

            <!-- Publish Button -->
            <div class="publish-action">
              <Button
                :label="scheduleMode ? '📅 Lên lịch đăng' : '🚀 Đăng bài ngay'"
                :icon="scheduleMode ? 'pi pi-clock' : 'pi pi-send'"
                :loading="isPublishing"
                :disabled="selectedAccounts.length === 0"
                @click="publishContent"
                class="publish-btn"
              />
            </div>

            <!-- Success State -->
            <div v-if="publishResult" class="publish-result">
              <div class="result-icon"><i class="pi pi-check-circle" /></div>
              <h4>{{ publishResult.message }}</h4>
              <Button label="Tạo bài mới" icon="pi pi-plus" text @click="resetAll" />
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Preview & Recent -->
      <div class="sidebar-panel">
        <!-- Real Social Preview -->
        <div v-if="currentStep === 2 && generatedData.contents[activePreviewPlatform]" class="preview-card">
          <!-- Preview Tabs -->
          <div class="preview-tabs">
            <button
              v-for="platform in form.platforms" :key="platform"
              class="preview-tab-btn" :class="{ active: previewPlatform === platform }"
              @click="previewPlatform = platform"
            >
              <i :class="platforms[platform]?.icon" />
            </button>
          </div>

          <!-- Facebook Preview -->
          <div v-if="previewPlatform === 'facebook'" class="fb-preview">
            <div class="fb-header">
              <div class="fb-avatar"><i class="pi pi-user" /></div>
              <div class="fb-author">
                <span class="fb-name">{{ previewAccountName }}</span>
                <span class="fb-time">Vừa xong · <i class="pi pi-globe" /></span>
              </div>
              <i class="pi pi-ellipsis-h fb-more" />
            </div>
            <p class="fb-text">{{ previewText }}</p>
            <div v-if="previewHashtags.length" class="fb-hashtags">
              <span v-for="(t, i) in previewHashtags" :key="i" class="fb-hash">#{{ t }}</span>
            </div>
            <img v-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="fb-image" />
            <div class="fb-stats">
              <span><span class="fb-emoji">👍❤️</span> 42</span>
              <span>5 bình luận · 2 chia sẻ</span>
            </div>
            <div class="fb-actions">
              <button><i class="pi pi-thumbs-up" /> Thích</button>
              <button><i class="pi pi-comment" /> Bình luận</button>
              <button><i class="pi pi-share-alt" /> Chia sẻ</button>
            </div>
          </div>

          <!-- Instagram Preview -->
          <div v-if="previewPlatform === 'instagram'" class="ig-preview">
            <div class="ig-header">
              <div class="ig-avatar-ring"><div class="ig-avatar"><i class="pi pi-user" /></div></div>
              <span class="ig-username">{{ previewUsername }}</span>
              <i class="pi pi-ellipsis-h ig-more" />
            </div>
            <img v-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="ig-image" />
            <div v-else class="ig-image-placeholder"><i class="pi pi-image" /></div>
            <div class="ig-actions">
              <div class="ig-actions-left">
                <i class="pi pi-heart" />
                <i class="pi pi-comment" />
                <i class="pi pi-send" />
              </div>
              <i class="pi pi-bookmark" />
            </div>
            <div class="ig-likes">128 lượt thích</div>
            <div class="ig-caption">
              <span class="ig-cap-user">{{ previewUsername }}</span>
              {{ previewTextShort(2200) }}
            </div>
            <div v-if="previewHashtags.length" class="ig-hash-row">
              <span v-for="(t, i) in previewHashtags" :key="i" class="ig-hash">#{{ t }}</span>
            </div>
            <div class="ig-time-ago">2 PHÚT TRƯỚC</div>
          </div>

          <!-- LinkedIn Preview -->
          <div v-if="previewPlatform === 'linkedin'" class="li-preview">
            <div class="li-header">
              <div class="li-avatar"><i class="pi pi-user" /></div>
              <div class="li-author">
                <span class="li-name">{{ previewAccountName }}</span>
                <span class="li-title">Digital Marketing · 1st</span>
                <span class="li-time">Vừa xong · <i class="pi pi-globe" /></span>
              </div>
              <i class="pi pi-ellipsis-h li-more" />
            </div>
            <p class="li-text">{{ previewTextShort(3000) }}</p>
            <div v-if="previewHashtags.length" class="li-hashtags">
              <span v-for="(t, i) in previewHashtags" :key="i" class="li-hash">#{{ t }}</span>
            </div>
            <img v-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="li-image" />
            <div class="li-stats">
              <span class="li-reactions">👍 💡 ❤️ 36</span>
              <span>4 bình luận · 1 chia sẻ lại</span>
            </div>
            <div class="li-actions">
              <button><i class="pi pi-thumbs-up" /> Thích</button>
              <button><i class="pi pi-comment" /> Bình luận</button>
              <button><i class="pi pi-replay" /> Chia sẻ</button>
              <button><i class="pi pi-send" /> Gửi</button>
            </div>
          </div>

          <!-- Twitter/X Preview -->
          <div v-if="previewPlatform === 'twitter'" class="tw-preview">
            <div class="tw-header">
              <div class="tw-avatar"><i class="pi pi-user" /></div>
              <div class="tw-author">
                <div class="tw-name-row">
                  <span class="tw-name">{{ previewAccountName }}</span>
                  <i class="pi pi-verified tw-verified" />
                  <span class="tw-handle">@{{ previewUsername }}</span>
                  <span class="tw-dot">·</span>
                  <span class="tw-time">vừa xong</span>
                </div>
              </div>
            </div>
            <p class="tw-text">{{ previewTextShort(280) }}</p>
            <div v-if="previewHashtags.length" class="tw-hashtags">
              <span v-for="(t, i) in previewHashtags" :key="i" class="tw-hash">#{{ t }}</span>
            </div>
            <img v-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="tw-image" />
            <div class="tw-actions">
              <button class="tw-act"><i class="pi pi-comment" /> <span>12</span></button>
              <button class="tw-act tw-retweet"><i class="pi pi-replay" /> <span>5</span></button>
              <button class="tw-act tw-like"><i class="pi pi-heart" /> <span>28</span></button>
              <button class="tw-act"><i class="pi pi-chart-bar" /> <span>1.2K</span></button>
              <button class="tw-act"><i class="pi pi-bookmark" /></button>
              <button class="tw-act"><i class="pi pi-upload" /></button>
            </div>
          </div>

          <div class="preview-footer">
            <span class="preview-label">📱 Xem trước {{ platforms[previewPlatform]?.label }}</span>
          </div>
        </div>

        <!-- Recent Content -->
        <div class="sidebar-card">
          <div class="sidebar-card-header">
            <h3><i class="pi pi-history" /> Gần đây</h3>
          </div>
          <div v-if="recentContent.length" class="recent-list">
            <div v-for="item in recentContent" :key="item.id" class="recent-item">
              <div class="recent-thumb" v-if="item.thumbnail">
                <img :src="item.thumbnail" />
              </div>
              <div class="recent-thumb recent-thumb--empty" v-else>
                <i class="pi pi-file" />
              </div>
              <div class="recent-info">
                <span class="recent-title">{{ item.title }}</span>
                <span class="recent-meta">
                  <i class="pi pi-clock" /> {{ item.created_at }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="recent-empty">
            <p>Chưa có nội dung nào</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: {
    recentContent: Array,
    socialAccounts: Array,
    postStats: Object,
    tones: Object,
    contentTypes: Object,
    thumbnailStyles: Object,
    platforms: Object,
  },
  data() {
    return {
      currentStep: 1,
      isGenerating: false,
      isSaving: false,
      isPublishing: false,
      isRegeneratingThumb: false,
      activePreviewPlatform: '',
      previewPlatform: '',
      selectedAccounts: [],
      scheduleMode: false,
      scheduledAt: '',
      publishResult: null,
      form: {
        topic: '',
        tone: 'professional',
        language: 'vi',
        platforms: ['facebook'],
        content_type: 'post',
        instructions: '',
        generate_thumbnail: true,
        thumbnail_style: 'modern',
        hashtags: true,
      },
      generatedData: {
        contents: {},
        thumbnail_url: null,
        ai_model: '',
        tokens_used: 0,
      },
    }
  },
  computed: {
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    matchingAccounts() {
      return this.socialAccounts.filter(a => this.form.platforms.includes(a.platform))
    },
    minScheduleDate() {
      const d = new Date()
      d.setMinutes(d.getMinutes() + 10)
      return d.toISOString().slice(0, 16)
    },
    previewAccountName() {
      const acct = this.socialAccounts.find(a => a.platform === this.previewPlatform)
      return acct?.name || 'Tài khoản của bạn'
    },
    previewUsername() {
      const acct = this.socialAccounts.find(a => a.platform === this.previewPlatform)
      return acct?.username || 'youraccount'
    },
    previewText() {
      return this.generatedData.contents[this.previewPlatform]?.content || ''
    },
    previewHashtags() {
      return this.generatedData.contents[this.previewPlatform]?.hashtags || []
    },
  },
  methods: {
    togglePlatform(platform) {
      const idx = this.form.platforms.indexOf(platform)
      if (idx >= 0) {
        this.form.platforms.splice(idx, 1)
      } else {
        this.form.platforms.push(platform)
      }
    },

    async generateContent() {
      this.isGenerating = true
      try {
        const res = await fetch('/content-studio/generate', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(this.form),
        })
        const data = await res.json()
        if (data.success) {
          this.generatedData = data.data
          this.activePreviewPlatform = this.form.platforms[0]
          this.previewPlatform = this.form.platforms[0]
          this.currentStep = 2
        } else {
          alert(data.message || 'Lỗi tạo nội dung')
        }
      } catch (e) {
        alert('Lỗi kết nối: ' + e.message)
      } finally {
        this.isGenerating = false
      }
    },

    async saveAsDraft() {
      this.isSaving = true
      try {
        const res = await fetch('/content-studio/save', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ...this.generatedData,
            topic: this.form.topic,
            tone: this.form.tone,
          }),
        })
        const data = await res.json()
        if (data.success) {
          alert('Đã lưu nháp thành công!')
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.isSaving = false
      }
    },

    async publishContent() {
      this.isPublishing = true
      try {
        // First save content
        const saveRes = await fetch('/content-studio/save', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ...this.generatedData,
            topic: this.form.topic,
            tone: this.form.tone,
          }),
        })
        const saveData = await saveRes.json()

        if (!saveData.success) {
          alert('Lỗi lưu nội dung')
          return
        }

        // Then publish
        const itemIds = Object.values(saveData.items).map(i => i.id)
        const res = await fetch('/content-studio/publish', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            content_item_ids: itemIds,
            social_account_ids: this.selectedAccounts,
            scheduled_at: this.scheduleMode ? this.scheduledAt : null,
          }),
        })
        const data = await res.json()
        if (data.success) {
          this.publishResult = data
        } else {
          alert(data.message || 'Lỗi đăng bài')
        }
      } catch (e) {
        alert('Lỗi: ' + e.message)
      } finally {
        this.isPublishing = false
      }
    },

    async regenerateThumbnail() {
      this.isRegeneratingThumb = true
      try {
        const res = await fetch('/content-studio/thumbnail', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ topic: this.form.topic, style: this.form.thumbnail_style }),
        })
        const data = await res.json()
        if (data.success) {
          this.generatedData.thumbnail_url = data.thumbnail_url
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.isRegeneratingThumb = false
      }
    },

    getCharCount(platform) {
      return (this.generatedData.contents[platform]?.content || '').length
    },
    getCharLimit(platform) {
      return { twitter: 280, linkedin: 3000, facebook: 2000, instagram: 2200 }[platform] || 2000
    },
    previewTextShort(limit) {
      const text = this.previewText
      if (text.length <= limit) return text
      return text.substring(0, limit - 3) + '...'
    },

    resetAll() {
      this.currentStep = 1
      this.generatedData = { contents: {}, thumbnail_url: null, ai_model: '', tokens_used: 0 }
      this.selectedAccounts = []
      this.previewPlatform = ''
      this.publishResult = null
      this.form.topic = ''
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Stats Row ===== */
.stats-row { display: flex; gap: 0.65rem; margin-bottom: 1rem; }
.stat-chip {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.75rem; border-radius: 10px; font-size: 0.75rem;
}
.stat-chip i { font-size: 0.7rem; }
.stat-value { font-weight: 700; }
.stat-label { font-weight: 400; }
.stat-chip--published { background: #ecfdf5; color: #10b981; }
.stat-chip--scheduled { background: #eff6ff; color: #3b82f6; }
.stat-chip--failed { background: #fef2f2; color: #ef4444; }

/* ===== Layout ===== */
.studio-layout { display: grid; grid-template-columns: 1fr 360px; gap: 1.25rem; }

/* ===== Step Indicator ===== */
.step-indicator {
  display: flex; align-items: center; justify-content: center;
  padding: 1rem; margin-bottom: 1rem; gap: 0;
}
.step {
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
}
.step-dot {
  width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: #94a3b8;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700; transition: all 0.3s;
}
.step.active .step-dot { background: #6366f1; color: white; }
.step.done .step-dot { background: #10b981; color: white; }
.step span { font-size: 0.68rem; color: #64748b; font-weight: 500; }
.step.active span { color: #6366f1; font-weight: 600; }
.step-line { width: 48px; height: 3px; background: #e2e8f0; margin: 0 0.5rem; margin-bottom: 1.2rem; }
.step-line.active { background: #10b981; }

/* ===== Generator Panel ===== */
.generator-panel { min-width: 0; }
.config-card {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); padding: 1.25rem;
}
.config-header {
  display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.15rem;
  padding-bottom: 0.75rem; border-bottom: 1px solid #f8fafc;
}
.config-header i { color: #6366f1; font-size: 0.95rem; }
.config-header h3 { font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }

/* ===== Form Elements ===== */
.form-group { margin-bottom: 0.85rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem; }
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; font-size: 0.68rem; }

.form-textarea {
  width: 100%; padding: 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.82rem; font-family: inherit; color: #1e293b; outline: none;
  resize: vertical; line-height: 1.6; transition: border-color 0.2s;
}
.form-textarea:focus { border-color: #6366f1; }
.form-textarea--sm { font-size: 0.78rem; }
.form-textarea::placeholder { color: #94a3b8; }

.form-select {
  width: 100%; padding: 0.55rem 0.65rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; font-size: 0.82rem; font-family: inherit;
  color: #1e293b; outline: none; background: white; transition: border-color 0.2s;
}
.form-select:focus { border-color: #6366f1; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

/* Language Toggle */
.lang-toggle { display: flex; gap: 0; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.lang-toggle button {
  flex: 1; padding: 0.5rem; border: none; background: white;
  font-size: 0.78rem; cursor: pointer; transition: all 0.2s;
}
.lang-toggle button.active { background: #6366f1; color: white; font-weight: 600; }

/* Platform Grid */
.platform-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
.platform-option {
  display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem;
  border: 1.5px solid #e2e8f0; border-radius: 10px; background: white;
  cursor: pointer; transition: all 0.2s; font-size: 0.78rem; color: #475569;
  position: relative;
}
.platform-option:hover { border-color: #6366f1; }
.platform-option.selected {
  border-color: #6366f1; background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}
.platform-option i { font-size: 1rem; }
.check-icon { position: absolute; right: 0.5rem; font-size: 0.65rem; }

/* Toggle Labels */
.toggle-label {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.78rem; color: #475569; cursor: pointer;
}
.toggle-label input { display: none; }
.toggle-slider {
  width: 36px; height: 20px; border-radius: 10px; background: #e2e8f0;
  position: relative; transition: background 0.2s;
}
.toggle-slider::after {
  content: ''; width: 16px; height: 16px; border-radius: 50%;
  background: white; position: absolute; top: 2px; left: 2px;
  transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.toggle-label input:checked + .toggle-slider { background: #6366f1; }
.toggle-label input:checked + .toggle-slider::after { transform: translateX(16px); }

/* Generate Button */
.generate-action { display: flex; justify-content: center; padding-top: 0.5rem; }
.generate-btn {
  font-size: 0.88rem !important; padding: 0.65rem 1.5rem !important;
  border-radius: 12px !important;
  background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
  border: none !important;
}

/* ===== Step 2: Preview ===== */
.thumbnail-preview {
  position: relative; border-radius: 12px; overflow: hidden; margin-bottom: 1rem;
}
.thumbnail-preview img { width: 100%; height: 200px; object-fit: cover; }
.thumbnail-overlay {
  position: absolute; bottom: 0.5rem; right: 0.5rem;
}

.platform-tabs { display: flex; gap: 0.25rem; margin-bottom: 0.75rem; }
.platform-tab {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.45rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
  background: white; font-size: 0.72rem; cursor: pointer; transition: all 0.2s;
}
.platform-tab.active { border-color: #6366f1; background: #eef2ff; font-weight: 600; }
.platform-tab i { font-size: 0.78rem; }

.content-editor { margin-bottom: 0.75rem; }
.char-count { font-size: 0.65rem; color: #94a3b8; text-align: right; margin-top: 0.25rem; }
.char-count.warning { color: #ef4444; font-weight: 600; }

.hashtags-row { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.5rem; }
.hashtag-chip {
  font-size: 0.68rem; color: #6366f1; background: #eef2ff;
  padding: 0.15rem 0.45rem; border-radius: 5px;
}

.ai-meta {
  display: flex; gap: 0.75rem; font-size: 0.65rem; color: #94a3b8;
  padding: 0.5rem 0; border-top: 1px solid #f8fafc; margin-bottom: 0.75rem;
}
.ai-meta span { display: flex; align-items: center; gap: 0.25rem; }
.ai-meta i { font-size: 0.6rem; }

.step-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }

/* ===== Step 3: Publish ===== */
.accounts-list { display: flex; flex-direction: column; gap: 0.4rem; }
.account-option {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.65rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  cursor: pointer; transition: all 0.2s;
}
.account-option:hover { border-color: #6366f1; }
.account-option.selected { border-color: #6366f1; background: #eef2ff; }
.account-option.expired { opacity: 0.5; }
.account-option input { display: none; }
.account-icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.85rem;
}
.account-info { flex: 1; }
.account-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; }
.account-platform { font-size: 0.68rem; color: #94a3b8; }
.token-expired { font-size: 0.62rem; color: #ef4444; font-weight: 600; }

.no-accounts {
  display: flex; flex-direction: column; align-items: center;
  padding: 1.5rem; color: #94a3b8; text-align: center;
}
.no-accounts i { font-size: 1.5rem; color: #f59e0b; margin-bottom: 0.5rem; }
.no-accounts p { font-size: 0.82rem; margin: 0 0 0.65rem; }

.schedule-options { display: flex; gap: 0.35rem; margin-bottom: 0.5rem; }
.schedule-opt {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.35rem;
  padding: 0.55rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: white; font-size: 0.78rem; cursor: pointer; transition: all 0.2s;
}
.schedule-opt.active { border-color: #6366f1; background: #eef2ff; color: #6366f1; font-weight: 600; }

.publish-action { display: flex; justify-content: center; padding-top: 0.75rem; }
.publish-btn {
  font-size: 0.88rem !important; padding: 0.65rem 1.5rem !important;
  border-radius: 12px !important;
}

.publish-result {
  display: flex; flex-direction: column; align-items: center;
  padding: 1.5rem; text-align: center;
}
.result-icon { font-size: 2.5rem; color: #10b981; margin-bottom: 0.5rem; }
.result-icon i { font-size: 2.5rem; }
.publish-result h4 { font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0 0 0.75rem; }

/* ===== Sidebar ===== */
.sidebar-panel { display: flex; flex-direction: column; gap: 1rem; }

/* ===== Preview Card ===== */
.preview-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
}
.preview-tabs {
  display: flex; gap: 0; border-bottom: 1px solid #f1f5f9;
}
.preview-tab-btn {
  flex: 1; padding: 0.55rem; border: none; background: #f8fafc;
  cursor: pointer; font-size: 0.85rem; color: #94a3b8; transition: all 0.15s;
  display: flex; align-items: center; justify-content: center;
}
.preview-tab-btn:hover { background: #f1f5f9; }
.preview-tab-btn.active { background: white; color: #1e293b; box-shadow: inset 0 -2px 0 #6366f1; }
.preview-footer { padding: 0.45rem 0.75rem; background: #f8fafc; border-top: 1px solid #f1f5f9; text-align: center; }
.preview-label { font-size: 0.62rem; color: #94a3b8; }

/* ===== FACEBOOK Preview ===== */
.fb-preview { padding: 0; font-family: Helvetica, Arial, sans-serif; }
.fb-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; }
.fb-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1877f2, #42a5f5); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.85rem; }
.fb-author { flex: 1; }
.fb-name { font-size: 0.82rem; font-weight: 700; color: #050505; display: block; }
.fb-time { font-size: 0.65rem; color: #65676b; display: flex; align-items: center; gap: 0.2rem; }
.fb-time i { font-size: 0.55rem; }
.fb-more { color: #65676b; font-size: 0.85rem; cursor: pointer; }
.fb-text { font-size: 0.82rem; color: #050505; line-height: 1.5; padding: 0 0.75rem 0.5rem; white-space: pre-wrap; word-break: break-word; margin: 0; }
.fb-hashtags { padding: 0 0.75rem 0.5rem; display: flex; flex-wrap: wrap; gap: 0.2rem; }
.fb-hash { font-size: 0.78rem; color: #1877f2; cursor: pointer; }
.fb-image { width: 100%; }
.fb-stats { display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; font-size: 0.72rem; color: #65676b; border-bottom: 1px solid #ced0d4; }
.fb-emoji { font-size: 0.82rem; }
.fb-actions { display: flex; border-top: 0; padding: 0.25rem; }
.fb-actions button { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem; border: none; background: none; font-size: 0.78rem; font-weight: 600; color: #65676b; cursor: pointer; border-radius: 4px; font-family: inherit; }
.fb-actions button:hover { background: #f2f2f2; }
.fb-actions button i { font-size: 0.82rem; }

/* ===== INSTAGRAM Preview ===== */
.ig-preview { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
.ig-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; }
.ig-avatar-ring { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); padding: 2px; }
.ig-avatar { width: 100%; height: 100%; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #262626; }
.ig-username { font-size: 0.78rem; font-weight: 600; color: #262626; flex: 1; }
.ig-more { color: #262626; font-size: 0.82rem; cursor: pointer; }
.ig-image { width: 100%; aspect-ratio: 1; object-fit: cover; }
.ig-image-placeholder { width: 100%; aspect-ratio: 1; background: #fafafa; display: flex; align-items: center; justify-content: center; color: #dbdbdb; font-size: 2rem; }
.ig-actions { display: flex; justify-content: space-between; padding: 0.55rem 0.75rem; }
.ig-actions-left { display: flex; gap: 0.85rem; }
.ig-actions i { font-size: 1.1rem; color: #262626; cursor: pointer; }
.ig-likes { font-size: 0.78rem; font-weight: 600; color: #262626; padding: 0 0.75rem 0.2rem; }
.ig-caption { font-size: 0.78rem; color: #262626; line-height: 1.4; padding: 0 0.75rem 0.3rem; white-space: pre-wrap; word-break: break-word; }
.ig-cap-user { font-weight: 600; margin-right: 0.25rem; }
.ig-hash-row { padding: 0 0.75rem 0.3rem; display: flex; flex-wrap: wrap; gap: 0.15rem; }
.ig-hash { font-size: 0.78rem; color: #00376b; }
.ig-time-ago { font-size: 0.6rem; color: #8e8e8e; text-transform: uppercase; padding: 0 0.75rem 0.65rem; letter-spacing: 0.02em; }

/* ===== LINKEDIN Preview ===== */
.li-preview { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
.li-header { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem; }
.li-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #0077b5, #00a0dc); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.92rem; flex-shrink: 0; }
.li-author { flex: 1; }
.li-name { font-size: 0.82rem; font-weight: 700; color: rgba(0,0,0,0.9); display: block; }
.li-title { font-size: 0.68rem; color: rgba(0,0,0,0.6); display: block; }
.li-time { font-size: 0.62rem; color: rgba(0,0,0,0.6); display: flex; align-items: center; gap: 0.2rem; }
.li-time i { font-size: 0.5rem; }
.li-more { color: rgba(0,0,0,0.6); font-size: 0.82rem; cursor: pointer; }
.li-text { font-size: 0.82rem; color: rgba(0,0,0,0.9); line-height: 1.5; padding: 0 0.75rem 0.5rem; white-space: pre-wrap; word-break: break-word; margin: 0; }
.li-hashtags { padding: 0 0.75rem 0.5rem; display: flex; flex-wrap: wrap; gap: 0.2rem; }
.li-hash { font-size: 0.78rem; color: #0a66c2; cursor: pointer; font-weight: 600; }
.li-image { width: 100%; }
.li-stats { display: flex; justify-content: space-between; padding: 0.5rem 0.75rem; font-size: 0.68rem; color: rgba(0,0,0,0.6); border-bottom: 1px solid #e0e0e0; }
.li-reactions { display: flex; align-items: center; gap: 0.2rem; }
.li-actions { display: flex; padding: 0.15rem; }
.li-actions button { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.1rem; padding: 0.5rem 0.25rem; border: none; background: none; font-size: 0.65rem; font-weight: 600; color: rgba(0,0,0,0.6); cursor: pointer; border-radius: 4px; font-family: inherit; }
.li-actions button:hover { background: rgba(0,0,0,0.08); }
.li-actions button i { font-size: 0.82rem; }

/* ===== TWITTER/X Preview ===== */
.tw-preview { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 0.75rem; }
.tw-header { display: flex; gap: 0.55rem; margin-bottom: 0.2rem; }
.tw-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1d9bf0; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.85rem; flex-shrink: 0; }
.tw-author { flex: 1; }
.tw-name-row { display: flex; align-items: center; gap: 0.2rem; flex-wrap: wrap; }
.tw-name { font-size: 0.82rem; font-weight: 700; color: #0f1419; }
.tw-verified { color: #1d9bf0; font-size: 0.72rem; }
.tw-handle { font-size: 0.78rem; color: #536471; }
.tw-dot { color: #536471; }
.tw-time { font-size: 0.78rem; color: #536471; }
.tw-text { font-size: 0.82rem; color: #0f1419; line-height: 1.45; margin: 0.2rem 0 0.5rem 0; white-space: pre-wrap; word-break: break-word; padding-left: 3rem; }
.tw-hashtags { padding-left: 3rem; margin-bottom: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.2rem; }
.tw-hash { font-size: 0.82rem; color: #1d9bf0; cursor: pointer; }
.tw-image { width: calc(100% - 3rem); border-radius: 16px; margin-left: 3rem; margin-bottom: 0.5rem; }
.tw-actions { display: flex; justify-content: space-between; padding-left: 3rem; padding-right: 1rem; max-width: 340px; }
.tw-act { display: flex; align-items: center; gap: 0.25rem; border: none; background: none; color: #536471; font-size: 0.72rem; cursor: pointer; padding: 0.3rem; border-radius: 50%; transition: all 0.15s; font-family: inherit; }
.tw-act:hover { color: #1d9bf0; }
.tw-act i { font-size: 0.78rem; }
.tw-retweet:hover { color: #00ba7c; }
.tw-like:hover { color: #f91880; }

/* ===== Sidebar Card ===== */
.sidebar-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
}
.sidebar-card-header {
  display: flex; align-items: center; padding: 0.75rem 1rem;
  border-bottom: 1px solid #f8fafc;
}
.sidebar-card-header h3 {
  font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0;
  display: flex; align-items: center; gap: 0.35rem;
}
.sidebar-card-header i { color: #6366f1; font-size: 0.78rem; }

.recent-list { max-height: 400px; overflow-y: auto; }
.recent-item {
  display: flex; gap: 0.6rem; padding: 0.6rem 1rem;
  border-bottom: 1px solid #f8fafc; transition: background 0.2s;
}
.recent-item:hover { background: #f8fafc; }
.recent-thumb {
  width: 42px; height: 42px; border-radius: 8px; overflow: hidden; flex-shrink: 0;
}
.recent-thumb img { width: 100%; height: 100%; object-fit: cover; }
.recent-thumb--empty {
  background: #f1f5f9; display: flex; align-items: center; justify-content: center;
  color: #94a3b8; font-size: 0.88rem;
}
.recent-info { min-width: 0; }
.recent-title {
  font-size: 0.75rem; font-weight: 600; color: #1e293b; display: block;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.recent-meta {
  font-size: 0.62rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem;
}
.recent-meta i { font-size: 0.55rem; }
.recent-empty { padding: 1.25rem; text-align: center; color: #cbd5e1; font-size: 0.78rem; }

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .studio-layout { grid-template-columns: 1fr; }
  .sidebar-panel { order: -1; }
}
@media (max-width: 768px) {
  .platform-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .step-indicator { flex-wrap: wrap; }
  .tw-text, .tw-hashtags, .tw-image, .tw-actions { padding-left: 0; margin-left: 0; }
}
</style>
