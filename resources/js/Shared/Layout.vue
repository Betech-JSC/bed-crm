<template>
  <div>
    <div id="dropdown" />
    <div class="md:flex md:flex-col">
      <div class="md:flex md:flex-col md:h-screen">
        <div class="md:flex md:shrink-0">
          <div class="flex items-center justify-between px-6 py-4 bg-secondary md:shrink-0 md:justify-center md:w-72">
            <Link class="flex items-center justify-center w-full" href="/">
              <img
                v-if="auth.user && auth.user.account && auth.user.account.logo"
                :src="auth.user.account.logo"
                :alt="auth.user.account.name || 'Logo'"
                class="h-10 w-auto max-w-full object-contain"
              />
              <logo v-else class="fill-white" width="120" height="28" />
            </Link>
            <dropdown class="md:hidden" placement="bottom-end">
              <template #default>
                <svg class="w-6 h-6 fill-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" /></svg>
              </template>
              <template #dropdown>
                <div class="mt-2 px-8 py-4 bg-secondary-900 rounded shadow-lg">
                  <main-menu />
                </div>
              </template>
            </dropdown>
          </div>
          <div class="md:text-md flex items-center justify-between p-4 w-full text-sm bg-white border-b md:px-12 md:py-0">
            <div class="mr-4 mt-1">{{ auth.user.account.name }}</div>
            <div class="flex items-center gap-4">
              <LanguageSwitcher :locale="locale" />
              <dropdown class="mt-1" placement="bottom-end">
                <template #default>
                  <div class="group flex items-center cursor-pointer select-none">
                    <div class="mr-1 text-gray-700 group-hover:text-primary-500 focus:text-primary-500 whitespace-nowrap">
                      <span>{{ auth.user.first_name }}</span>
                      <span class="hidden md:inline">&nbsp;{{ auth.user.last_name }}</span>
                    </div>
                    <icon class="w-5 h-5 fill-gray-700 group-hover:fill-primary-500 focus:fill-primary-500" name="cheveron-down" />
                  </div>
                </template>
                <template #dropdown>
                  <div class="mt-2 py-2 text-sm bg-white rounded shadow-xl">
                    <Link class="block px-6 py-2 hover:text-white hover:bg-primary-500" :href="`/users/${auth.user.id}/edit`">My Profile</Link>
                    <Link class="block px-6 py-2 hover:text-white hover:bg-primary-500" href="/users">Manage Users</Link>
                    <Link class="block px-6 py-2 w-full text-left hover:text-white hover:bg-primary-500" href="/logout" method="delete" as="button">Logout</Link>
                  </div>
                </template>
              </dropdown>
            </div>
          </div>
        </div>
        <div class="md:flex md:grow md:overflow-hidden">
          <main-menu class="hidden shrink-0 p-6 w-72 bg-secondary-900 overflow-y-auto md:block" />
          <div class="px-4 py-8 md:flex-1 md:p-12 md:overflow-y-auto" scroll-region>
            <flash-messages />
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'
import Logo from '@/Shared/Logo.vue'
import Dropdown from '@/Shared/Dropdown.vue'
import MainMenu from '@/Shared/MainMenu.vue'
import FlashMessages from '@/Shared/FlashMessages.vue'
import LanguageSwitcher from '@/Shared/LanguageSwitcher.vue'

export default {
  components: {
    Dropdown,
    FlashMessages,
    Icon,
    LanguageSwitcher,
    Link,
    Logo,
    MainMenu,
  },
  props: {
    auth: Object,
    locale: {
      type: String,
      default: 'vi',
    },
  },
}
</script>
