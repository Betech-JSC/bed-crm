<template>
  <div>
    <Head title="Tạo báo giá" />
    <div class="page-header">
      <div class="header-content">
        <Link href="/quotations" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div><h1 class="page-title">Tạo báo giá mới</h1><p class="page-subtitle">Mã: {{ nextNumber }}</p></div>
      </div>
    </div>
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="store" class="card-body">
        <!-- Basic Info -->
        <div class="form-section">
          <div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin chung</h3></div>
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="form.title" class="w-full" placeholder="VD: Báo giá dịch vụ CRM cho Công ty ABC" :class="{'p-invalid':form.errors.title}" /><small v-if="form.errors.title" class="field-error">{{ form.errors.title }}</small></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Khách hàng</label><Select v-model="form.customer_id" :options="customers" optionLabel="name" optionValue="id" placeholder="Chọn khách hàng" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Lead</label><Select v-model="form.lead_id" :options="leads" optionLabel="company" optionValue="id" placeholder="Chọn lead" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Deal</label><Select v-model="form.deal_id" :options="deals" optionLabel="name" optionValue="id" placeholder="Chọn deal" showClear filter class="w-full" /></div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Ngày tạo</label><InputText v-model="form.issue_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Hiệu lực đến</label><InputText v-model="form.valid_until" type="date" class="w-full" /></div>
            <div class="form-group" style="width:120px"><label>Giảm giá %</label><InputNumber v-model="form.discount_percent" class="w-full" suffix="%" :min="0" :max="100" /></div>
          </div>
        </div>

        <!-- Line Items -->
        <div class="form-section">
          <div class="section-header">
            <i class="pi pi-list section-icon" /><h3 class="section-title">Hạng mục ({{ form.items.length }})</h3>
            <button type="button" class="add-item-btn" @click="addItem"><i class="pi pi-plus" /> Thêm</button>
          </div>
          <div v-for="(item, idx) in form.items" :key="idx" class="line-item">
            <div class="item-row">
              <div class="form-group" style="flex:2;min-width:180px">
                <label>Sản phẩm/Dịch vụ</label>
                <Select v-model="item.product_id" :options="products" optionLabel="name" optionValue="id" placeholder="Chọn hoặc nhập tự do" showClear filter class="w-full" @change="fillProduct(idx)" />
              </div>
              <div class="form-group" style="flex:2;min-width:150px"><label>Tên <span class="req">*</span></label><InputText v-model="item.name" class="w-full" /></div>
              <div class="form-group" style="width:70px"><label>ĐVT</label><InputText v-model="item.unit" class="w-full" /></div>
              <div class="form-group" style="width:80px"><label>SL</label><InputNumber v-model="item.quantity" class="w-full" :min="0.01" /></div>
              <div class="form-group" style="width:140px"><label>Đơn giá</label><InputNumber v-model="item.unit_price" class="w-full" /></div>
              <div class="form-group" style="width:70px"><label>VAT%</label><InputNumber v-model="item.tax_rate" class="w-full" suffix="%" /></div>
              <button type="button" class="remove-item-btn" @click="removeItem(idx)"><i class="pi pi-trash" /></button>
            </div>
            <div class="item-total">Thành tiền: <strong>{{ formatPrice(item.quantity * item.unit_price) }}</strong></div>
          </div>
          <div class="items-summary">
            <div class="summary-row"><span>Tổng cộng:</span><strong>{{ formatPrice(subtotal) }}</strong></div>
          </div>
        </div>

        <!-- Notes -->
        <div class="form-section">
          <div class="section-header"><i class="pi pi-file section-icon" /><h3 class="section-title">Ghi chú & Điều khoản</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Ghi chú</label><Textarea v-model="form.notes" class="w-full" rows="3" /></div>
            <div class="form-group flex-1"><label>Điều khoản</label><Textarea v-model="form.terms" class="w-full" rows="3" /></div>
          </div>
        </div>

        <div class="form-footer">
          <Link href="/quotations"><Button label="Hủy" severity="secondary" outlined /></Link>
          <Button type="submit" label="Tạo báo giá" icon="pi pi-check" :loading="form.processing" />
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
  props: { customers: Array, leads: Array, deals: Array, products: Array, nextNumber: String },
  data() {
    return {
      form: this.$inertia.form({
        title: '', customer_id: null, lead_id: null, deal_id: null,
        issue_date: new Date().toISOString().split('T')[0],
        valid_until: new Date(Date.now() + 30*86400000).toISOString().split('T')[0],
        discount_percent: 0, notes: '', terms: '',
        items: [{ product_id: null, name: '', unit: 'cái', quantity: 1, unit_price: 0, discount_percent: 0, tax_rate: 10 }],
      }),
    }
  },
  computed: {
    subtotal() { return this.form.items.reduce((s, i) => s + (i.quantity || 0) * (i.unit_price || 0), 0) },
  },
  methods: {
    addItem() { this.form.items.push({ product_id: null, name: '', unit: 'cái', quantity: 1, unit_price: 0, discount_percent: 0, tax_rate: 10 }) },
    removeItem(idx) { if (this.form.items.length > 1) this.form.items.splice(idx, 1) },
    fillProduct(idx) {
      const p = this.products.find(x => x.id === this.form.items[idx].product_id)
      if (p) { Object.assign(this.form.items[idx], { name: p.name, unit: p.unit, unit_price: Number(p.unit_price), tax_rate: Number(p.tax_rate) }) }
    },
    formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' },
    store() { this.form.post('/quotations') },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;margin-bottom:1.5rem}.header-content{display:flex;align-items:center;gap:.85rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#3b82f6;color:#3b82f6}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#3b82f6;font-family:monospace;margin:.15rem 0 0}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:900px}
.card-accent{height:4px;background:linear-gradient(90deg,#3b82f6,#2563eb,#1d4ed8)}.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#3b82f6}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0;flex:1}
.add-item-btn{display:flex;align-items:center;gap:.2rem;border:none;background:none;color:#3b82f6;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit}.add-item-btn i{font-size:.6rem}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:150px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.field-error{display:block;font-size:.65rem;color:#ef4444;margin-top:.2rem}
.line-item{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:.65rem;margin-bottom:.5rem}
.item-row{display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap}
.remove-item-btn{width:32px;height:32px;border-radius:8px;border:1px solid #fecaca;background:#fef2f2;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem;margin-bottom:.75rem;transition:all .2s;flex-shrink:0}
.remove-item-btn:hover{background:#ef4444;color:white}
.item-total{text-align:right;font-size:.72rem;color:#64748b;padding-top:.25rem;border-top:1px dashed #f1f5f9}
.item-total strong{color:#1e293b}
.items-summary{text-align:right;padding:.75rem 0 0;border-top:2px solid #e2e8f0}
.summary-row{font-size:.85rem;color:#1e293b}.summary-row strong{font-size:1.1rem;font-weight:800}
.form-footer{display:flex;justify-content:flex-end;gap:.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
@media(max-width:768px){.form-row{flex-direction:column}.flex-1{min-width:100%}.item-row{flex-direction:column;align-items:stretch}}
</style>
