<template>
  <div>
    <Head title="Reports" />
    <h1 class="mb-8 text-3xl font-bold">{{ t('common.reports') }}</h1>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  setup() {
    const { t } = useTranslation()
    return { t }
  },

  components: {
    Head,
  },
  layout: Layout,
}
</script>
