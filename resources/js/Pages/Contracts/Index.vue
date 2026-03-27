<template>
  <div>
    <Head title="Hợp đồng" />
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-file-check" /></div>
        <div><h1 class="page-title">Quản lý hợp đồng</h1><p class="page-subtitle">{{ stats.total }} hợp đồng — Giá trị: {{ formatPrice(stats.total_value) }}</p></div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip sc-active"><i class="pi pi-check-circle" /> {{ stats.active }} hiệu lực</span>
          <span class="stat-chip sc-pending"><i class="pi pi-clock" /> {{ stats.pending }} chờ duyệt</span>
        </div>
        <Button label="Tạo hợp đồng" icon="pi pi-plus" @click="openDialog()" />
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-box"><i class="pi pi-search" /><input v-model="filterForm.search" placeholder="Tìm theo mã, tiêu đề..." class="search-input" /></div>
      <Select v-model="filterForm.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Trạng thái" showClear class="filter-select" />
      <Select v-model="filterForm.type" :options="typeOpts" optionLabel="label" optionValue="value" placeholder="Loại HĐ" showClear class="filter-select" />
    </div>

    <div v-if="contracts.data.length" class="contract-list">
      <div v-for="c in contracts.data" :key="c.id" class="contract-card" @click="openDialog(c)">
        <div class="contract-status-indicator" :class="`si-${c.status}`" />
        <div class="contract-info">
          <div class="contract-top">
            <span class="contract-number">{{ c.contract_number }}</span>
            <span class="status-badge" :class="`s-${c.status}`">{{ c.status_label }}</span>
            <span class="type-tag">{{ c.type_label }}</span>
          </div>
          <h3 class="contract-title">{{ c.title }}</h3>
          <div class="contract-meta">
            <span><i class="pi pi-building" /> {{ c.customer_name }}</span>
            <span v-if="c.start_date"><i class="pi pi-calendar" /> {{ c.start_date }} — {{ c.end_date || '∞' }}</span>
            <span v-if="c.days_remaining != null && c.is_active_contract" class="days-tag" :class="c.days_remaining < 30 ? 'urgent' : ''"><i class="pi pi-clock" /> {{ c.days_remaining }} ngày còn lại</span>
          </div>
        </div>
        <div class="contract-value">
          <span class="value-amount">{{ formatPrice(c.value) }}</span>
          <span class="value-label">giá trị HĐ</span>
        </div>
        <div class="contract-actions" @click.stop>
          <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openDialog(c)" />
          <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteContract(c)" />
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-file-check" /></div>
      <h3>Chưa có hợp đồng</h3>
      <p>Tạo hợp đồng đầu tiên để quản lý.</p>
      <Button label="Tạo hợp đồng" icon="pi pi-plus" class="mt-1" @click="openDialog()" />
    </div>

    <!-- ===== CREATE / EDIT DIALOG ===== -->
    <div v-if="dialog" class="dialog-overlay" @click.self="dialog = false" @keydown.esc="dialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon"><i class="pi pi-file-check" /></div>
            <h3>{{ form.id ? 'Chỉnh sửa' : 'Tạo' }} hợp đồng</h3>
          </div>
          <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitForm" class="dialog-body">
          <!-- General Info -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-info-circle section-icon" /><h4 class="section-title">Thông tin chung</h4></div>
            <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="form.title" class="w-full" placeholder="VD: Hợp đồng triển khai CRM" :class="{'p-invalid':form.errors?.title}" /></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Khách hàng</label><Select v-model="form.customer_id" :options="customers" optionLabel="name" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
              <div class="form-group flex-1"><label>Deal</label><Select v-model="form.deal_id" :options="deals" optionLabel="title" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            </div>
            <div class="form-group"><label>Từ báo giá</label><Select v-model="form.quotation_id" :options="quotations" optionLabel="quote_number" optionValue="id" placeholder="Chọn" showClear class="w-full" /></div>
            <div class="form-group">
              <label>Loại HĐ</label>
              <div class="type-grid">
                <button v-for="t in types" :key="t.value" type="button" class="type-chip" :class="{active:form.contract_type===t.value}" @click="form.contract_type=t.value"><i :class="t.icon" /> {{ t.label }}</button>
              </div>
            </div>
          </div>
          <!-- Value & Duration -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-dollar section-icon" /><h4 class="section-title">Giá trị & Thời hạn</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Giá trị HĐ <span class="req">*</span></label><InputNumber v-model="form.value" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
              <div class="form-group flex-1"><label>Bắt đầu</label><InputText v-model="form.start_date" type="date" class="w-full" /></div>
              <div class="form-group flex-1"><label>Kết thúc</label><InputText v-model="form.end_date" type="date" class="w-full" /></div>
            </div>
            <div class="toggle-row"><div><label class="toggle-label">Tự động gia hạn</label><small class="toggle-desc">Hợp đồng sẽ tự renew khi hết hạn</small></div><InputSwitch v-model="form.auto_renew" /></div>
          </div>
          <!-- Content -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-file section-icon" /><h4 class="section-title">Nội dung</h4></div>
            <div class="form-group"><label>Điều khoản thanh toán</label><Editor v-model="form.payment_terms" editorStyle="height: 80px" class="w-full" /></div>
            <div class="form-group"><label>Phạm vi công việc</label><Editor v-model="form.scope_of_work" editorStyle="height: 80px" class="w-full" /></div>
            <div class="form-group"><label>Điều khoản & Điều kiện</label><Editor v-model="form.terms_conditions" editorStyle="height: 80px" class="w-full" /></div>
          </div>

          <!-- Submit for approval -->
          <div v-if="form.id && form.status === 'draft'" class="submit-approval-row">
            <Button label="Gửi phê duyệt" icon="pi pi-send" severity="info" outlined type="button" @click="submitApproval" />
          </div>

          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="dialog = false" type="button" />
            <Button :label="form.id ? 'Cập nhật' : 'Tạo hợp đồng'" icon="pi pi-check" type="submit" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Editor from 'primevue/editor'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Button, Select, InputText, InputNumber, InputSwitch, Editor },
  layout: Layout,
  props: { contracts: Object, filters: Object, stats: Object, customers: Array, deals: Array, quotations: Array },
  data() {
    return {
      dialog: false,
      form: this.emptyForm(),
      filterForm: { search: this.filters.search, status: this.filters.status, type: this.filters.type },
      statusOpts: [
        { label: 'Nháp', value: 'draft' }, { label: 'Chờ duyệt', value: 'pending_approval' },
        { label: 'Đang hiệu lực', value: 'active' }, { label: 'Hoàn tất', value: 'completed' },
        { label: 'Đã hủy', value: 'cancelled' },
      ],
      typeOpts: [
        { label: 'Một lần', value: 'one_time' }, { label: 'Định kỳ', value: 'recurring' },
        { label: 'Retainer', value: 'retainer' }, { label: 'Theo dự án', value: 'project_based' },
      ],
      types: [
        { value: 'one_time', label: 'Một lần', icon: 'pi pi-bolt' },
        { value: 'recurring', label: 'Định kỳ', icon: 'pi pi-sync' },
        { value: 'retainer', label: 'Retainer', icon: 'pi pi-wallet' },
        { value: 'project_based', label: 'Theo dự án', icon: 'pi pi-folder' },
      ],
    }
  },
  mounted() {
    this._escHandler = (e) => { if (e.key === 'Escape') { this.dialog = false } }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  watch: { filterForm: { deep: true, handler: throttle(function () { this.$inertia.get('/contracts', pickBy(this.filterForm), { preserveState: true }) }, 300) } },
  methods: {
    emptyForm() {
      return this.$inertia.form({
        id: null, title: '', contract_type: 'one_time', customer_id: null, deal_id: null, quotation_id: null,
        value: 0, start_date: new Date().toISOString().split('T')[0], end_date: '',
        payment_terms: '', scope_of_work: '', terms_conditions: '', auto_renew: false, status: 'draft',
      })
    },
    openDialog(c = null) {
      if (c) {
        this.form = this.$inertia.form({
          id: c.id, title: c.title, contract_type: c.contract_type || 'one_time',
          customer_id: c.customer_id, deal_id: c.deal_id, quotation_id: c.quotation_id,
          value: Number(c.value || 0), start_date: c.start_date || '', end_date: c.end_date || '',
          payment_terms: c.payment_terms || '', scope_of_work: c.scope_of_work || '',
          terms_conditions: c.terms_conditions || '', auto_renew: c.auto_renew || false, status: c.status,
        })
      } else {
        this.form = this.emptyForm()
      }
      this.dialog = true
    },
    submitForm() {
      if (this.form.id) {
        this.form.put(`/contracts/${this.form.id}`, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      } else {
        this.form.post('/contracts', { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      }
    },
    submitApproval() {
      this.$inertia.post(`/contracts/${this.form.id}/submit-approval`, {}, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
    },
    deleteContract(c) { if (confirm(`Xóa "${c.title}"?`)) this.$inertia.delete(`/contracts/${c.id}`, { preserveScroll: true }) },
    formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.85rem}
.header-icon-wrapper{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#059669,#047857);display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;box-shadow:0 4px 14px rgba(5,150,105,.3)}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.header-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
.stat-chips{display:flex;gap:.4rem}.stat-chip{display:flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:20px;font-size:.65rem;font-weight:600}.stat-chip i{font-size:.58rem}
.sc-active{background:#ecfdf5;color:#059669}.sc-pending{background:#fffbeb;color:#d97706}
.filter-bar{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:white;border:1.5px solid #e2e8f0;border-radius:14px;margin-bottom:1.25rem;flex-wrap:wrap}
.search-box{display:flex;align-items:center;flex:1;min-width:200px;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden}
.search-box:focus-within{border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.08)}
.search-box i{padding:0 .6rem;color:#94a3b8;font-size:.75rem}
.search-input{flex:1;border:none;outline:none;padding:.5rem .5rem .5rem 0;font-size:.8rem;color:#1e293b;font-family:inherit}
.search-input::placeholder{color:#cbd5e1}.filter-select{min-width:120px;font-size:.8rem}
.contract-list{display:flex;flex-direction:column;gap:.5rem}
.contract-card{display:flex;align-items:center;gap:.75rem;padding:.85rem 1.15rem;background:white;border:1.5px solid #f1f5f9;border-radius:14px;cursor:pointer;transition:all .25s}
.contract-card:hover{border-color:#059669;box-shadow:0 4px 18px rgba(5,150,105,.08);transform:translateX(2px)}
.contract-status-indicator{width:4px;height:40px;border-radius:4px;flex-shrink:0}
.si-draft{background:#94a3b8}.si-pending_approval{background:#f59e0b}.si-approved{background:#6366f1}.si-active{background:#059669}.si-paused{background:#f97316}.si-completed{background:#3b82f6}.si-cancelled{background:#ef4444}
.contract-info{flex:1;min-width:0}
.contract-top{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.contract-number{font-size:.62rem;font-weight:700;color:#059669;font-family:monospace}
.status-badge{font-size:.5rem;font-weight:700;padding:.08rem .35rem;border-radius:4px;text-transform:uppercase}
.s-draft{background:#f1f5f9;color:#64748b}.s-pending_approval{background:#fffbeb;color:#d97706}.s-approved{background:#eef2ff;color:#6366f1}.s-active{background:#ecfdf5;color:#059669}.s-paused{background:#fff7ed;color:#f97316}.s-completed{background:#eff6ff;color:#3b82f6}.s-cancelled{background:#fef2f2;color:#ef4444}
.type-tag{font-size:.5rem;font-weight:600;padding:.08rem .35rem;border-radius:4px;background:#f8fafc;color:#94a3b8}
.contract-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:.15rem 0}
.contract-meta{display:flex;gap:.6rem;flex-wrap:wrap}
.contract-meta span{font-size:.65rem;color:#94a3b8;display:flex;align-items:center;gap:.2rem}
.contract-meta i{font-size:.55rem}
.days-tag{font-weight:600;color:#059669!important}.days-tag.urgent{color:#ef4444!important}
.contract-value{text-align:right;flex-shrink:0}
.value-amount{font-size:.92rem;font-weight:800;color:#1e293b;display:block}.value-label{font-size:.55rem;color:#94a3b8}
.contract-actions{display:flex;gap:.125rem;flex-shrink:0}
.empty-state{text-align:center;padding:3rem 2rem;background:white;border-radius:16px;border:2px dashed #e2e8f0}
.empty-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#ecfdf5,#d1fae5);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#059669}
.empty-state h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .35rem}.empty-state p{font-size:.82rem;color:#94a3b8;margin:0}
.mt-1{margin-top:.75rem}
/* Dialog */
.dialog-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(4px);padding:1.5rem}
.dialog-card{background:white;border-radius:18px;width:680px;max-width:100%;max-height:calc(100vh - 3rem);display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:slideUp .25s ease-out}
.dialog-card *{box-sizing:border-box}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.dialog-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;flex-shrink:0}
.dialog-header-left{display:flex;align-items:center;gap:.6rem}
.dialog-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#059669,#047857);display:flex;align-items:center;justify-content:center;color:white;font-size:.85rem;flex-shrink:0}
.dialog-header h3{font-size:1rem;font-weight:700;color:#1e293b;margin:0}
.dialog-close{background:none;border:none;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;cursor:pointer;transition:all .2s;flex-shrink:0}
.dialog-close:hover{background:#fef2f2;color:#ef4444}
.dialog-body{padding:1.25rem 1.5rem;overflow-y:auto;flex:1;min-height:0}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1rem;margin-bottom:.85rem}
.section-header{display:flex;align-items:center;gap:.4rem;margin-bottom:.65rem;padding-bottom:.4rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.78rem;color:#059669}.section-title{font-size:.78rem;font-weight:700;color:#1e293b;margin:0}
.form-row{display:flex;gap:.65rem;flex-wrap:wrap}.form-group{margin-bottom:.7rem;min-width:0}.flex-1{flex:1;min-width:120px}.w-full{width:100%}
.form-group label{display:block;font-size:.7rem;font-weight:600;color:#475569;margin-bottom:.3rem}.req{color:#ef4444}
.form-group :deep(.p-inputtext),.form-group :deep(.p-inputnumber),.form-group :deep(.p-textarea),.form-group :deep(.p-select){width:100%}
.type-grid{display:flex;gap:.3rem;flex-wrap:wrap}
.type-chip{display:flex;align-items:center;gap:.25rem;padding:.35rem .55rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.7rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s}
.type-chip i{font-size:.6rem}.type-chip:hover{border-color:#059669;color:#059669}
.type-chip.active{border-color:#059669;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:#065f46}
.toggle-row{display:flex;justify-content:space-between;align-items:center;margin-top:.4rem}
.toggle-label{font-size:.78rem;font-weight:600;color:#1e293b}.toggle-desc{display:block;font-size:.58rem;color:#94a3b8}
.submit-approval-row{display:flex;justify-content:center;padding:.5rem 0;margin-bottom:.5rem}
.dialog-footer{display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid #f1f5f9;flex-shrink:0;background:white;border-radius:0 0 18px 18px}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.filter-bar{flex-direction:column}.search-box{min-width:100%}.contract-card{flex-wrap:wrap}.contract-value{margin-left:auto}.form-row{flex-direction:column}.flex-1{min-width:100%}.type-grid{flex-direction:column}.dialog-overlay{padding:.75rem}.dialog-card{max-height:calc(100vh - 1.5rem)}}
</style>
