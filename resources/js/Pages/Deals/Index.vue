<template>
  <div>
    <Head title="Deals Pipeline" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Deals Pipeline</h1>
        <p class="mt-1 text-gray-600">Manage your sales opportunities</p>
      </div>
      <Link href="/deals/create">
        <Button label="Create Deal" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Kanban Board -->
    <div class="flex gap-4 overflow-x-auto pb-4">
      <div
        v-for="[stageKey, stageLabel] in stageEntries"
        :key="stageKey"
        class="flex-shrink-0 w-80"
      >
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ stageLabel }}</span>
              <Badge :value="getStageCount(stageKey)" />
            </div>
          </template>
          <template #content>
            <draggable
              v-model="localDealsByStage[stageKey]"
              :group="{ name: 'deals', pull: true, put: true }"
              :animation="200"
              :ghost-class="'ghost-card'"
              :chosen-class="'chosen-card'"
              :drag-class="'dragging-card'"
              :empty-insert-threshold="20"
              :force-fallback="false"
              :fallback-on-body="true"
              :swap-threshold="0.65"
              handle=".drag-handle"
              item-key="id"
              :data-stage="stageKey"
              class="space-y-3 min-h-[400px]"
              @end="onDragEnd($event, stageKey)"
            >
              <template #item="{ element: deal }">
                <div
                  :key="deal.id"
                  :data-id="deal.id"
                  class="p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow cursor-move"
                >
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                      <Link :href="`/deals/${deal.id}/edit`" class="block">
                        <h3 class="font-semibold text-gray-900 hover:text-primary-600 transition-colors">{{ deal.title }}</h3>
                        <p v-if="deal.lead" class="text-sm text-gray-600 mt-1">{{ deal.lead.name }}</p>
                        <p v-if="deal.lead?.company" class="text-xs text-gray-500">{{ deal.lead.company }}</p>
                      </Link>
                    </div>
                    <div class="drag-handle ml-2 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                      <i class="pi pi-bars text-sm" />
                    </div>
                  </div>
                  <div class="flex items-center justify-between mb-2">
                    <span v-if="deal.value" class="font-bold text-primary-600">
                      {{ formatCurrency(deal.value) }}
                    </span>
                    <span v-else class="text-sm text-gray-400">No value</span>
                    <Tag v-if="deal.assigned_user" :value="deal.assigned_user.name" severity="info" />
                  </div>
                  <div v-if="deal.expected_close_date" class="text-xs text-gray-500">
                    <i class="pi pi-calendar mr-1" />
                    {{ formatDate(deal.expected_close_date) }}
                  </div>
                </div>
              </template>
              <template #footer>
                <div v-if="getStageCount(stageKey) === 0" class="py-8 text-center text-gray-400 text-sm">
                  No deals
                </div>
              </template>
            </draggable>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import draggable from 'vuedraggable'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    Badge,
    Tag,
    draggable,
  },
  layout: Layout,
  props: {
    dealsByStage: Object,
    stages: Object,
    salesUsers: Array,
  },
  data() {
    return {
      localDealsByStage: {},
      isDragging: false,
    }
  },
  computed: {
    stageEntries() {
      return Object.entries(this.stages)
    },
  },
  watch: {
    dealsByStage: {
      immediate: true,
      deep: true,
      handler(newVal) {
        // Clone dealsByStage to local state for v-model
        this.localDealsByStage = {}
        Object.keys(this.stages).forEach(stageKey => {
          this.localDealsByStage[stageKey] = [...(newVal[stageKey] || [])]
        })
      },
    },
  },
  methods: {
    getStageCount(stage) {
      return this.localDealsByStage[stage]?.length || 0
    },
    onDragEnd(event, newStage) {
      // Get deal ID from the moved element
      const dealId = event.item?.getAttribute('data-id')
      
      if (!dealId) {
        // Try to find deal ID from the element structure
        const dealElement = event.item
        if (dealElement) {
          const idAttr = dealElement.getAttribute('data-id')
          if (idAttr) {
            this.updateDealStage(parseInt(idAttr), newStage)
            return
          }
        }
        
        // Last resort: sync state and return
        this.syncLocalState()
        return
      }

      // Get old stage from event.from (the source container)
      const oldStage = event.from?.getAttribute('data-stage') || 
                       event.from?.closest('[data-stage]')?.getAttribute('data-stage') ||
                       this.findStageForDeal(parseInt(dealId))
      
      // Only update if stage actually changed
      if (oldStage && oldStage !== newStage) {
        this.updateDealStage(parseInt(dealId), newStage)
      } else {
        // Stage didn't change, just sync state
        this.syncLocalState()
      }
    },
    findStageForDeal(dealId) {
      for (const [stageKey, deals] of Object.entries(this.localDealsByStage)) {
        if (deals.some(d => d.id === dealId)) {
          return stageKey
        }
      }
      return null
    },
    updateDealStage(dealId, newStage) {
      router.patch(`/deals/${dealId}/stage`, {
        stage: newStage,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          // Sync local state after successful update
          this.syncLocalState()
        },
        onError: () => {
          // Revert on error
          this.syncLocalState()
        },
      })
    },
    syncLocalState() {
      // Re-sync from props
      this.$nextTick(() => {
        Object.keys(this.stages).forEach(stageKey => {
          this.localDealsByStage[stageKey] = [...(this.dealsByStage[stageKey] || [])]
        })
      })
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(value)
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    },
  },
}
</script>

<style scoped>
.ghost-card {
  opacity: 0.5;
  background: #f3f4f6;
  border: 2px dashed #9ca3af;
}

.chosen-card {
  cursor: grabbing !important;
}

.dragging-card {
  opacity: 0.8;
  transform: rotate(2deg);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.drag-handle {
  transition: color 0.2s;
}

.drag-handle:hover {
  color: #ef6820;
}

/* Smooth transitions for cards */
.space-y-3 > * {
  transition: transform 0.2s, box-shadow 0.2s;
}

.space-y-3 > *:hover {
  transform: translateY(-2px);
}
</style>

