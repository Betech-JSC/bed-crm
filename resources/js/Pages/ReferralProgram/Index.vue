<template>
  <div>
    <Head title="Referral Program" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-users" style="color:#10b981;" /> Referral Program</h1>
        <p class="page-subtitle">Tạo chương trình giới thiệu — biến khách hàng thành đối tác tăng trưởng</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo mã giới thiệu</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-codes"><i class="pi pi-tag" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_codes }}</span><span class="stat-lbl">Mã giới thiệu</span></div></div>
      <div class="stat-card"><div class="stat-icon si-active"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.active_codes }}</span><span class="stat-lbl">Active</span></div></div>
      <div class="stat-card"><div class="stat-icon si-refs"><i class="pi pi-users" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_referrals }}</span><span class="stat-lbl">Referrals</span></div></div>
      <div class="stat-card"><div class="stat-icon si-rev"><i class="pi pi-dollar" /></div><div class="stat-body"><span class="stat-val">{{ formatMoney(stats.total_revenue) }}</span><span class="stat-lbl">Revenue</span></div></div>
      <div class="stat-card"><div class="stat-icon si-comm"><i class="pi pi-wallet" /></div><div class="stat-body"><span class="stat-val">{{ formatMoney(stats.total_commission) }}</span><span class="stat-lbl">Commission</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'codes' }" @click="tab = 'codes'"><i class="pi pi-tag" /> Mã giới thiệu</button>
      <button class="tab" :class="{ active: tab === 'conversions' }" @click="tab = 'conversions'"><i class="pi pi-chart-bar" /> Conversions</button>
    </div>

    <!-- Codes Tab -->
    <div v-show="tab === 'codes'">
      <div class="filter-bar">
        <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm mã..." @input="doSearch" /></div>
        <select v-model="filterStatus" class="filter-select" @change="doSearch"><option value="">Tất cả</option><option value="active">Active</option><option value="paused">Paused</option><option value="expired">Expired</option></select>
      </div>

      <div v-if="codes.data?.length" class="codes-grid">
        <div v-for="code in codes.data" :key="code.id" class="code-card">
          <div class="cc-header">
            <span class="cc-code">{{ code.code }}</span>
            <span class="cc-status" :class="'cs-' + code.status">{{ code.status }}</span>
          </div>
          <div class="cc-referrer">
            <div class="cc-avatar">{{ code.referrer_name.charAt(0) }}</div>
            <div><span class="cc-name">{{ code.referrer_name }}</span><span class="cc-email">{{ code.referrer_email }}</span></div>
          </div>
          <div class="cc-reward"><i class="pi pi-gift" /> {{ code.reward_display }} ({{ code.reward_type }})</div>
          <div class="cc-metrics">
            <div class="cm-item"><span class="cm-val">{{ code.uses_count }}</span><span class="cm-lbl">Uses</span></div>
            <div class="cm-item"><span class="cm-val">{{ code.conversions_count }}</span><span class="cm-lbl">Converts</span></div>
            <div class="cm-item"><span class="cm-val cm-money">{{ formatMoney(code.total_revenue) }}</span><span class="cm-lbl">Revenue</span></div>
          </div>
          <div class="cc-footer">
            <span v-if="code.expires_at" class="cc-exp">Hết hạn: {{ code.expires_at }}</span>
            <span v-if="code.max_uses" class="cc-limit">{{ code.uses_count }}/{{ code.max_uses }}</span>
            <div class="cc-actions">
              <button class="cc-btn" @click="copyCode(code.code)" title="Copy"><i class="pi pi-copy" /></button>
              <button class="cc-btn" @click="openConversionModal(code)" title="Add referral"><i class="pi pi-user-plus" /></button>
              <button class="cc-btn cc-del" @click="deleteCode(code.id)" title="Xóa"><i class="pi pi-trash" /></button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-users" /></div>
        <h3>Chưa có mã giới thiệu</h3>
        <p>Tạo mã để khách hàng giới thiệu bạn bè</p>
      </div>
    </div>

    <!-- Conversions Tab -->
    <div v-show="tab === 'conversions'">
      <div v-if="recentConversions?.length" class="conv-list">
        <div v-for="c in recentConversions" :key="c.id" class="conv-row">
          <div class="cv-avatar">{{ c.referred_name.charAt(0) }}</div>
          <div class="cv-body">
            <span class="cv-name">{{ c.referred_name }}</span>
            <span class="cv-from">via <strong>{{ c.code }}</strong> ({{ c.referrer_name }})</span>
          </div>
          <span class="cv-status" :class="'cvs-' + c.status">{{ c.status }}</span>
          <span v-if="c.deal_value" class="cv-deal">{{ formatMoney(c.deal_value) }}</span>
          <span v-if="c.commission_amount" class="cv-comm">💰 {{ formatMoney(c.commission_amount) }}</span>
          <span class="cv-date">{{ c.created_at }}</span>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-chart-bar" /></div>
        <h3>Chưa có conversion</h3>
        <p>Khi khách giới thiệu thành công, dữ liệu sẽ hiện ở đây</p>
      </div>
    </div>

    <!-- Create Code Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Tạo mã giới thiệu</h3><button class="modal-close" @click="showCreate = false"><i class="pi pi-times" /></button></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Tên người giới thiệu <span class="req">*</span></label><input v-model="form.referrer_name" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Email</label><input v-model="form.referrer_email" type="email" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Mã tùy chỉnh (để trống = tự tạo)</label><input v-model="form.code" type="text" class="fm-input" placeholder="VD: NGUYEN2026" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Loại reward</label>
            <select v-model="form.reward_type" class="fm-input"><option value="discount">Discount</option><option value="credit">Credit</option><option value="commission">Commission</option></select>
          </div>
          <div class="fm-group" style="width:100px;"><label>Giá trị</label><input v-model.number="form.reward_value" type="number" class="fm-input" /></div>
          <div class="fm-group" style="width:100px;"><label>Đơn vị</label>
            <select v-model="form.reward_unit" class="fm-input"><option value="percent">%</option><option value="fixed">VNĐ</option></select>
          </div>
        </div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Giới hạn sử dụng</label><input v-model.number="form.max_uses" type="number" min="1" class="fm-input" placeholder="Không giới hạn" /></div>
          <div class="fm-group flex-1"><label>Hết hạn</label><input v-model="form.expires_at" type="date" class="fm-input" /></div>
        </div>
        <button class="btn-save" :disabled="!form.referrer_name || saving" @click="saveCode">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Tạo mã
        </button>
      </div>
    </div>

    <!-- Add Conversion Modal -->
    <div v-if="showConversion" class="modal-overlay" @click.self="showConversion = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Ghi nhận referral — {{ convCode?.code }}</h3><button class="modal-close" @click="showConversion = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Tên người được giới thiệu <span class="req">*</span></label><input v-model="convForm.referred_name" type="text" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Email</label><input v-model="convForm.referred_email" type="email" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Điện thoại</label><input v-model="convForm.referred_phone" type="text" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Giá trị deal</label><input v-model.number="convForm.deal_value" type="number" class="fm-input" placeholder="VND" /></div>
        <button class="btn-save btn-green" :disabled="!convForm.referred_name || saving" @click="saveConversion">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-user-plus'" /> Ghi nhận
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
  props: { codes: Object, stats: Object, recentConversions: Array, filters: Object },
  data() {
    return {
      tab: 'codes', search: this.filters?.search || '', filterStatus: this.filters?.status || '',
      searchTimeout: null, showCreate: false, showConversion: false, saving: false,
      convCode: null,
      form: { referrer_name: '', referrer_email: '', code: '', reward_type: 'commission', reward_value: 10, reward_unit: 'percent', max_uses: null, expires_at: '' },
      convForm: { referred_name: '', referred_email: '', referred_phone: '', deal_value: null },
    }
  },
  methods: {
    formatMoney(v) { return v ? new Intl.NumberFormat('vi-VN').format(v) + 'đ' : '0đ' },
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/referral-program', { search: this.search || undefined, status: this.filterStatus || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    saveCode() {
      this.saving = true
      router.post('/referral-program', this.form, {
        onSuccess: () => { this.form = { referrer_name: '', referrer_email: '', code: '', reward_type: 'commission', reward_value: 10, reward_unit: 'percent', max_uses: null, expires_at: '' }; this.showCreate = false },
        onFinish: () => { this.saving = false },
      })
    },
    deleteCode(id) { if (confirm('Xóa mã giới thiệu này?')) router.delete(`/referral-program/${id}`) },
    copyCode(code) { navigator.clipboard.writeText(code); alert('Đã copy: ' + code) },
    openConversionModal(code) { this.convCode = code; this.convForm = { referred_name: '', referred_email: '', referred_phone: '', deal_value: null }; this.showConversion = true },
    saveConversion() {
      this.saving = true
      router.post(`/referral-program/${this.convCode.id}/conversion`, this.convForm, {
        onSuccess: () => { this.showConversion = false },
        onFinish: () => { this.saving = false },
      })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-codes { background: #ecfdf5; color: #10b981; }
.si-active { background: #d1fae5; color: #059669; }
.si-refs { background: #eef2ff; color: #6366f1; }
.si-rev { background: #fef3c7; color: #f59e0b; }
.si-comm { background: #ede9fe; color: #8b5cf6; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; }

.tabs-bar { display: flex; gap: 0; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.tab { padding: 0.5rem 0.9rem; border: none; background: transparent; font-size: 0.72rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; }
.tab.active { color: #10b981; border-bottom-color: #10b981; }

.filter-bar { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 240px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.68rem; color: #475569; font-family: inherit; outline: none; }

/* Code Cards */
.codes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 0.65rem; }
.code-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; transition: all 0.15s; }
.code-card:hover { border-color: #10b981; box-shadow: 0 4px 14px rgba(16,185,129,0.08); }
.cc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.cc-code { font-family: 'JetBrains Mono', monospace; font-size: 1rem; font-weight: 900; color: #10b981; letter-spacing: 0.05em; }
.cc-status { padding: 0.1rem 0.35rem; border-radius: 5px; font-size: 0.55rem; font-weight: 700; text-transform: uppercase; }
.cs-active { background: #ecfdf5; color: #10b981; }
.cs-paused { background: #fef3c7; color: #f59e0b; }
.cs-expired { background: #f1f5f9; color: #94a3b8; }
.cc-referrer { display: flex; gap: 0.4rem; align-items: center; margin-bottom: 0.4rem; }
.cc-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; flex-shrink: 0; }
.cc-name { font-size: 0.72rem; font-weight: 700; color: #0f172a; display: block; }
.cc-email { font-size: 0.58rem; color: #94a3b8; }
.cc-reward { font-size: 0.68rem; color: #6366f1; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.2rem; }
.cc-reward i { font-size: 0.6rem; }
.cc-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.25rem; margin-bottom: 0.5rem; }
.cm-item { text-align: center; padding: 0.3rem; background: #fafbfc; border-radius: 6px; }
.cm-val { font-size: 0.82rem; font-weight: 800; color: #0f172a; display: block; }
.cm-money { color: #f59e0b; }
.cm-lbl { font-size: 0.45rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; }
.cc-footer { display: flex; justify-content: space-between; align-items: center; }
.cc-exp, .cc-limit { font-size: 0.55rem; color: #94a3b8; }
.cc-actions { display: flex; gap: 0.2rem; }
.cc-btn { width: 24px; height: 24px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; font-size: 0.5rem; }
.cc-btn:hover { border-color: #10b981; color: #10b981; }
.cc-del:hover { border-color: #ef4444; color: #ef4444; }

/* Conversions List */
.conv-list { display: flex; flex-direction: column; gap: 0.3rem; }
.conv-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.7rem; background: white; border-radius: 10px; border: 1.5px solid #f1f5f9; }
.cv-avatar { width: 28px; height: 28px; border-radius: 50%; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 800; flex-shrink: 0; }
.cv-body { flex: 1; }
.cv-name { font-size: 0.75rem; font-weight: 700; color: #0f172a; display: block; }
.cv-from { font-size: 0.58rem; color: #94a3b8; }
.cv-from strong { color: #10b981; }
.cv-status { padding: 0.1rem 0.35rem; border-radius: 5px; font-size: 0.55rem; font-weight: 700; text-transform: capitalize; }
.cvs-pending { background: #fef3c7; color: #f59e0b; }
.cvs-qualified { background: #eef2ff; color: #6366f1; }
.cvs-converted { background: #ecfdf5; color: #10b981; }
.cvs-paid { background: #d1fae5; color: #059669; }
.cv-deal { font-size: 0.68rem; font-weight: 700; color: #0f172a; }
.cv-comm { font-size: 0.6rem; color: #8b5cf6; font-weight: 600; }
.cv-date { font-size: 0.55rem; color: #cbd5e1; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.btn-save { width: 100%; padding: 0.5rem; border-radius: 9px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 0.3rem; margin-top: 0.5rem; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-green { background: linear-gradient(135deg, #10b981, #059669); }

.empty-state { text-align: center; padding: 2.5rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #10b981; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }

@media (max-width: 768px) { .stats-row { grid-template-columns: repeat(2, 1fr); } .codes-grid { grid-template-columns: 1fr; } }
</style>
