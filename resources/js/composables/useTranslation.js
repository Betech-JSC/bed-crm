import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * Composable for translations in Vue components.
 *
 * Usage:
 *   const { t, locale } = useTranslation()
 *   t('common.dashboard')  → 'Bảng điều khiển' (vi) or 'Dashboard' (en)
 *   t('common.lead_name')  → 'Tên khách hàng'
 */
export function useTranslation() {
  const page = usePage()

  const locale = computed(() => page.props.locale || 'vi')

  const t = (key, replacements = {}) => {
    const translations = page.props.translations || {}
    let translation = key.split('.').reduce((obj, k) => obj?.[k], translations) || key

    // Replace placeholders like :name, :count
    Object.keys(replacements).forEach((placeholder) => {
      translation = translation.replace(new RegExp(`:${placeholder}`, 'g'), replacements[placeholder])
    })

    return translation
  }

  return { t, locale }
}
