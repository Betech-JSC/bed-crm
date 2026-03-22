<template>
  <div>
    <Head :title="isVi ? 'Cài đặt hệ thống' : 'System Settings'" />

    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-cog" />
        </div>
        <div>
          <h1 class="page-title">{{ isVi ? 'Cài đặt hệ thống' : 'System Settings' }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Cấu hình công ty, nhận diện thương hiệu, ngôn ngữ và tiền tệ' : 'Configure company, branding, language, and currency' }}</p>
        </div>
      </div>
      <div class="header-actions">
        <Button :label="isVi ? 'Khởi tạo mặc định' : 'Seed Defaults'" icon="pi pi-refresh" severity="secondary" outlined @click="seedDefaults" />
      </div>
    </div>

    <TabView v-model:activeIndex="activeTab">
      <!-- ─── Tab 1: Company Profile ─── -->
      <TabPanel :header="isVi ? '🏢 Hồ sơ công ty' : '🏢 Company Profile'">
        <form @submit.prevent="saveProfile" class="settings-form">
          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Thông tin doanh nghiệp' : 'Business Info' }}</h3>
            <div class="form-row">
              <div class="form-group flex-2">
                <label>{{ isVi ? 'Tên doanh nghiệp' : 'Company Name' }} *</label>
                <InputText v-model="form.name" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Mã số thuế' : 'Tax ID' }}</label>
                <InputText v-model="form.tax_id" class="w-full" placeholder="VD: 0123456789" />
              </div>
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Slogan / Khẩu hiệu' : 'Slogan / Tagline' }}</label>
              <InputText v-model="form.slogan" class="w-full" :placeholder="isVi ? 'VD: Giải pháp CRM thông minh cho doanh nghiệp' : 'Your company tagline'" />
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Mô tả doanh nghiệp' : 'Company Description' }}</label>
              <Textarea v-model="form.description" rows="3" class="w-full" :placeholder="isVi ? 'Giới thiệu ngắn về doanh nghiệp của bạn...' : 'Brief description about your business...'" />
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Ngành nghề' : 'Industry' }}</label>
                <Dropdown v-model="form.industry" :options="industryOptions" optionLabel="label" optionValue="value" :placeholder="isVi ? 'Chọn...' : 'Select...'" class="w-full" showClear />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Quy mô nhân sự' : 'Company Size' }}</label>
                <InputText v-model.number="form.company_size" type="number" class="w-full" :placeholder="isVi ? 'Số nhân viên' : 'Number of employees'" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Năm thành lập' : 'Founded Year' }}</label>
                <InputText v-model="form.founded_year" class="w-full" placeholder="VD: 2020" maxlength="4" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Số ĐKKD / GPKD' : 'Registration No.' }}</label>
                <InputText v-model="form.registration_number" class="w-full" :placeholder="isVi ? 'Giấy phép kinh doanh' : 'Business Registration'" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Thông tin liên hệ' : 'Contact Info' }}</h3>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ t('common.email') }}</label>
                <InputText v-model="form.email" type="email" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Điện thoại' : 'Phone' }}</label>
                <InputText v-model="form.phone" class="w-full" />
              </div>
            </div>
            <div class="form-group">
              <label>{{ t('common.website') }}</label>
              <InputText v-model="form.website" class="w-full" placeholder="https://..." />
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Địa chỉ' : 'Address' }}</label>
              <Textarea v-model="form.address" rows="2" class="w-full" />
            </div>
          </div>

          <!-- Social Links -->
          <div class="form-section">
            <h3 class="form-section-title"><i class="pi pi-share-alt" /> {{ isVi ? 'Mạng xã hội' : 'Social Links' }}</h3>
            <div class="form-row">
              <div class="form-group flex-1">
                <label><i class="pi pi-facebook social-icon si-fb" /> Facebook</label>
                <InputText v-model="form.social_links.facebook" class="w-full" placeholder="https://facebook.com/..." />
              </div>
              <div class="form-group flex-1">
                <label><i class="pi pi-linkedin social-icon si-li" /> LinkedIn</label>
                <InputText v-model="form.social_links.linkedin" class="w-full" placeholder="https://linkedin.com/company/..." />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label><i class="pi pi-twitter social-icon si-tw" /> Twitter / X</label>
                <InputText v-model="form.social_links.twitter" class="w-full" placeholder="https://x.com/..." />
              </div>
              <div class="form-group flex-1">
                <label><i class="pi pi-youtube social-icon si-yt" /> YouTube</label>
                <InputText v-model="form.social_links.youtube" class="w-full" placeholder="https://youtube.com/@..." />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label><i class="pi pi-instagram social-icon si-ig" /> Instagram</label>
                <InputText v-model="form.social_links.instagram" class="w-full" placeholder="https://instagram.com/..." />
              </div>
              <div class="form-group flex-1">
                <label><i class="pi pi-stopwatch social-icon si-tk" /> TikTok</label>
                <InputText v-model="form.social_links.tiktok" class="w-full" placeholder="https://tiktok.com/@..." />
              </div>
            </div>
          </div>

          <!-- Logo & Favicon -->
          <div class="form-section">
            <h3 class="form-section-title"><i class="pi pi-palette" /> {{ isVi ? 'Nhận diện thương hiệu' : 'Branding' }}</h3>

            <div class="branding-grid">
              <!-- Logo -->
              <div class="branding-card">
                <div class="branding-label">Logo</div>
                <div class="branding-desc">{{ isVi ? 'Logo chính hiển thị trên sidebar và tài liệu' : 'Main logo displayed on sidebar & documents' }}</div>
                <div class="branding-preview-area">
                  <div class="branding-preview logo-prev" v-if="account.logo || logoPreview">
                    <img :src="logoPreview || account.logo" alt="Logo" />
                  </div>
                  <div class="branding-placeholder" v-else>
                    <i class="pi pi-image" />
                    <span>{{ isVi ? 'Chưa có logo' : 'No logo' }}</span>
                  </div>
                </div>
                <div class="branding-actions">
                  <input type="file" ref="logoInput" accept="image/*" @change="onLogoChange" class="hidden" />
                  <Button :label="isVi ? 'Tải lên' : 'Upload'" icon="pi pi-upload" size="small" severity="secondary" outlined @click="$refs.logoInput.click()" />
                  <Button v-if="account.logo" icon="pi pi-trash" size="small" text severity="danger" @click="removeLogo" />
                </div>
                <span class="branding-hint">PNG, JPG, SVG · {{ isVi ? 'Tối đa 2MB' : 'Max 2MB' }}</span>
              </div>

              <!-- Favicon -->
              <div class="branding-card">
                <div class="branding-label">Favicon</div>
                <div class="branding-desc">{{ isVi ? 'Icon nhỏ hiển thị trên tab trình duyệt' : 'Small icon shown on browser tab' }}</div>
                <div class="branding-preview-area">
                  <div class="branding-preview favicon-prev" v-if="account.favicon || faviconPreview">
                    <img :src="faviconPreview || account.favicon" alt="Favicon" />
                  </div>
                  <div class="branding-placeholder" v-else>
                    <i class="pi pi-globe" />
                    <span>{{ isVi ? 'Chưa có favicon' : 'No favicon' }}</span>
                  </div>
                </div>
                <div class="branding-actions">
                  <input type="file" ref="faviconInput" accept="image/png,image/x-icon,image/svg+xml,image/jpeg" @change="onFaviconChange" class="hidden" />
                  <Button :label="isVi ? 'Tải lên' : 'Upload'" icon="pi pi-upload" size="small" severity="secondary" outlined @click="$refs.faviconInput.click()" />
                  <Button v-if="account.favicon" icon="pi pi-trash" size="small" text severity="danger" @click="removeFavicon" />
                </div>
                <span class="branding-hint">PNG, ICO, SVG · {{ isVi ? 'Tối đa 1MB · Tốt nhất 32×32px' : 'Max 1MB · Best 32×32px' }}</span>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <Button type="submit" :label="isVi ? 'Lưu hồ sơ doanh nghiệp' : 'Save Business Profile'" icon="pi pi-check" :loading="saving" />
          </div>
        </form>
      </TabPanel>

      <!-- ─── Tab 2: Regional Settings ─── -->
      <TabPanel :header="isVi ? '🌍 Vùng & Ngôn ngữ' : '🌍 Regional'">
        <form @submit.prevent="saveProfile" class="settings-form">
          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Ngôn ngữ & Định dạng' : 'Language & Format' }}</h3>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Ngôn ngữ mặc định' : 'Default Language' }}</label>
                <Dropdown v-model="form.locale" :options="localeOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Múi giờ' : 'Timezone' }}</label>
                <Dropdown v-model="form.timezone" :options="timezoneOptions" optionLabel="label" optionValue="value" class="w-full" filter />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Tiền tệ' : 'Currency' }}</label>
                <Dropdown v-model="form.currency" :options="currencyOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Định dạng ngày' : 'Date Format' }}</label>
                <Dropdown v-model="form.date_format" :options="dateFormatOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Định dạng giờ' : 'Time Format' }}</label>
                <Dropdown v-model="form.time_format" :options="timeFormatOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Năm tài chính bắt đầu' : 'Fiscal Year Start' }}</label>
                <InputText v-model="form.fiscal_year_start" class="w-full" placeholder="MM-DD (01-01)" />
              </div>
            </div>
          </div>

          <div class="form-actions">
            <Button type="submit" :label="isVi ? 'Lưu cài đặt' : 'Save Settings'" icon="pi pi-check" :loading="saving" />
          </div>
        </form>
      </TabPanel>

      <!-- ─── Tab 3: CRM Config ─── -->
      <TabPanel :header="isVi ? '📊 CRM' : '📊 CRM'">
        <div class="settings-form">
          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Cấu hình CRM' : 'CRM Configuration' }}</h3>
            <div class="config-item">
              <div class="config-info">
                <span class="config-label">{{ isVi ? 'Tự phân công lead' : 'Auto-assign Leads' }}</span>
                <span class="config-desc">{{ isVi ? 'Tự động phân lead cho nhân viên kinh doanh' : 'Automatically assign new leads to sales reps' }}</span>
              </div>
              <InputSwitch v-model="crmConfigs.lead_auto_assign" @change="saveCrmConfig('lead_auto_assign', crmConfigs.lead_auto_assign)" />
            </div>
            <div class="config-item">
              <div class="config-info">
                <span class="config-label">{{ isVi ? 'Tiền tệ deal mặc định' : 'Default Deal Currency' }}</span>
                <span class="config-desc">{{ isVi ? 'Tiền tệ mặc định khi tạo deal mới' : 'Default currency when creating new deals' }}</span>
              </div>
              <Dropdown v-model="crmConfigs.default_deal_currency" :options="currencyOptions" optionLabel="label" optionValue="value" style="width: 160px" @change="saveCrmConfig('default_deal_currency', crmConfigs.default_deal_currency)" />
            </div>
          </div>
        </div>
      </TabPanel>

      <!-- ─── Tab 4: Appearance ─── -->
      <TabPanel :header="isVi ? '🎨 Giao diện' : '🎨 Appearance'">
        <div class="settings-form">
          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Tuỳ chỉnh giao diện' : 'Appearance Customization' }}</h3>
            <div class="config-item">
              <div class="config-info">
                <span class="config-label">{{ isVi ? 'Màu chủ đạo' : 'Primary Color' }}</span>
                <span class="config-desc">{{ isVi ? 'Màu thương hiệu chính' : 'Main brand color' }}</span>
              </div>
              <div class="color-picker">
                <input type="color" v-model="appearanceConfigs.primary_color" @change="saveAppearanceConfig('primary_color', appearanceConfigs.primary_color)" />
                <span class="color-hex">{{ appearanceConfigs.primary_color }}</span>
              </div>
            </div>
            <div class="config-item">
              <div class="config-info">
                <span class="config-label">{{ isVi ? 'Giao diện sidebar' : 'Sidebar Theme' }}</span>
                <span class="config-desc">{{ isVi ? 'Sáng hoặc tối' : 'Light or dark' }}</span>
              </div>
              <Dropdown v-model="appearanceConfigs.sidebar_theme" :options="[{ label: isVi ? 'Tối' : 'Dark', value: 'dark' }, { label: isVi ? 'Sáng' : 'Light', value: 'light' }]" optionLabel="label" optionValue="value" style="width: 140px" @change="saveAppearanceConfig('sidebar_theme', appearanceConfigs.sidebar_theme)" />
            </div>
          </div>
        </div>
      </TabPanel>
    </TabView>
  </div>
