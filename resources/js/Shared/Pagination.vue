<template>
  <Paginator
    v-if="totalRecords > 0"
    :first="first"
    :rows="perPage"
    :totalRecords="totalRecords"
    @page="onPageChange"
    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
  />
</template>

<script>
import Paginator from 'primevue/paginator'
import { Link, router } from '@inertiajs/vue3'

export default {
  components: {
    Paginator,
    Link,
  },
  props: {
    links: Array,
    perPage: {
      type: Number,
      default: 15,
    },
  },
  computed: {
    totalRecords() {
      // Extract total from links if available, or calculate from links length
      const lastLink = this.links[this.links.length - 2] // Usually second to last
      if (lastLink && lastLink.url) {
        const match = lastLink.url.match(/page=(\d+)/)
        if (match) {
          return parseInt(match[1]) * this.perPage
        }
      }
      return this.links.length > 3 ? (this.links.length - 2) * this.perPage : 0
    },
    first() {
      const activeLink = this.links.find(link => link.active)
      if (activeLink && activeLink.url) {
        const match = activeLink.url.match(/page=(\d+)/)
        if (match) {
          return (parseInt(match[1]) - 1) * this.perPage
        }
      }
      return 0
    },
  },
  methods: {
    onPageChange(event) {
      const page = (event.page + 1) // PrimeVue uses 0-based, Laravel uses 1-based
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('page', page)
      router.visit(currentUrl.pathname + currentUrl.search, {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
}
</script>
