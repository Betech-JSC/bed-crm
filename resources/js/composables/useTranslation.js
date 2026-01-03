import { usePage } from '@inertiajs/vue3'

/**
 * Composable for translations in Vue components
 */
export function useTranslation() {
  const page = usePage()
  
  const t = (key, replacements = {}) => {
    // Get translations from Inertia page props
    const translations = page.props.translations || {}
    let translation = key.split('.').reduce((obj, k) => obj?.[k], translations) || key

    // Replace placeholders
    Object.keys(replacements).forEach((placeholder) => {
      translation = translation.replace(`:${placeholder}`, replacements[placeholder])
    })

    return translation
  }

  return { t }
}

