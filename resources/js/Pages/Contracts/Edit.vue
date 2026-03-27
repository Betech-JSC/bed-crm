<template>
  <div>
    <Head :title="contract.contract_number" />
    <div class="page-header">
      <div class="header-content">
        <Link href="/contracts" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div><h1 class="page-title">{{ contract.contract_number }}</h1><p class="page-subtitle">{{ form.title }}</p></div>
      </div>
      <div class="header-actions">
        <span class="status-badge" :class="`s-${contract.status}`">{{ contract.status === 'draft' ? 'Nháp' : contract.status === 'pending_approval' ? 'Chờ duyệt' : contract.status === 'active' ? 'Hiệu lực' : contract.status === 'completed' ? 'Hoàn tất' : contract.status === 'cancelled' ? 'Đã hủy' : contract.status }}</span>
        <Button v-if="contract.status === 'draft'" label="Gửi duyệt" icon="pi pi-send" severity="warning" size="small" @click="submitApproval" />
      </div>
    </div>
    <div class="form-card"><div class="card-accent" />
      <form @submit.prevent="update" class="card-body">
        <div class="form-section"><div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin chung</h3></div>
          <div class="form-group"><label>Tiêu đề</label><InputText v-model="form.title" class="w-full" /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Khách hàng</label><Select v-model="form.customer_id" :options="customers" optionLabel="name" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Deal</label><Select v-model="form.deal_id" :options="deals" optionLabel="title" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Từ báo giá</label><Select v-model="form.quotation_id" :options="quotations" optionLabel="quote_number" optionValue="id" placeholder="Chọn" showClear class="w-full" /></div>
          </div>
          <div class="form-group"><label>Loại HĐ</label>
            <div class="type-grid">
              <button v-for="t in types" :key="t.value" type="button" class="type-chip" :class="{active:form.contract_type===t.value}" @click="form.contract_type=t.value"><i :class="t.icon" /> {{ t.label }}</button>
            </div>
          </div>
        </div>
        <div class="form-section"><div class="section-header"><i class="pi pi-dollar section-icon" /><h3 class="section-title">Giá trị & Thời hạn</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Giá trị HĐ</label><InputNumber v-model="form.value" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
            <div class="form-group flex-1"><label>Bắt đầu</label><InputText v-model="form.start_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Kết thúc</label><InputText v-model="form.end_date" type="date" class="w-full" /></div>
          </div>
          <div class="toggle-row"><div><label class="toggle-label">Tự động gia hạn</label><small class="toggle-desc">Tự động renew khi hết hạn</small></div><InputSwitch v-model="form.auto_renew" /></div>
        </div>
        <div class="form-section"><div class="section-header"><i class="pi pi-file section-icon" /><h3 class="section-title">Nội dung</h3></div>
          <div class="form-group"><label>Điều khoản thanh toán</label><Textarea v-model="form.payment_terms" class="w-full" rows="2" /></div>
          <div class="form-group"><label>Phạm vi công việc</label><Textarea v-model="form.scope_of_work" class="w-full" rows="3" /></div>
          <div class="form-group"><label>Điều khoản & Điều kiện</label><Textarea v-model="form.terms_conditions" class="w-full" rows="3" /></div>
        </div>
        <div class="form-footer">
          <button type="button" class="delete-btn" @click="destroy"><i class="pi pi-trash" /> Xóa</button>
          <div class="footer-right"><Link href="/contracts"><Button label="Hủy" severity="secondary" outlined /></Link><Button type="submit" label="Cập nhật" icon="pi pi-check" :loading="form.processing" /></div>
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
import Select from 'primevue/select'
import Button from 'primevue/button'

export default {
  components: { Head, Link, InputText, InputNumber, InputSwitch, Textarea, Select, Button },
  layout: Layout,
  props: { contract: Object, customers: Array, deals: Array, quotations: Array },
  data() {
    return {
      form: this.$inertia.form({
        title: this.contract.title, contract_type: this.contract.contract_type,
        customer_id: this.contract.customer_id, deal_id: this.contract.deal_id, quotation_id: this.contract.quotation_id,
        value: Number(this.contract.value), start_date: this.contract.start_date, end_date: this.contract.end_date,
        payment_terms: this.contract.payment_terms, scope_of_work: this.contract.scope_of_work,
        terms_conditions: this.contract.terms_conditions, auto_renew: this.contract.auto_renew,
      }),
      types: [
        { value: 'one_time', label: 'Một lần', icon: 'pi pi-bolt' },
        { value: 'recurring', label: 'Định kỳ', icon: 'pi pi-sync' },
        { value: 'retainer', label: 'Retainer', icon: 'pi pi-wallet' },
        { value: 'project_based', label: 'Theo dự án', icon: 'pi pi-folder' },
      ],
    }
  },
  methods: {
    update() { this.form.put(`/contracts/${this.contract.id}`) },
    destroy() { if (confirm('Xóa hợp đồng?')) this.$inertia.delete(`/contracts/${this.contract.id}`) },
    submitApproval() { this.$inertia.post(`/contracts/${this.contract.id}/submit-approval`) },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.header-content{display:flex;align-items:center;gap:.75rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#059669;color:#059669}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;font-family:monospace}.page-subtitle{font-size:.82rem;color:#64748b;margin:.15rem 0 0}
.header-actions{display:flex;align-items:center;gap:.5rem}
.status-badge{font-size:.62rem;font-weight:700;padding:.2rem .55rem;border-radius:6px;text-transform:uppercase}
.s-draft{background:#f1f5f9;color:#64748b}.s-pending_approval{background:#fffbeb;color:#d97706}.s-active{background:#ecfdf5;color:#059669}.s-completed{background:#eff6ff;color:#3b82f6}.s-cancelled{background:#fef2f2;color:#ef4444}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:780px}
.card-accent{height:4px;background:linear-gradient(90deg,#059669,#047857,#065f46)}.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#059669}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:150px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.type-grid{display:flex;gap:.35rem;flex-wrap:wrap}
.type-chip{display:flex;align-items:center;gap:.3rem;padding:.4rem .65rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.72rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s}
.type-chip i{font-size:.62rem}.type-chip:hover{border-color:#059669;color:#059669}
.type-chip.active{border-color:#059669;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:#065f46}
.toggle-row{display:flex;justify-content:space-between;align-items:center;margin-top:.5rem}
.toggle-label{font-size:.82rem;font-weight:600;color:#1e293b}.toggle-desc{display:block;font-size:.62rem;color:#94a3b8}
.form-footer{display:flex;justify-content:space-between;align-items:center;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
.footer-right{display:flex;gap:.5rem}
.delete-btn{display:flex;align-items:center;gap:.3rem;background:none;border:none;color:#ef4444;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit}.delete-btn:hover{opacity:.7}.delete-btn i{font-size:.68rem}
@media(max-width:768px){.page-header{flex-direction:column;align-items:flex-start}.form-row{flex-direction:column}.flex-1{min-width:100%}}
</style>
