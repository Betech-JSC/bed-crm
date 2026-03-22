<template>
  <div>
    <Head title="Sản phẩm & Dịch vụ" />
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-box" /></div>
        <div>
          <h1 class="page-title">Sản phẩm & Dịch vụ</h1>
          <p class="page-subtitle">Quản lý danh mục sản phẩm, dịch vụ doanh nghiệp</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip c1"><i class="pi pi-box" /> {{ stats.products }} sản phẩm</span>
          <span class="stat-chip c2"><i class="pi pi-cog" /> {{ stats.services }} dịch vụ</span>
          <span class="stat-chip c3"><i class="pi pi-check-circle" /> {{ stats.active }} đang bán</span>
        </div>
        <Button label="Thêm mới" icon="pi pi-plus" @click="openDialog()" />
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-box"><i class="pi pi-search" /><input v-model="filterForm.search" placeholder="Tìm theo tên, SKU, danh mục..." class="search-input" /></div>
      <div class="filter-group">
        <Select v-model="filterForm.type" :options="[{label:'Sản phẩm',value:'product'},{label:'Dịch vụ',value:'service'}]" optionLabel="label" optionValue="value" placeholder="Loại" showClear class="filter-select" />
        <Select v-model="filterForm.status" :options="[{label:'Đang bán',value:'active'},{label:'Ngừng bán',value:'inactive'}]" optionLabel="label" optionValue="value" placeholder="Trạng thái" showClear class="filter-select" />
      </div>
    </div>

    <div v-if="products.data.length" class="product-grid">
      <div v-for="p in products.data" :key="p.id" class="product-card" @click="openDialog(p)">
        <div class="product-icon" :class="p.type === 'service' ? 'icon-service' : 'icon-product'">
          <i :class="p.type === 'service' ? 'pi pi-cog' : 'pi pi-box'" />
        </div>
        <div class="product-info">
          <div class="product-name-row">
            <h3>{{ p.name }}</h3>
            <span class="product-type-tag" :class="p.type">{{ p.type === 'service' ? 'DV' : 'SP' }}</span>
            <span v-if="!p.is_active" class="inactive-tag">Ngừng</span>
          </div>
          <div class="product-meta">
            <span v-if="p.sku" class="meta-item"><i class="pi pi-hashtag" /> {{ p.sku }}</span>
            <span v-if="p.category" class="meta-item"><i class="pi pi-tag" /> {{ p.category }}</span>
            <span class="meta-item"><i class="pi pi-calculator" /> {{ p.unit }}</span>
          </div>
        </div>
        <div class="product-price">
          <span class="price-amount">{{ formatPrice(p.unit_price) }}</span>
          <span class="price-unit">/ {{ p.unit }}</span>
          <span v-if="p.margin" class="margin-badge" :class="p.margin > 30 ? 'good' : 'low'">{{ p.margin }}% margin</span>
        </div>
        <div class="product-actions" @click.stop>
          <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openDialog(p)" />
          <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteProduct(p)" />
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-box" /></div>
      <h3>Chưa có sản phẩm/dịch vụ</h3>
      <p>Tạo sản phẩm đầu tiên để bắt đầu tạo báo giá.</p>
      <Button label="Thêm mới" icon="pi pi-plus" class="mt-1" @click="openDialog()" />
    </div>

    <!-- ===== CREATE / EDIT DIALOG ===== -->
    <div v-if="dialog" class="dialog-overlay" @click.self="dialog = false" @keydown.esc="dialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon"><i class="pi pi-box" /></div>
            <h3>{{ form.id ? 'Chỉnh sửa' : 'Thêm mới' }} sản phẩm/dịch vụ</h3>
          </div>
          <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
        </div>

        <form @submit.prevent="submitForm" class="dialog-body">
          <!-- Basic Info -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-info-circle section-icon" /><h4 class="section-title">Thông tin cơ bản</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Tên <span class="req">*</span></label><InputText v-model="form.name" class="w-full" placeholder="Tên sản phẩm/dịch vụ" :class="{'p-invalid':form.errors?.name}" /><small v-if="form.errors?.name" class="field-error">{{ form.errors.name }}</small></div>
              <div class="form-group" style="width:130px"><label>SKU</label><InputText v-model="form.sku" class="w-full" placeholder="ABC-001" /></div>
            </div>
            <div class="form-row">
              <div class="form-group" style="width:180px">
                <label>Loại <span class="req">*</span></label>
                <div class="type-selector">
                  <button type="button" class="type-btn" :class="{active:form.type==='product'}" @click="form.type='product'"><i class="pi pi-box" /> SP</button>
                  <button type="button" class="type-btn" :class="{active:form.type==='service'}" @click="form.type='service'"><i class="pi pi-cog" /> DV</button>
                </div>
              </div>
              <div class="form-group flex-1"><label>Danh mục</label><InputText v-model="form.category" class="w-full" placeholder="VD: Phần mềm, Tư vấn..." /></div>
              <div class="form-group" style="width:100px"><label>Đơn vị</label><InputText v-model="form.unit" class="w-full" /></div>
            </div>
            <div class="form-group"><label>Mô tả</label><Editor v-model="form.description" editorStyle="height: 100px" class="w-full" /></div>
          </div>

          <!-- Pricing -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-dollar section-icon" /><h4 class="section-title">Giá</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Giá bán <span class="req">*</span></label><InputNumber v-model="form.unit_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
              <div class="form-group flex-1"><label>Giá vốn</label><InputNumber v-model="form.cost_price" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
              <div class="form-group" style="width:90px"><label>VAT %</label><InputNumber v-model="form.tax_rate" class="w-full" suffix="%" /></div>
            </div>
          </div>

          <!-- Options -->
          <div class="toggle-row">
            <div><label class="toggle-label">Đang bán</label><small class="toggle-desc">Hiển thị khi tạo báo giá</small></div>
            <InputSwitch v-model="form.is_active" />
          </div>
        </form>

        <div class="dialog-footer">
          <Button label="Hủy" severity="secondary" outlined @click="dialog = false" type="button" />
          <Button :label="form.id ? 'Cập nhật' : 'Tạo mới'" icon="pi pi-check" @click="submitForm" :loading="form.processing" />
        </div>
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
  props: { products: Object, filters: Object, stats: Object },
  data() {
    return {
      dialog: false,
      form: this.emptyForm(),
      filterForm: { search: this.filters.search, type: this.filters.type, status: this.filters.status },
    }
  },
  mounted() {
    this._escHandler = (e) => { if (e.key === 'Escape') { this.dialog = false } }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  watch: {
    filterForm: { deep: true, handler: throttle(function () { this.$inertia.get('/products', pickBy(this.filterForm), { preserveState: true }) }, 300) },
  },
  methods: {
    emptyForm() {
      return this.$inertia.form({
        id: null, name: '', sku: '', type: 'product', category: '', description: '',
        unit: 'cái', unit_price: 0, cost_price: null, tax_rate: 10, is_active: true,
      })
    },
    openDialog(product = null) {
      if (product) {
        this.form = this.$inertia.form({
          id: product.id, name: product.name, sku: product.sku || '', type: product.type,
          category: product.category || '', description: product.description || '',
          unit: product.unit || 'cái', unit_price: Number(product.unit_price), cost_price: product.cost_price ? Number(product.cost_price) : null,
          tax_rate: Number(product.tax_rate || 10), is_active: product.is_active,
        })
      } else {
        this.form = this.emptyForm()
      }
      this.dialog = true
    },
    submitForm() {
      if (this.form.id) {
        this.form.put(`/products/${this.form.id}`, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      } else {
        this.form.post('/products', { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      }
    },
    deleteProduct(p) { if (confirm(`Xóa "${p.name}"?`)) this.$inertia.delete(`/products/${p.id}`, { preserveScroll: true }) },
    formatPrice(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫' },
  },
}
</script>

<style scoped>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:1.25rem;box-shadow:0 4px 14px rgba(245,158,11,.3) }
.page-title { font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em }
.page-subtitle { font-size:.82rem;color:#64748b;margin:.15rem 0 0 }
.header-actions { display:flex;align-items:center;gap:.65rem;flex-wrap:wrap }
.stat-chips { display:flex;gap:.4rem }
.stat-chip { display:flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:20px;font-size:.65rem;font-weight:600 }
.stat-chip i { font-size:.58rem }
.c1 { background:#fffbeb;color:#d97706 } .c2 { background:#eef2ff;color:#6366f1 } .c3 { background:#ecfdf5;color:#059669 }
.filter-bar { display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:white;border:1.5px solid #e2e8f0;border-radius:14px;margin-bottom:1.25rem;flex-wrap:wrap }
.search-box { display:flex;align-items:center;flex:1;min-width:200px;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden }
.search-box:focus-within { border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.08) }
.search-box i { padding:0 .6rem;color:#94a3b8;font-size:.75rem }
.search-input { flex:1;border:none;outline:none;padding:.5rem .5rem .5rem 0;font-size:.8rem;color:#1e293b;font-family:inherit }
.search-input::placeholder { color:#cbd5e1 }
.filter-group { display:flex;gap:.4rem }
.filter-select { min-width:120px;font-size:.8rem }
.product-grid { display:flex;flex-direction:column;gap:.5rem }
.product-card { display:flex;align-items:center;gap:.85rem;padding:.85rem 1.15rem;background:white;border:1.5px solid #f1f5f9;border-radius:14px;cursor:pointer;transition:all .25s }
.product-card:hover { border-color:#f59e0b;box-shadow:0 4px 18px rgba(245,158,11,.08);transform:translateX(2px) }
.product-icon { width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0 }
.icon-product { background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e }
.icon-service { background:linear-gradient(135deg,#e0e7ff,#eef2ff);color:#6366f1 }
.product-info { flex:1;min-width:0 }
.product-name-row { display:flex;align-items:center;gap:.4rem }
.product-name-row h3 { font-size:.88rem;font-weight:700;color:#1e293b;margin:0 }
.product-type-tag { font-size:.5rem;font-weight:700;padding:.08rem .3rem;border-radius:4px;text-transform:uppercase }
.product-type-tag.product { background:#fef3c7;color:#92400e }
.product-type-tag.service { background:#eef2ff;color:#6366f1 }
.inactive-tag { font-size:.5rem;font-weight:700;padding:.08rem .3rem;border-radius:4px;background:#fef2f2;color:#ef4444 }
.product-meta { display:flex;gap:.6rem;margin-top:.15rem }
.meta-item { font-size:.65rem;color:#94a3b8;display:flex;align-items:center;gap:.2rem }
.meta-item i { font-size:.55rem }
.product-price { text-align:right;flex-shrink:0 }
.price-amount { font-size:.92rem;font-weight:800;color:#1e293b;display:block }
.price-unit { font-size:.6rem;color:#94a3b8 }
.margin-badge { display:block;font-size:.52rem;font-weight:700;padding:.08rem .3rem;border-radius:4px;margin-top:.2rem }
.margin-badge.good { background:#ecfdf5;color:#059669 } .margin-badge.low { background:#fffbeb;color:#d97706 }
.product-actions { display:flex;gap:.125rem;flex-shrink:0 }
.empty-state { text-align:center;padding:3rem 2rem;background:white;border-radius:16px;border:2px dashed #e2e8f0 }
.empty-icon { width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#d97706 }
.empty-state h3 { font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .35rem } .empty-state p { font-size:.82rem;color:#94a3b8;margin:0 }
.mt-1 { margin-top:.75rem }
/* Dialog */
.dialog-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(4px);padding:1.5rem }
.dialog-card { background:white;border-radius:18px;width:680px;max-width:100%;max-height:calc(100vh - 3rem);display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:slideUp .25s ease-out }
.dialog-card * { box-sizing:border-box }
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
.dialog-header { display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;flex-shrink:0 }
.dialog-header-left { display:flex;align-items:center;gap:.6rem }
.dialog-icon { width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:.85rem;flex-shrink:0 }
.dialog-header h3 { font-size:1rem;font-weight:700;color:#1e293b;margin:0 }
.dialog-close { background:none;border:none;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;cursor:pointer;transition:all .2s;flex-shrink:0 }
.dialog-close:hover { background:#fef2f2;color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem;overflow-y:auto;flex:1;min-height:0 }
.form-section { background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1rem;margin-bottom:.85rem }
.section-header { display:flex;align-items:center;gap:.4rem;margin-bottom:.65rem;padding-bottom:.4rem;border-bottom:1px solid #f1f5f9 }
.section-icon { font-size:.78rem;color:#f59e0b } .section-title { font-size:.78rem;font-weight:700;color:#1e293b;margin:0 }
.form-row { display:flex;gap:.65rem;flex-wrap:wrap } .form-group { margin-bottom:.7rem;min-width:0 } .flex-1 { flex:1;min-width:120px } .w-full { width:100% }
.form-group label { display:block;font-size:.7rem;font-weight:600;color:#475569;margin-bottom:.3rem } .req { color:#ef4444 }
.form-group :deep(.p-inputtext), .form-group :deep(.p-inputnumber), .form-group :deep(.p-textarea), .form-group :deep(.p-select) { width:100% }
.field-error { display:block;font-size:.62rem;color:#ef4444;margin-top:.15rem }
.type-selector { display:flex;gap:.3rem }
.type-btn { display:flex;align-items:center;gap:.25rem;padding:.35rem .55rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.7rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s }
.type-btn i { font-size:.6rem } .type-btn:hover { border-color:#f59e0b;color:#f59e0b }
.type-btn.active { border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#92400e }
.toggle-row { display:flex;justify-content:space-between;align-items:center;margin-bottom:.85rem }
.toggle-label { font-size:.8rem;font-weight:600;color:#1e293b } .toggle-desc { display:block;font-size:.6rem;color:#94a3b8 }
.dialog-footer { display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid #f1f5f9;flex-shrink:0;background:white;border-radius:0 0 18px 18px }
@media(max-width:768px) { .page-header{flex-direction:column;align-items:flex-start} .filter-bar{flex-direction:column} .search-box{min-width:100%} .product-card{flex-wrap:wrap} .product-price{margin-left:auto} .form-row{flex-direction:column} .flex-1{min-width:100%} .dialog-overlay{padding:.75rem} .dialog-card{max-height:calc(100vh - 1.5rem)} }
</style>
