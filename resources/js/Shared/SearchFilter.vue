<template>
  <div class="search-filter-container">
    <div class="search-input-group">
      <!-- Filter Dropdown -->
      <dropdown :auto-close="false" class="filter-dropdown-trigger" placement="bottom-start">
        <template #default>
          <div class="filter-btn">
            <i class="pi pi-filter" />
            <span class="filter-label">{{ isVi ? 'Lọc' : 'Filter' }}</span>
            <i class="pi pi-chevron-down chevron-icon" />
          </div>
        </template>
        <template #dropdown>
          <div class="filter-panel anim-slide-up">
            <div class="panel-header">
              <i class="pi pi-sliders-h" />
              <span>{{ isVi ? 'Tuỳ chỉnh tìm kiếm' : 'Search Options' }}</span>
            </div>
            <div class="panel-content">
              <slot />
            </div>
          </div>
        </template>
      </dropdown>

      <!-- Search Input -->
      <div class="search-input-wrapper">
        <i class="pi pi-search search-icon" />
        <input
          class="search-input"
          autocomplete="off"
          type="text"
          name="search"
          :placeholder="isVi ? 'Tìm kiếm...' : 'Search...'"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
        />
      </div>
    </div>

    <!-- Reset Link -->
    <button v-if="modelValue" class="reset-link" type="button" @click="$emit('reset')">
      <i class="pi pi-times-circle" />
      {{ isVi ? 'Xoá' : 'Reset' }}
    </button>
  </div>
</template>

<script>
import Dropdown from '@/Shared/Dropdown.vue'

export default {
  components: {
    Dropdown,
  },
  props: {
    modelValue: String,
    maxWidth: {
      type: Number,
      default: 300,
    },
  },
  emits: ['update:modelValue', 'reset'],
  computed: {
    // Basic detection for multi-lang if useTranslation isn't global here
    isVi() {
      return this.$page.props.locale === 'vi' || true // Default to true if not found for now
    }
  }
}
</script>

<style scoped>
.search-filter-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.search-input-group {
  display: flex;
  flex: 1;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  transition: all 0.2s;
}

.search-input-group:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

/* Filter Dropdown Style */
.filter-dropdown-trigger {
  border-right: 1px solid #f1f5f9;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 1rem;
  height: 42px;
  background: #f8fafc;
  color: #475569;
  cursor: pointer;
  transition: background 0.2s;
}

.filter-btn:hover {
  background: #f1f5f9;
}

.filter-label {
  font-size: 0.85rem;
  font-weight: 600;
}

.chevron-icon {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Search Input Style */
.search-input-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  padding: 0 1rem;
  position: relative;
}

.search-icon {
  color: #94a3b8;
  font-size: 0.9rem;
}

.search-input {
  flex: 1;
  border: none;
  padding: 0.6rem 0.75rem;
  font-size: 0.9rem;
  color: #1e293b;
  outline: none;
  background: transparent;
}

.search-input::placeholder {
  color: #cbd5e1;
}

/* Reset Link Style */
.reset-link {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0.4rem 0.6rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.reset-link:hover {
  color: #ef4444;
  background: #fef2f2;
}

.reset-link i {
  font-size: 0.9rem;
}

/* Filter Panel Style */
.filter-panel {
  background: white;
  min-width: 280px;
  border-radius: 12px;
}

.panel-header {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.panel-content {
  padding: 1.25rem 1.25rem;
}

.anim-slide-up {
  animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
  .filter-label { display: none; }
}
</style>
