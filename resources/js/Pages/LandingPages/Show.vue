<template>
  <div class="lp-public">
    <div v-for="(block, i) in page.blocks" :key="i" class="lp-block" :class="'lp-' + block.type">
      <!-- Hero -->
      <div v-if="block.type === 'hero'" class="hero-section" :style="{ background: block.data.bg_color || '#4f46e5', color: block.data.text_color || '#fff' }">
        <h1 class="hero-h1">{{ block.data.heading }}</h1>
        <p class="hero-sub">{{ block.data.subheading }}</p>
        <a href="#form-section" class="hero-cta">{{ block.data.cta_text }}</a>
      </div>

      <!-- Stats -->
      <div v-if="block.type === 'stats'" class="stats-section">
        <div v-for="(item, si) in block.data.items" :key="si" class="stat-box">
          <span class="stat-num">{{ item.number }}</span>
          <span class="stat-label">{{ item.label }}</span>
        </div>
      </div>

      <!-- Features -->
      <div v-if="block.type === 'features'" class="features-section">
        <h2 class="sec-heading">{{ block.data.heading }}</h2>
        <div class="features-grid">
          <div v-for="(item, fi) in block.data.items" :key="fi" class="feat-card">
            <span class="feat-icon">{{ item.icon }}</span>
            <h3>{{ item.title }}</h3>
            <p>{{ item.desc }}</p>
          </div>
        </div>
      </div>

      <!-- Testimonials -->
      <div v-if="block.type === 'testimonials'" class="testi-section">
        <h2 class="sec-heading">{{ block.data.heading }}</h2>
        <div class="testi-grid">
          <div v-for="(item, ti) in block.data.items" :key="ti" class="testi-card">
            <p class="testi-text">"{{ item.text }}"</p>
            <div class="testi-author"><strong>{{ item.name }}</strong><span>{{ item.role }}</span></div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div v-if="block.type === 'cta'" class="cta-section" :style="{ background: block.data.bg_color || '#10b981' }">
        <h2>{{ block.data.heading }}</h2>
        <p>{{ block.data.subheading }}</p>
        <a href="#form-section" class="cta-btn">{{ block.data.button_text }}</a>
      </div>

      <!-- FAQ -->
      <div v-if="block.type === 'faq'" class="faq-section">
        <h2 class="sec-heading">{{ block.data.heading }}</h2>
        <div v-for="(item, qi) in block.data.items" :key="qi" class="faq-item">
          <h4>{{ item.q }}</h4>
          <p>{{ item.a }}</p>
        </div>
      </div>

      <!-- Text -->
      <div v-if="block.type === 'text'" class="text-section">
        <div v-html="block.data.content" />
      </div>

      <!-- Form -->
      <div v-if="block.type === 'form'" id="form-section" class="form-section">
        <h2 class="sec-heading">{{ block.data.heading }}</h2>
        <p class="form-hint">Vui lòng liên hệ để được tư vấn</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'

export default {
  components: { Head },
  props: { page: Object, webForm: Object },
}
</script>

<style scoped>
.lp-public { max-width: 960px; margin: 0 auto; font-family: 'Inter', sans-serif; }
.hero-section { text-align: center; padding: 4rem 2rem; border-radius: 0 0 24px 24px; }
.hero-h1 { font-size: 2.2rem; font-weight: 900; margin: 0 0 0.6rem; }
.hero-sub { font-size: 1.1rem; opacity: 0.9; margin: 0 0 1.5rem; }
.hero-cta { display: inline-block; padding: 0.75rem 2rem; border-radius: 12px; background: rgba(255,255,255,0.2); color: white; font-weight: 700; text-decoration: none; border: 2px solid rgba(255,255,255,0.4); font-size: 1rem; }
.stats-section { display: flex; justify-content: center; gap: 2rem; padding: 2rem; }
.stat-box { text-align: center; }
.stat-num { font-size: 2rem; font-weight: 900; color: #0f172a; display: block; }
.stat-label { font-size: 0.85rem; color: #64748b; }
.features-section, .testi-section, .faq-section, .form-section { padding: 2.5rem 1.5rem; }
.sec-heading { text-align: center; font-size: 1.5rem; font-weight: 800; margin: 0 0 1.5rem; color: #0f172a; }
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.feat-card { text-align: center; padding: 1.5rem; border-radius: 14px; border: 1.5px solid #f1f5f9; background: white; }
.feat-icon { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.feat-card h3 { font-size: 1rem; margin: 0 0 0.3rem; color: #0f172a; }
.feat-card p { font-size: 0.85rem; color: #64748b; margin: 0; }
.testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
.testi-card { padding: 1.2rem; border-radius: 12px; background: #f8fafc; border: 1px solid #f1f5f9; }
.testi-text { font-size: 0.95rem; color: #475569; font-style: italic; margin: 0 0 0.75rem; }
.testi-author strong { font-size: 0.85rem; color: #0f172a; display: block; }
.testi-author span { font-size: 0.75rem; color: #94a3b8; }
.cta-section { text-align: center; padding: 3rem 2rem; color: white; border-radius: 16px; margin: 1rem 0; }
.cta-section h2 { font-size: 1.6rem; font-weight: 800; margin: 0 0 0.4rem; }
.cta-section p { font-size: 1rem; opacity: 0.9; margin: 0 0 1.2rem; }
.cta-btn { display: inline-block; padding: 0.7rem 2rem; border-radius: 10px; background: white; color: #0f172a; font-weight: 700; text-decoration: none; }
.faq-item { padding: 0.85rem; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 0.5rem; }
.faq-item h4 { font-size: 0.9rem; margin: 0 0 0.3rem; color: #0f172a; }
.faq-item p { font-size: 0.82rem; color: #64748b; margin: 0; }
.text-section { padding: 2rem 1.5rem; font-size: 0.95rem; color: #475569; line-height: 1.7; }
.form-section { text-align: center; }
.form-hint { color: #64748b; font-size: 0.9rem; }
</style>
