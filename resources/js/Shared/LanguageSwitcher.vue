<template>
  <div class="relative">
    <Button
      :label="currentLanguageLabel"
      icon="pi pi-globe"
      text
      rounded
      @click="toggleMenu"
    />
    <OverlayPanel ref="op" class="w-48">
      <div class="space-y-2">
        <button
          v-for="lang in languages"
          :key="lang.code"
          :class="[
            'w-full text-left px-4 py-2 rounded hover:bg-gray-100 flex items-center justify-between',
            currentLocale === lang.code ? 'bg-primary-50 text-primary-600 font-medium' : 'text-gray-700'
          ]"
          @click="switchLanguage(lang.code)"
        >
          <span>{{ lang.label }}</span>
          <i v-if="currentLocale === lang.code" class="pi pi-check text-primary-600" />
        </button>
      </div>
    </OverlayPanel>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import OverlayPanel from 'primevue/overlaypanel'

export default {
  components: {
    Button,
    OverlayPanel,
  },
  props: {
    locale: {
      type: String,
      default: 'vi',
    },
  },
  data() {
    return {
      languages: [
        { code: 'vi', label: 'Tiếng Việt' },
        { code: 'en', label: 'English' },
      ],
    }
  },
  computed: {
    currentLocale() {
      return this.locale || 'vi'
    },
    currentLanguageLabel() {
      const lang = this.languages.find(l => l.code === this.currentLocale)
      return lang ? lang.label : 'Tiếng Việt'
    },
  },
  methods: {
    toggleMenu(event) {
      this.$refs.op.toggle(event)
    },
    switchLanguage(locale) {
      this.$refs.op.hide()
      // Use window.location to force full page reload with new locale
      // This ensures translations are properly reloaded
      window.location.href = `/lang/${locale}`
    },
  },
}
</script>

