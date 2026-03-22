<template>
  <div>
    <Head :title="product.name" />
    <div class="page-header">
      <div class="header-content">
        <Link href="/products" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="product-icon-header" :class="product.type === 'service' ? 'icon-service' : 'icon-product'">
          <i :class="product.type === 'service' ? 'pi pi-cog' : 'pi pi-box'" />
        </div>
        <div><h1 class="page-title">{{ form.name || product.name }}</h1><p class="page-subtitle">{{ product.sku || 'Không có SKU' }}</p></div>
      </div>
      <div class="header-badges">
        <span class="badge" :class="product.type">{{ product.type === 'service' ? 'Dịch vụ' : 'Sản phẩm' }}</span>
        <span class="badge" :class="product.is_active ? 'active' : 'inactive'">{{ product.is_active ? 'Đang bán' : 'Ngừng bán' }}</span>
      </div>
    </div>
    <div class="form-card">
      <div class="card-accent" />
      <form @submit.prevent="update" class="card-body">
        <div class="form-section">
          <div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin cơ bản</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Tên <span class="req">*</span></label><InputText v-model="form.name" class="w-full" :class="{'p-invalid':form.errors.name}" /><small v-if="form.errors.name" class="field-error">{{ form.errors.name }}</small></div>
            <div class="form-group" style="width:140px"><label>SKU</label><InputText v-model="form.sku" class="w-full" /></div>
          </div>
          <div class="form-row">
            <div class="form-group" style="width:180px">
              <label>Loại</label>
              <div class="type-selector">
                <button type="button" class="type-btn" :class="{active:form.type==='product'}" @click="form.type='product'"><i class="pi pi-box" /> SP</button>
                <button type="button" class="type-btn" :class="{active:form.type==='service'}" @click="form.type='service'"><i class="pi pi-cog" /> DV</button>
              </div>
            </div>
            <div class="form-group flex-1"><label>Danh mục</label><InputText v-model="form.category" class="w-full" /></div>
            <div class="form-group" style="width:120px"><label>Đơn vị</label><InputText v-model="form.unit" class="w-full" /></div>
          </div>
          <div class="form-group"><label>Mô tả</label><Textarea v-model="form.description" class="w-full" rows="3" /></div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-dollar section-icon" /><h3 class="section-title">Giá</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Giá bán</label><InputNumber v-model="form.unit_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
            <div class="form-group flex-1"><label>Giá vốn</label><InputNumber v-model="form.cost_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
            <div class="form-group" style="width:100px"><label>VAT %</label><InputNumber v-model="form.tax_rate" class="w-full" suffix="%" /></div>
          </div>
        </div>
        <div class="form-section">
          <div class="section-header"><i class="pi pi-cog section-icon" /><h3 class="section-title">Tùy chọn</h3></div>
          <div class="toggle-row">
            <div><label class="toggle-label">Đang bán</label><small class="toggle-desc">Hiển thị khi tạo báo giá</small></div>
            <InputSwitch v-model="form.is_active" />
          </div>
        </div>
        <div class="form-footer">
          <button type="button" class="delete-btn" @click="destroy"><i class="pi pi-trash" /> Xóa</button>
          <div class="footer-right">
            <Link href="/products"><Button label="Hủy" severity="secondary" outlined /></Link>
            <Button type="submit" label="Cập nhật" icon="pi pi-check" :loading="form.processing" />
          </div>
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
  props: { product: Object },
  data() {
    return {
      form: this.$inertia.form({
        name: this.product.name, sku: this.product.sku, type: this.product.type,
        category: this.product.category, description: this.product.description,
        unit: this.product.unit, unit_price: Number(this.product.unit_price),
        cost_price: this.product.cost_price ? Number(this.product.cost_price) : null,
        tax_rate: Number(this.product.tax_rate), is_active: this.product.is_active,
      }),
    }
  },
  methods: {
    update() { this.form.put(`/products/${this.product.id}`) },
    destroy() { if (confirm('Xóa sản phẩm này?')) this.$inertia.delete(`/products/${this.product.id}`) },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.75rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#f59e0b;color:#f59e0b}
.product-icon-header{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:.92rem;flex-shrink:0}
.icon-product{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e}.icon-service{background:linear-gradient(135deg,#e0e7ff,#eef2ff);color:#6366f1}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.header-badges{display:flex;gap:.4rem}
.badge{font-size:.6rem;font-weight:700;padding:.15rem .45rem;border-radius:6px;text-transform:uppercase}
.badge.product{background:#fef3c7;color:#92400e}.badge.service{background:#eef2ff;color:#6366f1}
.badge.active{background:#ecfdf5;color:#059669}.badge.inactive{background:#fef2f2;color:#ef4444}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:720px}
.card-accent{height:4px;background:linear-gradient(90deg,#f59e0b,#d97706,#b45309)}.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#f59e0b}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:180px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.field-error{display:block;font-size:.65rem;color:#ef4444;margin-top:.2rem}
.type-selector{display:flex;gap:.35rem}
.type-btn{display:flex;align-items:center;gap:.3rem;padding:.4rem .55rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.72rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s}
.type-btn i{font-size:.62rem}.type-btn:hover{border-color:#f59e0b;color:#f59e0b}
.type-btn.active{border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#92400e}
.toggle-row{display:flex;justify-content:space-between;align-items:center}
.toggle-label{font-size:.82rem;font-weight:600;color:#1e293b}.toggle-desc{display:block;font-size:.62rem;color:#94a3b8}
.form-footer{display:flex;justify-content:space-between;align-items:center;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
.footer-right{display:flex;gap:.5rem}
.delete-btn{display:flex;align-items:center;gap:.3rem;background:none;border:none;color:#ef4444;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit}.delete-btn:hover{opacity:.7}.delete-btn i{font-size:.68rem}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.form-row{flex-direction:column}.flex-1{min-width:100%}}
</style>
