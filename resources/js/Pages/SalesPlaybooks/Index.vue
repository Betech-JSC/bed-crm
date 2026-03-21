<template>
  <div>
    <Head :title="t('common.sales_playbooks')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-book" /></div>
        <div>
          <h1 class="page-title">{{ t('common.sales_playbooks') }}</h1>
          <p class="page-subtitle">{{ playbooks.length }} {{ isVi ? 'kịch bản bán hàng' : 'playbooks' }}</p>
        </div>
      </div>
      <Link href="/sales-playbooks/create"><button class="btn-primary"><i class="pi pi-plus" /> {{ isVi ? 'Tạo kịch bản' : 'Create Playbook' }}</button></Link>
    </div>

    <!-- Empty -->
    <div v-if="!playbooks || playbooks.length === 0" class="empty-state">
      <div class="empty-icon"><i class="pi pi-book" /></div>
      <h3>{{ isVi ? 'Chưa có kịch bản nào' : 'No playbooks yet' }}</h3>
      <p>{{ isVi ? 'Tạo kịch bản đầu tiên để bắt đầu' : 'Create your first sales playbook' }}</p>
      <Link href="/sales-playbooks/create"><button class="btn-primary sm"><i class="pi pi-plus" /> {{ isVi ? 'Tạo kịch bản' : 'Create' }}</button></Link>
    </div>

    <!-- Playbook Grid -->
    <div v-else class="pb-grid">
      <Link v-for="pb in playbooks" :key="pb.id" :href="`/sales-playbooks/${pb.id}`" class="pb-card">
        <div class="pb-accent" :style="{ background: cardGradient(pb.name) }" />
        <div class="pb-body">
          <div class="pb-top">
            <h3 class="pb-name">{{ pb.name }}</h3>
            <div class="pb-priority" :class="priorityClass(pb.priority)">
              <span class="priority-bar" :style="{ width: pb.priority + '%' }" />
              <span class="priority-val">{{ pb.priority }}</span>
            </div>
          </div>

          <p v-if="pb.description" class="pb-desc">{{ pb.description }}</p>

          <!-- Industries -->
          <div v-if="pb.industries && pb.industries.length" class="pb-tags">
            <span v-for="ind in pb.industries.slice(0, 3)" :key="ind" class="tag industry-tag"><i class="pi pi-briefcase" /> {{ ind }}</span>
            <span v-if="pb.industries.length > 3" class="tag-more">+{{ pb.industries.length - 3 }}</span>
          </div>

          <!-- Deal Stages -->
          <div v-if="pb.deal_stages && pb.deal_stages.length" class="pb-stages">
            <span v-for="stage in pb.deal_stages" :key="stage" class="tag stage-tag"><i class="pi pi-circle-fill" /> {{ stage }}</span>
          </div>

          <div class="pb-footer">
            <span class="pb-arrow"><i class="pi pi-arrow-right" /> {{ isVi ? 'Xem chi tiết' : 'View details' }}</span>
          </div>
        </div>
      </Link>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { playbooks: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  computed: {
    isVi() { return this.locale === 'vi' },
  },
  methods: {
    priorityClass(p) { return p >= 70 ? 'pri-high' : p >= 40 ? 'pri-mid' : 'pri-low' },
    cardGradient(name) {
      const c = [['#6366f1','#8b5cf6'],['#3b82f6','#06b6d4'],['#10b981','#14b8a6'],['#f59e0b','#f97316'],['#ec4899','#db2777'],['#8b5cf6','#6d28d9']]
      const i = (name || '').charCodeAt(0) % c.length; return `linear-gradient(135deg, ${c[i][0]}, ${c[i][1]})`
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
.btn-primary.sm { font-size: 0.78rem; padding: 0.45rem 0.85rem; }

/* Grid */
.pb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1rem; }

/* Card */
.pb-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; text-decoration: none; color: inherit; transition: all 0.3s; display: flex; flex-direction: column; }
.pb-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); border-color: #cbd5e1; }
.pb-accent { height: 4px; }
.pb-body { padding: 1.15rem 1.25rem; flex: 1; display: flex; flex-direction: column; }
.pb-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem; }
.pb-name { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; flex: 1; min-width: 0; }
.pb-card:hover .pb-name { color: #6366f1; }

/* Priority mini bar */
.pb-priority { display: flex; align-items: center; gap: 0.3rem; flex-shrink: 0; }
.priority-bar { height: 4px; border-radius: 2px; min-width: 30px; max-width: 50px; transition: width 0.3s; }
.pri-high .priority-bar { background: #10b981; } .pri-mid .priority-bar { background: #f59e0b; } .pri-low .priority-bar { background: #94a3b8; }
.priority-val { font-size: 0.6rem; font-weight: 700; }
.pri-high .priority-val { color: #10b981; } .pri-mid .priority-val { color: #f59e0b; } .pri-low .priority-val { color: #94a3b8; }

.pb-desc { font-size: 0.78rem; color: #64748b; margin: 0 0 0.6rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Tags */
.pb-tags, .pb-stages { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.5rem; }
.tag { font-size: 0.6rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 6px; display: flex; align-items: center; gap: 0.2rem; }
.tag i { font-size: 0.5rem; }
.industry-tag { background: #eff6ff; color: #3b82f6; }
.stage-tag { background: #f1f5f9; color: #64748b; }
.stage-tag i { font-size: 0.3rem; }
.tag-more { font-size: 0.6rem; color: #94a3b8; font-weight: 600; display: flex; align-items: center; }

/* Footer */
.pb-footer { margin-top: auto; padding-top: 0.65rem; border-top: 1px solid #f1f5f9; }
.pb-arrow { font-size: 0.72rem; font-weight: 600; color: #94a3b8; display: flex; align-items: center; gap: 0.3rem; transition: color 0.2s; }
.pb-arrow i { font-size: 0.6rem; transition: transform 0.2s; }
.pb-card:hover .pb-arrow { color: #6366f1; }
.pb-card:hover .pb-arrow i { transform: translateX(3px); }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; background: white; border: 2px dashed #e2e8f0; border-radius: 20px; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #fffbeb; color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

@media (max-width: 768px) {
  .pb-grid { grid-template-columns: 1fr; }
}
</style>
