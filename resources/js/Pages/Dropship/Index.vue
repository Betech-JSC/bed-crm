<template>
  <div>
    <Head title="Dropship Management" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-truck" /></div>
        <div>
          <h1 class="page-title">Quản lý Dropship</h1>
          <p class="page-subtitle">Vận hành đơn hàng dropship Shopify — theo dõi NCC, tracking, lợi nhuận</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip c1"><i class="pi pi-shopping-cart" /> {{ stats.total_orders }} đơn</span>
          <span class="stat-chip c2"><i class="pi pi-clock" /> {{ stats.pending_orders }} chờ xử lý</span>
          <span class="stat-chip c3"><i class="pi pi-send" /> {{ stats.shipped_orders }} đang ship</span>
          <span class="stat-chip c4"><i class="pi pi-dollar" /> {{ formatPrice(stats.total_profit) }}</span>
        </div>
      </div>
    </div>

    <!-- KPI Dashboard -->
    <div class="kpi-row">
      <div class="kpi-card"><div class="kpi-icon ki1"><i class="pi pi-shopping-cart" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.month_orders }}</span><span class="kpi-label">Đơn tháng này</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki2"><i class="pi pi-dollar" /></div><div class="kpi-info"><span class="kpi-value">{{ formatPrice(stats.total_revenue) }}</span><span class="kpi-label">Doanh thu</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki3"><i class="pi pi-chart-line" /></div><div class="kpi-info"><span class="kpi-value">{{ formatPrice(stats.month_profit) }}</span><span class="kpi-label">Lợi nhuận tháng</span></div></div>
      <div class="kpi-card"><div class="kpi-icon ki4"><i class="pi pi-users" /></div><div class="kpi-info"><span class="kpi-value">{{ stats.active_suppliers }}</span><span class="kpi-label">NCC hoạt động</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <button class="tab-btn" :class="{active: activeTab === 'orders'}" @click="activeTab = 'orders'"><i class="pi pi-shopping-cart" /> Đơn hàng</button>
      <button class="tab-btn" :class="{active: activeTab === 'suppliers'}" @click="activeTab = 'suppliers'"><i class="pi pi-truck" /> Nhà cung cấp</button>
    </div>

    <!-- ═══ ORDERS TAB ═══ -->
    <div v-if="activeTab === 'orders'">
      <div class="filter-bar">
        <div class="search-box"><i class="pi pi-search" /><input v-model="filterForm.search" placeholder="Tìm mã đơn, khách hàng, tracking..." class="search-input" /></div>
        <Select v-model="filterForm.order_status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Trạng thái" showClear class="filter-select" />
        <Select v-model="filterForm.supplier_id" :options="supplierOpts" optionLabel="name" optionValue="id" placeholder="Nhà cung cấp" showClear class="filter-select" />
        <Button label="Tạo đơn" icon="pi pi-plus" @click="openOrderDialog()" />
      </div>

      <div v-if="orders.data.length" class="orders-list">
        <div v-for="o in orders.data" :key="o.id" class="order-card" @click="openOrderDialog(o)">
          <div class="order-left">
            <div class="order-icon" :class="`oi-${o.order_status}`">
              <i :class="statusIcon(o.order_status)" />
            </div>
            <div class="order-info">
              <div class="order-top">
                <span class="order-num">{{ o.order_number }}</span>
                <span v-if="o.shopify_order_number" class="shopify-tag"><i class="pi pi-shopping-bag" /> #{{ o.shopify_order_number }}</span>
                <span class="status-badge" :class="`sb-${o.order_status}`">{{ o.status_label }}</span>
              </div>
              <div class="order-customer"><i class="pi pi-user" /> {{ o.customer_name }}</div>
              <div class="order-meta">
                <span><i class="pi pi-truck" /> {{ o.supplier_name }}</span>
                <span v-if="o.tracking_number" class="tracking-tag"><i class="pi pi-map-marker" /> {{ o.tracking_number }}</span>
                <span><i class="pi pi-calendar" /> {{ o.created_at }}</span>
              </div>
            </div>
          </div>
          <div class="order-right">
            <div class="order-prices">
              <div class="op-sell">{{ formatPrice(o.selling_price) }}</div>
              <div class="op-cost">Chi phí: {{ formatPrice(o.total_cost) }}</div>
              <div class="op-profit" :class="o.profit > 0 ? 'profit-pos' : 'profit-neg'">
                <i :class="o.profit > 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
                {{ formatPrice(Math.abs(o.profit)) }}
                <span v-if="o.profit_margin" class="margin-tag">({{ o.profit_margin }}%)</span>
              </div>
            </div>
            <div class="order-actions" @click.stop>
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openOrderDialog(o)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteOrder(o)" />
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-shopping-cart" /></div>
        <h3>Chưa có đơn dropship</h3>
        <p>Tạo đơn hàng đầu tiên để bắt đầu vận hành.</p>
        <Button label="Tạo đơn" icon="pi pi-plus" class="mt-1" @click="openOrderDialog()" />
      </div>
    </div>

    <!-- ═══ SUPPLIERS TAB ═══ -->
    <div v-if="activeTab === 'suppliers'">
      <div class="filter-bar">
        <div style="flex:1"></div>
        <Button label="Thêm NCC" icon="pi pi-plus" @click="openSupplierDialog()" />
      </div>

      <div v-if="suppliers.length" class="supplier-grid">
        <div v-for="s in suppliers" :key="s.id" class="supplier-card" @click="openSupplierDialog(s)">
          <div class="supplier-top">
            <div class="supplier-icon"><i class="pi pi-truck" /></div>
            <div class="supplier-header">
              <h3>{{ s.name }}</h3>
              <span class="supplier-code">{{ s.code }}</span>
              <span class="supplier-platform" v-if="s.platform">{{ s.platform }}</span>
            </div>
            <span class="active-dot" :class="s.is_active ? 'dot-active' : 'dot-inactive'" :title="s.is_active ? 'Hoạt động' : 'Ngừng'" />
          </div>
          <div class="supplier-meta">
            <span v-if="s.country"><i class="pi pi-globe" /> {{ s.country }}</span>
            <span v-if="s.email"><i class="pi pi-envelope" /> {{ s.email }}</span>
            <span v-if="s.avg_processing_days"><i class="pi pi-clock" /> {{ s.avg_processing_days }}d xử lý</span>
            <span v-if="s.avg_shipping_days"><i class="pi pi-send" /> {{ s.avg_shipping_days }}d ship</span>
          </div>
          <div class="supplier-stats">
            <div class="ss-item"><span class="ss-val">{{ s.orders_count }}</span><span class="ss-lbl">Đơn hàng</span></div>
            <div class="ss-item"><span class="ss-val">{{ s.rating || '—' }}</span><span class="ss-lbl">Đánh giá</span></div>
            <div class="ss-item"><span class="ss-val">{{ s.fulfillment_rate ? s.fulfillment_rate + '%' : '—' }}</span><span class="ss-lbl">Tỷ lệ giao</span></div>
          </div>
          <div class="supplier-actions" @click.stop>
            <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openSupplierDialog(s)" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteSupplier(s)" />
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-truck" /></div>
        <h3>Chưa có nhà cung cấp</h3>
        <p>Thêm NCC dropship đầu tiên (AliExpress, CJ, 1688...).</p>
        <Button label="Thêm NCC" icon="pi pi-plus" class="mt-1" @click="openSupplierDialog()" />
      </div>
    </div>

    <!-- ═══ ORDER DIALOG ═══ -->
    <div v-if="orderDialog" class="dialog-overlay" @click.self="orderDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon"><i class="pi pi-shopping-cart" /></div>
            <h3>{{ orderForm.id ? 'Cập nhật' : 'Tạo' }} đơn dropship</h3>
          </div>
          <button class="dialog-close" @click="orderDialog = false"><i class="pi pi-times" /></button>
        </div>

        <div class="dialog-body">
          <!-- Shopify info -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-shopping-bag section-icon" /><h4 class="section-title">Shopify & NCC</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Nhà cung cấp <span class="req">*</span></label><Select v-model="orderForm.supplier_id" :options="suppliers" optionLabel="name" optionValue="id" placeholder="Chọn NCC" class="w-full" /></div>
              <div class="form-group flex-1"><label>Shopify Order #</label><InputText v-model="orderForm.shopify_order_number" class="w-full" placeholder="#1001" /></div>
            </div>
          </div>

          <!-- Customer info -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-user section-icon" /><h4 class="section-title">Khách hàng</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Tên KH <span class="req">*</span></label><InputText v-model="orderForm.customer_name" class="w-full" /></div>
              <div class="form-group flex-1"><label>Email</label><InputText v-model="orderForm.customer_email" class="w-full" /></div>
              <div class="form-group" style="width:130px"><label>SĐT</label><InputText v-model="orderForm.customer_phone" class="w-full" /></div>
            </div>
          </div>

          <!-- Shipping -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-map-marker section-icon" /><h4 class="section-title">Ship hàng</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Người nhận</label><InputText v-model="orderForm.shipping_name" class="w-full" /></div>
              <div class="form-group" style="width:140px"><label>Phương thức</label><InputText v-model="orderForm.shipping_method" class="w-full" placeholder="ePacket, YunExpress..." /></div>
            </div>
            <div class="form-group"><label>Địa chỉ</label><InputText v-model="orderForm.shipping_address" class="w-full" /></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Thành phố</label><InputText v-model="orderForm.shipping_city" class="w-full" /></div>
              <div class="form-group flex-1"><label>Quốc gia</label><InputText v-model="orderForm.shipping_country" class="w-full" /></div>
            </div>
          </div>

          <!-- Pricing -->
          <div class="form-section">
            <div class="section-header"><i class="pi pi-dollar section-icon" /><h4 class="section-title">Chi phí & Giá bán</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Giá cost (NCC) <span class="req">*</span></label><InputNumber v-model="orderForm.total_cost" class="w-full" mode="currency" currency="USD" locale="en-US" /></div>
              <div class="form-group flex-1"><label>Giá bán (Shopify) <span class="req">*</span></label><InputNumber v-model="orderForm.selling_price" class="w-full" mode="currency" currency="USD" locale="en-US" /></div>
              <div class="form-group" style="width:120px"><label>Ship cost</label><InputNumber v-model="orderForm.shipping_cost" class="w-full" mode="currency" currency="USD" locale="en-US" /></div>
            </div>
            <div v-if="orderForm.selling_price && orderForm.total_cost" class="profit-preview">
              <span>Lợi nhuận dự kiến: </span>
              <strong :class="(orderForm.selling_price - orderForm.total_cost) > 0 ? 'profit-pos' : 'profit-neg'">
                ${{ (orderForm.selling_price - orderForm.total_cost).toFixed(2) }}
              </strong>
            </div>
          </div>

          <!-- Status & Tracking (edit only) -->
          <div v-if="orderForm.id" class="form-section">
            <div class="section-header"><i class="pi pi-map section-icon" /><h4 class="section-title">Trạng thái & Tracking</h4></div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Trạng thái</label>
                <Select v-model="orderForm.order_status" :options="statusOpts" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="form-group flex-1"><label>Mã đơn NCC</label><InputText v-model="orderForm.supplier_order_id" class="w-full" /></div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1"><label>Tracking Number</label><InputText v-model="orderForm.tracking_number" class="w-full" /></div>
              <div class="form-group flex-1"><label>Carrier</label><InputText v-model="orderForm.carrier" class="w-full" placeholder="USPS, DHL, YunExpress..." /></div>
            </div>
            <div class="form-group"><label>Tracking URL</label><InputText v-model="orderForm.tracking_url" class="w-full" placeholder="https://..." /></div>
          </div>

          <!-- Notes -->
          <div class="form-group"><label>Ghi chú</label><Editor v-model="orderForm.notes" editorStyle="height: 80px" class="w-full" /></div>
        </div>

        <div class="dialog-footer">
          <Button label="Hủy" severity="secondary" outlined @click="orderDialog = false" type="button" />
          <Button :label="orderForm.id ? 'Cập nhật' : 'Tạo đơn'" icon="pi pi-check" @click="submitOrder" :loading="orderForm.processing" />
        </div>
      </div>
    </div>

    <!-- ═══ SUPPLIER DIALOG ═══ -->
    <div v-if="supplierDialog" class="dialog-overlay" @click.self="supplierDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon dicon-supplier"><i class="pi pi-truck" /></div>
            <h3>{{ supplierForm.id ? 'Cập nhật' : 'Thêm' }} nhà cung cấp</h3>
          </div>
          <button class="dialog-close" @click="supplierDialog = false"><i class="pi pi-times" /></button>
        </div>

        <div class="dialog-body">
          <div class="form-row">
            <div class="form-group flex-1"><label>Tên NCC <span class="req">*</span></label><InputText v-model="supplierForm.name" class="w-full" placeholder="VD: CJ Dropshipping" /></div>
            <div class="form-group" style="width:140px"><label>Nền tảng</label><InputText v-model="supplierForm.platform" class="w-full" placeholder="AliExpress, CJ..." /></div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Liên hệ</label><InputText v-model="supplierForm.contact_name" class="w-full" /></div>
            <div class="form-group flex-1"><label>Email</label><InputText v-model="supplierForm.email" class="w-full" /></div>
            <div class="form-group" style="width:130px"><label>SĐT</label><InputText v-model="supplierForm.phone" class="w-full" /></div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Website</label><InputText v-model="supplierForm.website" class="w-full" /></div>
            <div class="form-group flex-1"><label>Quốc gia</label><InputText v-model="supplierForm.country" class="w-full" placeholder="China, Vietnam..." /></div>
          </div>
          <div class="form-group"><label>Địa chỉ</label><InputText v-model="supplierForm.address" class="w-full" /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Thời gian xử lý (ngày)</label><InputNumber v-model="supplierForm.avg_processing_days" class="w-full" /></div>
            <div class="form-group flex-1"><label>Thời gian ship (ngày)</label><InputNumber v-model="supplierForm.avg_shipping_days" class="w-full" /></div>
            <div class="form-group" style="width:100px"><label>Đánh giá</label><InputNumber v-model="supplierForm.rating" class="w-full" :min="0" :max="5" :step="0.5" /></div>
          </div>
          <div class="form-group"><label>Chính sách đổi trả</label><Editor v-model="supplierForm.return_policy" editorStyle="height: 60px" class="w-full" /></div>
          <div class="form-group"><label>Điều khoản thanh toán</label><Editor v-model="supplierForm.payment_terms" editorStyle="height: 60px" class="w-full" /></div>
          <div class="form-group"><label>Ghi chú</label><Editor v-model="supplierForm.notes" editorStyle="height: 60px" class="w-full" /></div>
          <div class="toggle-row">
            <div><label class="toggle-label">Hoạt động</label><small class="toggle-desc">NCC đang cung cấp dịch vụ</small></div>
            <InputSwitch v-model="supplierForm.is_active" />
          </div>
        </div>

        <div class="dialog-footer">
          <Button label="Hủy" severity="secondary" outlined @click="supplierDialog = false" type="button" />
          <Button :label="supplierForm.id ? 'Cập nhật' : 'Thêm'" icon="pi pi-check" @click="submitSupplier" :loading="supplierForm.processing" />
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
  props: { orders: Object, suppliers: Array, filters: Object, stats: Object },
  data() {
    return {
      activeTab: 'orders',
      orderDialog: false,
      supplierDialog: false,
      orderForm: this.emptyOrderForm(),
      supplierForm: this.emptySupplierForm(),
      filterForm: {
        search: this.filters.search,
        order_status: this.filters.order_status,
        supplier_id: this.filters.supplier_id ? Number(this.filters.supplier_id) : null,
      },
      statusOpts: [
        { label: 'Chờ xử lý', value: 'pending' },
        { label: 'Đang xử lý', value: 'processing' },
        { label: 'Đã đặt NCC', value: 'ordered' },
        { label: 'Đang vận chuyển', value: 'shipped' },
        { label: 'Đã giao', value: 'delivered' },
        { label: 'Đã hủy', value: 'cancelled' },
        { label: 'Hoàn trả', value: 'returned' },
      ],
    }
  },
  computed: {
    supplierOpts() { return this.suppliers.filter(s => s.is_active) },
  },
  mounted() {
    this._escHandler = (e) => {
      if (e.key === 'Escape') { this.orderDialog = false; this.supplierDialog = false }
    }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  watch: {
    filterForm: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/dropship', pickBy(this.filterForm), { preserveState: true })
      }, 300),
    },
  },
  methods: {
    formatPrice(v) { return '$' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    statusIcon(s) {
      return { pending: 'pi pi-clock', processing: 'pi pi-spinner', ordered: 'pi pi-check', shipped: 'pi pi-send', delivered: 'pi pi-check-circle', cancelled: 'pi pi-times-circle', returned: 'pi pi-replay' }[s] || 'pi pi-circle'
    },
    emptyOrderForm() {
      return this.$inertia.form({
        id: null, supplier_id: null, shopify_order_id: '', shopify_order_number: '',
        customer_name: '', customer_email: '', customer_phone: '',
        shipping_name: '', shipping_address: '', shipping_city: '', shipping_country: '', shipping_method: '',
        items: [], subtotal: 0, shipping_cost: 0, total_cost: 0, selling_price: 0,
        order_status: 'pending', fulfillment_status: 'unfulfilled',
        supplier_order_id: '', tracking_number: '', tracking_url: '', carrier: '',
        notes: '',
      })
    },
    emptySupplierForm() {
      return this.$inertia.form({
        id: null, name: '', contact_name: '', email: '', phone: '',
        website: '', platform: '', country: '', address: '',
        avg_processing_days: 3, avg_shipping_days: 14,
        return_policy: '', payment_terms: '', notes: '',
        rating: 0, is_active: true,
      })
    },
    openOrderDialog(order = null) {
      if (order) {
        this.orderForm = this.$inertia.form({ ...order })
      } else {
        this.orderForm = this.emptyOrderForm()
      }
      this.orderDialog = true
    },
    openSupplierDialog(supplier = null) {
      if (supplier) {
        this.supplierForm = this.$inertia.form({ ...supplier })
      } else {
        this.supplierForm = this.emptySupplierForm()
      }
      this.supplierDialog = true
    },
    submitOrder() {
      if (this.orderForm.id) {
        this.orderForm.put(`/dropship/orders/${this.orderForm.id}`, { preserveScroll: true, onSuccess: () => { this.orderDialog = false } })
      } else {
        this.orderForm.post('/dropship/orders', { preserveScroll: true, onSuccess: () => { this.orderDialog = false } })
      }
    },
    submitSupplier() {
      if (this.supplierForm.id) {
        this.supplierForm.put(`/dropship/suppliers/${this.supplierForm.id}`, { preserveScroll: true, onSuccess: () => { this.supplierDialog = false } })
      } else {
        this.supplierForm.post('/dropship/suppliers', { preserveScroll: true, onSuccess: () => { this.supplierDialog = false } })
      }
    },
    deleteOrder(o) {
      if (confirm(`Xóa đơn "${o.order_number}"?`)) {
        this.$inertia.delete(`/dropship/orders/${o.id}`, { preserveScroll: true })
      }
    },
    deleteSupplier(s) {
      if (confirm(`Xóa NCC "${s.name}"?`)) {
        this.$inertia.delete(`/dropship/suppliers/${s.id}`, { preserveScroll: true })
      }
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#f97316,#ea580c); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(249,115,22,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap }
.stat-chips { display:flex; gap:.4rem; flex-wrap:wrap }
.stat-chip { display:flex; align-items:center; gap:.3rem; padding:.3rem .65rem; border-radius:20px; font-size:.65rem; font-weight:600 }
.stat-chip i { font-size:.58rem }
.c1 { background:#fff7ed; color:#ea580c } .c2 { background:#fef3c7; color:#d97706 } .c3 { background:#ecfdf5; color:#059669 } .c4 { background:#f0fdf4; color:#16a34a }

/* ===== KPI Row ===== */
.kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.25rem }
.kpi-card { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; transition:all .25s }
.kpi-card:hover { border-color:#f97316; box-shadow:0 4px 18px rgba(249,115,22,.06) }
.kpi-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.ki1 { background:linear-gradient(135deg,#fff7ed,#ffedd5); color:#ea580c }
.ki2 { background:linear-gradient(135deg,#ecfdf5,#d1fae5); color:#059669 }
.ki3 { background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#6366f1 }
.ki4 { background:linear-gradient(135deg,#fdf2f8,#fce7f3); color:#db2777 }
.kpi-info { display:flex; flex-direction:column }
.kpi-value { font-size:1.1rem; font-weight:800; color:#0f172a }
.kpi-label { font-size:.62rem; color:#94a3b8; font-weight:500 }

/* ===== Tab Bar ===== */
.tab-bar { display:flex; gap:.35rem; margin-bottom:1rem; padding:.3rem; background:#f8fafc; border-radius:12px; border:1.5px solid #f1f5f9 }
.tab-btn { display:flex; align-items:center; gap:.35rem; padding:.45rem .85rem; border:none; border-radius:8px; background:transparent; font-size:.78rem; font-weight:600; color:#64748b; cursor:pointer; font-family:inherit; transition:all .2s }
.tab-btn i { font-size:.7rem }
.tab-btn:hover { color:#f97316 }
.tab-btn.active { background:white; color:#ea580c; box-shadow:0 2px 8px rgba(0,0,0,.06) }

/* ===== Filter Bar ===== */
.filter-bar { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; background:white; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:1rem; flex-wrap:wrap }
.search-box { display:flex; align-items:center; flex:1; min-width:200px; border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden }
.search-box:focus-within { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.08) }
.search-box i { padding:0 .6rem; color:#94a3b8; font-size:.75rem }
.search-input { flex:1; border:none; outline:none; padding:.5rem .5rem .5rem 0; font-size:.8rem; color:#1e293b; font-family:inherit }
.search-input::placeholder { color:#cbd5e1 }
.filter-select { min-width:130px; font-size:.8rem }

/* ===== Orders List ===== */
.orders-list { display:flex; flex-direction:column; gap:.5rem }
.order-card { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1.15rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; cursor:pointer; transition:all .25s; gap:1rem }
.order-card:hover { border-color:#f97316; box-shadow:0 4px 18px rgba(249,115,22,.06); transform:translateX(2px) }
.order-left { display:flex; align-items:center; gap:.75rem; flex:1; min-width:0 }
.order-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.oi-pending { background:#fff7ed; color:#ea580c } .oi-processing { background:#fef3c7; color:#d97706 }
.oi-ordered { background:#dbeafe; color:#3b82f6 } .oi-shipped { background:#e0e7ff; color:#6366f1 }
.oi-delivered { background:#dcfce7; color:#16a34a } .oi-cancelled { background:#fef2f2; color:#ef4444 }
.oi-returned { background:#fce7f3; color:#db2777 }
.order-info { flex:1; min-width:0 }
.order-top { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap }
.order-num { font-size:.72rem; font-weight:700; color:#ea580c; font-family:monospace }
.shopify-tag { font-size:.52rem; font-weight:600; padding:.06rem .3rem; border-radius:4px; background:#f0fdf4; color:#16a34a; display:flex; align-items:center; gap:.15rem }
.shopify-tag i { font-size:.45rem }
.status-badge { font-size:.5rem; font-weight:700; padding:.08rem .3rem; border-radius:4px; text-transform:uppercase }
.sb-pending { background:#fff7ed; color:#ea580c } .sb-processing { background:#fef3c7; color:#d97706 }
.sb-ordered { background:#dbeafe; color:#3b82f6 } .sb-shipped { background:#e0e7ff; color:#6366f1 }
.sb-delivered { background:#dcfce7; color:#16a34a } .sb-cancelled { background:#fef2f2; color:#ef4444 }
.sb-returned { background:#fce7f3; color:#db2777 }
.order-customer { font-size:.82rem; font-weight:600; color:#1e293b; margin:.1rem 0; display:flex; align-items:center; gap:.25rem }
.order-customer i { font-size:.65rem; color:#94a3b8 }
.order-meta { display:flex; gap:.6rem; flex-wrap:wrap }
.order-meta span { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.15rem }
.order-meta i { font-size:.52rem }
.tracking-tag { font-weight:600; color:#6366f1 !important; background:#eef2ff; padding:.06rem .3rem; border-radius:4px }
.order-right { display:flex; align-items:center; gap:1rem; flex-shrink:0 }
.order-prices { text-align:right }
.op-sell { font-size:1rem; font-weight:800; color:#0f172a }
.op-cost { font-size:.62rem; color:#94a3b8 }
.op-profit { font-size:.72rem; font-weight:700; display:flex; align-items:center; gap:.15rem; justify-content:flex-end }
.op-profit i { font-size:.55rem }
.profit-pos { color:#16a34a } .profit-neg { color:#ef4444 }
.margin-tag { font-size:.55rem; font-weight:600; opacity:.7 }
.order-actions { display:flex; gap:.125rem }

/* ===== Supplier Grid ===== */
.supplier-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:.65rem }
.supplier-card { padding:.85rem 1rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; cursor:pointer; transition:all .25s; position:relative }
.supplier-card:hover { border-color:#f97316; box-shadow:0 4px 18px rgba(249,115,22,.06); transform:translateY(-1px) }
.supplier-top { display:flex; align-items:center; gap:.65rem }
.supplier-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#fff7ed,#ffedd5); color:#ea580c; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0 }
.supplier-header { flex:1; min-width:0 }
.supplier-header h3 { font-size:.85rem; font-weight:700; color:#1e293b; margin:0 }
.supplier-code { font-size:.55rem; font-weight:600; color:#94a3b8; font-family:monospace; margin-right:.25rem }
.supplier-platform { font-size:.5rem; font-weight:700; padding:.06rem .3rem; border-radius:4px; background:#eef2ff; color:#6366f1; text-transform:uppercase }
.active-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0 }
.dot-active { background:#16a34a } .dot-inactive { background:#94a3b8 }
.supplier-meta { display:flex; gap:.5rem; margin:.45rem 0; flex-wrap:wrap }
.supplier-meta span { font-size:.6rem; color:#64748b; display:flex; align-items:center; gap:.15rem }
.supplier-meta i { font-size:.5rem }
.supplier-stats { display:flex; gap:.65rem; padding-top:.5rem; border-top:1px solid #f1f5f9 }
.ss-item { display:flex; flex-direction:column; align-items:center }
.ss-val { font-size:.85rem; font-weight:800; color:#0f172a }
.ss-lbl { font-size:.52rem; color:#94a3b8 }
.supplier-actions { position:absolute; top:.65rem; right:.65rem; display:flex; gap:.125rem }

/* ===== Empty State ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#fff7ed,#ffedd5); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#ea580c }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

/* ===== Dialog ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:680px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-wide { width:760px }
.dialog-card * { box-sizing:border-box }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#f97316,#ea580c); display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.dicon-supplier { background:linear-gradient(135deg,#8b5cf6,#7c3aed) }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.form-section { margin-bottom:.75rem; padding-bottom:.5rem; border-bottom:1px solid #f8fafc }
.section-header { display:flex; align-items:center; gap:.4rem; margin-bottom:.5rem }
.section-icon { font-size:.7rem; color:#f97316 }
.section-title { font-size:.75rem; font-weight:700; color:#475569; margin:0 }
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.form-group :deep(.p-inputtext), .form-group :deep(.p-select), .form-group :deep(.p-inputnumber) { width:100% }
.req { color:#ef4444 }
.toggle-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem }
.toggle-label { font-size:.82rem; font-weight:600; color:#1e293b }
.toggle-desc { display:block; font-size:.62rem; color:#94a3b8 }
.profit-preview { padding:.5rem .75rem; background:#f0fdf4; border-radius:8px; font-size:.82rem; color:#15803d; margin-bottom:.85rem }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:white; border-radius:0 0 18px 18px }

@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .kpi-row { grid-template-columns:repeat(2,1fr) }
  .filter-bar { flex-direction:column }
  .search-box { min-width:100% }
  .order-card { flex-direction:column; align-items:flex-start }
  .order-right { width:100%; justify-content:space-between }
  .supplier-grid { grid-template-columns:1fr }
  .form-row { flex-direction:column }
  .flex-1 { min-width:100% }
  .dialog-overlay { padding:.75rem }
  .dialog-card { max-height:calc(100vh - 1.5rem) }
}
</style>
