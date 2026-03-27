<template>
  <div>
    <Head title="Tạo hợp đồng" />
    <div class="page-header"><div class="header-content"><Link href="/contracts" class="back-btn"><i class="pi pi-arrow-left" /></Link><div><h1 class="page-title">Tạo hợp đồng mới</h1><p class="page-subtitle">Mã: {{ nextNumber }}</p></div></div></div>
    <div class="form-card"><div class="card-accent" />
      <form @submit.prevent="store" class="card-body">
        <div class="form-section"><div class="section-header"><i class="pi pi-info-circle section-icon" /><h3 class="section-title">Thông tin chung</h3></div>
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="form.title" class="w-full" placeholder="VD: Hợp đồng triển khai CRM" :class="{'p-invalid':form.errors.title}" /><small v-if="form.errors.title" class="field-error">{{ form.errors.title }}</small></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Khách hàng</label><Select v-model="form.customer_id" :options="customers" optionLabel="name" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Deal</label><Select v-model="form.deal_id" :options="deals" optionLabel="title" optionValue="id" placeholder="Chọn" showClear filter class="w-full" /></div>
            <div class="form-group flex-1"><label>Từ báo giá</label><Select v-model="form.quotation_id" :options="quotations" optionLabel="quote_number" optionValue="id" placeholder="Chọn" showClear class="w-full" /></div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Loại HĐ</label>
              <div class="type-grid">
                <button v-for="t in types" :key="t.value" type="button" class="type-chip" :class="{active:form.contract_type===t.value}" @click="form.contract_type=t.value"><i :class="t.icon" /> {{ t.label }}</button>
              </div>
            </div>
          </div>
        </div>
        <div class="form-section"><div class="section-header"><i class="pi pi-dollar section-icon" /><h3 class="section-title">Giá trị & Thời hạn</h3></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Giá trị HĐ <span class="req">*</span></label><InputNumber v-model="form.value" class="w-full" mode="currency" currency="VND" locale="vi-VN" /></div>
            <div class="form-group flex-1"><label>Bắt đầu</label><InputText v-model="form.start_date" type="date" class="w-full" /></div>
            <div class="form-group flex-1"><label>Kết thúc</label><InputText v-model="form.end_date" type="date" class="w-full" /></div>
          </div>
          <div class="toggle-row"><div><label class="toggle-label">Tự động gia hạn</label><small class="toggle-desc">Hợp đồng sẽ tự động renew khi hết hạn</small></div><InputSwitch v-model="form.auto_renew" /></div>
        </div>
        <div class="form-section"><div class="section-header"><i class="pi pi-file section-icon" /><h3 class="section-title">Nội dung</h3></div>
          <div class="form-group"><label>Điều khoản thanh toán</label><Textarea v-model="form.payment_terms" class="w-full" rows="2" placeholder="VD: Thanh toán 50% trước, 50% sau khi nghiệm thu" /></div>
          <div class="form-group"><label>Phạm vi công việc</label><Textarea v-model="form.scope_of_work" class="w-full" rows="3" /></div>
          <div class="form-group"><label>Điều khoản & Điều kiện</label><Textarea v-model="form.terms_conditions" class="w-full" rows="3" /></div>
        </div>
        <div class="form-footer"><Link href="/contracts"><Button label="Hủy" severity="secondary" outlined /></Link><Button type="submit" label="Tạo hợp đồng" icon="pi pi-check" :loading="form.processing" /></div>
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
  props: { customers: Array, deals: Array, quotations: Array, nextNumber: String },
  data() {
    return {
      form: this.$inertia.form({
        title: '', contract_type: 'one_time', customer_id: null, deal_id: null, quotation_id: null,
        value: 0, start_date: new Date().toISOString().split('T')[0], end_date: '',
        payment_terms: '', scope_of_work: '', terms_conditions: '', auto_renew: false,
      }),
      types: [
        { value: 'one_time', label: 'Một lần', icon: 'pi pi-bolt' },
        { value: 'recurring', label: 'Định kỳ', icon: 'pi pi-sync' },
        { value: 'retainer', label: 'Retainer', icon: 'pi pi-wallet' },
        { value: 'project_based', label: 'Theo dự án', icon: 'pi pi-folder' },
      ],
    }
  },
  methods: { store() { this.form.post('/contracts') } },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;margin-bottom:1.5rem}.header-content{display:flex;align-items:center;gap:.85rem}
.back-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:white;border:1.5px solid #e2e8f0;color:#64748b;text-decoration:none;transition:all .2s}.back-btn:hover{border-color:#059669;color:#059669}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}.page-subtitle{font-size:.82rem;color:#059669;font-family:monospace;margin:.15rem 0 0}
.form-card{background:white;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);max-width:780px}
.card-accent{height:4px;background:linear-gradient(90deg,#059669,#047857,#065f46)}.card-body{padding:1.5rem}
.form-section{background:#fafbfc;border:1px solid #f1f5f9;border-radius:12px;padding:1.15rem;margin-bottom:1rem}
.section-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.section-icon{font-size:.85rem;color:#059669}.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0}
.form-row{display:flex;gap:.75rem;flex-wrap:wrap}.form-group{margin-bottom:.75rem;min-width:0}.flex-1{flex:1;min-width:150px}.w-full{width:100%}
.form-group label{display:block;font-size:.72rem;font-weight:600;color:#475569;margin-bottom:.35rem}.req{color:#ef4444}
.field-error{display:block;font-size:.65rem;color:#ef4444;margin-top:.2rem}
.type-grid{display:flex;gap:.35rem;flex-wrap:wrap}
.type-chip{display:flex;align-items:center;gap:.3rem;padding:.4rem .65rem;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:.72rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .2s}
.type-chip i{font-size:.62rem}.type-chip:hover{border-color:#059669;color:#059669}
.type-chip.active{border-color:#059669;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:#065f46}
.toggle-row{display:flex;justify-content:space-between;align-items:center;margin-top:.5rem}
.toggle-label{font-size:.82rem;font-weight:600;color:#1e293b}.toggle-desc{display:block;font-size:.62rem;color:#94a3b8}
.form-footer{display:flex;justify-content:flex-end;gap:.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;margin-top:.5rem}
@media(max-width:768px){.form-row{flex-direction:column}.flex-1{min-width:100%}.type-grid{flex-direction:column}}
</style>
