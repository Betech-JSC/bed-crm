<template>
  <div class="menu-group">
    <button
      @click="$emit('toggle')"
      class="menu-group-trigger"
      :class="{ 'is-open': isOpen }"
    >
      <div class="menu-group-left">
        <div class="group-icon-wrapper" :class="{ 'group-icon-active': isOpen }">
          <i :class="icon" />
        </div>
        <span class="group-title">{{ title }}</span>
      </div>
      <i
        class="pi pi-chevron-down group-chevron"
        :class="{ 'chevron-rotated': isOpen }"
      />
    </button>
    <Transition name="expand">
      <div v-show="isOpen" class="menu-group-content">
        <div class="group-items-wrapper">
          <slot />
        </div>
      </div>
    </Transition>
  </div>
</template>

<script>
export default {
  props: {
    title: String,
    icon: String,
    isOpen: Boolean,
  },
  emits: ['toggle'],
}
</script>

<style scoped>
.menu-group {
  margin-bottom: 2px;
}

.menu-group-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: none;
  background: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.menu-group-trigger:hover {
  background: rgba(255, 255, 255, 0.06);
}

.menu-group-trigger.is-open {
  background: rgba(255, 255, 255, 0.04);
}

.menu-group-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.group-icon-wrapper {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}

.group-icon-wrapper i {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.5);
  transition: color 0.2s;
}

.menu-group-trigger:hover .group-icon-wrapper {
  background: rgba(255, 255, 255, 0.1);
}

.menu-group-trigger:hover .group-icon-wrapper i {
  color: rgba(255, 255, 255, 0.8);
}

.group-icon-active {
  background: rgba(239, 104, 32, 0.15) !important;
}

.group-icon-active i {
  color: #f48554 !important;
}

.group-title {
  font-size: 0.82rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.6);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
}

.menu-group-trigger:hover .group-title {
  color: rgba(255, 255, 255, 0.9);
}

.menu-group-trigger.is-open .group-title {
  color: rgba(255, 255, 255, 0.85);
}

.group-chevron {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.3);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s;
  flex-shrink: 0;
}

.chevron-rotated {
  transform: rotate(180deg);
  color: rgba(255, 255, 255, 0.5);
}

/* ===== Group Content ===== */
.menu-group-content {
  overflow: hidden;
}

.group-items-wrapper {
  padding: 0.25rem 0 0.25rem 1rem;
  margin-left: 0.75rem;
  border-left: 2px solid rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
  gap: 1px;
}

/* ===== Expand Transition ===== */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 500px;
  opacity: 1;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
