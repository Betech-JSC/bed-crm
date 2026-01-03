import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import 'primeicons/primeicons.css'

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    return pages[`./Pages/${name}.vue`]
  },
  title: title => title ? `${title} - Betech CRM` : 'Betech CRM',
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })

    app.use(plugin)
    app.use(PrimeVue, {
      theme: {
        preset: Aura,
        options: {
          darkModeSelector: false,
          prefix: 'p',
          cssLayer: false,
        },
        semantic: {
          primary: {
            50: '#fef5f0',
            100: '#fde8dc',
            200: '#fbd0b8',
            300: '#f8b089',
            400: '#f48554',
            500: '#ef6820',
            600: '#e04f0f',
            700: '#ba3d0f',
            800: '#943214',
            900: '#782b13',
          },
        }
      }
    })

    app.mount(el)
  },
})
