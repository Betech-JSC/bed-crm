<template>
  <div>
    <Head :title="t('common.deals')" />
    <div class="kanban-header">
      <div>
        <h1 class="kanban-title">{{ t('common.pipeline') }}</h1>
        <p class="kanban-subtitle">{{ t('common.deals') }}</p>
      </div>
      <Link href="/deals/create">
        <Button :label="t('common.create_deal')" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board">
      <div
        v-for="[stageKey, stageLabel] in stageEntries"
        :key="stageKey"
        class="kanban-column"
      >
        <div class="column-header">
          <div class="column-header-left">
            <span class="column-dot" :class="`dot-${stageKey}`"></span>
            <span class="column-title">{{ stageLabel }}</span>
          </div>
          <span class="column-count">{{ getStageCount(stageKey) }}</span>
        </div>

        <draggable
          v-model="localDealsByStage[stageKey]"
          :group="{ name: 'deals', pull: true, put: true }"
          :animation="200"
          ghost-class="ghost-card"
          chosen-class="chosen-card"
          drag-class="dragging-card"
          :empty-insert-threshold="30"
          item-key="id"
          class="column-body"
          @change="onStageDrop(stageKey, $event)"
        >
          <template #item="{ element: deal }">
            <div
              :key="deal.id"
              class="deal-card"
            >
              <div class="deal-card-top">
                <Link :href="`/deals/${deal.id}/edit`" class="deal-card-link">
                  <h3 class="deal-card-title">{{ deal.title }}</h3>
                  <p v-if="deal.lead" class="deal-card-lead">{{ deal.lead.name }}</p>
                  <p v-if="deal.lead?.company" class="deal-card-company">{{ deal.lead.company }}</p>
                </Link>
                <div class="drag-handle">
                  <i class="pi pi-bars" />
                </div>
              </div>

              <div v-if="deal.value" class="deal-card-value">
                {{ formatCurrency(deal.value) }}
              </div>

              <div class="deal-card-bottom">
                <div v-if="deal.assigned_user" class="deal-card-user">
                  <div class="deal-user-avatar">{{ initials(deal.assigned_user.name) }}</div>
                  <span>{{ deal.assigned_user.name }}</span>
                </div>
                <div v-if="deal.expected_close_date" class="deal-card-date">
                  <i class="pi pi-calendar" />
                  {{ formatDate(deal.expected_close_date) }}
                </div>
              </div>
            </div>
          </template>
        </draggable>

        <div v-if="getStageCount(stageKey) === 0" class="column-empty">
          <i class="pi pi-inbox" />
          <span>{{ t('common.no_deals') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import draggable from 'vuedraggable'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Button,
    draggable,
  },
  layout: Layout,
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  props: {
    dealsByStage: Object,
    stages: Object,
    salesUsers: Array,
  },
  data() {
    return {
      localDealsByStage: {},
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
    initials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
    },

    /**
     * Handle drop into a column.
     * vuedraggable @change fires on the TARGET column with {added: {element, newIndex}}
     */
    onStageDrop(targetStage, event) {
      if (!event.added) return // only act when a card is added to this column

      const deal = event.added.element
      const dealId = deal.id
      const oldStage = deal.stage

      // Skip if same stage
      if (oldStage === targetStage) return

      // Optimistic: update the deal's stage in local data
      deal.stage = targetStage

      // Use Inertia router - handles CSRF automatically
      router.patch(`/deals/${dealId}/stage`, {
        stage: targetStage,
      }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
          // Revert on failure
          deal.stage = oldStage
          this.syncLocalState()
        },
      })
    },

    syncLocalState() {
      this.$nextTick(() => {
        Object.keys(this.stages).forEach(stageKey => {
          this.localDealsByStage[stageKey] = [...(this.dealsByStage[stageKey] || [])]
        })
      })
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
      }).format(value)
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('vi-VN', { month: 'short', day: 'numeric' })
    },
  },
}
</script>

<style scoped>
/* ===== Kanban Header ===== */
.kanban-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.kanban-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
.kanban-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Kanban Board ===== */
.kanban-board {
  display: flex;
  gap: 0.75rem;
  overflow-x: auto;
  padding-bottom: 1rem;
  min-height: calc(100vh - 180px);
}

.kanban-column {
  flex-shrink: 0;
  width: 300px;
  display: flex;
  flex-direction: column;
}

/* ===== Column Header ===== */
.column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.65rem 0.75rem;
  margin-bottom: 0.5rem;
  background: white;
  border-radius: 10px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}

.column-header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.column-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot-prospecting { background: #6366f1; }
.dot-qualification { background: #f59e0b; }
.dot-proposal { background: #8b5cf6; }
.dot-negotiation { background: #ef4444; }
.dot-closing { background: #10b981; }

.column-title { font-size: 0.82rem; font-weight: 600; color: #334155; }

.column-count {
  font-size: 0.72rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #64748b;
  padding: 0.15rem 0.5rem;
  border-radius: 10px;
  min-width: 22px;
  text-align: center;
}

/* ===== Column Body ===== */
.column-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 200px;
  padding: 0.25rem;
  border-radius: 10px;
  background: rgba(241, 245, 249, 0.5);
  border: 2px dashed transparent;
  transition: all 0.2s;
}

.column-body:empty {
  border-color: #e2e8f0;
}

/* ===== Deal Card ===== */
.deal-card {
  background: white;
  border-radius: 10px;
  padding: 0.85rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  cursor: grab;
  transition: all 0.2s;
}

.deal-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border-color: #e2e8f0;
  transform: translateY(-1px);
}

.deal-card:active { cursor: grabbing; }

.deal-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.deal-card-link { text-decoration: none; flex: 1; min-width: 0; }
.deal-card-title { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.3; }
.deal-card-link:hover .deal-card-title { color: #6366f1; }
.deal-card-lead { font-size: 0.75rem; color: #64748b; margin: 0.2rem 0 0; }
.deal-card-company { font-size: 0.7rem; color: #94a3b8; margin: 0.1rem 0 0; }

.drag-handle {
  padding: 0.2rem;
  color: #cbd5e1;
  cursor: grab;
  transition: color 0.15s;
  flex-shrink: 0;
}
.drag-handle:hover { color: #6366f1; }
.drag-handle i { font-size: 0.75rem; }

/* Deal Value */
.deal-card-value {
  font-size: 0.9rem;
  font-weight: 700;
  color: #10b981;
  margin-bottom: 0.5rem;
}

/* Bottom row */
.deal-card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.deal-card-user {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.72rem;
  color: #64748b;
}

.deal-user-avatar {
  width: 20px; height: 20px;
  border-radius: 50%;
  background: #e0e7ff;
  color: #4f46e5;
  font-size: 0.55rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}

.deal-card-date {
  font-size: 0.7rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.deal-card-date i { font-size: 0.6rem; }

/* ===== Column Empty ===== */
.column-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  padding: 2rem;
  color: #cbd5e1;
  font-size: 0.78rem;
}
.column-empty i { font-size: 1.2rem; }

/* ===== Drag States ===== */
.ghost-card {
  opacity: 0.4;
  background: #f1f5f9 !important;
  border: 2px dashed #94a3b8 !important;
  box-shadow: none !important;
}

.chosen-card {
  cursor: grabbing !important;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
  transform: rotate(1deg);
}

.dragging-card {
  opacity: 0.9;
}

/* ===== Scrollbar ===== */
.kanban-board::-webkit-scrollbar {
  height: 8px;
}
.kanban-board::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}
.kanban-board::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.kanban-board::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
