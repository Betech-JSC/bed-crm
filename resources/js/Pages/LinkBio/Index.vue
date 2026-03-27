<template>
  <div>
    <Head title="Link-in-Bio" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-link" style="color:#6366f1;" /> Link-in-Bio</h1>
        <p class="page-subtitle">Tạo trang link đẹp — chia sẻ mọi thứ chỉ với 1 URL</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo Bio Page</button>
    </div>

    <!-- Pages -->
    <div v-if="pages?.length" class="pages-grid">
      <div v-for="page in pages" :key="page.id" class="bio-card">
        <div class="bc-theme" :class="'bt-' + page.theme">
          <div class="bc-avatar">{{ page.display_name.charAt(0) }}</div>
          <strong class="bc-name">{{ page.display_name }}</strong>
          <span class="bc-user">@{{ page.username }}</span>
        </div>
        <div class="bc-body">
          <div class="bc-stats">
            <div class="bs-item"><span class="bs-val">{{ page.total_views }}</span><span class="bs-lbl">Views</span></div>
            <div class="bs-item"><span class="bs-val">{{ page.total_clicks }}</span><span class="bs-lbl">Clicks</span></div>
            <div class="bs-item"><span class="bs-val bs-ctr">{{ page.ctr }}%</span><span class="bs-lbl">CTR</span></div>
            <div class="bs-item"><span class="bs-val">{{ page.links_count }}</span><span class="bs-lbl">Links</span></div>
          </div>
          <div class="bc-url"><code>{{ page.public_url }}</code></div>
          <div class="bc-actions">
            <button class="bc-btn" @click="copyUrl(page.public_url)"><i class="pi pi-copy" /> Copy</button>
            <a :href="page.public_url" target="_blank" class="bc-btn"><i class="pi pi-external-link" /> View</a>
            <button class="bc-btn bc-del" @click="deletePage(page.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-link" /></div>
      <h3>Chưa có Bio Page</h3>
      <p>Tạo Link-in-Bio cá nhân hoặc công ty</p>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo ngay</button>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Tạo Bio Page</h3><button class="modal-close" @click="showCreate = false"><i class="pi pi-times" /></button></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Username <span class="req">*</span></label><input v-model="form.username" type="text" class="fm-input" placeholder="your-name" /><span class="hint">/bio/{{ form.username || '...' }}</span></div>
          <div class="fm-group flex-1"><label>Display Name <span class="req">*</span></label><input v-model="form.display_name" type="text" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Bio</label><textarea v-model="form.bio" rows="2" class="fm-input" placeholder="Giới thiệu ngắn..." /></div>
        <div class="fm-group"><label>Theme</label>
          <div class="theme-grid">
            <button v-for="(info, key) in themes" :key="key" class="theme-opt" :class="{ active: form.theme === key }" @click="form.theme = key">{{ info.label }}</button>
          </div>
        </div>

        <!-- Links -->
        <div class="fm-group">
          <label>Links</label>
          <div v-for="(link, i) in form.links" :key="i" class="link-row">
            <input v-model="link.title" type="text" class="fm-input" placeholder="Title" />
            <input v-model="link.url" type="url" class="fm-input" placeholder="https://..." />
            <button class="opt-del" @click="form.links.splice(i, 1)"><i class="pi pi-times" /></button>
          </div>
          <button class="opt-add" @click="form.links.push({ title: '', url: '' })"><i class="pi pi-plus" /> Thêm link</button>
        </div>

        <button class="btn-save" :disabled="!form.username || !form.display_name || saving" @click="savePage">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Tạo
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { pages: Array, themes: Object },
  data() {
    return {
      showCreate: false, saving: false,
      form: { username: '', display_name: '', bio: '', theme: 'default', links: [{ title: '', url: '' }], social_links: [] },
    }
  },
  methods: {
    savePage() {
      this.saving = true
      router.post('/link-bio', this.form, {
        onSuccess: () => { this.form = { username: '', display_name: '', bio: '', theme: 'default', links: [{ title: '', url: '' }], social_links: [] }; this.showCreate = false },
        onFinish: () => { this.saving = false },
      })
    },
    deletePage(id) { if (confirm('Xóa bio page?')) router.delete(`/link-bio/${id}`) },
    copyUrl(url) { navigator.clipboard.writeText(url); alert('Đã copy!') },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.pages-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.75rem; }
.bio-card { background: white; border-radius: 16px; border: 1.5px solid #f1f5f9; overflow: hidden; transition: all 0.15s; }
.bio-card:hover { border-color: #6366f1; box-shadow: 0 8px 24px rgba(99,102,241,0.1); }
.bc-theme { padding: 1.2rem 1rem 0.8rem; text-align: center; }
.bt-default { background: #f8fafc; }
.bt-dark { background: #0f172a; color: #f1f5f9; }
.bt-gradient { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.bt-minimal { background: #fafbfc; }
.bt-neon { background: #0a0a0a; color: #00ff88; }
.bc-avatar { width: 48px; height: 48px; border-radius: 50%; background: rgba(99,102,241,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 900; margin: 0 auto 0.4rem; color: #6366f1; }
.bt-dark .bc-avatar, .bt-gradient .bc-avatar, .bt-neon .bc-avatar { background: rgba(255,255,255,0.2); color: white; }
.bc-name { display: block; font-size: 0.9rem; }
.bc-user { font-size: 0.65rem; opacity: 0.7; }
.bc-body { padding: 0.85rem; }
.bc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.25rem; margin-bottom: 0.5rem; }
.bs-item { text-align: center; padding: 0.3rem; background: #fafbfc; border-radius: 6px; }
.bs-val { font-size: 0.82rem; font-weight: 800; color: #0f172a; display: block; }
.bs-ctr { color: #6366f1; }
.bs-lbl { font-size: 0.45rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }
.bc-url { padding: 0.4rem; background: #0f172a; border-radius: 7px; margin-bottom: 0.5rem; }
.bc-url code { font-size: 0.58rem; color: #6366f1; word-break: break-all; font-family: 'JetBrains Mono', monospace; }
.bc-actions { display: flex; gap: 0.3rem; }
.bc-btn { display: flex; align-items: center; gap: 0.2rem; padding: 0.3rem 0.55rem; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-size: 0.6rem; font-weight: 600; cursor: pointer; text-decoration: none; font-family: inherit; }
.bc-btn:hover { border-color: #6366f1; color: #6366f1; }
.bc-del:hover { border-color: #ef4444; color: #ef4444; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.hint { font-size: 0.55rem; color: #94a3b8; margin-top: 0.1rem; display: block; }
.theme-grid { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.theme-opt { padding: 0.3rem 0.6rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; cursor: pointer; font-family: inherit; font-weight: 600; }
.theme-opt.active { border-color: #6366f1; background: #eef2ff; color: #6366f1; }
.link-row { display: flex; gap: 0.25rem; margin-bottom: 0.2rem; align-items: center; }
.opt-del { width: 22px; height: 22px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.5rem; flex-shrink: 0; }
.opt-add { padding: 0.2rem 0.5rem; border: 1px dashed #e2e8f0; border-radius: 5px; background: transparent; color: #94a3b8; font-size: 0.58rem; cursor: pointer; font-family: inherit; }
.btn-save { width: 100%; padding: 0.5rem; border-radius: 9px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 0.3rem; margin-top: 0.5rem; }
.btn-save:disabled { opacity: 0.5; }

.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #6366f1; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0 0 0.75rem; }
</style>
