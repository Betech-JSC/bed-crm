<template>
  <div>
    <Head title="Thêm sản phẩm/dịch vụ" />
    <div class="page-header">
      <div class="header-content">
        <Link href="/products" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div><h1 class="page-title">Thêm sản phẩm / dịch vụ</h1><p class="page-subtitle">Tạo mục mới trong danh mục</p></div>
      </div>
    </div>
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="store" class="card-body">
        <div class="form-section">
          <div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin cơ bản</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Tên <span class="req">*</span></label><InputText v-model="form.name" class="w-full" placeholder="Tên sản phẩm/dịch vụ" :class="{'p-invalid':form.errors.name}" /><small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small></div>
            <div class="form-group" style="width:140px"><label>SKU</label><InputText v-model="form.sku" class="w-full" placeholder="ABC-001" /></div>
          </div>
          <div class="form-row">
            <div class="form-group" style="width:180px">
              <label>Loại <span class="req">*</span></label>
              <div class="type-selector">
                <button type="button" class="type-btn" :class="{active:form.type==='product'}" @click="form.type='product'"><i class="pi pi-box" /> Sản phẩm</button>
                <button type="button" class="type-btn" :class="{active:form.type==='service'}" @click="form.type='service'"><i class="pi pi-cog" /> Dịch vụ</button>
              </div>
            </div>
            <div class="form-group flex-1"><label>Danh mục</label><InputText v-model="form.category" class="w-full" placeholder="VD: Phần mềm, Tư vấn..." /></div>
            <div class="form-group" style="width:120px"><label>Đơn vị</label><InputText v-model="form.unit" class="w-full" /></div>
          </div>
          <div class="form-group"><label>Mô tả</label><Textarea v-model="form.description" class="w-full" rows="3" placeholder="Mô tả chi tiết..." /></div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-dollar section-icon" /><h3 class="section-title">Giá</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Giá bán <span class="req">*</span></label><InputNumber v-model="form.unit_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" :class="{'p-invalid':form.errors.unit_price}" /><small v-if="form.errors.unit_price" class="field-error">{{ form.errors.unit_price }}</small></div>
            <div class="form-group flex-1"><label>Giá vốn</label><InputNumber v-model="form.cost_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
            <div class="form-group" style="width:100px"><label>VAT %</label><InputNumber v-model="form.tax_rate" class="w-full" suffix="%" /></div>
          </div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-cog section-icon" /><h3 class="section-title">Tùy chọn</h3></div>
          <div class="toggle-row">
            <div><label class="toggle-label">Đang bán</label><small class="toggle-desc">Sản phẩm hiển thị trong danh mục khi tạo báo giá</small></div>
            <InputSwitch v-model="form.is_active" />
          </div>
        </div>
        <div class="form-footer">
          <Link href="/products"><Button label="Hủy" severity="secondary" outlined /></Link>
          <Button type="submit" label="Tạo mới" icon="pi pi-check" :loading="form.processing" />
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
import InputSwitch from 'primevue/inputswitch'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'

export default {
  components: { Head, Link, InputText, InputNumber, InputSwitch, Textarea, Button },
  layout: Layout,
  data() {
    return {
      form: this.$inertia.form({
        name: '', sku: '', type: 'product', category: '', description: '',
        unit: 'cái', unit_price: 0, cost_price: null, tax_rate: 10, is_active: true,
      }),
    }
  },
  methods: { store() { this.form.post('/products') } },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;margin-bottom:1.5rem}.header-content{display:flex;align-items:center;gap:.85rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#f59e0b;color:#f59e0b}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:720px}
.card-accent{height:4px;background:linear-gradient(90deg,#f59e0b,#d97706,#b45309)}
.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#f59e0b}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:180px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.field-error{display:block;font-size:.65rem;color:#ef4444;margin-top:.2rem}
.type-selector{display:flex;gap:.35rem}
.type-btn{display:flex;align-items:center;gap:.3rem;padding:.4rem .65rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.72rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s}
.type-btn i{font-size:.62rem}.type-btn:hover{border-color:#f59e0b;color:#f59e0b}
.type-btn.active{border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#92400e}
.toggle-row{display:flex;justify-content:space-between;align-items:center}
.toggle-label{font-size:.82rem;font-weight:600;color:#1e293b}.toggle-desc{display:block;font-size:.62rem;color:#94a3b8;margin-top:.1rem}
.form-footer{display:flex;justify-content:flex-end;gap:.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
@media(max-width:768px){.form-row{flex-direction:column}.flex-1{min-width:100%}}
</style>
