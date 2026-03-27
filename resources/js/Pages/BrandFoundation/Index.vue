<template>
  <div class="brand-page">
    <Head title="Brand Foundation" />

    <!-- ═══ HERO ═══ -->
    <div class="hero">
      <div class="hero-bg">
        <div v-for="n in 12" :key="n" class="hero-shape" :style="shapeStyle(n)" />
      </div>
      <div class="hero-content">
        <div class="hero-badge"><i class="pi pi-palette" /> BRAND IDENTITY SYSTEM</div>
        <h1>Brand <span class="gradient-text">Foundation</span></h1>
        <p>Xây dựng & bảo vệ nhận diện thương hiệu — Strategy, Visual, Voice, Assets</p>
        <div class="hero-meta">
          <div class="completion-ring">
            <svg viewBox="0 0 48 48">
              <circle cx="24" cy="24" r="20" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="3" />
              <circle cx="24" cy="24" r="20" fill="none" stroke="#a78bfa" stroke-width="3"
                :stroke-dasharray="completionDash" stroke-linecap="round" transform="rotate(-90 24 24)" />
            </svg>
            <span class="ring-pct">{{ brand.completion_score }}%</span>
          </div>
          <div>
            <span class="hero-stat-lbl">Completion</span>
            <span :class="['status-chip', brand.status]">{{ statusLabels[brand.status] }}</span>
          </div>
        </div>
      </div>
      <div class="hero-actions">
        <button v-if="brand.status === 'draft'" class="btn-glow" @click="publish"><i class="pi pi-check-circle" /> Publish</button>
      </div>
    </div>

    <!-- ═══ TAB NAVIGATION ═══ -->
    <div class="tab-nav">
      <button v-for="tab in tabs" :key="tab.key" :class="['tab-btn', activeTab === tab.key && 'active']"
        @click="activeTab = tab.key">
        <i :class="tab.icon" />
        <span>{{ tab.label }}</span>
        <span v-if="brand.section_status?.[tab.key]" class="tab-done"><i class="pi pi-check" /></span>
      </button>
    </div>

    <!-- ═══ TAB: BRAND STRATEGY ═══ -->
    <div v-show="activeTab === 'foundation'" class="tab-content">
      <form @submit.prevent="saveFoundation">
        <!-- Purpose/Vision/Mission/Promise -->
        <div class="section-grid">
          <div v-for="field in coreFields" :key="field.key" class="glass-card">
            <div class="card-icon" :style="{ background: field.color + '15', color: field.color }"><i :class="field.icon" /></div>
            <h3>{{ field.label }}</h3>
            <p class="card-hint">{{ field.hint }}</p>
            <textarea v-model="foundationForm[field.key]" :placeholder="field.placeholder" rows="3" class="brand-input" />
          </div>
        </div>

        <!-- Tagline -->
        <div class="glass-card mt-1">
          <h3><i class="pi pi-megaphone" style="color:#f59e0b" /> Tagline</h3>
          <input v-model="foundationForm.tagline" class="brand-input" placeholder='VD: "Empowering Your Growth"' />
        </div>

        <!-- Brand Values -->
        <div class="glass-card mt-1">
          <div class="card-header-row">
            <h3><i class="pi pi-heart" style="color:#ef4444" /> Brand Values</h3>
            <button type="button" class="add-btn" @click="addValue"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="values-grid">
            <div v-for="(val, i) in foundationForm.brand_values" :key="i" class="value-card">
              <button type="button" class="remove-mini" @click="foundationForm.brand_values.splice(i, 1)"><i class="pi pi-times" /></button>
              <input v-model="val.name" class="brand-input" placeholder="Tên giá trị" />
              <textarea v-model="val.description" class="brand-input" rows="2" placeholder="Mô tả..." />
            </div>
          </div>
        </div>

        <!-- Personality Radar -->
        <div class="glass-card mt-1">
          <div class="card-header-row">
            <h3><i class="pi pi-user" style="color:#8b5cf6" /> Brand Personality</h3>
            <button type="button" class="add-btn" @click="addPersonality"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="personality-section">
            <div class="personality-sliders">
              <div v-for="(p, i) in foundationForm.brand_personality" :key="i" class="personality-row">
                <button type="button" class="remove-mini" @click="foundationForm.brand_personality.splice(i, 1)"><i class="pi pi-times" /></button>
                <input v-model="p.trait" class="trait-input" placeholder="Trait" />
                <input type="range" v-model.number="p.score" min="0" max="10" class="range-slider" />
                <span class="score-val">{{ p.score }}</span>
              </div>
            </div>
            <!-- Radar Chart -->
            <div v-if="foundationForm.brand_personality?.length >= 3" class="radar-chart">
              <svg viewBox="0 0 200 200">
                <!-- Grid circles -->
                <circle v-for="r in [20,40,60,80]" :key="r" cx="100" cy="100" :r="r" fill="none" stroke="rgba(139,92,246,.1)" stroke-width="1" />
                <!-- Radar polygon -->
                <polygon :points="radarPoints" fill="rgba(139,92,246,.15)" stroke="#8b5cf6" stroke-width="2" />
                <!-- Labels -->
                <text v-for="(p, i) in foundationForm.brand_personality" :key="'t'+i"
                  :x="radarLabelPos(i).x" :y="radarLabelPos(i).y"
                  text-anchor="middle" fill="#64748b" font-size="8" font-weight="600">{{ p.trait }}</text>
              </svg>
            </div>
          </div>
        </div>

        <!-- Value Propositions -->
        <div class="glass-card mt-1">
          <div class="card-header-row">
            <h3><i class="pi pi-star" style="color:#f59e0b" /> Value Propositions</h3>
            <button type="button" class="add-btn" @click="addProp"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div v-for="(vp, i) in foundationForm.value_propositions" :key="i" class="prop-row">
            <button type="button" class="remove-mini" @click="foundationForm.value_propositions.splice(i, 1)"><i class="pi pi-times" /></button>
            <input v-model="vp.title" class="brand-input" placeholder="Tiêu đề" />
            <textarea v-model="vp.description" class="brand-input" rows="2" placeholder="Mô tả lợi ích..." />
          </div>
        </div>

        <div class="form-footer"><button type="submit" class="btn-primary" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> Lưu Strategy</button></div>
      </form>
    </div>

    <!-- ═══ TAB: VISUAL IDENTITY ═══ -->
    <div v-show="activeTab === 'visual'" class="tab-content">
      <form @submit.prevent="saveVisual">
        <!-- Colors -->
        <div class="glass-card">
          <div class="card-header-row">
            <h3><i class="pi pi-palette" style="color:#ec4899" /> Primary Colors</h3>
            <button type="button" class="add-btn" @click="addColor('primary_colors')"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="color-grid">
            <div v-for="(c, i) in visualForm.primary_colors" :key="i" class="color-card">
              <button type="button" class="remove-mini" @click="visualForm.primary_colors.splice(i, 1)"><i class="pi pi-times" /></button>
              <div class="color-preview" :style="{ background: c.hex }" @click="$refs['cpri'+i]?.[0]?.click()">
                <input type="color" v-model="c.hex" :ref="'cpri'+i" class="color-picker-hidden" />
              </div>
              <input v-model="c.name" class="brand-input sm" placeholder="Tên" />
              <span class="color-hex">{{ c.hex }}</span>
            </div>
          </div>
        </div>

        <div class="glass-card mt-1">
          <div class="card-header-row">
            <h3><i class="pi pi-palette" style="color:#06b6d4" /> Secondary Colors</h3>
            <button type="button" class="add-btn" @click="addColor('secondary_colors')"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="color-grid">
            <div v-for="(c, i) in visualForm.secondary_colors" :key="i" class="color-card">
              <button type="button" class="remove-mini" @click="visualForm.secondary_colors.splice(i, 1)"><i class="pi pi-times" /></button>
              <div class="color-preview" :style="{ background: c.hex }" @click="$refs['csec'+i]?.[0]?.click()">
                <input type="color" v-model="c.hex" :ref="'csec'+i" class="color-picker-hidden" />
              </div>
              <input v-model="c.name" class="brand-input sm" placeholder="Tên" />
              <span class="color-hex">{{ c.hex }}</span>
            </div>
          </div>
        </div>

        <!-- Typography -->
        <div class="glass-card mt-1">
          <h3><i class="pi pi-align-left" style="color:#6366f1" /> Typography</h3>
          <div class="typo-row">
            <div class="form-group">
              <label>Primary Font (Headings)</label>
              <select v-model="visualForm.font_primary" class="brand-input">
                <option v-for="f in fontOptions" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Secondary Font (Body)</label>
              <select v-model="visualForm.font_secondary" class="brand-input">
                <option v-for="f in fontOptions" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
          </div>
          <!-- Live Preview -->
          <div class="typo-preview" :style="{ fontFamily: visualForm.font_primary }">
            <h2 :style="{ fontFamily: visualForm.font_primary }">The Quick Brown Fox</h2>
            <p :style="{ fontFamily: visualForm.font_secondary }">Một đoạn văn bản mẫu để kiểm tra hiển thị font. 0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz</p>
            <div class="typo-scale">
              <span :style="{ fontFamily: visualForm.font_primary, fontSize: '2rem' }">Aa</span>
              <span :style="{ fontFamily: visualForm.font_primary, fontSize: '1.5rem' }">Aa</span>
              <span :style="{ fontFamily: visualForm.font_secondary, fontSize: '1rem' }">Aa</span>
              <span :style="{ fontFamily: visualForm.font_secondary, fontSize: '.85rem' }">Aa</span>
            </div>
          </div>
        </div>

        <!-- Logo Gallery -->
        <div class="glass-card mt-1">
          <h3><i class="pi pi-image" style="color:#10b981" /> Logo System</h3>
          <div class="logo-grid">
            <div v-for="variant in logoVariants" :key="variant.key" class="logo-slot">
              <div class="logo-preview" :class="{ 'dark-bg': variant.dark }">
                <img v-if="brand[variant.key]" :src="brand[variant.key]" :alt="variant.label" />
                <i v-else class="pi pi-image" />
              </div>
              <span class="logo-label">{{ variant.label }}</span>
              <label class="upload-mini-btn">
                <i class="pi pi-upload" /> Upload
                <input type="file" accept="image/*" class="hidden" @change="uploadLogo(variant.key, $event)" />
              </label>
            </div>
          </div>
        </div>

        <div class="form-footer"><button type="submit" class="btn-primary" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> Lưu Visual</button></div>
      </form>
    </div>

    <!-- ═══ TAB: BRAND VOICE ═══ -->
    <div v-show="activeTab === 'voice'" class="tab-content">
      <form @submit.prevent="saveVoice">
        <!-- Voice Traits -->
        <div class="glass-card">
          <div class="card-header-row">
            <h3><i class="pi pi-volume-up" style="color:#8b5cf6" /> Voice Traits</h3>
            <button type="button" class="add-btn" @click="addVoiceTrait"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="voice-grid">
            <div v-for="(vt, i) in voiceForm.voice_traits" :key="i" class="voice-card">
              <button type="button" class="remove-mini" @click="voiceForm.voice_traits.splice(i, 1)"><i class="pi pi-times" /></button>
              <input v-model="vt.trait" class="brand-input" placeholder="Trait (VD: Chuyên nghiệp)" />
              <textarea v-model="vt.description" class="brand-input" rows="2" placeholder="Mô tả..." />
              <div class="do-dont-row">
                <div class="do-col">
                  <span class="do-label"><i class="pi pi-check-circle" /> Nên</span>
                  <textarea v-model="vt.do_example" class="brand-input" rows="2" placeholder="Ví dụ nên viết..." />
                </div>
                <div class="dont-col">
                  <span class="dont-label"><i class="pi pi-times-circle" /> Không nên</span>
                  <textarea v-model="vt.dont_example" class="brand-input" rows="2" placeholder="Ví dụ không nên..." />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tone Variations -->
        <div class="glass-card mt-1">
          <div class="card-header-row">
            <h3><i class="pi pi-sliders-h" style="color:#f59e0b" /> Tone Variations</h3>
            <button type="button" class="add-btn" @click="addTone"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div class="tone-grid">
            <div v-for="(t, i) in voiceForm.tone_variations" :key="i" class="tone-card">
              <button type="button" class="remove-mini" @click="voiceForm.tone_variations.splice(i, 1)"><i class="pi pi-times" /></button>
              <input v-model="t.context" class="brand-input" placeholder="Context (VD: Email khách hàng)" />
              <input v-model="t.tone" class="brand-input sm" placeholder="Tone" />
              <textarea v-model="t.example" class="brand-input" rows="2" placeholder="Ví dụ..." />
            </div>
          </div>
        </div>

        <!-- Writing Guidelines -->
        <div class="glass-card mt-1">
          <h3><i class="pi pi-pencil" style="color:#06b6d4" /> Writing Guidelines</h3>
          <div class="typo-row">
            <div class="form-group">
              <label>Vocabulary (Từ nên dùng)</label>
              <textarea v-model="vocabularyText" class="brand-input" rows="3" placeholder="Một từ mỗi dòng..." />
            </div>
            <div class="form-group">
              <label>Avoid Words (Từ không nên dùng)</label>
              <textarea v-model="avoidText" class="brand-input" rows="3" placeholder="Một từ mỗi dòng..." />
            </div>
          </div>
        </div>

        <div class="form-footer"><button type="submit" class="btn-primary" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" /> Lưu Voice</button></div>
      </form>
    </div>

    <!-- ═══ TAB: BRAND ASSETS ═══ -->
    <div v-show="activeTab === 'assets'" class="tab-content">
      <!-- Upload -->
      <div class="glass-card">
        <h3><i class="pi pi-upload" style="color:#10b981" /> Upload Asset</h3>
        <form @submit.prevent="uploadAsset" class="upload-form">
          <div class="typo-row">
            <div class="form-group">
              <label>Tên</label>
              <input v-model="assetForm.name" class="brand-input" placeholder="Logo chính, Icon app..." required />
            </div>
            <div class="form-group">
              <label>Category</label>
              <select v-model="assetForm.category" class="brand-input" required>
                <option v-for="(label, key) in assetCategories" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>
          <div class="upload-area">
            <label class="drop-zone" @dragover.prevent @drop.prevent="handleDrop">
              <i class="pi pi-cloud-upload" />
              <span>Kéo thả file hoặc <strong>nhấn để chọn</strong></span>
              <span class="drop-hint">PNG, SVG, PDF, JPG — tối đa 20MB</span>
              <input type="file" class="hidden" @change="assetForm.file = $event.target.files[0]" accept="image/*,.pdf,.svg" />
            </label>
            <span v-if="assetForm.file" class="file-name"><i class="pi pi-file" /> {{ assetForm.file.name }}</span>
          </div>
          <button type="submit" class="btn-primary" :disabled="!assetForm.file || !assetForm.name"><i class="pi pi-upload" /> Upload</button>
        </form>
      </div>

      <!-- Asset Grid -->
      <div class="glass-card mt-1">
        <div class="card-header-row">
          <h3><i class="pi pi-folder-open" style="color:#8b5cf6" /> Asset Library ({{ brand.assets?.length || 0 }})</h3>
          <div class="filter-chips">
            <button :class="['chip', !assetFilter && 'active']" @click="assetFilter = null">Tất cả</button>
            <button v-for="(label, key) in assetCategories" :key="key" :class="['chip', assetFilter === key && 'active']" @click="assetFilter = key">{{ label }}</button>
          </div>
        </div>
        <div class="asset-grid">
          <div v-for="asset in filteredAssets" :key="asset.id" class="asset-card">
            <div class="asset-preview">
              <img v-if="isImage(asset.file_type)" :src="asset.file_path" :alt="asset.name" />
              <i v-else class="pi pi-file" />
            </div>
            <div class="asset-info">
              <span class="asset-name">{{ asset.name }}</span>
              <span class="asset-meta">{{ asset.file_type.toUpperCase() }} · {{ formatSize(asset.file_size) }}</span>
            </div>
            <button class="remove-mini" @click="deleteAsset(asset)"><i class="pi pi-trash" /></button>
          </div>
          <p v-if="!filteredAssets.length" class="no-data">Chưa có assets</p>
        </div>
      </div>

      <!-- Audit Log -->
      <div class="glass-card mt-1">
        <h3><i class="pi pi-history" style="color:#94a3b8" /> Lịch sử thay đổi</h3>
        <div class="log-list">
          <div v-for="log in recentLogs" :key="log.id" class="log-item">
            <div class="log-dot" :class="log.action" />
            <div>
              <span class="log-action">{{ actionLabels[log.action] || log.action }}</span>
              <span class="log-section">{{ log.section }}</span>
            </div>
            <span class="log-meta">{{ log.user_name }} · {{ log.created_at }}</span>
          </div>
          <p v-if="!recentLogs.length" class="no-data">Chưa có hoạt động</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { brand: Object, assetStats: Object, recentLogs: Array, assetCategories: Object },
  data() {
    return {
      activeTab: 'foundation',
      saving: false,
      assetFilter: null,
      tabs: [
        { key: 'foundation', label: 'Brand Strategy', icon: 'pi pi-flag' },
        { key: 'visual', label: 'Visual Identity', icon: 'pi pi-palette' },
        { key: 'voice', label: 'Brand Voice', icon: 'pi pi-volume-up' },
        { key: 'assets', label: 'Assets', icon: 'pi pi-folder' },
      ],
      statusLabels: { draft: 'Draft', active: 'Published', archived: 'Archived' },
      actionLabels: { created: 'Tạo mới', updated: 'Cập nhật', asset_uploaded: 'Upload asset', asset_deleted: 'Xóa asset', published: 'Publish' },
      fontOptions: ['Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins', 'Lato', 'Nunito', 'Raleway', 'Playfair Display', 'Oswald', 'Merriweather', 'Source Sans 3', 'Outfit', 'DM Sans', 'Space Grotesk', 'Be Vietnam Pro'],
      logoVariants: [
        { key: 'logo_primary', label: 'Primary Logo', dark: false },
        { key: 'logo_horizontal', label: 'Horizontal', dark: false },
        { key: 'logo_icon', label: 'Icon Only', dark: false },
        { key: 'logo_white', label: 'White Version', dark: true },
      ],
      coreFields: [
        { key: 'brand_purpose', label: 'Brand Purpose', hint: 'Tại sao thương hiệu tồn tại?', placeholder: 'Chúng tôi tồn tại để...', icon: 'pi pi-compass', color: '#8b5cf6' },
        { key: 'brand_vision', label: 'Brand Vision', hint: 'Tương lai bạn muốn tạo ra?', placeholder: 'Trở thành...', icon: 'pi pi-eye', color: '#06b6d4' },
        { key: 'brand_mission', label: 'Brand Mission', hint: 'Bạn làm gì cho ai?', placeholder: 'Chúng tôi giúp...', icon: 'pi pi-flag', color: '#10b981' },
        { key: 'brand_promise', label: 'Brand Promise', hint: 'Cam kết với khách hàng?', placeholder: 'Khách hàng luôn có thể...', icon: 'pi pi-shield', color: '#f59e0b' },
      ],
      foundationForm: {
        brand_purpose: this.$props.brand.brand_purpose || '',
        brand_vision: this.$props.brand.brand_vision || '',
        brand_mission: this.$props.brand.brand_mission || '',
        brand_promise: this.$props.brand.brand_promise || '',
        tagline: this.$props.brand.tagline || '',
        brand_values: this.$props.brand.brand_values || [],
        brand_personality: this.$props.brand.brand_personality || [],
        brand_positioning: this.$props.brand.brand_positioning || {},
        value_propositions: this.$props.brand.value_propositions || [],
      },
      visualForm: {
        primary_colors: this.$props.brand.primary_colors || [],
        secondary_colors: this.$props.brand.secondary_colors || [],
        neutral_colors: this.$props.brand.neutral_colors || [],
        font_primary: this.$props.brand.font_primary || 'Inter',
        font_secondary: this.$props.brand.font_secondary || 'Inter',
        font_config: this.$props.brand.font_config || {},
        logo_guidelines: this.$props.brand.logo_guidelines || {},
      },
      voiceForm: {
        voice_traits: this.$props.brand.voice_traits || [],
        tone_variations: this.$props.brand.tone_variations || [],
        writing_guidelines: this.$props.brand.writing_guidelines || { vocabulary: [], avoid_words: [] },
      },
      assetForm: { name: '', category: 'logo', file: null },
    }
  },
  computed: {
    completionDash() {
      const circ = 2 * Math.PI * 20
      return `${(this.brand.completion_score / 100) * circ} ${circ}`
    },
    radarPoints() {
      const pts = this.foundationForm.brand_personality || []
      if (pts.length < 3) return ''
      const cx = 100, cy = 100, max = 80
      return pts.map((p, i) => {
        const angle = (Math.PI * 2 * i) / pts.length - Math.PI / 2
        const r = (p.score / 10) * max
        return `${cx + r * Math.cos(angle)},${cy + r * Math.sin(angle)}`
      }).join(' ')
    },
    vocabularyText: {
      get() { return (this.voiceForm.writing_guidelines?.vocabulary || []).join('\n') },
      set(v) { this.voiceForm.writing_guidelines = { ...this.voiceForm.writing_guidelines, vocabulary: v.split('\n').filter(Boolean) } },
    },
    avoidText: {
      get() { return (this.voiceForm.writing_guidelines?.avoid_words || []).join('\n') },
      set(v) { this.voiceForm.writing_guidelines = { ...this.voiceForm.writing_guidelines, avoid_words: v.split('\n').filter(Boolean) } },
    },
    filteredAssets() {
      const assets = this.brand.assets || []
      return this.assetFilter ? assets.filter(a => a.category === this.assetFilter) : assets
    },
  },
  methods: {
    radarLabelPos(i) {
      const pts = this.foundationForm.brand_personality
      const angle = (Math.PI * 2 * i) / pts.length - Math.PI / 2
      return { x: 100 + 95 * Math.cos(angle), y: 100 + 95 * Math.sin(angle) }
    },
    addValue() { this.foundationForm.brand_values.push({ name: '', description: '', icon: 'pi pi-heart' }) },
    addPersonality() { this.foundationForm.brand_personality.push({ trait: '', score: 5 }) },
    addProp() { this.foundationForm.value_propositions.push({ title: '', description: '' }) },
    addColor(key) { this.visualForm[key].push({ name: '', hex: '#6366f1' }) },
    addVoiceTrait() { this.voiceForm.voice_traits.push({ trait: '', description: '', do_example: '', dont_example: '' }) },
    addTone() { this.voiceForm.tone_variations.push({ context: '', tone: '', example: '' }) },
    saveFoundation() { this.saving = true; this.$inertia.post('/brand-foundation/foundation', this.foundationForm, { preserveScroll: true, onFinish: () => this.saving = false }) },
    saveVisual() { this.saving = true; this.$inertia.post('/brand-foundation/visual', this.visualForm, { preserveScroll: true, onFinish: () => this.saving = false }) },
    saveVoice() { this.saving = true; this.$inertia.post('/brand-foundation/voice', this.voiceForm, { preserveScroll: true, onFinish: () => this.saving = false }) },
    publish() { if (confirm('Publish Brand Guidelines?')) this.$inertia.post('/brand-foundation/publish', {}, { preserveScroll: true }) },
    uploadLogo(variant, e) {
      const file = e.target.files[0]
      if (!file) return
      const fd = new FormData()
      fd.append('variant', variant)
      fd.append('file', file)
      this.$inertia.post('/brand-foundation/upload-logo', fd, { preserveScroll: true, forceFormData: true })
    },
    uploadAsset() {
      const fd = new FormData()
      fd.append('name', this.assetForm.name)
      fd.append('category', this.assetForm.category)
      fd.append('file', this.assetForm.file)
      this.$inertia.post('/brand-foundation/assets', fd, {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { this.assetForm = { name: '', category: 'logo', file: null } },
      })
    },
    deleteAsset(asset) { if (confirm(`Xóa "${asset.name}"?`)) this.$inertia.delete(`/brand-foundation/assets/${asset.id}`, { preserveScroll: true }) },
    handleDrop(e) { this.assetForm.file = e.dataTransfer.files[0] },
    isImage(type) { return ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'].includes(type) },
    formatSize(b) { return b >= 1048576 ? (b/1048576).toFixed(1)+' MB' : b >= 1024 ? (b/1024).toFixed(0)+' KB' : b+' B' },
    shapeStyle(n) {
      const size = 40 + Math.random() * 120
      return { width: size+'px', height: size+'px', left: Math.random()*100+'%', top: Math.random()*100+'%', animationDelay: n*0.4+'s', animationDuration: (12+Math.random()*10)+'s' }
    },
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
.brand-page { font-family:'Inter', sans-serif; }

/* ═══ HERO ═══ */
.hero { position:relative; overflow:hidden; border-radius:20px; padding:2.25rem 2.5rem 2rem; margin-bottom:1.25rem; background:linear-gradient(135deg, #0c0a1a 0%, #1e1145 40%, #3b1d8e 100%); color:white; min-height:180px }
.hero-bg { position:absolute; inset:0; overflow:hidden }
.hero-shape { position:absolute; border-radius:50%; background:rgba(139,92,246,.08); border:1px solid rgba(139,92,246,.12); animation:shapeDrift linear infinite }
@keyframes shapeDrift { 0% { transform:translateY(0) rotate(0) } 100% { transform:translateY(-60px) rotate(180deg) } }
.hero-content { position:relative; z-index:2 }
.hero-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.52rem; font-weight:700; letter-spacing:.1em; padding:.3rem .7rem; border-radius:16px; background:rgba(139,92,246,.18); border:1px solid rgba(139,92,246,.25); color:#c4b5fd; margin-bottom:.6rem }
.hero-badge i { font-size:.48rem }
.hero h1 { font-size:1.85rem; font-weight:900; margin:0 0 .3rem; letter-spacing:-.03em }
.gradient-text { background:linear-gradient(135deg, #a78bfa, #f0abfc); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text }
.hero p { font-size:.8rem; color:#a5b4fc; margin:0 0 1rem; max-width:400px }
.hero-meta { display:flex; align-items:center; gap:.75rem }
.completion-ring { position:relative; width:48px; height:48px }
.completion-ring svg { width:100%; height:100% }
.ring-pct { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:800; color:white }
.hero-stat-lbl { display:block; font-size:.5rem; color:#a5b4fc; text-transform:uppercase; letter-spacing:.04em }
.status-chip { font-size:.48rem; font-weight:700; padding:.15rem .4rem; border-radius:5px; text-transform:uppercase }
.status-chip.draft { background:rgba(251,191,36,.15); color:#fbbf24 }
.status-chip.active { background:rgba(16,185,129,.15); color:#10b981 }
.hero-actions { position:absolute; top:2rem; right:2rem; z-index:3 }
.btn-glow { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.3rem; font-size:.75rem; font-weight:700; border:none; border-radius:11px; color:white; cursor:pointer; font-family:inherit; background:linear-gradient(135deg, #7c3aed, #a855f7); box-shadow:0 4px 16px rgba(139,92,246,.4); transition:all .2s }
.btn-glow:hover { transform:translateY(-2px); box-shadow:0 6px 24px rgba(139,92,246,.5) }

/* ═══ TAB NAV ═══ */
.tab-nav { display:flex; gap:.3rem; margin-bottom:1rem; background:#f8f9fb; border-radius:14px; padding:.3rem; border:1px solid #e8ecf2 }
.tab-btn { flex:1; display:flex; align-items:center; justify-content:center; gap:.4rem; padding:.6rem .75rem; border:none; border-radius:10px; background:transparent; font-size:.72rem; font-weight:600; color:#64748b; cursor:pointer; transition:all .25s; font-family:inherit; position:relative }
.tab-btn:hover { color:#475569; background:white }
.tab-btn.active { background:white; color:#7c3aed; box-shadow:0 2px 8px rgba(0,0,0,.06) }
.tab-btn i { font-size:.65rem }
.tab-done { position:absolute; top:4px; right:6px; width:14px; height:14px; border-radius:50%; background:#10b981; color:white; display:flex; align-items:center; justify-content:center; font-size:.4rem }

/* ═══ GLASS CARDS ═══ */
.glass-card { background:white; border:1px solid #e8ecf2; border-radius:18px; padding:1.35rem; transition:all .25s }
.glass-card:hover { border-color:#ddd6fe }
.mt-1 { margin-top:.85rem }
.glass-card h3 { font-size:.88rem; font-weight:800; color:#0f172a; margin:0 0 .7rem; display:flex; align-items:center; gap:.4rem }
.glass-card h3 i { font-size:.78rem }
.card-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem; margin-bottom:.6rem }
.card-hint { font-size:.65rem; color:#94a3b8; margin:-.3rem 0 .6rem }
.card-header-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:.65rem }
.section-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.85rem }

/* ═══ FORM ELEMENTS ═══ */
.brand-input { width:100%; padding:.5rem .65rem; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.75rem; font-family:inherit; color:#1e293b; background:#fafbfc; transition:all .2s; outline:none; resize:vertical }
.brand-input:focus { border-color:#8b5cf6; background:white; box-shadow:0 0 0 3px rgba(139,92,246,.08) }
.brand-input.sm { padding:.35rem .5rem; font-size:.68rem }
.form-group { flex:1; margin-bottom:.6rem }
.form-group label { display:block; font-size:.65rem; font-weight:600; color:#475569; margin-bottom:.25rem }
.form-footer { display:flex; justify-content:flex-end; margin-top:1rem }
.btn-primary { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.3rem; font-size:.75rem; font-weight:700; border:none; border-radius:10px; background:linear-gradient(135deg, #7c3aed, #a855f7); color:white; cursor:pointer; font-family:inherit; transition:all .2s; box-shadow:0 2px 10px rgba(139,92,246,.3) }
.btn-primary:hover { transform:translateY(-1px) }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none }
.add-btn { display:inline-flex; align-items:center; gap:.25rem; padding:.3rem .65rem; font-size:.62rem; font-weight:700; border:1.5px dashed #c4b5fd; border-radius:8px; background:transparent; color:#7c3aed; cursor:pointer; font-family:inherit; transition:all .15s }
.add-btn:hover { background:#ede9fe; border-style:solid }
.remove-mini { position:absolute; top:.35rem; right:.35rem; width:20px; height:20px; border-radius:6px; border:none; background:#fef2f2; color:#ef4444; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:.48rem; transition:all .15s; opacity:.5 }
.remove-mini:hover { opacity:1; background:#fee2e2 }

/* ═══ VALUES ═══ */
.values-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:.6rem }
.value-card { position:relative; background:#faf5ff; border:1px solid #ede9fe; border-radius:12px; padding:.75rem; display:flex; flex-direction:column; gap:.35rem }

/* ═══ PERSONALITY ═══ */
.personality-section { display:flex; gap:1.5rem; align-items:flex-start }
.personality-sliders { flex:1 }
.personality-row { display:flex; align-items:center; gap:.5rem; margin-bottom:.4rem; position:relative; padding-left:1.5rem }
.trait-input { width:100px; padding:.3rem .5rem; border:1px solid #e2e8f0; border-radius:6px; font-size:.68rem; font-family:inherit }
.range-slider { flex:1; accent-color:#8b5cf6 }
.score-val { font-size:.72rem; font-weight:800; color:#7c3aed; min-width:20px; text-align:center }
.radar-chart { width:200px; flex-shrink:0 }
.radar-chart svg { width:100% }

/* ═══ COLORS ═══ */
.color-grid { display:flex; flex-wrap:wrap; gap:.65rem }
.color-card { position:relative; display:flex; flex-direction:column; align-items:center; gap:.3rem; min-width:90px }
.color-preview { width:64px; height:64px; border-radius:14px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,.1); transition:all .2s; border:3px solid white }
.color-preview:hover { transform:scale(1.08) }
.color-picker-hidden { position:absolute; opacity:0; width:0; height:0 }
.color-hex { font-size:.52rem; font-weight:600; color:#94a3b8; font-family:monospace }

/* ═══ TYPOGRAPHY ═══ */
.typo-row { display:flex; gap:.75rem }
.typo-preview { background:#f8f9fb; border:1px solid #e8ecf2; border-radius:12px; padding:1rem; margin-top:.65rem }
.typo-preview h2 { font-size:1.35rem; font-weight:800; color:#0f172a; margin:0 0 .4rem }
.typo-preview p { font-size:.78rem; color:#475569; margin:0 0 .65rem; line-height:1.6 }
.typo-scale { display:flex; gap:1rem; align-items:baseline; color:#8b5cf6 }

/* ═══ LOGO GRID ═══ */
.logo-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:.65rem }
.logo-slot { text-align:center }
.logo-preview { width:100%; aspect-ratio:16/10; border-radius:12px; background:#f8f9fb; border:1.5px dashed #e2e8f0; display:flex; align-items:center; justify-content:center; overflow:hidden }
.logo-preview.dark-bg { background:#1e293b }
.logo-preview img { max-width:85%; max-height:85%; object-fit:contain }
.logo-preview i { font-size:1.5rem; color:#cbd5e1 }
.logo-label { display:block; font-size:.55rem; font-weight:600; color:#64748b; margin:.35rem 0 .25rem }
.upload-mini-btn { display:inline-flex; align-items:center; gap:.2rem; font-size:.52rem; font-weight:600; color:#7c3aed; cursor:pointer; padding:.2rem .4rem; border-radius:5px; transition:all .15s }
.upload-mini-btn:hover { background:#ede9fe }
.hidden { display:none }

/* ═══ VOICE ═══ */
.voice-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:.75rem }
.voice-card { position:relative; background:#faf5ff; border:1px solid #ede9fe; border-radius:14px; padding:.85rem; display:flex; flex-direction:column; gap:.4rem }
.do-dont-row { display:flex; gap:.5rem }
.do-col, .dont-col { flex:1 }
.do-label, .dont-label { display:flex; align-items:center; gap:.2rem; font-size:.55rem; font-weight:700; margin-bottom:.2rem }
.do-label { color:#10b981 }
.dont-label { color:#ef4444 }
.tone-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:.65rem }
.tone-card { position:relative; background:#fffbeb; border:1px solid #fef3c7; border-radius:12px; padding:.75rem; display:flex; flex-direction:column; gap:.35rem }

/* ═══ ASSETS ═══ */
.upload-form { display:flex; flex-direction:column; gap:.6rem }
.drop-zone { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1.5rem; border:2px dashed #c4b5fd; border-radius:14px; cursor:pointer; text-align:center; transition:all .2s; color:#64748b; font-size:.72rem }
.drop-zone:hover { background:#faf5ff; border-color:#8b5cf6 }
.drop-zone i { font-size:1.5rem; color:#8b5cf6; margin-bottom:.35rem }
.drop-hint { font-size:.52rem; color:#94a3b8; margin-top:.2rem }
.file-name { display:flex; align-items:center; gap:.3rem; font-size:.68rem; color:#7c3aed; font-weight:600 }
.file-name i { font-size:.55rem }
.filter-chips { display:flex; gap:.25rem; flex-wrap:wrap }
.chip { padding:.25rem .55rem; border:1px solid #e2e8f0; border-radius:7px; background:white; font-size:.52rem; font-weight:600; color:#64748b; cursor:pointer; transition:all .15s; font-family:inherit }
.chip:hover { border-color:#c4b5fd }
.chip.active { background:#ede9fe; color:#7c3aed; border-color:#c4b5fd }
.asset-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:.65rem; margin-top:.65rem }
.asset-card { position:relative; background:#f8f9fb; border:1px solid #e8ecf2; border-radius:12px; overflow:hidden; transition:all .2s }
.asset-card:hover { border-color:#c4b5fd; box-shadow:0 4px 12px rgba(0,0,0,.04) }
.asset-preview { aspect-ratio:1; display:flex; align-items:center; justify-content:center; background:#f1f5f9; overflow:hidden }
.asset-preview img { width:100%; height:100%; object-fit:cover }
.asset-preview i { font-size:1.5rem; color:#94a3b8 }
.asset-info { padding:.5rem .6rem }
.asset-name { display:block; font-size:.68rem; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.asset-meta { font-size:.48rem; color:#94a3b8 }
.no-data { text-align:center; padding:1.5rem; font-size:.72rem; color:#94a3b8 }

/* ═══ AUDIT LOG ═══ */
.log-list { max-height:300px; overflow-y:auto }
.log-item { display:flex; align-items:center; gap:.5rem; padding:.4rem 0; border-bottom:1px solid #f8f9fb }
.log-item:last-child { border-bottom:none }
.log-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0 }
.log-dot.created { background:#8b5cf6 }
.log-dot.updated { background:#06b6d4 }
.log-dot.asset_uploaded { background:#10b981 }
.log-dot.asset_deleted { background:#ef4444 }
.log-dot.published { background:#f59e0b }
.log-action { font-size:.68rem; font-weight:600; color:#1e293b }
.log-section { font-size:.52rem; color:#94a3b8; margin-left:.25rem; background:#f1f5f9; padding:.1rem .3rem; border-radius:4px }
.log-meta { margin-left:auto; font-size:.48rem; color:#94a3b8; white-space:nowrap }

@media (max-width:768px) {
  .section-grid { grid-template-columns:1fr }
  .logo-grid { grid-template-columns:repeat(2, 1fr) }
  .personality-section { flex-direction:column }
  .radar-chart { width:100% }
  .typo-row { flex-direction:column }
  .voice-grid, .tone-grid { grid-template-columns:1fr }
  .tab-btn span { display:none }
  .do-dont-row { flex-direction:column }
}
</style>
