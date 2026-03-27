<template>
  <div>
    <Head title="QR Codes" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-qrcode" style="color:#0ea5e9;" /> QR Codes</h1>
        <p class="page-subtitle">Tạo QR code thông minh với tracking scans</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo QR Code</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-qrcode" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">QR Codes</span></div></div>
      <div class="stat-card"><div class="stat-icon si-scans"><i class="pi pi-mobile" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_scans }}</span><span class="stat-lbl">Total Scans</span></div></div>
      <div class="stat-card"><div class="stat-icon si-unique"><i class="pi pi-users" /></div><div class="stat-body"><span class="stat-val">{{ stats.unique_scans }}</span><span class="stat-lbl">Unique Scans</span></div></div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm QR..." @input="doSearch" /></div>
      <select v-model="filterType" class="filter-select" @change="doSearch"><option value="">Tất cả loại</option><option v-for="(info, key) in qrTypes" :key="key" :value="key">{{ info.label }}</option></select>
    </div>

    <!-- QR Grid -->
    <div v-if="codes.data?.length" class="qr-grid">
      <div v-for="qr in codes.data" :key="qr.id" class="qr-card">
        <div class="qc-preview">
          <div class="qc-placeholder"><i class="pi pi-qrcode" /></div>
        </div>
        <div class="qc-body">
          <div class="qc-type"><i :class="qrTypes[qr.qr_type]?.icon" /> {{ qrTypes[qr.qr_type]?.label }}</div>
          <h3 class="qc-name">{{ qr.name }}</h3>
          <div class="qc-url">{{ qr.target_url }}</div>
          <div class="qc-stats">
            <div class="qs-item"><span class="qs-val">{{ qr.scans_count }}</span><span class="qs-lbl">Scans</span></div>
            <div class="qs-item"><span class="qs-val">{{ qr.unique_scans }}</span><span class="qs-lbl">Unique</span></div>
          </div>
          <div class="qc-actions">
            <button class="qc-btn" @click="copyUrl(qr.tracking_url)"><i class="pi pi-copy" /> URL</button>
            <button class="qc-btn qc-del" @click="deleteQr(qr.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-qrcode" /></div>
      <h3>Chưa có QR Code</h3>
      <p>Tạo QR code với tracking scans thông minh</p>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Tạo QR Code</h3><button class="modal-close" @click="showCreate = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Tên <span class="req">*</span></label><input v-model="form.name" type="text" class="fm-input" placeholder="QR cho website, vCard, WiFi..." /></div>
        <div class="fm-group"><label>Loại QR</label>
          <div class="type-grid">
            <button v-for="(info, key) in qrTypes" :key="key" class="type-opt" :class="{ active: form.qr_type === key }" @click="form.qr_type = key"><i :class="info.icon" /> {{ info.label }}</button>
          </div>
        </div>
        <div class="fm-group"><label>URL đích <span class="req">*</span></label><input v-model="form.target_url" type="url" class="fm-input" placeholder="https://..." /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Màu QR</label><input v-model="designFg" type="color" class="color-input" /></div>
          <div class="fm-group flex-1"><label>Màu nền</label><input v-model="designBg" type="color" class="color-input" /></div>
        </div>
        <button class="btn-save" :disabled="!form.name || !form.target_url || saving" @click="saveQr">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-qrcode'" /> Tạo QR Code
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
  props: { codes: Object, stats: Object, qrTypes: Object, filters: Object },
  data() {
    return {
      search: this.filters?.search || '', filterType: this.filters?.qr_type || '',
      searchTimeout: null, showCreate: false, saving: false,
      designFg: '#000000', designBg: '#ffffff',
      form: { name: '', target_url: '', qr_type: 'url', design: null },
    }
  },
  methods: {
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/qr-codes', { search: this.search || undefined, qr_type: this.filterType || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    saveQr() {
      this.saving = true
      this.form.design = { foreground: this.designFg, background: this.designBg }
      router.post('/qr-codes', this.form, {
        onSuccess: () => { this.form = { name: '', target_url: '', qr_type: 'url', design: null }; this.showCreate = false },
        onFinish: () => { this.saving = false },
      })
    },
    deleteQr(id) { if (confirm('Xóa QR code?')) router.delete(`/qr-codes/${id}`) },
    copyUrl(url) { navigator.clipboard.writeText(url); alert('Đã copy tracking URL!') },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #e0f2fe; color: #0ea5e9; }
.si-scans { background: #ecfdf5; color: #10b981; }
.si-unique { background: #ede9fe; color: #8b5cf6; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; }

.filter-bar { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 240px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.68rem; color: #475569; font-family: inherit; outline: none; }

.qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.65rem; }
.qr-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; overflow: hidden; transition: all 0.15s; }
.qr-card:hover { border-color: #0ea5e9; box-shadow: 0 4px 14px rgba(14,165,233,0.08); }
.qc-preview { height: 120px; background: #f8fafc; display: flex; align-items: center; justify-content: center; }
.qc-placeholder { width: 80px; height: 80px; border-radius: 10px; background: white; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; }
.qc-placeholder i { font-size: 2rem; color: #0ea5e9; }
.qc-body { padding: 0.75rem; }
.qc-type { font-size: 0.55rem; font-weight: 700; color: #0ea5e9; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.15rem; }
.qc-type i { font-size: 0.5rem; }
.qc-name { font-size: 0.82rem; font-weight: 700; color: #0f172a; margin: 0 0 0.15rem; }
.qc-url { font-size: 0.55rem; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 0.5rem; }
.qc-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.25rem; margin-bottom: 0.5rem; }
.qs-item { text-align: center; padding: 0.3rem; background: #fafbfc; border-radius: 6px; }
.qs-val { font-size: 0.82rem; font-weight: 800; color: #0f172a; display: block; }
.qs-lbl { font-size: 0.45rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }
.qc-actions { display: flex; gap: 0.2rem; }
.qc-btn { display: flex; align-items: center; gap: 0.15rem; padding: 0.25rem 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-size: 0.55rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.qc-btn:hover { border-color: #0ea5e9; color: #0ea5e9; }
.qc-del:hover { border-color: #ef4444; color: #ef4444; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.type-grid { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.type-opt { padding: 0.35rem 0.6rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; font-weight: 600; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.2rem; }
.type-opt.active { border-color: #0ea5e9; background: #e0f2fe; color: #0284c7; }
.type-opt i { font-size: 0.6rem; }
.color-input { width: 100%; height: 32px; border: none; cursor: pointer; border-radius: 6px; }
.btn-save { width: 100%; padding: 0.5rem; border-radius: 9px; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 0.3rem; margin-top: 0.5rem; }
.btn-save:disabled { opacity: 0.5; }

.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #e0f2fe; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #0ea5e9; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }
</style>
