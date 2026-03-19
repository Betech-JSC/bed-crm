<template>
  <div>
    <Head :title="`Edit: ${widget.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit Chat Widget</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Widget Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Widget Key</label>
              <div class="flex items-center gap-2">
                <InputText :value="widget.widget_key" readonly class="flex-1" />
                <Button
                  icon="pi pi-copy"
                  severity="secondary"
                  outlined
                  @click="copyToClipboard(widget.widget_key)"
                />
              </div>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Embed Code</label>
              <div class="flex items-center gap-2">
                <InputText :value="embedCode" readonly class="flex-1 font-mono text-xs" />
                <Button
                  icon="pi pi-copy"
                  severity="secondary"
                  outlined
                  @click="copyToClipboard(embedCode)"
                />
              </div>
              <small class="text-gray-500 mt-1">Copy this code and paste it before the closing &lt;/body&gt; tag on your website</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Welcome Message</label>
              <Textarea
                v-model="form.welcome_message"
                rows="2"
                :class="{ 'p-invalid': form.errors.welcome_message }"
              />
              <small v-if="form.errors.welcome_message" class="p-error">{{ form.errors.welcome_message }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">AI System Prompt</label>
              <Textarea
                v-model="form.system_prompt"
                rows="4"
                :class="{ 'p-invalid': form.errors.system_prompt }"
              />
              <small v-if="form.errors.system_prompt" class="p-error">{{ form.errors.system_prompt }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Primary Color</label>
              <InputText
                v-model="form.primary_color"
                type="color"
                :class="{ 'p-invalid': form.errors.primary_color }"
              />
              <small v-if="form.errors.primary_color" class="p-error">{{ form.errors.primary_color }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Position</label>
              <Select
                v-model="form.position"
                :options="positionOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.position }"
              />
              <small v-if="form.errors.position" class="p-error">{{ form.errors.position }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">AI Model</label>
              <Select
                v-model="form.ai_model"
                :options="modelOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors.ai_model }"
              />
              <small v-if="form.errors.ai_model" class="p-error">{{ form.errors.ai_model }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Temperature</label>
              <InputNumber
                v-model="form.temperature"
                :min="0"
                :max="2"
                :step="0.1"
                :class="{ 'p-invalid': form.errors.temperature }"
              />
              <small v-if="form.errors.temperature" class="p-error">{{ form.errors.temperature }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Max Tokens</label>
              <InputNumber
                v-model="form.max_tokens"
                :min="1"
                :max="4000"
                :class="{ 'p-invalid': form.errors.max_tokens }"
              />
              <small v-if="form.errors.max_tokens" class="p-error">{{ form.errors.max_tokens }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Rate Limit (per hour)</label>
              <InputNumber
                v-model="form.rate_limit_per_hour"
                :min="1"
                :max="1000"
                :class="{ 'p-invalid': form.errors.rate_limit_per_hour }"
              />
              <small v-if="form.errors.rate_limit_per_hour" class="p-error">{{ form.errors.rate_limit_per_hour }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Allowed Domains (optional)</label>
              <div class="space-y-2">
                <div v-for="(domain, index) in form.allowed_domains" :key="index" class="flex items-center gap-2">
                  <InputText
                    v-model="form.allowed_domains[index]"
                    placeholder="example.com"
                    class="flex-1"
                  />
                  <Button
                    icon="pi pi-times"
                    severity="danger"
                    text
                    rounded
                    @click="removeDomain(index)"
                  />
                </div>
                <Button
                  label="Add Domain"
                  icon="pi pi-plus"
                  severity="secondary"
                  outlined
                  size="small"
                  @click="addDomain"
                />
              </div>
              <small class="text-gray-500 mt-1">Leave empty to allow all domains</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <h3 class="text-lg font-semibold mb-4">Banner Sliders</h3>
              <div class="space-y-4">
                <div class="flex items-center">
                  <Checkbox v-model="form.show_banners" inputId="show_banners" :binary="true" />
                  <label for="show_banners" class="ml-2">Show banners in chat box</label>
                </div>
                
                <div v-if="form.show_banners" class="space-y-4">
                  <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium">Banner Rotation (seconds)</label>
                    <InputNumber
                      v-model="form.banner_rotation_seconds"
                      :min="0"
                      :max="60"
                      :class="{ 'p-invalid': form.errors.banner_rotation_seconds }"
                    />
                    <small v-if="form.errors.banner_rotation_seconds" class="p-error">{{ form.errors.banner_rotation_seconds }}</small>
                    <small class="text-gray-500 mt-1">Set to 0 to disable auto-rotation</small>
                  </div>

                  <div class="space-y-3">
                    <div
                      v-for="(banner, index) in form.banners"
                      :key="index"
                      class="border border-gray-200 rounded-lg p-4 bg-gray-50"
                    >
                      <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold">Banner {{ index + 1 }}</h4>
                        <Button
                          icon="pi pi-times"
                          severity="danger"
                          text
                          rounded
                          size="small"
                          @click="removeBanner(index)"
                        />
                      </div>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Title</label>
                          <InputText v-model="banner.title" placeholder="Banner title" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Text</label>
                          <InputText v-model="banner.text" placeholder="Banner description" />
                        </div>
                        <div class="flex flex-col md:col-span-2">
                          <label class="mb-1 text-sm font-medium">Image URL</label>
                          <InputText v-model="banner.image" placeholder="https://example.com/image.jpg" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Link URL</label>
                          <InputText v-model="banner.link" placeholder="https://example.com" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Link Target</label>
                          <Select
                            v-model="banner.link_target"
                            :options="linkTargetOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select target"
                          />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Button Text</label>
                          <InputText v-model="banner.button_text" placeholder="Click here" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Button Color</label>
                          <InputText v-model="banner.button_color" type="color" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Background Color</label>
                          <InputText v-model="banner.bg_color" type="color" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Background Color 2 (Gradient)</label>
                          <InputText v-model="banner.bg_color_2" type="color" />
                        </div>
                        <div class="flex flex-col">
                          <label class="mb-1 text-sm font-medium">Text Color</label>
                          <InputText v-model="banner.text_color" type="color" />
                        </div>
                      </div>
                    </div>
                    <Button
                      label="Add Banner"
                      icon="pi pi-plus"
                      severity="secondary"
                      outlined
                      size="small"
                      @click="addBanner"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="flex flex-col md:col-span-2">
              <h3 class="text-lg font-semibold mb-4">Settings</h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
                  <label for="is_active" class="ml-2">Active</label>
                </div>
                <div class="flex items-center">
                  <Checkbox v-model="form.auto_create_leads" inputId="auto_create_leads" :binary="true" />
                  <label for="auto_create_leads" class="ml-2">Auto-create leads from conversations</label>
                </div>
                <div class="flex items-center">
                  <Checkbox v-model="form.collect_email" inputId="collect_email" :binary="true" />
                  <label for="collect_email" class="ml-2">Collect visitor email</label>
                </div>
                <div class="flex items-center">
                  <Checkbox v-model="form.collect_phone" inputId="collect_phone" :binary="true" />
                  <label for="collect_phone" class="ml-2">Collect visitor phone</label>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t">
            <Link :href="`/chat-widgets/${widget.id}/documents`">
              <Button label="Manage Knowledge Base" icon="pi pi-book" severity="secondary" outlined />
            </Link>
            <div class="flex items-center gap-2">
              <Link href="/chat-widgets">
                <Button label="Cancel" severity="secondary" outlined />
              </Link>
              <Link :href="`/chat-widgets/${widget.id}/preview`" target="_blank">
                <Button label="Preview Widget" icon="pi pi-eye" severity="info" outlined />
              </Link>
              <Button label="Update Widget" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    Textarea,
    InputNumber,
    Select,
    Checkbox,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    widget: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        name: this.widget.name,
        welcome_message: this.widget.welcome_message || '',
        system_prompt: this.widget.system_prompt || '',
        primary_color: this.widget.primary_color || '#ef6820',
        position: this.widget.position || 'bottom-right',
        is_active: this.widget.is_active ?? true,
        auto_create_leads: this.widget.auto_create_leads ?? true,
        collect_email: this.widget.collect_email ?? true,
        collect_phone: this.widget.collect_phone ?? false,
        allowed_domains: this.widget.allowed_domains || [],
        banners: this.widget.banners || [],
        show_banners: this.widget.show_banners ?? true,
        banner_rotation_seconds: this.widget.banner_rotation_seconds || 5,
        ai_model: this.widget.ai_model || 'gpt-4o-mini',
        temperature: this.widget.temperature || 0.7,
        max_tokens: this.widget.max_tokens || 500,
        rate_limit_per_hour: this.widget.rate_limit_per_hour || 100,
      }),
      positionOptions: [
        { label: 'Bottom Right', value: 'bottom-right' },
        { label: 'Bottom Left', value: 'bottom-left' },
      ],
      modelOptions: [
        { label: 'GPT-4o Mini (Fast & Cost-effective)', value: 'gpt-4o-mini' },
        { label: 'GPT-4o (Most Capable)', value: 'gpt-4o' },
        { label: 'GPT-4 Turbo', value: 'gpt-4-turbo' },
        { label: 'GPT-3.5 Turbo', value: 'gpt-3.5-turbo' },
      ],
      breadcrumbItems: [
        { label: 'Chat Widgets', route: '/chat-widgets' },
        { label: 'Edit' },
      ],
      linkTargetOptions: [
        { label: 'New Tab', value: '_blank' },
        { label: 'Same Tab', value: '_self' },
      ],
    }
  },
  computed: {
    embedCode() {
      return `<script src="${this.widget.embed_url}"><\/script>`
    },
  },
  methods: {
    update() {
      this.form.put(`/chat-widgets/${this.widget.id}`)
    },
    addDomain() {
      this.form.allowed_domains.push('')
    },
    removeDomain(index) {
      this.form.allowed_domains.splice(index, 1)
    },
    copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!')
      })
    },
    addBanner() {
      this.form.banners.push({
        title: '',
        text: '',
        image: '',
        link: '',
        link_target: '_blank',
        button_text: '',
        button_color: this.form.primary_color || '#ef6820',
        bg_color: '#f3f4f6',
        bg_color_2: '#e5e7eb',
        text_color: '#1f2937',
      })
    },
    removeBanner(index) {
      this.form.banners.splice(index, 1)
    },
  },
}
</script>

