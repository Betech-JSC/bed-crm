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
        <p class="page-subtitle">AI tạo bài viết cho Social Media & Website SEO</p>
      </div>
      <!-- Mode Switcher -->
      <div class="mode-switcher">
        <button class="mode-btn" :class="{ active: studioMode === 'social' }" @click="studioMode = 'social'">
          <i class="pi pi-share-alt" /> Social Media
        </button>
        <button class="mode-btn" :class="{ active: studioMode === 'seo' }" @click="studioMode = 'seo'">
          <i class="pi pi-globe" /> SEO Blog
        </button>
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

    <!-- ══════════ SOCIAL MEDIA MODE ══════════ -->
    <div v-if="studioMode === 'social'">
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

            <!-- ═══ MULTI-IMAGE GENERATOR ═══ -->
            <div class="social-images-section">
              <div class="section-header-row">
                <h4 class="section-label"><i class="pi pi-images" /> Ảnh đính kèm ({{ socialImages.length }}/10)</h4>
                <span class="img-hint">Tạo nhiều ảnh bằng AI hoặc nhập prompt riêng cho mỗi ảnh</span>
              </div>

              <!-- Quick Generate -->
              <div class="img-gen-toolbar">
                <input
                  v-model="socialImgPrompt"
                  type="text"
                  class="form-select img-prompt-input"
                  :placeholder="form.language === 'vi' ? 'Mô tả ảnh cần tạo (VD: sản phẩm trên nền trắng)...' : 'Describe the image...'"
                  @keyup.enter="generateSocialImage"
                />
                <select v-model="socialImgStyle" class="form-select img-style-select">
                  <option value="modern">Modern</option>
                  <option value="vibrant">Vibrant</option>
                  <option value="minimal">Minimal</option>
                  <option value="corporate">Corporate</option>
                  <option value="creative">Creative</option>
                  <option value="photo">Realistic</option>
                  <option value="flat">Flat Design</option>
                  <option value="gradient">Gradient</option>
                </select>
                <select v-model="socialImgRatio" class="form-select img-ratio-select">
                  <option value="1:1">1:1 Vuông</option>
                  <option value="4:5">4:5 IG Feed</option>
                  <option value="16:9">16:9 Ngang</option>
                  <option value="9:16">9:16 Story</option>
                </select>
                <Button
                  label="Tạo ảnh"
                  icon="pi pi-sparkles"
                  size="small"
                  @click="generateSocialImage"
                  :loading="isGenSocialImg"
                  :disabled="isGenSocialImg || socialImages.length >= 10"
                  class="img-gen-btn-sm"
                />
              </div>

              <!-- Batch quick-generate -->
              <div class="batch-row">
                <button
                  type="button"
                  class="batch-btn"
                  @click="batchGenerateImages(3)"
                  :disabled="isGenSocialImg || socialImages.length >= 8"
                >
                  <i class="pi pi-images" /> Tạo nhanh 3 ảnh
                </button>
                <button
                  type="button"
                  class="batch-btn"
                  @click="batchGenerateImages(5)"
                  :disabled="isGenSocialImg || socialImages.length >= 6"
                >
                  <i class="pi pi-th-large" /> Tạo nhanh 5 ảnh
                </button>
                <button
                  v-if="socialImages.length"
                  type="button"
                  class="batch-btn batch-btn--danger"
                  @click="socialImages = []"
                >
                  <i class="pi pi-trash" /> Xóa tất cả
                </button>
              </div>

              <!-- Image Gallery Grid -->
              <div v-if="socialImages.length" class="social-img-gallery">
                <div
                  v-for="(img, idx) in socialImages" :key="idx"
                  class="social-img-card"
                  :class="{ generating: img.loading }"
                >
                  <!-- Loading state -->
                  <div v-if="img.loading" class="img-loading-state">
                    <i class="pi pi-spin pi-spinner" />
                    <span>Đang tạo...</span>
                  </div>
                  <!-- Image -->
                  <template v-else>
                    <img :src="img.url" :alt="img.prompt || 'Social image'" class="social-img" />
                    <div class="img-overlay">
                      <div class="img-overlay-actions">
                        <button type="button" class="img-act-btn" title="Tạo lại" @click="regenerateSingleImage(idx)">
                          <i class="pi pi-refresh" />
                        </button>
                        <button type="button" class="img-act-btn" title="Copy URL" @click="copySocialImgUrl(img.url)">
                          <i class="pi pi-copy" />
                        </button>
                        <button type="button" class="img-act-btn img-act-btn--danger" title="Xóa" @click="removeSocialImage(idx)">
                          <i class="pi pi-trash" />
                        </button>
                      </div>
                    </div>
                    <div class="img-card-info">
                      <span class="img-idx">{{ idx + 1 }}</span>
                      <span class="img-card-style">{{ img.style || 'modern' }}</span>
                    </div>
                  </template>
                </div>

                <!-- Add More Card -->
                <button
                  v-if="socialImages.length < 10"
                  type="button"
                  class="social-img-card add-card"
                  @click="generateSocialImage"
                  :disabled="isGenSocialImg"
                >
                  <i class="pi pi-plus" />
                  <span>Thêm ảnh</span>
                </button>
              </div>

              <!-- Empty state -->
              <div v-else class="social-img-empty">
                <i class="pi pi-image" />
                <p>Nhập mô tả và nhấn "Tạo ảnh" để tạo ảnh đính kèm bài social</p>
                <p class="img-empty-hint">Bạn có thể tạo tối đa 10 ảnh cho mỗi bài đăng</p>
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
            <!-- Multi-image carousel or single thumbnail -->
            <div v-if="previewImages.length > 1" class="fb-carousel">
              <div class="carousel-track" :style="{ transform: `translateX(-${fbCarouselIdx * 100}%)` }">
                <img v-for="(img, i) in previewImages" :key="i" :src="img" class="fb-image" />
              </div>
              <div class="carousel-dots">
                <span v-for="(_, i) in previewImages" :key="i" class="carousel-dot" :class="{ active: fbCarouselIdx === i }" @click="fbCarouselIdx = i" />
              </div>
              <button v-if="fbCarouselIdx > 0" class="carousel-nav carousel-nav--prev" @click="fbCarouselIdx--"><i class="pi pi-chevron-left" /></button>
              <button v-if="fbCarouselIdx < previewImages.length - 1" class="carousel-nav carousel-nav--next" @click="fbCarouselIdx++"><i class="pi pi-chevron-right" /></button>
            </div>
            <img v-else-if="previewImages.length === 1" :src="previewImages[0]" class="fb-image" />
            <img v-else-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="fb-image" />
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
            <!-- Multi-image carousel or single -->
            <div v-if="previewImages.length > 1" class="ig-carousel">
              <div class="carousel-track" :style="{ transform: `translateX(-${igCarouselIdx * 100}%)` }">
                <img v-for="(img, i) in previewImages" :key="i" :src="img" class="ig-image" />
              </div>
              <div class="carousel-dots">
                <span v-for="(_, i) in previewImages" :key="i" class="carousel-dot" :class="{ active: igCarouselIdx === i }" @click="igCarouselIdx = i" />
              </div>
              <button v-if="igCarouselIdx > 0" class="carousel-nav carousel-nav--prev" @click="igCarouselIdx--"><i class="pi pi-chevron-left" /></button>
              <button v-if="igCarouselIdx < previewImages.length - 1" class="carousel-nav carousel-nav--next" @click="igCarouselIdx++"><i class="pi pi-chevron-right" /></button>
            </div>
            <img v-else-if="previewImages.length === 1" :src="previewImages[0]" class="ig-image" />
            <img v-else-if="generatedData.thumbnail_url" :src="generatedData.thumbnail_url" class="ig-image" />
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
    </div><!-- end social mode -->

    <!-- ══════════ SEO BLOG MODE ══════════ -->
    <div v-if="studioMode === 'seo'">
      <div class="seo-layout">
        <!-- Left: SEO Generator -->
        <div class="seo-generator">
          <!-- Step 1: Config -->
          <div v-if="seoStep === 1" class="config-card">
            <div class="config-header">
              <i class="pi pi-globe" />
              <h3>Tạo bài viết SEO</h3>
            </div>

            <div class="form-group">
              <label>Chủ đề bài viết <span class="required">*</span></label>
              <textarea v-model="seoForm.topic" rows="3" class="form-textarea" placeholder="VD: Hướng dẫn SEO On-Page cho website WordPress 2026..." />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Focus Keyword</label>
                <input v-model="seoForm.focus_keyword" class="form-select" placeholder="VD: seo on-page" />
              </div>
              <div class="form-group">
                <label>Độ dài bài viết</label>
                <select v-model="seoForm.article_length" class="form-select">
                  <option value="short">Ngắn (800-1200 từ)</option>
                  <option value="medium">Trung bình (1500-2500 từ)</option>
                  <option value="long">Dài (3000-5000 từ)</option>
                  <option value="pillar">Pillar (5000-8000 từ)</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Phong cách</label>
                <select v-model="seoForm.tone" class="form-select">
                  <option value="professional">Chuyên nghiệp</option>
                  <option value="casual">Thân thiện</option>
                  <option value="educational">Giáo dục</option>
                  <option value="storytelling">Kể chuyện</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ngôn ngữ</label>
                <div class="lang-toggle">
                  <button :class="{ active: seoForm.language === 'vi' }" @click="seoForm.language = 'vi'">🇻🇳 Tiếng Việt</button>
                  <button :class="{ active: seoForm.language === 'en' }" @click="seoForm.language = 'en'">🇺🇸 English</button>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kiểu thumbnail</label>
                <select v-model="seoForm.thumbnail_style" class="form-select">
                  <option v-for="(label, key) in thumbnailStyles" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <label class="toggle-label">
                <input type="checkbox" v-model="seoForm.generate_thumbnail" />
                <span class="toggle-slider" />
                <span>Tạo thumbnail</span>
              </label>
            </div>

            <div class="form-group">
              <label>Hướng dẫn bổ sung <span class="optional">(tuỳ chọn)</span></label>
              <textarea v-model="seoForm.instructions" rows="2" class="form-textarea form-textarea--sm" placeholder="VD: Thêm case study cụ thể, nhấn mạnh E-E-A-T..." />
            </div>

            <div class="generate-action">
              <Button label="📝 AI Viết bài SEO" icon="pi pi-sparkles" :loading="isSeoGenerating" :disabled="!seoForm.topic" @click="generateSeoArticle" class="generate-btn seo-gen-btn" />
            </div>
          </div>

          <!-- Step 2: Edit Article -->
          <div v-if="seoStep === 2" class="config-card">
            <div class="config-header">
              <i class="pi pi-file-edit" />
              <h3>Chỉnh sửa bài viết</h3>
              <Button label="← Quay lại" text size="small" @click="seoStep = 1" />
            </div>

            <!-- SEO Meta Fields -->
            <div class="seo-meta-section">
              <h4 class="seo-section-title"><i class="pi pi-search" /> SEO Meta</h4>
              <div class="form-group">
                <label>Meta Title <span class="char-hint" :class="{ 'char-warn': (seoArticle.meta_title||'').length > 60 }">{{ (seoArticle.meta_title||'').length }}/60</span></label>
                <input v-model="seoArticle.meta_title" class="form-select" />
              </div>
              <div class="form-group">
                <label>Meta Description <span class="char-hint" :class="{ 'char-warn': (seoArticle.meta_description||'').length > 160 }">{{ (seoArticle.meta_description||'').length }}/160</span></label>
                <textarea v-model="seoArticle.meta_description" rows="2" class="form-textarea form-textarea--sm" />
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>URL Slug</label>
                  <div class="slug-input"><span class="slug-prefix">yoursite.com/</span><input v-model="seoArticle.slug" class="form-select slug-field" /></div>
                </div>
                <div class="form-group">
                  <label>Focus Keyword</label>
                  <input v-model="seoArticle.focus_keyword" class="form-select" />
                </div>
              </div>
            </div>

            <!-- Keywords -->
            <div v-if="seoArticle.secondary_keywords?.length" class="keywords-bar">
              <span class="kw-label">Keywords:</span>
              <span v-for="(kw, i) in seoArticle.secondary_keywords" :key="i" class="kw-chip">{{ kw }}</span>
            </div>

            <!-- Thumbnail -->
            <div v-if="seoData.thumbnail_url" class="thumbnail-preview">
              <img :src="seoData.thumbnail_url" alt="Generated thumbnail" />
              <div class="thumbnail-overlay">
                <Button icon="pi pi-refresh" label="Tạo lại" text size="small" @click="regenerateSeoThumb" :loading="isRegeneratingThumb" />
              </div>
            </div>

            <!-- Content Editor -->
            <div class="seo-section-title"><i class="pi pi-align-left" /> Nội dung bài viết</div>
            <div class="article-editor">
              <div class="editor-toolbar">
                <span class="editor-stat"><i class="pi pi-book" /> {{ seoArticle.word_count || 0 }} từ</span>
                <span class="editor-stat"><i class="pi pi-clock" /> {{ seoArticle.reading_time || 0 }} phút đọc</span>
                <span style="flex:1"></span>
                <button class="editor-toggle" :class="{ active: showHtmlSource }" @click="showHtmlSource = !showHtmlSource" type="button">
                  <i class="pi pi-code" /> {{ showHtmlSource ? 'Visual' : 'HTML' }}
                </button>
              </div>
              <div v-if="showHtmlSource">
                <textarea v-model="seoArticle.content" rows="20" class="form-textarea article-textarea" />
              </div>
              <div v-else class="editor-wrapper">
                <Editor v-model="seoArticle.content" editorStyle="min-height: 400px; font-size: 0.85rem; line-height: 1.8;" />
              </div>
            </div>

            <!-- Article Images -->
            <div class="img-gen-section">
              <h4 class="seo-section-title"><i class="pi pi-images" /> Ảnh minh hoạ bài viết</h4>
              <div class="img-gen-form">
                <div class="img-gen-row">
                  <input v-model="imgGenContext" class="form-select img-gen-input" :placeholder="seoForm.language === 'vi' ? 'Mô tả ảnh cần tạo (VD: ảnh minh họa SEO on-page)...' : 'Describe the image (e.g. SEO on-page illustration)...'" />
                  <select v-model="imgGenStyle" class="form-select img-gen-style">
                    <option value="modern">Modern</option>
                    <option value="corporate">Corporate</option>
                    <option value="creative">Creative</option>
                    <option value="tech">Tech</option>
                    <option value="infographic">Infographic</option>
                    <option value="illustration">Minh họa</option>
                    <option value="photo">Realistic Photo</option>
                  </select>
                  <Button label="Tạo ảnh" icon="pi pi-image" @click="generateInlineImage" :loading="isGenImage" :disabled="isGenImage" class="img-gen-btn" />
                </div>
              </div>
              <!-- Generated Images Gallery -->
              <div v-if="articleImages.length" class="img-gallery">
                <div v-for="(img, i) in articleImages" :key="i" class="img-card">
                  <img :src="img.url" :alt="img.context || 'Article image'" />
                  <div class="img-card-overlay">
                    <Button icon="pi pi-copy" text size="small" v-tooltip="'Copy URL'" @click="copyImgUrl(img.url)" />
                    <Button icon="pi pi-plus" text size="small" v-tooltip="'Chèn vào bài viết'" @click="insertImgToContent(img.url, img.context)" />
                  </div>
                  <span class="img-card-label">{{ img.context || 'Image ' + (i + 1) }}</span>
                </div>
              </div>
            </div>

            <!-- FAQ Section -->
            <div v-if="seoArticle.faq?.length" class="faq-section">
              <h4 class="seo-section-title"><i class="pi pi-question-circle" /> FAQ Schema ({{ seoArticle.faq.length }})</h4>
              <div v-for="(faq, i) in seoArticle.faq" :key="i" class="faq-item">
                <div class="faq-q"><i class="pi pi-question" /> <input v-model="faq.question" class="form-select faq-input" /></div>
                <div class="faq-a"><i class="pi pi-comment" /> <textarea v-model="faq.answer" rows="2" class="form-textarea form-textarea--sm faq-input" /></div>
              </div>
            </div>

            <!-- Internal Links -->
            <div v-if="seoArticle.internal_links?.length" class="links-section">
              <h4 class="seo-section-title"><i class="pi pi-link" /> Internal Links</h4>
              <div v-for="(link, i) in seoArticle.internal_links" :key="i" class="link-item">
                <span class="link-anchor">{{ link.anchor }}</span>
                <span class="link-arrow">→</span>
                <span class="link-topic">{{ link.suggested_topic }}</span>
              </div>
            </div>

            <!-- AI Meta -->
            <div class="ai-meta">
              <span><i class="pi pi-cpu" /> {{ seoData.ai_model }}</span>
              <span><i class="pi pi-bolt" /> {{ seoData.tokens_used }} tokens</span>
            </div>

            <!-- Actions -->
            <div class="step-actions">
              <Button label="Lưu nháp" icon="pi pi-save" severity="secondary" outlined @click="saveSeoArticle" :loading="isSeoSaving" />
              <Button label="Copy HTML" icon="pi pi-copy" severity="secondary" outlined @click="copySeoHtml" />
            </div>
          </div>
        </div>

        <!-- Right: SERP Preview & SEO Score -->
        <div class="seo-sidebar">
          <!-- SEO Score -->
          <div v-if="seoStep === 2" class="seo-score-card">
            <div class="score-header">SEO Score</div>
            <div class="score-gauge">
              <div class="score-circle" :class="seoScoreClass">
                <span class="score-num">{{ seoArticle.seo_score || 0 }}</span>
              </div>
              <span class="score-label" :class="seoScoreClass">{{ seoScoreLabel }}</span>
            </div>
            <div class="seo-checklist">
              <div class="check-item" :class="{ pass: (seoArticle.meta_title||'').length >= 40 && (seoArticle.meta_title||'').length <= 70 }">
                <i :class="(seoArticle.meta_title||'').length >= 40 && (seoArticle.meta_title||'').length <= 70 ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
                Meta Title (50-60 chars)
              </div>
              <div class="check-item" :class="{ pass: (seoArticle.meta_description||'').length >= 120 && (seoArticle.meta_description||'').length <= 180 }">
                <i :class="(seoArticle.meta_description||'').length >= 120 && (seoArticle.meta_description||'').length <= 180 ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
                Meta Description (150-160)
              </div>
              <div class="check-item" :class="{ pass: !!seoArticle.slug }">
                <i :class="seoArticle.slug ? 'pi pi-check-circle' : 'pi pi-times-circle'" /> URL Slug
              </div>
              <div class="check-item" :class="{ pass: !!seoArticle.focus_keyword }">
                <i :class="seoArticle.focus_keyword ? 'pi pi-check-circle' : 'pi pi-times-circle'" /> Focus Keyword
              </div>
              <div class="check-item" :class="{ pass: (seoArticle.word_count || 0) >= 800 }">
                <i :class="(seoArticle.word_count || 0) >= 800 ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
                Content ≥ 800 words
              </div>
              <div class="check-item" :class="{ pass: (seoArticle.faq||[]).length > 0 }">
                <i :class="(seoArticle.faq||[]).length > 0 ? 'pi pi-check-circle' : 'pi pi-times-circle'" /> FAQ Schema
              </div>
              <div class="check-item" :class="{ pass: (seoArticle.internal_links||[]).length > 0 }">
                <i :class="(seoArticle.internal_links||[]).length > 0 ? 'pi pi-check-circle' : 'pi pi-times-circle'" /> Internal Links
              </div>
            </div>
          </div>

          <!-- SERP Preview -->
          <div v-if="seoStep === 2" class="serp-card">
            <div class="serp-header"><i class="pi pi-google" /> Google Preview</div>
            <div class="serp-preview">
              <div class="serp-breadcrumb">
                <span class="serp-site">yoursite.com</span>
                <span class="serp-sep"> › </span>
                <span class="serp-slug">{{ seoArticle.slug || 'blog-post' }}</span>
              </div>
              <h3 class="serp-title">{{ seoArticle.meta_title || 'Tiêu đề bài viết...' }}</h3>
              <p class="serp-desc">{{ seoArticle.meta_description || 'Mô tả bài viết sẽ hiển thị ở đây...' }}</p>
            </div>
          </div>

          <!-- Article Stats -->
          <div v-if="seoStep === 2 && seoArticle.image_alt_suggestions?.length" class="alt-text-card">
            <div class="alt-header"><i class="pi pi-image" /> Alt Text Suggestions</div>
            <div v-for="(alt, i) in seoArticle.image_alt_suggestions" :key="i" class="alt-item">
              <i class="pi pi-image" /> {{ alt }}
            </div>
          </div>

          <!-- Tips -->
          <div v-if="seoStep === 1" class="tips-card seo-tips">
            <div class="tips-header"><i class="pi pi-lightbulb" /> SEO Tips</div>
            <div class="tips-list">
              <div class="tip-item"><i class="pi pi-check" /><span>Focus keyword nên xuất hiện trong 100 từ đầu</span></div>
              <div class="tip-item"><i class="pi pi-check" /><span>Keyword density lý tưởng: 1-2%</span></div>
              <div class="tip-item"><i class="pi pi-check" /><span>Bài viết ≥ 1500 từ rank tốt hơn</span></div>
              <div class="tip-item"><i class="pi pi-check" /><span>FAQ Schema giúp hiện rich snippets</span></div>
              <div class="tip-item"><i class="pi pi-check" /><span>Internal links cải thiện crawlability</span></div>
              <div class="tip-item"><i class="pi pi-check" /><span>Meta title 50-60 chars, description 150-160</span></div>
            </div>
          </div>
        </div>
      </div>
    </div><!-- end seo mode -->

  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Editor from 'primevue/editor'
