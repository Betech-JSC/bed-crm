<template>
  <div class="bio-public" :class="'theme-' + page.theme">
    <div class="bio-container">
      <div class="bio-avatar">{{ page.display_name.charAt(0) }}</div>
      <h1 class="bio-name">{{ page.display_name }}</h1>
      <p v-if="page.bio" class="bio-desc">{{ page.bio }}</p>

      <div class="bio-links">
        <a v-for="(link, i) in (page.links || [])" :key="i" :href="link.url" target="_blank" class="bio-link">
          {{ link.title }}
        </a>
      </div>

      <div v-if="page.social_links?.length" class="bio-social">
        <a v-for="(s, i) in page.social_links" :key="i" :href="s.url" target="_blank" class="social-icon">{{ s.platform }}</a>
      </div>

      <div class="bio-footer">Powered by BED CRM</div>
    </div>
  </div>
</template>

<script>
export default {
  props: { page: Object },
}
</script>

<style scoped>
.bio-public { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; font-family: 'Inter', sans-serif; }
.theme-default { background: #f8fafc; color: #1e293b; }
.theme-dark { background: #0f172a; color: #f1f5f9; }
.theme-gradient { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.theme-minimal { background: #fafbfc; color: #475569; }
.theme-neon { background: #0a0a0a; color: #00ff88; }
.bio-container { max-width: 400px; width: 100%; text-align: center; }
.bio-avatar { width: 80px; height: 80px; border-radius: 50%; background: rgba(99,102,241,0.2); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900; margin: 0 auto 1rem; }
.theme-dark .bio-avatar, .theme-gradient .bio-avatar, .theme-neon .bio-avatar { background: rgba(255,255,255,0.15); }
.bio-name { font-size: 1.5rem; font-weight: 900; margin: 0 0 0.3rem; }
.bio-desc { font-size: 0.9rem; opacity: 0.8; margin: 0 0 1.5rem; }
.bio-links { display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1.5rem; }
.bio-link { display: block; padding: 0.85rem 1rem; border-radius: 14px; text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; }
.theme-default .bio-link { background: white; border: 1.5px solid #e2e8f0; color: #1e293b; }
.theme-default .bio-link:hover { border-color: #6366f1; transform: translateY(-2px); }
.theme-dark .bio-link { background: rgba(255,255,255,0.08); border: 1.5px solid rgba(255,255,255,0.15); color: white; }
.theme-dark .bio-link:hover { background: rgba(255,255,255,0.15); }
.theme-gradient .bio-link { background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.25); color: white; }
.theme-gradient .bio-link:hover { background: rgba(255,255,255,0.25); }
.theme-minimal .bio-link { background: white; border: 1px solid #f1f5f9; color: #475569; }
.theme-neon .bio-link { background: transparent; border: 1.5px solid #00ff88; color: #00ff88; }
.theme-neon .bio-link:hover { background: #00ff88; color: #0a0a0a; }
.bio-social { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1rem; }
.social-icon { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.65rem; opacity: 0.6; text-decoration: none; color: inherit; }
.bio-footer { font-size: 0.6rem; opacity: 0.4; margin-top: 1.5rem; }
</style>
