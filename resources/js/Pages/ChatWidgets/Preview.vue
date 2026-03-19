<template>
  <div>
    <Head :title="`Preview: ${widget.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>
        <div class="flex items-center justify-between">
          <span>Preview Chat Widget</span>
          <div class="flex items-center gap-2">
            <Link :href="`/chat-widgets/${widget.id}/edit`">
              <Button label="Edit Widget" icon="pi pi-pencil" severity="secondary" outlined />
            </Link>
            <Button label="Close Preview" icon="pi pi-times" severity="secondary" @click="goBack" />
          </div>
        </div>
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <i class="pi pi-info-circle text-blue-600 mt-1"></i>
              <div class="flex-1">
                <h4 class="font-semibold text-blue-900 mb-1">Preview Mode</h4>
                <p class="text-sm text-blue-700">
                  This is a preview of how your chat widget will appear on your website. 
                  The widget is fully functional - you can interact with it to test the experience.
                </p>
              </div>
            </div>
          </div>

          <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 bg-gray-50 min-h-[600px] relative">
            <div class="text-center mb-4">
              <h3 class="text-lg font-semibold text-gray-700">Website Preview</h3>
              <p class="text-sm text-gray-500">This simulates how the widget appears on your website</p>
            </div>

            <!-- Preview Content Area -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-4">
              <h2 class="text-2xl font-bold mb-2">Welcome to Our Website</h2>
              <p class="text-gray-600 mb-4">
                This is a sample page to demonstrate how the chat widget appears. 
                Scroll down or interact with the widget in the corner to see it in action.
              </p>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-100 rounded p-4">
                  <h3 class="font-semibold mb-2">Feature 1</h3>
                  <p class="text-sm text-gray-600">Sample content area</p>
                </div>
                <div class="bg-gray-100 rounded p-4">
                  <h3 class="font-semibold mb-2">Feature 2</h3>
                  <p class="text-sm text-gray-600">Sample content area</p>
                </div>
                <div class="bg-gray-100 rounded p-4">
                  <h3 class="font-semibold mb-2">Feature 3</h3>
                  <p class="text-sm text-gray-600">Sample content area</p>
                </div>
              </div>
              <p class="text-gray-500 text-sm">
                The chat widget button should appear in the {{ widget.position === 'bottom-right' ? 'bottom-right' : 'bottom-left' }} corner.
                Click it to open the chat window and test the widget functionality.
              </p>
            </div>
          </div>

          <!-- Widget Settings Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <Card>
              <template #title>Widget Settings</template>
              <template #content>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Position:</span>
                    <span class="font-medium">{{ widget.position === 'bottom-right' ? 'Bottom Right' : 'Bottom Left' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Primary Color:</span>
                    <div class="flex items-center gap-2">
                      <div
                        class="w-6 h-6 rounded border border-gray-300"
                        :style="{ backgroundColor: widget.primary_color }"
                      ></div>
                      <span class="font-mono text-xs">{{ widget.primary_color }}</span>
                    </div>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Collect Email:</span>
                    <Tag :value="widget.collect_email ? 'Yes' : 'No'" :severity="widget.collect_email ? 'success' : 'secondary'" />
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Collect Phone:</span>
                    <Tag :value="widget.collect_phone ? 'Yes' : 'No'" :severity="widget.collect_phone ? 'success' : 'secondary'" />
                  </div>
                </div>
              </template>
            </Card>

            <Card>
              <template #title>Welcome Message</template>
              <template #content>
                <p class="text-sm text-gray-700 italic">
                  {{ widget.welcome_message || 'No welcome message configured' }}
                </p>
              </template>
            </Card>
          </div>
        </div>
      </template>
    </Card>

    <!-- Widget will be loaded via script injection -->
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Breadcrumb from 'primevue/breadcrumb'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    Tag,
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
      breadcrumbItems: [
        { label: 'Chat Widgets', route: '/chat-widgets' },
        { label: 'Preview' },
      ],
    }
  },
  methods: {
    goBack() {
      router.visit(`/chat-widgets/${this.widget.id}/edit`)
    },
    loadWidgetScript() {
      // Remove existing script if any
      const existingScript = document.querySelector(`script[data-widget-preview="${this.widget.id}"]`)
      if (existingScript) {
        existingScript.remove()
      }

      // Create and inject script
      const script = document.createElement('script')
      script.src = this.widget.embed_url
      script.type = 'text/javascript'
      script.setAttribute('data-widget-preview', this.widget.id)
      script.async = true
      document.body.appendChild(script)
    },
  },
  mounted() {
    // Load widget script dynamically
    this.loadWidgetScript()
  },
}
</script>

<style scoped>
/* Ensure preview area is scrollable */
.min-h-[600px] {
  min-height: 600px;
}
</style>