import axios from 'axios'

export default {
  components: { Head, Link, Button, Editor },
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
      studioMode: 'social',
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
      // SEO Blog state
      seoStep: 1,
      isSeoGenerating: false,
      isSeoSaving: false,
      seoForm: {
        topic: '',
        tone: 'professional',
        language: 'vi',
        article_length: 'medium',
        focus_keyword: '',
        instructions: '',
        generate_thumbnail: true,
        thumbnail_style: 'modern',
      },
      seoData: {
        article: {},
        thumbnail_url: null,
        ai_model: '',
        tokens_used: 0,
      },
      seoArticle: {},
      showHtmlSource: false,
      // Image generation (SEO)
      isRegeneratingThumb: false,
      isGenImage: false,
      imgGenContext: '',
      imgGenStyle: 'modern',
      articleImages: [],
      // Social Images
      socialImages: [],
      socialImgPrompt: '',
      socialImgStyle: 'modern',
      socialImgRatio: '1:1',
      isGenSocialImg: false,
      fbCarouselIdx: 0,
      igCarouselIdx: 0,
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
    previewImages() {
      return this.socialImages.filter(img => !img.loading && img.url).map(img => img.url)
    },
    seoScoreClass() {
      const s = this.seoArticle.seo_score || 0
      if (s >= 80) return 'score-great'
      if (s >= 60) return 'score-good'
      if (s >= 40) return 'score-fair'
      return 'score-poor'
    },
    seoScoreLabel() {
      const s = this.seoArticle.seo_score || 0
      if (s >= 80) return 'Excellent'
      if (s >= 60) return 'Good'
      if (s >= 40) return 'Needs Work'
      return 'Poor'
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
        const { data } = await axios.post('/content-studio/generate', this.form, { timeout: 120000 })
        if (data.success) {
          this.generatedData = data.data
          this.activePreviewPlatform = this.form.platforms[0]
          this.previewPlatform = this.form.platforms[0]
          this.currentStep = 2
        } else {
          alert(data.message || 'Lỗi tạo nội dung')
        }
      } catch (e) {
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      } finally {
        this.isGenerating = false
      }
    },

    async saveAsDraft() {
      this.isSaving = true
      try {
        const { data } = await axios.post('/content-studio/save', {
          ...this.generatedData,
          topic: this.form.topic,
          tone: this.form.tone,
        }, { timeout: 30000 })
        if (data.success) {
          alert('Đã lưu nháp thành công!')
        }
      } catch (e) {
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      } finally {
        this.isSaving = false
      }
    },

    async publishContent() {
      this.isPublishing = true
      try {
        // First save content
        const { data: saveData } = await axios.post('/content-studio/save', {
          ...this.generatedData,
          topic: this.form.topic,
          tone: this.form.tone,
        }, { timeout: 30000 })

        if (!saveData.success) {
          alert('Lỗi lưu nội dung')
          return
        }

        // Then publish
        const itemIds = Object.values(saveData.items).map(i => i.id)
        const { data } = await axios.post('/content-studio/publish', {
          content_item_ids: itemIds,
          social_account_ids: this.selectedAccounts,
          scheduled_at: this.scheduleMode ? this.scheduledAt : null,
        }, { timeout: 60000 })
        if (data.success) {
          this.publishResult = data
        } else {
          alert(data.message || 'Lỗi đăng bài')
        }
      } catch (e) {
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      } finally {
        this.isPublishing = false
      }
    },

    async regenerateThumbnail() {
      this.isRegeneratingThumb = true
      try {
        const { data } = await axios.post('/content-studio/thumbnail', {
          topic: this.form.topic,
          style: this.form.thumbnail_style,
        }, { timeout: 90000 })
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
      this.socialImages = []
      this.socialImgPrompt = ''
      this.fbCarouselIdx = 0
      this.igCarouselIdx = 0
    },

    // ═══ SEO Methods ═══
    async generateSeoArticle() {
      this.isSeoGenerating = true
      try {
        const { data } = await axios.post('/content-studio/generate-seo', this.seoForm, { timeout: 180000 })
        if (data.success) {
          this.seoData = data.data
          this.seoArticle = { ...data.data.article }
          this.seoStep = 2
        } else {
          alert(data.message || 'Lỗi tạo bài SEO')
        }
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        if (e.code === 'ECONNABORTED') {
          alert('AI đang xử lý lâu, vui lòng thử lại với bài ngắn hơn hoặc đợi thêm.')
        } else {
          alert('Lỗi: ' + msg)
        }
      } finally {
        this.isSeoGenerating = false
      }
    },

    async saveSeoArticle() {
      this.isSeoSaving = true
      try {
        const { data } = await axios.post('/content-studio/save-seo', {
          article: this.seoArticle,
          topic: this.seoForm.topic,
          tone: this.seoForm.tone,
          thumbnail_url: this.seoData.thumbnail_url,
          ai_model: this.seoData.ai_model,
          tokens_used: this.seoData.tokens_used,
        }, { timeout: 30000 })
        if (data.success) { alert('Đã lưu bài SEO thành công!') }
        else { alert(data.message || 'Lỗi lưu') }
      } catch (e) { alert('Lỗi: ' + (e.response?.data?.message || e.message)) }
      finally { this.isSeoSaving = false }
    },

    async regenerateSeoThumb() {
      this.isRegeneratingThumb = true
      try {
        const { data } = await axios.post('/content-studio/thumbnail', {
          topic: this.seoForm.topic,
          style: this.seoForm.thumbnail_style,
        }, { timeout: 90000 })
        if (data.success) { this.seoData.thumbnail_url = data.thumbnail_url }
      } catch (e) { console.error(e) }
      finally { this.isRegeneratingThumb = false }
    },

    copySeoHtml() {
      navigator.clipboard.writeText(this.seoArticle.content || '')
      alert('Đã copy HTML!')
    },

    async generateInlineImage() {
      this.isGenImage = true
      try {
        const { data } = await axios.post('/content-studio/article-image', {
          topic: this.seoForm.topic,
          context: this.imgGenContext,
          style: this.imgGenStyle,
        }, { timeout: 90000 })
        if (data.success && data.image_url) {
          this.articleImages.push({ url: data.image_url, context: this.imgGenContext || this.seoForm.topic })
          this.imgGenContext = ''
        } else {
          alert(data.message || 'Không thể tạo ảnh')
        }
      } catch (e) {
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      } finally {
        this.isGenImage = false
      }
    },

    copyImgUrl(url) {
      navigator.clipboard.writeText(url)
      alert('Copied URL!')
    },

    insertImgToContent(url, alt) {
      const imgTag = `\n<figure>\n  <img src="${url}" alt="${alt || 'Article image'}" width="100%" />\n  <figcaption>${alt || ''}</figcaption>\n</figure>\n`
      this.seoArticle.content = (this.seoArticle.content || '') + imgTag
    },

    // ═══ Social Multi-Image Methods ═══
    async generateSocialImage() {
      if (this.socialImages.length >= 10) return
      const prompt = this.socialImgPrompt || this.form.topic
      if (!prompt) return

      const idx = this.socialImages.length
      this.socialImages.push({ loading: true, prompt, style: this.socialImgStyle, ratio: this.socialImgRatio, url: null })
      this.isGenSocialImg = true

      try {
        const { data } = await axios.post('/content-studio/article-image', {
          topic: this.form.topic,
          context: prompt,
          style: this.socialImgStyle,
        }, { timeout: 90000 })

        if (data.success && data.image_url) {
          this.socialImages[idx] = { loading: false, prompt, style: this.socialImgStyle, ratio: this.socialImgRatio, url: data.image_url }
        } else {
          this.socialImages.splice(idx, 1)
          alert(data.message || 'Không thể tạo ảnh')
        }
      } catch (e) {
        this.socialImages.splice(idx, 1)
        alert('Lỗi: ' + (e.response?.data?.message || e.message))
      } finally {
        this.isGenSocialImg = false
      }
    },

    async batchGenerateImages(count) {
      const available = 10 - this.socialImages.length
      const toGen = Math.min(count, available)
      if (toGen <= 0) return

      const prompt = this.socialImgPrompt || this.form.topic
      if (!prompt) { alert('Vui lòng nhập mô tả ảnh hoặc chủ đề'); return }

      this.isGenSocialImg = true

      // Add placeholders
      const startIdx = this.socialImages.length
      for (let i = 0; i < toGen; i++) {
        this.socialImages.push({ loading: true, prompt: `${prompt} (${i + 1}/${toGen})`, style: this.socialImgStyle, ratio: this.socialImgRatio, url: null })
      }

      // Generate sequentially to avoid overloading
      for (let i = 0; i < toGen; i++) {
        try {
          const { data } = await axios.post('/content-studio/article-image', {
            topic: this.form.topic,
            context: `${prompt} — variation ${i + 1}`,
            style: this.socialImgStyle,
          }, { timeout: 90000 })

          if (data.success && data.image_url) {
            this.socialImages[startIdx + i] = {
              loading: false,
              prompt: `${prompt} (${i + 1})`,
              style: this.socialImgStyle,
              ratio: this.socialImgRatio,
              url: data.image_url,
            }
          } else {
            this.socialImages[startIdx + i] = { loading: false, prompt: '', style: '', ratio: '', url: null, failed: true }
          }
        } catch (e) {
          this.socialImages[startIdx + i] = { loading: false, prompt: '', style: '', ratio: '', url: null, failed: true }
        }
      }

      // Clean up failed items
      this.socialImages = this.socialImages.filter(img => !img.failed)
      this.isGenSocialImg = false
    },

    async regenerateSingleImage(idx) {
      const old = this.socialImages[idx]
      this.socialImages[idx] = { ...old, loading: true }
      try {
        const { data } = await axios.post('/content-studio/article-image', {
          topic: this.form.topic,
          context: old.prompt || this.form.topic,
          style: old.style || this.socialImgStyle,
        }, { timeout: 90000 })

        if (data.success && data.image_url) {
          this.socialImages[idx] = { ...old, loading: false, url: data.image_url }
        } else {
          this.socialImages[idx] = { ...old, loading: false }
        }
      } catch (e) {
        this.socialImages[idx] = { ...old, loading: false }
      }
    },

    removeSocialImage(idx) {
      this.socialImages.splice(idx, 1)
      // Reset carousel indices
      if (this.fbCarouselIdx >= this.socialImages.length) this.fbCarouselIdx = Math.max(0, this.socialImages.length - 1)
      if (this.igCarouselIdx >= this.socialImages.length) this.igCarouselIdx = Math.max(0, this.socialImages.length - 1)
    },

    copySocialImgUrl(url) {
      navigator.clipboard.writeText(url)
      alert('Đã copy URL ảnh!')
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Mode Switcher ===== */
.mode-switcher { display: flex; gap: 0; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.mode-btn {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.5rem 1rem; border: none; background: white;
  font-size: 0.78rem; font-weight: 500; color: #64748b;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.mode-btn:hover { background: #f8fafc; }
.mode-btn.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: 600; }
.mode-btn i { font-size: 0.72rem; }

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
  .studio-layout, .seo-layout { grid-template-columns: 1fr; }
  .sidebar-panel, .seo-sidebar { order: -1; }
}
@media (max-width: 768px) {
  .platform-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .step-indicator { flex-wrap: wrap; }
  .tw-text, .tw-hashtags, .tw-image, .tw-actions { padding-left: 0; margin-left: 0; }
  .mode-switcher { width: 100%; }
  .mode-btn { flex: 1; justify-content: center; }
}

/* ═══════════════════════════════════════════════ */
/*                SEO BLOG MODE                    */
/* ═══════════════════════════════════════════════ */
.seo-layout { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; }
.seo-generator { min-width: 0; }
.seo-sidebar { display: flex; flex-direction: column; gap: 0.75rem; }
.seo-gen-btn { background: linear-gradient(135deg, #10b981, #059669) !important; }

.seo-meta-section { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; margin-bottom: 0.85rem; }
.seo-section-title { font-size: 0.78rem; font-weight: 700; color: #475569; margin: 0.75rem 0 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
.seo-section-title i { color: #6366f1; font-size: 0.72rem; }
.seo-meta-section .seo-section-title { margin-top: 0; }

.char-hint { font-size: 0.6rem; color: #94a3b8; font-weight: 500; margin-left: 0.3rem; }
.char-warn { color: #ef4444; font-weight: 600; }

.slug-input { display: flex; align-items: center; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.slug-prefix { font-size: 0.72rem; color: #94a3b8; padding: 0.5rem 0 0.5rem 0.65rem; white-space: nowrap; background: #f8fafc; }
.slug-field { border: none !important; border-radius: 0 !important; padding-left: 0.25rem !important; }

.keywords-bar { display: flex; align-items: center; gap: 0.3rem; flex-wrap: wrap; margin-bottom: 0.75rem; padding: 0.5rem; background: #f8fafc; border-radius: 8px; }
.kw-label { font-size: 0.68rem; font-weight: 600; color: #475569; }
.kw-chip { font-size: 0.62rem; font-weight: 600; padding: 0.15rem 0.45rem; background: #eef2ff; color: #6366f1; border-radius: 5px; }

.article-editor { margin-bottom: 0.75rem; }
.editor-toolbar { display: flex; gap: 0.75rem; padding: 0.45rem 0.65rem; background: #f1f5f9; border-radius: 8px 8px 0 0; border: 1px solid #e2e8f0; border-bottom: none; }
.editor-stat { font-size: 0.65rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.editor-stat i { font-size: 0.6rem; color: #6366f1; }
.article-textarea { border-radius: 0 0 10px 10px !important; min-height: 350px; font-family: 'Courier New', monospace; font-size: 0.78rem; line-height: 1.7; }

.editor-toggle {
  display: flex; align-items: center; gap: 0.25rem;
  padding: 0.2rem 0.55rem; border-radius: 6px;
  border: 1px solid #cbd5e1; background: white;
  font-size: 0.62rem; font-weight: 600; color: #64748b;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.editor-toggle:hover { background: #f1f5f9; }
.editor-toggle.active { background: #6366f1; color: white; border-color: #6366f1; }
.editor-toggle i { font-size: 0.6rem; }

.editor-wrapper { border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; overflow: hidden; }
.editor-wrapper :deep(.p-editor) { border: none; }
.editor-wrapper :deep(.p-editor .p-editor-toolbar) {
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  padding: 0.35rem 0.5rem;
}
.editor-wrapper :deep(.p-editor .p-editor-toolbar .ql-formats) { margin-right: 0.5rem; }
.editor-wrapper :deep(.p-editor .p-editor-toolbar button) {
  width: 28px; height: 28px; border-radius: 5px;
}
.editor-wrapper :deep(.p-editor .p-editor-toolbar button:hover) { background: #e2e8f0; }
.editor-wrapper :deep(.p-editor .p-editor-toolbar button.ql-active) { background: #6366f1; color: white; }
.editor-wrapper :deep(.p-editor .p-editor-content) { border: none; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor) {
  font-size: 0.85rem; line-height: 1.8; color: #1e293b;
  padding: 1rem 1.25rem; min-height: 400px;
}
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor h1) { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor h2) { font-size: 1.25rem; font-weight: 700; margin: 0.85rem 0 0.4rem; color: #1e293b; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor h3) { font-size: 1.1rem; font-weight: 600; margin: 0.75rem 0 0.35rem; color: #334155; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor p) { margin: 0 0 0.65rem; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor ul),
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor ol) { margin: 0.25rem 0 0.65rem 1rem; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor blockquote) {
  border-left: 3px solid #6366f1; padding-left: 0.75rem;
  color: #475569; font-style: italic; margin: 0.5rem 0;
}
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor a) { color: #6366f1; text-decoration: underline; }
.editor-wrapper :deep(.p-editor .p-editor-content .ql-editor img) { max-width: 100%; border-radius: 8px; margin: 0.5rem 0; }

.faq-section { margin-bottom: 0.75rem; }
.faq-item { background: #f8fafc; border-radius: 10px; padding: 0.65rem; margin-bottom: 0.35rem; border: 1px solid #f1f5f9; }
.faq-q, .faq-a { display: flex; align-items: flex-start; gap: 0.35rem; margin-bottom: 0.3rem; }
.faq-q i { color: #f59e0b; font-size: 0.72rem; margin-top: 0.35rem; }
.faq-a i { color: #10b981; font-size: 0.72rem; margin-top: 0.35rem; }
.faq-input { flex: 1 !important; }

.links-section { margin-bottom: 0.75rem; }
.link-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.65rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.25rem; font-size: 0.72rem; border: 1px solid #f1f5f9; }
.link-anchor { font-weight: 600; color: #0a66c2; }
.link-arrow { color: #94a3b8; }
.link-topic { color: #475569; }

/* SEO Score */
.seo-score-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
.score-header { padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.score-gauge { display: flex; flex-direction: column; align-items: center; padding: 1.25rem; gap: 0.4rem; }
.score-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 5px solid; transition: all 0.3s; }
.score-circle.score-great { border-color: #10b981; color: #10b981; background: #ecfdf5; }
.score-circle.score-good { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.score-circle.score-fair { border-color: #f59e0b; color: #f59e0b; background: #fffbeb; }
.score-circle.score-poor { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
.score-num { font-size: 1.5rem; font-weight: 800; }
.score-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.score-label.score-great { color: #10b981; }
.score-label.score-good { color: #3b82f6; }
.score-label.score-fair { color: #f59e0b; }
.score-label.score-poor { color: #ef4444; }

.seo-checklist { padding: 0 0.85rem 0.85rem; }
.check-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.68rem; color: #94a3b8; padding: 0.25rem 0; }
.check-item i { font-size: 0.65rem; color: #ef4444; }
.check-item.pass { color: #334155; }
.check-item.pass i { color: #10b981; }

/* SERP */
.serp-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
.serp-header { padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.78rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.35rem; }
.serp-header i { color: #4285f4; font-size: 0.82rem; }
.serp-preview { padding: 1rem; font-family: Arial, sans-serif; }
.serp-breadcrumb { font-size: 0.72rem; margin-bottom: 0.15rem; }
.serp-site { color: #202124; }
.serp-sep, .serp-slug { color: #5f6368; }
.serp-title { font-size: 1rem; font-weight: 400; color: #1a0dab; margin: 0 0 0.2rem; line-height: 1.3; cursor: pointer; }
.serp-title:hover { text-decoration: underline; }
.serp-desc { font-size: 0.78rem; color: #4d5156; line-height: 1.5; margin: 0; }

/* Alt Text */
.alt-text-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
.alt-header { padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.78rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.35rem; }
.alt-header i { color: #6366f1; font-size: 0.75rem; }
.alt-item { display: flex; align-items: center; gap: 0.35rem; padding: 0.4rem 1rem; font-size: 0.72rem; color: #475569; border-bottom: 1px solid #f8fafc; }
.alt-item i { color: #94a3b8; font-size: 0.62rem; }

/* Tips */
.tips-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
.tips-header { padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.35rem; }
.tips-header i { color: #f59e0b; font-size: 0.82rem; }
.tips-list { padding: 0.5rem 0.85rem; }
.tip-item { display: flex; align-items: flex-start; gap: 0.35rem; font-size: 0.72rem; color: #475569; padding: 0.3rem 0; line-height: 1.4; }
.tip-item i { color: #10b981; font-size: 0.62rem; margin-top: 0.15rem; flex-shrink: 0; }

/* Article Image Generator */
.img-gen-section { margin-bottom: 0.85rem; padding: 0.85rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
.img-gen-section .seo-section-title { margin-top: 0; }
.img-gen-form { margin-bottom: 0.5rem; }
.img-gen-row { display: flex; gap: 0.4rem; align-items: center; }
.img-gen-input { flex: 1; }
.img-gen-style { width: 140px; flex-shrink: 0; }
.img-gen-btn { flex-shrink: 0; background: linear-gradient(135deg, #8b5cf6, #6366f1) !important; white-space: nowrap; }

.img-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.5rem; margin-top: 0.5rem; }
.img-card {
  position: relative; border-radius: 10px; overflow: hidden;
  border: 1px solid #e2e8f0; background: white; transition: all 0.2s;
}
.img-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.img-card img { width: 100%; height: 100px; object-fit: cover; display: block; }
.img-card-overlay {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center;
  gap: 0.25rem; opacity: 0; transition: opacity 0.2s;
}
.img-card:hover .img-card-overlay { opacity: 1; }
.img-card-overlay :deep(.p-button) { color: white !important; }
.img-card-label {
  display: block; padding: 0.25rem 0.45rem; font-size: 0.58rem; color: #64748b;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

@media (max-width: 768px) {
  .img-gen-row { flex-direction: column; }
  .img-gen-style { width: 100%; }
  .img-gallery { grid-template-columns: repeat(2, 1fr); }
}

/* ═══ SOCIAL MULTI-IMAGE GALLERY ═══ */
.social-images-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1.5px solid #f1f5f9;
}
.section-header-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.35rem;
}
.section-label {
  font-size: 0.82rem; font-weight: 700; color: #1e293b; margin: 0;
  display: flex; align-items: center; gap: 0.3rem;
}
.section-label i { color: #8b5cf6; font-size: 0.78rem; }
.img-hint { font-size: 0.62rem; color: #94a3b8; }

/* Toolbar */
.img-gen-toolbar {
  display: flex; gap: 0.35rem; align-items: center; margin-bottom: 0.5rem;
  flex-wrap: wrap;
}
.img-prompt-input { flex: 1; min-width: 180px; font-size: 0.78rem; }
.img-style-select { width: 110px; font-size: 0.72rem; }
.img-ratio-select { width: 100px; font-size: 0.72rem; }
.img-gen-btn-sm { white-space: nowrap; }

/* Batch row */
.batch-row { display: flex; gap: 0.35rem; margin-bottom: 0.65rem; flex-wrap: wrap; }
.batch-btn {
  display: inline-flex; align-items: center; gap: 0.25rem;
  padding: 0.3rem 0.65rem; border-radius: 7px;
  border: 1.5px solid #e2e8f0; background: white;
  font-size: 0.68rem; font-weight: 600; color: #64748b;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.batch-btn:hover { border-color: #8b5cf6; color: #8b5cf6; }
.batch-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.batch-btn i { font-size: 0.6rem; }
.batch-btn--danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

/* Image Gallery */
.social-img-gallery {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 0.5rem;
}
.social-img-card {
  position: relative; border-radius: 10px; overflow: hidden;
  aspect-ratio: 1; background: #f8fafc;
  border: 1.5px solid #f1f5f9; transition: all 0.2s;
}
.social-img-card:hover { border-color: #e2e8f0; box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
.social-img-card.generating { border-color: #c4b5fd; }

.social-img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Loading state */
.img-loading-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  height: 100%; gap: 0.3rem; color: #8b5cf6;
}
.img-loading-state i { font-size: 1.2rem; }
.img-loading-state span { font-size: 0.6rem; font-weight: 600; }

/* Overlay */
.img-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,0.55);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s;
}
.social-img-card:hover .img-overlay { opacity: 1; }
.img-overlay-actions { display: flex; gap: 0.35rem; }
.img-act-btn {
  width: 30px; height: 30px; border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15);
  color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.15s; font-size: 0.72rem;
}
.img-act-btn:hover { background: rgba(255,255,255,0.3); }
.img-act-btn--danger:hover { background: rgba(239,68,68,0.7); }

/* Card info */
.img-card-info {
  position: absolute; bottom: 0; left: 0; right: 0;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.2rem 0.35rem; background: rgba(0,0,0,0.5);
  font-size: 0.5rem; color: rgba(255,255,255,0.8);
}
.img-idx {
  width: 16px; height: 16px; border-radius: 4px;
  background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.5rem;
}
.img-card-style { text-transform: capitalize; font-weight: 500; }

/* Add card */
.add-card {
  border: 2px dashed #e2e8f0; background: transparent;
  cursor: pointer; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 0.25rem;
  color: #94a3b8; transition: all 0.2s; font-family: inherit;
}
.add-card:hover { border-color: #8b5cf6; color: #8b5cf6; }
.add-card:disabled { opacity: 0.5; cursor: not-allowed; }
.add-card i { font-size: 1.1rem; }
.add-card span { font-size: 0.6rem; font-weight: 600; }

/* Empty */
.social-img-empty {
  text-align: center; padding: 1.5rem; background: #fafbfc;
  border-radius: 10px; border: 1.5px dashed #e2e8f0;
}
.social-img-empty i { font-size: 1.5rem; color: #cbd5e1; margin-bottom: 0.4rem; display: block; }
.social-img-empty p { font-size: 0.72rem; color: #94a3b8; margin: 0 0 0.15rem; }
.img-empty-hint { font-size: 0.62rem !important; color: #cbd5e1 !important; }

/* ═══ CAROUSEL (Preview) ═══ */
.fb-carousel, .ig-carousel {
  position: relative; overflow: hidden; border-radius: 0;
}
.carousel-track {
  display: flex; transition: transform 0.35s ease;
}
.carousel-track img {
  flex-shrink: 0; width: 100%;
}
.carousel-dots {
  position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 4px;
}
.carousel-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(255,255,255,0.45); cursor: pointer;
  transition: all 0.2s;
}
.carousel-dot.active { background: white; transform: scale(1.2); }
.carousel-nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 24px; height: 24px; border-radius: 50%;
  background: rgba(0,0,0,0.4); border: none; color: white;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 0.6rem; transition: all 0.15s;
}
.carousel-nav:hover { background: rgba(0,0,0,0.65); }
.carousel-nav--prev { left: 6px; }
.carousel-nav--next { right: 6px; }

@media (max-width: 640px) {
  .img-gen-toolbar { flex-direction: column; }
  .img-prompt-input { min-width: 100%; }
  .social-img-gallery { grid-template-columns: repeat(2, 1fr); }
}
</style>
