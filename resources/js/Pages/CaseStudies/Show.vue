<template>
  <div>
    <Head :title="caseStudy.title" />

    <!-- Hero Section -->
    <div class="show-hero" :style="caseStudy.featured_image_url ? { backgroundImage: `url(${caseStudy.featured_image_url})` } : {}">
      <div class="hero-overlay">
        <div class="hero-content">
          <div class="hero-badges">
            <span class="hero-cat-badge"><i :class="catIcon(caseStudy.service_category)" /> {{ caseStudy.service_category }}</span>
            <span v-if="caseStudy.is_featured" class="hero-featured"><i class="pi pi-star-fill" /> {{ isVi ? 'Nổi bật' : 'Featured' }}</span>
            <span class="hero-status-badge" :class="`status-${caseStudy.status}`">{{ caseStudy.status }}</span>
          </div>
          <h1 class="hero-title">{{ caseStudy.title }}</h1>
          <p class="hero-client" v-if="caseStudy.client_name">
            {{ caseStudy.client_name }}<span v-if="caseStudy.client_industry"> · {{ caseStudy.client_industry }}</span>
          </p>
        </div>
        <div class="hero-actions">
          <Button :label="t('common.edit')" icon="pi pi-pencil" class="hero-btn" @click="$inertia.visit(`/case-studies/${caseStudy.id}/edit`)" />
          <Button :label="isVi ? 'Quay lại' : 'Back'" icon="pi pi-arrow-left" severity="secondary" @click="$inertia.visit('/case-studies')" class="hero-btn-secondary" />
        </div>
      </div>
    </div>

    <!-- Metrics Ribbon -->
    <div class="metrics-ribbon" v-if="caseStudy.result_metrics && caseStudy.result_metrics.length">
      <div v-for="(m, i) in caseStudy.result_metrics" :key="i" class="metric-item">
        <i v-if="m.icon" :class="m.icon" class="metric-icon" />
        <span class="metric-value">{{ m.value }}</span>
        <span class="metric-label">{{ m.label }}</span>
      </div>
    </div>

    <!-- Content Layout -->
    <div class="show-grid">
      <div class="main-content">
        <!-- Problem -->
        <div class="content-section" v-if="caseStudy.problem">
          <div class="section-icon" style="background:#fef2f2; color:#ef4444"><i class="pi pi-exclamation-circle" /></div>
          <div class="section-body">
            <h2 class="section-title">{{ t('common.problem') }}</h2>
            <p class="section-text">{{ caseStudy.problem }}</p>
          </div>
        </div>

        <!-- Solution -->
        <div class="content-section" v-if="caseStudy.solution">
          <div class="section-icon" style="background:#ecfdf5; color:#10b981"><i class="pi pi-check-circle" /></div>
          <div class="section-body">
            <h2 class="section-title">{{ t('common.the_solution') }}</h2>
            <p class="section-text">{{ caseStudy.solution }}</p>
          </div>
        </div>

        <!-- Approach -->
        <div class="content-section" v-if="caseStudy.approach">
          <div class="section-icon" style="background:#eef2ff; color:#6366f1"><i class="pi pi-cog" /></div>
          <div class="section-body">
            <h2 class="section-title">{{ t('common.the_approach') }}</h2>
            <p class="section-text">{{ caseStudy.approach }}</p>
          </div>
        </div>

        <!-- Result -->
        <div class="content-section" v-if="caseStudy.result">
          <div class="section-icon" style="background:#fffbeb; color:#f59e0b"><i class="pi pi-chart-line" /></div>
          <div class="section-body">
            <h2 class="section-title">{{ t('common.the_result') }}</h2>
            <p class="section-text">{{ caseStudy.result }}</p>
          </div>
        </div>

        <!-- Testimonial -->
        <div class="testimonial-card" v-if="caseStudy.testimonial_quote">
          <div class="testi-quote-mark">"</div>
          <blockquote>{{ caseStudy.testimonial_quote }}</blockquote>
          <div class="testi-author">
            <div class="testi-avatar" v-if="caseStudy.testimonial_author">{{ caseStudy.testimonial_author.charAt(0) }}</div>
            <div>
              <span class="testi-name">{{ caseStudy.testimonial_author }}</span>
              <span class="testi-role" v-if="caseStudy.testimonial_role">{{ caseStudy.testimonial_role }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Client Card -->
        <div class="sidebar-card">
          <h3 class="sidebar-title"><i class="pi pi-user" /> {{ isVi ? 'Khách hàng' : 'Client' }}</h3>
          <div class="client-profile">
            <div class="client-avatar-lg" :style="{ background: clientColor(caseStudy.client_name) }">
              {{ (caseStudy.client_name || '?').charAt(0) }}
            </div>
            <div>
              <span class="client-name-lg">{{ caseStudy.client_name }}</span>
              <span class="client-industry-lg" v-if="caseStudy.client_industry">{{ caseStudy.client_industry }}</span>
            </div>
          </div>
          <div class="info-rows">
            <div class="info-row" v-if="caseStudy.client_company_size"><span>{{ isVi ? 'Quy mô' : 'Size' }}</span><span class="info-val">{{ caseStudy.client_company_size }}</span></div>
            <div class="info-row" v-if="caseStudy.client_website"><span>Website</span><a :href="caseStudy.client_website" target="_blank" class="info-link">{{ isVi ? 'Truy cập' : 'Visit' }} ↗</a></div>
          </div>
        </div>

        <!-- Project Info -->
        <div class="sidebar-card">
          <h3 class="sidebar-title"><i class="pi pi-info-circle" /> {{ isVi ? 'Thông tin' : 'Details' }}</h3>
          <div class="info-rows">
            <div class="info-row"><span>{{ t('common.service_category') }}</span><span class="info-badge">{{ caseStudy.service_category }}</span></div>
            <div class="info-row"><span>{{ t('common.status') }}</span><span class="info-status" :class="`st-${caseStudy.status}`">{{ caseStudy.status }}</span></div>
            <div class="info-row"><span>{{ isVi ? 'Hiển thị' : 'Visibility' }}</span><span class="info-val"><i :class="caseStudy.visibility === 'public' ? 'pi pi-globe' : 'pi pi-lock'" /> {{ caseStudy.visibility }}</span></div>
            <div class="info-row" v-if="caseStudy.project_url"><span>{{ t('common.project_url') }}</span><a :href="caseStudy.project_url" target="_blank" class="info-link">{{ isVi ? 'Xem dự án' : 'View' }} ↗</a></div>
            <div class="info-row" v-if="caseStudy.project_start_date"><span>{{ isVi ? 'Bắt đầu' : 'Start' }}</span><span class="info-val">{{ caseStudy.project_start_date }}</span></div>
            <div class="info-row" v-if="caseStudy.project_end_date"><span>{{ isVi ? 'Kết thúc' : 'End' }}</span><span class="info-val">{{ caseStudy.project_end_date }}</span></div>
            <div class="info-row" v-if="caseStudy.view_count"><span>{{ isVi ? 'Lượt xem' : 'Views' }}</span><span class="info-val">{{ caseStudy.view_count }}</span></div>
          </div>
        </div>

        <!-- Tags -->
        <div class="sidebar-card" v-if="caseStudy.tags && caseStudy.tags.length">
          <h3 class="sidebar-title"><i class="pi pi-tags" /> {{ t('common.case_study_tags') }}</h3>
          <div class="tags-wrap">
            <span v-for="tag in caseStudy.tags" :key="tag.id" class="tag-pill" :style="{ background: tag.color + '18', color: tag.color, borderColor: tag.color + '30' }">{{ tag.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'
export default {
  components: { Head, Button },
  layout: Layout,
  props: { caseStudy: Object, linkedLeads: Array, linkedDeals: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  computed: { isVi() { return this.locale === 'vi' } },
  methods: {
    catIcon(cat) { return { website: 'pi pi-globe', marketing: 'pi pi-megaphone', seo: 'pi pi-chart-line', branding: 'pi pi-palette', landing_page: 'pi pi-desktop', ai_agent: 'pi pi-microchip' }[cat] || 'pi pi-folder' },
    clientColor(name) { const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6']; if (!name) return colors[0]; return colors[name.charCodeAt(0) % colors.length] },
  },
}
</script>

<style scoped>
.show-hero { min-height: 220px; background: linear-gradient(135deg, #581c87, #7c3aed, #a78bfa); background-size: cover; background-position: center; border-radius: 20px; overflow: hidden; margin-bottom: 1.25rem; position: relative; }
.hero-overlay { background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.65) 100%); padding: 2rem 2.5rem; min-height: 220px; display: flex; flex-direction: column; justify-content: flex-end; }
.hero-content { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; }
.hero-badges { display: flex; gap: 0.35rem; margin-bottom: 0.75rem; }
.hero-cat-badge { font-size: 0.65rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); color: white; display: flex; align-items: center; gap: 0.25rem; text-transform: uppercase; letter-spacing: 0.03em; }
.hero-cat-badge i { font-size: 0.6rem; }
.hero-featured { font-size: 0.6rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 6px; background: rgba(251,191,36,0.9); color: #78350f; display: flex; align-items: center; gap: 0.2rem; }
.hero-status-badge { font-size: 0.6rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 6px; }
.status-published { background: rgba(16,185,129,0.85); color: white; }
.status-draft { background: rgba(148,163,184,0.7); color: white; }
.hero-title { font-size: 1.75rem; font-weight: 800; color: white; margin: 0; letter-spacing: -0.02em; line-height: 1.2; }
.hero-client { font-size: 0.92rem; color: rgba(255,255,255,0.8); margin: 0.35rem 0 0; font-weight: 500; }
.hero-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
.hero-btn { background: white !important; color: #7c3aed !important; border: none !important; font-weight: 600; }
.hero-btn-secondary { background: rgba(255,255,255,0.15) !important; color: white !important; border: 1px solid rgba(255,255,255,0.3) !important; backdrop-filter: blur(8px); }

.metrics-ribbon { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.metric-item { background: white; border: 1px solid #f1f5f9; border-radius: 14px; padding: 1rem; text-align: center; transition: all 0.25s; }
.metric-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-2px); }
.metric-icon { font-size: 1.15rem; color: #6366f1; display: block; margin-bottom: 0.35rem; }
.metric-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; display: block; letter-spacing: -0.02em; }
.metric-label { font-size: 0.68rem; color: #94a3b8; font-weight: 500; }

.show-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; }
@media(max-width:900px) { .show-grid { grid-template-columns: 1fr; } }

.main-content { display: flex; flex-direction: column; gap: 0.75rem; }
.content-section { display: flex; gap: 1rem; background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 1.35rem 1.5rem; transition: all 0.25s; }
.content-section:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.04); }
.section-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
.section-body { flex: 1; min-width: 0; }
.section-title { font-size: 0.82rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.5rem; }
.section-text { font-size: 0.88rem; color: #334155; line-height: 1.75; margin: 0; white-space: pre-wrap; }

.testimonial-card { background: linear-gradient(135deg, #fef3c7, #fffbeb); border: 1px solid #fde68a; border-radius: 16px; padding: 1.5rem 1.75rem; position: relative; }
.testi-quote-mark { font-size: 4rem; font-weight: 800; color: #fbbf24; opacity: 0.4; line-height: 1; position: absolute; top: 0.5rem; left: 1rem; }
.testimonial-card blockquote { font-size: 0.95rem; color: #78350f; margin: 0 0 0.85rem; font-style: italic; line-height: 1.7; position: relative; z-index: 1; }
.testi-author { display: flex; align-items: center; gap: 0.5rem; }
.testi-avatar { width: 32px; height: 32px; border-radius: 50%; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; }
.testi-name { font-size: 0.82rem; font-weight: 700; color: #92400e; display: block; }
.testi-role { font-size: 0.68rem; color: #b45309; }

.sidebar { display: flex; flex-direction: column; gap: 0.75rem; }
.sidebar-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; padding: 1.15rem 1.35rem; }
.sidebar-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.85rem; padding-bottom: 0.6rem; border-bottom: 1px solid #f8fafc; display: flex; align-items: center; gap: 0.4rem; }
.sidebar-title i { font-size: 0.78rem; color: #6366f1; }
.client-profile { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.85rem; }
.client-avatar-lg { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; font-weight: 700; flex-shrink: 0; }
.client-name-lg { font-size: 0.92rem; font-weight: 700; color: #0f172a; display: block; }
.client-industry-lg { font-size: 0.72rem; color: #94a3b8; }
.info-rows { display: flex; flex-direction: column; gap: 0; }
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 0.45rem 0; border-bottom: 1px solid #fafbfe; font-size: 0.78rem; }
.info-row:last-child { border-bottom: none; }
.info-row span:first-child { color: #94a3b8; font-weight: 500; }
.info-val { color: #334155; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; }
.info-val i { font-size: 0.65rem; }
.info-badge { font-size: 0.68rem; font-weight: 700; background: #eef2ff; color: #6366f1; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: capitalize; }
.info-status { font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: capitalize; }
.st-published { background: #dcfce7; color: #16a34a; }
.st-draft { background: #f1f5f9; color: #64748b; }
.info-link { color: #6366f1; text-decoration: none; font-weight: 600; font-size: 0.78rem; }
.info-link:hover { text-decoration: underline; }
.tags-wrap { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.tag-pill { font-size: 0.72rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 20px; border: 1px solid; }
</style>
