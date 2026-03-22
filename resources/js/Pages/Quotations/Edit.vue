<template>
  <div>
    <Head :title="quotation.quote_number" />
    <div class="page-header">
      <div class="header-content">
        <Link href="/quotations" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div><h1 class="page-title">{{ quotation.quote_number }}</h1><p class="page-subtitle">{{ form.title }}</p></div>
      </div>
      <div class="header-actions">
        <span class="status-badge" :class="`s-${quotation.status}`">{{ quotation.status === 'draft' ? 'Nháp' : quotation.status === 'pending_approval' ? 'Chờ duyệt' : quotation.status === 'approved' ? 'Đã duyệt' : quotation.status === 'sent' ? 'Đã gửi' : quotation.status === 'accepted' ? 'Chấp nhận' : 'Từ chối' }}</span>
        <Button v-if="quotation.status === 'draft'" label="Gửi duyệt" icon="pi pi-send" severity="warning" size="small" @click="submitApproval" />
      </div>
    </div>
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="update" class="card-body">
        <div class="form-section">
          <div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin chung</h3></div>
          <div class="form-group"><label>Tiêu đề</label><InputText v-model="form.title" class="w-full" /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Khách hàng</label><Select v-model="form.customer_id" :options="customers" optionLabel="name" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Lead</label><Select v-model="form.lead_id" :options="leads" optionLabel="company" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Deal</label><Select v-model="form.deal_id" :options="deals" optionLabel="name" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Ngày tạo</label><InputText v-model="form.issue_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Hiệu lực đến</label><InputText v-model="form.valid_until" type="date" class="w-full" /></div>
            <div class="form-group" style="width:120px"><label>Giảm giá %</label><InputNumber v-model="form.discount_percent" class="w-full" suffix="%" /></div>
          </div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-list section-icon" /><h3 class="section-title">Hạng mục</h3><button type="button" class="add-item-btn" @click="addItem"><i class="pi pi-plus" /> Thêm</button></div>
          <div v-for="(item, idx) in form.items" :key="idx" class="line-item">
            <div class="item-row">
              <div class="form-group" style="flex:2;min-width:180px"><label>SP/DV</label><Select v-model="item.product_id" :options="products" optionLabel="name" optionValue="id" showClear filter class="w-full" @change="fillProduct(idx)" /></div>
              <div class="form-group" style="flex:2;min-width:150px"><label>Tên</label><InputText v-model="item.name" class="w-full" /></div>
              <div class="form-group" style="width:70px"><label>ĐVT</label><InputText v-model="item.unit" class="w-full" /></div>
              <div class="form-group" style="width:80px"><label>SL</label><InputNumber v-model="item.quantity" class="w-full" :min="0.01" /></div>
              <div class="form-group" style="width:140px"><label>Đơn giá</label><InputNumber v-model="item.unit_price" class="w-full" /></div>
              <div class="form-group" style="width:70px"><label>VAT%</label><InputNumber v-model="item.tax_rate" class="w-full" suffix="%" /></div>
              <button type="button" class="remove-item-btn" @click="removeItem(idx)"><i class="pi pi-trash" /></button>
            </div>
            <div class="item-total">Thành tiền: <strong>{{ formatPrice(item.quantity * item.unit_price) }}</strong></div>
          </div>
          <div class="items-summary"><div class="summary-row"><span>Tổng cộng:</span><strong>{{ formatPrice(subtotal) }}</strong></div></div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-file section-icon" /><h3 class="section-title">Ghi chú</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Ghi chú</label><Textarea v-model="form.notes" class="w-full" rows="3" /></div>
            <div class="form-group flex-1"><label>Điều khoản</label><Textarea v-model="form.terms" class="w-full" rows="3" /></div>
          </div>
        </div>
        <div class="form-footer">
          <button type="button" class="delete-btn" @click="destroy"><i class="pi pi-trash" /> Xóa</button>
          <div class="footer-right"><Link href="/quotations"><Button label="Hủy" severity="secondary" outlined /></Link><Button type="submit" label="Cập nhật" icon="pi pi-check" :loading="form.processing" /></div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'

