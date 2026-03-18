<template>
  <div>
    <Head :title="isVi ? 'Cài đặt hệ thống' : 'System Settings'" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ isVi ? 'Cài đặt hệ thống' : 'System Settings' }}</h1>
        <p class="page-subtitle">{{ isVi ? 'Cấu hình công ty, ngôn ngữ, múi giờ và tiền tệ' : 'Configure company info, language, timezone, and currency' }}</p>
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
            <h3 class="form-section-title">{{ isVi ? 'Thông tin công ty' : 'Company Info' }}</h3>
            <div class="form-row">
              <div class="form-group flex-2">
                <label>{{ isVi ? 'Tên công ty' : 'Company Name' }} *</label>
                <InputText v-model="form.name" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Mã số thuế' : 'Tax ID' }}</label>
                <InputText v-model="form.tax_id" class="w-full" placeholder="VD: 0123456789" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Ngành nghề' : 'Industry' }}</label>
                <Dropdown v-model="form.industry" :options="industryOptions" optionLabel="label" optionValue="value" :placeholder="isVi ? 'Chọn...' : 'Select...'" class="w-full" showClear />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Quy mô' : 'Company Size' }}</label>
                <InputText v-model.number="form.company_size" type="number" class="w-full" :placeholder="isVi ? 'Số nhân viên' : 'Number of employees'" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3 class="form-section-title">{{ isVi ? 'Thông tin liên hệ' : 'Contact Info' }}</h3>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>Email</label>
                <InputText v-model="form.email" type="email" class="w-full" />
              </div>
              <div class="form-group flex-1">
                <label>{{ isVi ? 'Điện thoại' : 'Phone' }}</label>
                <InputText v-model="form.phone" class="w-full" />
              </div>
            </div>
            <div class="form-group">
              <label>Website</label>
              <InputText v-model="form.website" class="w-full" placeholder="https://..." />
            </div>
            <div class="form-group">
              <label>{{ isVi ? 'Địa chỉ' : 'Address' }}</label>
              <Textarea v-model="form.address" rows="2" class="w-full" />
            </div>
          </div>

          <div class="form-section">
            <h3 class="form-section-title">Logo</h3>
            <div class="logo-section">
              <div class="logo-preview" v-if="account.logo">
                <img :src="account.logo" alt="Logo" />
                <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="removeLogo" />
              </div>
              <div class="logo-upload">
                <input type="file" ref="logoInput" accept="image/*" @change="onLogoChange" class="hidden" />
                <Button :label="isVi ? 'Tải logo' : 'Upload Logo'" icon="pi pi-upload" severity="secondary" outlined @click="$refs.logoInput.click()" />
                <span class="logo-hint">{{ isVi ? 'PNG, JPG, tối đa 2MB' : 'PNG, JPG, max 2MB' }}</span>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <Button type="submit" :label="isVi ? 'Lưu hồ sơ' : 'Save Profile'" icon="pi pi-check" :loading="saving" />
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
        industry: this.account.industry,
        company_size: this.account.company_size,
      },
      logoFile: null,
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
    },
    saveProfile() {
      this.saving = true
      const formData = new FormData()
      Object.entries(this.form).forEach(([k, v]) => { if (v !== null && v !== undefined) formData.append(k, v) })
      formData.append('_method', 'PUT')
      if (this.logoFile) formData.append('logo', this.logoFile)

      router.post('/account-settings', formData, {
        onFinish: () => { this.saving = false },
        forceFormData: true,
      })
    },
    async removeLogo() {
      await axios.delete('/account-settings/logo')
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
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; gap: 0.5rem; }

.settings-form { max-width: 680px; }
.form-section { background: white; border: 1px solid #f1f5f9; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
.form-section-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0 0 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid #f8fafc; }
.form-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.form-group { margin-bottom: 0.75rem; min-width: 0; }
.form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem; }
.flex-1 { flex: 1; }
.flex-2 { flex: 2; }
.w-full { width: 100%; }
.form-actions { margin-top: 0.5rem; }

/* Logo */
.logo-section { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.logo-preview { position: relative; display: inline-block; }
.logo-preview img { width: 80px; height: 80px; object-fit: contain; border: 1px solid #f1f5f9; border-radius: 8px; background: #fafbfc; }
.logo-upload { display: flex; align-items: center; gap: 0.5rem; }
.logo-hint { font-size: 0.62rem; color: #94a3b8; }
.hidden { display: none; }

/* Config items */
.config-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f8fafc; }
.config-item:last-child { border-bottom: 0; }
.config-info { display: flex; flex-direction: column; flex: 1; }
.config-label { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
.config-desc { font-size: 0.65rem; color: #94a3b8; margin-top: 0.1rem; }

/* Color picker */
.color-picker { display: flex; align-items: center; gap: 0.5rem; }
.color-picker input[type="color"] { width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2px; cursor: pointer; }
.color-hex { font-size: 0.72rem; font-family: monospace; color: #64748b; }
</style>
