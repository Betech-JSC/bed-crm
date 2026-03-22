<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div v-for="toast in toasts" :key="toast.id" class="toast-item" :class="`toast-${toast.type}`" @click="removeToast(toast.id)">
          <div class="toast-icon-wrap">
            <i :class="iconMap[toast.type]" />
          </div>
          <div class="toast-body">
            <span class="toast-title">{{ titleMap[toast.type] }}</span>
            <p class="toast-message">{{ toast.message }}</p>
          </div>
          <button class="toast-close" @click.stop="removeToast(toast.id)">
            <i class="pi pi-times" />
          </button>
          <div class="toast-progress">
            <div class="toast-progress-bar" :class="`bar-${toast.type}`" :style="{ animationDuration: `${toast.duration}ms` }" />
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script>
let toastId = 0

export default {
  data() {
    return {
      toasts: [],
      iconMap: {
        success: 'pi pi-check-circle',
        error: 'pi pi-times-circle',
        warning: 'pi pi-exclamation-triangle',
        info: 'pi pi-info-circle',
      },
      titleMap: {
        success: 'Thành công',
        error: 'Lỗi',
        warning: 'Cảnh báo',
        info: 'Thông tin',
      },
    }
  },
  watch: {
    '$page.props.flash': {
      handler(flash) {
        if (flash?.success) this.addToast('success', flash.success)
        if (flash?.error) this.addToast('error', flash.error)
        if (flash?.warning) this.addToast('warning', flash.warning)
        if (flash?.info) this.addToast('info', flash.info)
      },
      deep: true,
      immediate: true,
    },
    '$page.props.errors': {
      handler(errors) {
        if (errors && Object.keys(errors).length > 0) {
          const count = Object.keys(errors).length
          this.addToast('error', count === 1 ? 'Vui lòng kiểm tra lại 1 trường bị lỗi.' : `Có ${count} trường cần sửa lại.`)
        }
      },
      deep: true,
    },
  },
  methods: {
    addToast(type, message, duration = 4500) {
      const id = ++toastId
      this.toasts.push({ id, type, message, duration })
      setTimeout(() => this.removeToast(id), duration)
    },
    removeToast(id) {
      this.toasts = this.toasts.filter(t => t.id !== id)
    },
  },
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  max-width: 400px;
  pointer-events: none;
}

.toast-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border-radius: 14px;
  background: white;
  box-shadow: 0 8px 32px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  border: 1px solid rgba(0,0,0,.04);
  cursor: pointer;
  pointer-events: all;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
}

/* ===== Type Colors ===== */
.toast-success { border-left: 4px solid #10b981; }
.toast-error   { border-left: 4px solid #ef4444; }
.toast-warning { border-left: 4px solid #f59e0b; }
.toast-info    { border-left: 4px solid #3b82f6; }

/* ===== Icon ===== */
.toast-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.95rem;
}

.toast-success .toast-icon-wrap { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669; }
.toast-error   .toast-icon-wrap { background: linear-gradient(135deg, #fef2f2, #fecaca); color: #dc2626; }
.toast-warning .toast-icon-wrap { background: linear-gradient(135deg, #fffbeb, #fde68a); color: #d97706; }
.toast-info    .toast-icon-wrap { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb; }

/* ===== Body ===== */
.toast-body {
  flex: 1;
  min-width: 0;
}

.toast-title {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  margin-bottom: 0.1rem;
}

.toast-success .toast-title { color: #059669; }
.toast-error   .toast-title { color: #dc2626; }
.toast-warning .toast-title { color: #d97706; }
.toast-info    .toast-title { color: #2563eb; }

.toast-message {
  font-size: 0.78rem;
  color: #475569;
  margin: 0;
  line-height: 1.45;
  word-break: break-word;
}

/* ===== Close ===== */
.toast-close {
  background: none;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.15s;
  font-size: 0.7rem;
}

.toast-close:hover {
  background: #f1f5f9;
  color: #475569;
}

/* ===== Progress Bar ===== */
.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0,0,0,.04);
}

.toast-progress-bar {
  height: 100%;
  border-radius: 0 0 0 14px;
  animation: progressShrink linear forwards;
}

.bar-success { background: linear-gradient(90deg, #10b981, #059669); }
.bar-error   { background: linear-gradient(90deg, #ef4444, #dc2626); }
.bar-warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.bar-info    { background: linear-gradient(90deg, #3b82f6, #2563eb); }

@keyframes progressShrink {
  from { width: 100%; }
  to   { width: 0%; }
}

/* ===== Transitions ===== */
.toast-enter-active {
  animation: toastSlideIn 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
}

.toast-leave-active {
  animation: toastSlideOut 0.25s ease-in forwards;
}

.toast-move {
  transition: transform 0.3s ease;
}

@keyframes toastSlideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes toastSlideOut {
  from {
    transform: translateX(0);
    opacity: 1;
    max-height: 200px;
    margin-bottom: 0.65rem;
  }
  to {
    transform: translateX(110%);
    opacity: 0;
    max-height: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
  }
}

/* ===== Responsive ===== */
@media (max-width: 480px) {
  .toast-container {
    left: 0.75rem;
    right: 0.75rem;
    max-width: none;
  }
}
</style>