export default {
  components: { Head, Link, InputText, InputNumber, Textarea, Select, Button },
  layout: Layout,
  props: { quotation: Object, customers: Array, leads: Array, deals: Array, products: Array },
  data() {
    return {
      form: this.$inertia.form({
        title: this.quotation.title, customer_id: this.quotation.customer_id, lead_id: this.quotation.lead_id,
        deal_id: this.quotation.deal_id, issue_date: this.quotation.issue_date, valid_until: this.quotation.valid_until,
        discount_percent: Number(this.quotation.discount_percent || 0), notes: this.quotation.notes, terms: this.quotation.terms,
        items: (this.quotation.items || []).map(i => ({ product_id: i.product_id, name: i.name, unit: i.unit, quantity: Number(i.quantity), unit_price: Number(i.unit_price), discount_percent: Number(i.discount_percent || 0), tax_rate: Number(i.tax_rate || 10) })),
      }),
    }
  },
  computed: { subtotal() { return this.form.items.reduce((s, i) => s + (i.quantity || 0) * (i.unit_price || 0), 0) } },
  methods: {
    addItem() { this.form.items.push({ product_id: null, name: '', unit: 'cái', quantity: 1, unit_price: 0, discount_percent: 0, tax_rate: 10 }) },
    removeItem(idx) { if (this.form.items.length > 1) this.form.items.splice(idx, 1) },
    fillProduct(idx) { const p = this.products.find(x => x.id === this.form.items[idx].product_id); if (p) Object.assign(this.form.items[idx], { name: p.name, unit: p.unit, unit_price: Number(p.unit_price), tax_rate: Number(p.tax_rate) }) },
    formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' },
    update() { this.form.put(`/quotations/${this.quotation.id}`) },
    destroy() { if (confirm('Xóa báo giá này?')) this.$inertia.delete(`/quotations/${this.quotation.id}`) },
    submitApproval() { this.$inertia.post(`/quotations/${this.quotation.id}/submit-approval`) },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.75rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#3b82f6;color:#3b82f6}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;font-family:monospace}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.header-actions{display:flex;align-items:center;gap:.5rem}
.status-badge{font-size:.62rem;font-weight:700;padding:.2rem .55rem;border-radius:6px;text-transform:uppercase}
.s-draft{background:#f1f5f9;color:#64748b}.s-pending_approval{background:#fffbeb;color:#d97706}.s-approved{background:#eef2ff;color:#6366f1}.s-sent{background:#eff6ff;color:#3b82f6}.s-accepted{background:#ecfdf5;color:#059669}.s-rejected{background:#fef2f2;color:#ef4444}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:900px}
.card-accent{height:4px;background:linear-gradient(90deg,#3b82f6,#2563eb,#1d4ed8)}.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#3b82f6}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0;flex:1}
.add-item-btn{display:flex;align-items:center;gap:.2rem;border:none;background:none;color:#3b82f6;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit}.add-item-btn i{font-size:.6rem}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:150px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.line-item{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:.65rem;margin-bottom:.5rem}
.item-row{display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap}
.remove-item-btn{width:32px;height:32px;border-radius:8px;border:1px solid #fecaca;background:#fef2f2;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem;margin-bottom:.75rem;transition:all .2s;flex-shrink:0}.remove-item-btn:hover{background:#ef4444;color:white}
.item-total{text-align:right;font-size:.72rem;color:#64748b;padding-top:.25rem;border-top:1px dashed #f1f5f9}.item-total strong{color:#1e293b}
.items-summary{text-align:right;padding:.75rem 0 0;border-top:2px solid #e2e8f0}.summary-row{font-size:.85rem;color:#1e293b}.summary-row strong{font-size:1.1rem;font-weight:800}
.form-footer{display:flex;justify-content:space-between;align-items:center;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
.footer-right{display:flex;gap:.5rem}
.delete-btn{display:flex;align-items:center;gap:.3rem;background:none;border:none;color:#ef4444;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit}.delete-btn:hover{opacity:.7}.delete-btn i{font-size:.68rem}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.form-row{flex-direction:column}.flex-1{min-width:100%}.item-row{flex-direction:column;align-items:stretch}}
</style>
