<template>
  <Link
    :href="href"
    class="menu-item-link"
    :class="{ 'is-active': active }"
  >
    <i :class="icon" class="menu-item-icon" />
    <span class="menu-item-label"><slot /></span>
  </Link>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  components: {
    Link,
  },
  props: {
    href: String,
    icon: String,
    active: Boolean,
  },
}
</script>

<style scoped>
.menu-item-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.75rem;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.15s ease;
}

.menu-item-link:hover {
  background: rgba(255, 255, 255, 0.06);
}

.menu-item-link.is-active {
  background: rgba(239, 104, 32, 0.1);
}

.menu-item-icon {
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.35);
  width: 16px;
  text-align: center;
  flex-shrink: 0;
  transition: color 0.15s;
}

.menu-item-link:hover .menu-item-icon {
  color: rgba(255, 255, 255, 0.7);
}

.menu-item-link.is-active .menu-item-icon {
  color: #f48554;
}

.menu-item-label {
  font-size: 0.78rem;
  font-weight: 450;
  color: rgba(255, 255, 255, 0.5);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.15s;
}

.menu-item-link:hover .menu-item-label {
  color: rgba(255, 255, 255, 0.85);
}

.menu-item-link.is-active .menu-item-label {
  color: #f8b089;
  font-weight: 600;
}
</style>