</template>

<script>
import { Head, router, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import InputSwitch from 'primevue/inputswitch'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Button, InputText, Textarea, Dropdown, TabView, TabPanel, InputSwitch },
  layout: Layout,
  props: { account: Object, config_groups: Object, options: Object, app_locale: String },
  setup() {
    const { t, locale } = useTranslation()
    return { t, locale }
  },
  data() {
    return {
      activeTab: 0,
      saving: false,
      form: {
        name: this.account.name,
        slogan: this.account.slogan || '',
        description: this.account.description || '',
        timezone: this.account.timezone || 'Asia/Ho_Chi_Minh',
        currency: this.account.currency || 'VND',
        locale: this.account.locale || 'vi',
        date_format: this.account.date_format || 'DD/MM/YYYY',
        time_format: this.account.time_format || '24h',
        fiscal_year_start: this.account.fiscal_year_start || '01-01',
        phone: this.account.phone,
        email: this.account.email,
        website: this.account.website,
        address: this.account.address,
        tax_id: this.account.tax_id,
        registration_number: this.account.registration_number || '',
        industry: this.account.industry,
        company_size: this.account.company_size,
        founded_year: this.account.founded_year || '',
        social_links: {
          facebook: this.account.social_links?.facebook || '',
          linkedin: this.account.social_links?.linkedin || '',
          twitter: this.account.social_links?.twitter || '',
          youtube: this.account.social_links?.youtube || '',
          instagram: this.account.social_links?.instagram || '',
          tiktok: this.account.social_links?.tiktok || '',
        },
      },
      logoFile: null,
      logoPreview: null,
      faviconFile: null,
      faviconPreview: null,
      crmConfigs: {
        lead_auto_assign: this.config_groups?.crm?.lead_auto_assign ?? false,
        default_deal_currency: this.config_groups?.crm?.default_deal_currency ?? 'VND',
      },
      appearanceConfigs: {
        primary_color: this.config_groups?.appearance?.primary_color ?? '#6366f1',
        sidebar_theme: this.config_groups?.appearance?.sidebar_theme ?? 'dark',
      },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    timezoneOptions() {
      return Object.entries(this.options.timezones).map(([value, label]) => ({ value, label }))
    },
    currencyOptions() {
      return Object.entries(this.options.currencies).map(([value, c]) => ({
        value, label: `${c.symbol} ${value} — ${this.isVi ? c.name_vi : c.name_en}`,
      }))
    },
    localeOptions() {
      return Object.entries(this.options.locales).map(([value, l]) => ({
        value, label: `${l.flag} ${this.isVi ? l.name_vi : l.name_en}`,
      }))
    },
    dateFormatOptions() {
      return Object.entries(this.options.date_formats).map(([value, label]) => ({
        value, label: `${value} (${label})`,
      }))
    },
    timeFormatOptions() {
      return Object.entries(this.options.time_formats).map(([value, label]) => ({
        value, label: `${value} (${label})`,
      }))
    },
    industryOptions() {
      return Object.entries(this.options.industries).map(([value, l]) => ({
        value, label: this.isVi ? l.vi : l.en,
      }))
    },
  },
  methods: {
    onLogoChange(e) {
      this.logoFile = e.target.files[0]
      if (this.logoFile) {
        this.logoPreview = URL.createObjectURL(this.logoFile)
      }
    },
    onFaviconChange(e) {
      this.faviconFile = e.target.files[0]
      if (this.faviconFile) {
        this.faviconPreview = URL.createObjectURL(this.faviconFile)
      }
    },
    saveProfile() {
      this.saving = true
      const formData = new FormData()

      // Flatten form data
      Object.entries(this.form).forEach(([k, v]) => {
        if (k === 'social_links') {
          Object.entries(v).forEach(([sk, sv]) => {
            if (sv) formData.append(`social_links[${sk}]`, sv)
          })
        } else if (v !== null && v !== undefined && v !== '') {
          formData.append(k, v)
        }
      })

      formData.append('_method', 'PUT')
      if (this.logoFile) formData.append('logo', this.logoFile)
      if (this.faviconFile) formData.append('favicon', this.faviconFile)

      router.post('/account-settings', formData, {
        onFinish: () => { this.saving = false },
        forceFormData: true,
      })
    },
    async removeLogo() {
      await axios.delete('/account-settings/logo')
      router.reload()
    },
    async removeFavicon() {
      await axios.delete('/account-settings/favicon')
      router.reload()
    },
    async seedDefaults() {
      if (!confirm(this.isVi ? 'Khởi tạo cấu hình mặc định?' : 'Initialize default configs?')) return
      await axios.post('/account-settings/seed')
      router.reload()
    },
    async saveCrmConfig(key, value) {
      await axios.put('/account-settings/config/crm', { configs: { [key]: value } })
    },
    async saveAppearanceConfig(key, value) {
      await axios.put('/account-settings/config/appearance', { configs: { [key]: value } })
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-actions { display: flex; gap: 0.5rem; }

/* ===== Form ===== */
.settings-form { max-width: 780px; }
.form-section {
  background: white; border: 1.5px solid #f1f5f9; border-radius: 14px;
  padding: 1.25rem; margin-bottom: 1rem;
  transition: border-color 0.2s;
}
.form-section:hover { border-color: #e2e8f0; }
.form-section-title {
  font-size: 0.88rem; font-weight: 700; color: #1e293b;
  margin: 0 0 0.85rem; padding-bottom: 0.55rem;
  border-bottom: 1.5px solid #f1f5f9;
  display: flex; align-items: center; gap: 0.4rem;
}
.form-section-title i { font-size: 0.85rem; color: #6366f1; }
.form-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.form-group { margin-bottom: 0.75rem; min-width: 0; }
.form-group label {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.72rem; font-weight: 600;
  color: #475569; margin-bottom: 0.3rem;
}
.flex-1 { flex: 1; min-width: 180px; }
.flex-2 { flex: 2; min-width: 220px; }
.w-full { width: 100%; }
.form-actions { margin-top: 0.75rem; }

/* Social icons */
.social-icon { font-size: 0.8rem; }
.si-fb { color: #1877f2; }
.si-li { color: #0a66c2; }
.si-tw { color: #1d9bf0; }
.si-yt { color: #ff0000; }
.si-ig { color: #e4405f; }
.si-tk { color: #010101; }

/* ===== Branding Grid ===== */
.branding-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
}
.branding-card {
  background: #fafbfc; border: 1.5px dashed #e2e8f0; border-radius: 12px;
  padding: 1rem; display: flex; flex-direction: column; align-items: center;
  gap: 0.45rem; text-align: center; transition: all 0.2s;
}
.branding-card:hover { border-color: #6366f1; background: #faf5ff; }
.branding-label { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.branding-desc { font-size: 0.62rem; color: #94a3b8; line-height: 1.3; }
.branding-preview-area {
  margin: 0.5rem 0; width: 100%;
  display: flex; align-items: center; justify-content: center;
}
.branding-preview {
  position: relative; display: inline-block;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  background: white; overflow: hidden;
}
.logo-prev { padding: 0.5rem; }
.logo-prev img { width: 120px; height: 60px; object-fit: contain; }
.favicon-prev { padding: 0.6rem; }
.favicon-prev img { width: 40px; height: 40px; object-fit: contain; image-rendering: pixelated; }
.branding-placeholder {
  display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
  padding: 1.25rem; color: #cbd5e1;
}
.branding-placeholder i { font-size: 1.5rem; }
.branding-placeholder span { font-size: 0.62rem; }
.branding-actions { display: flex; gap: 0.4rem; align-items: center; }
.branding-hint { font-size: 0.55rem; color: #94a3b8; }
.hidden { display: none; }

/* Config items */
.config-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.85rem 0; border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}
.config-item:last-child { border-bottom: 0; }
.config-item:hover { background: #fafbfc; margin: 0 -0.5rem; padding-left: 0.5rem; padding-right: 0.5rem; border-radius: 8px; }
.config-info { display: flex; flex-direction: column; flex: 1; }
.config-label { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.config-desc { font-size: 0.65rem; color: #94a3b8; margin-top: 0.15rem; }

/* Color picker */
.color-picker { display: flex; align-items: center; gap: 0.5rem; }
.color-picker input[type="color"] {
  width: 38px; height: 38px; border: 1.5px solid #e2e8f0;
  border-radius: 10px; padding: 3px; cursor: pointer;
  transition: border-color 0.2s;
}
.color-picker input[type="color"]:hover { border-color: #6366f1; }
.color-hex { font-size: 0.72rem; font-family: monospace; color: #64748b; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .form-row { flex-direction: column; }
  .flex-1, .flex-2 { min-width: 100%; }
  .branding-grid { grid-template-columns: 1fr; }
}
</style>
