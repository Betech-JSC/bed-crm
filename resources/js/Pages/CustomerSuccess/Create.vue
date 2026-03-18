<template>
  <div>
    <Head :title="t('common.create_customer')" />
    <div class="page-header">
      <h1 class="page-title">{{ t('common.create_customer') }}</h1>
    </div>
    <div class="form-card">
      <form @submit.prevent="store">
        <div class="form-grid">
          <div class="form-group">
            <label>{{ t('common.name') }} <span class="req">*</span></label>
            <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
            <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
          </div>
          <div class="form-group">
            <label>{{ t('common.email') }}</label>
            <InputText v-model="form.email" type="email" />
          </div>
          <div class="form-group">
            <label>{{ t('common.phone') }}</label>
            <InputText v-model="form.phone" />
          </div>
          <div class="form-group">
            <label>{{ t('common.organization') }}</label>
            <Select v-model="form.organization_id" :options="orgOptions" optionLabel="label" optionValue="value" placeholder="—" />
          </div>
          <div class="form-group">
            <label>{{ t('common.lifecycle') }}</label>
            <Select v-model="form.lifecycle_status" :options="statusOptions" optionLabel="label" optionValue="value" />
          </div>
          <div class="form-group">
            <label>{{ t('common.assigned_to') }}</label>
            <Select v-model="form.assigned_to" :options="userOptions" optionLabel="label" optionValue="value" placeholder="—" />
          </div>
          <div class="form-group">
            <label>MRR</label>
            <InputNumber v-model="form.mrr" mode="currency" currency="VND" locale="vi-VN" />
          </div>
          <div class="form-group">
            <label>ARR</label>
            <InputNumber v-model="form.arr" mode="currency" currency="VND" locale="vi-VN" />
          </div>
          <div class="form-group">
            <label>{{ t('common.start_date') }}</label>
            <Calendar v-model="form.start_date" dateFormat="yy-mm-dd" />
          </div>
          <div class="form-group">
            <label>{{ t('common.contract_start') }}</label>
            <Calendar v-model="form.contract_start" dateFormat="yy-mm-dd" />
          </div>
          <div class="form-group">
            <label>{{ t('common.contract_end') }}</label>
            <Calendar v-model="form.contract_end" dateFormat="yy-mm-dd" />
          </div>
          <div class="form-group">
            <label>{{ t('common.contract_term') }}</label>
            <Select v-model="form.contract_term" :options="termOptions" optionLabel="label" optionValue="value" placeholder="—" />
          </div>
        </div>
        <div class="form-group full">
          <label>{{ t('common.notes') }}</label>
          <Textarea v-model="form.notes" rows="3" />
        </div>
        <div class="form-actions">
          <Link href="/customers"><Button :label="t('common.cancel')" severity="secondary" outlined /></Link>
          <Button :label="t('common.create_customer')" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, InputText, InputNumber, Textarea, Select, Calendar, Button },
  layout: Layout,
  props: { lifecycleStatuses: Object, renewalStatuses: Object, organizations: Array, contacts: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: this.$inertia.form({
        name: '', email: '', phone: '', organization_id: null, contact_id: null, assigned_to: null,
        lifecycle_status: 'onboarding', start_date: null, mrr: 0, arr: 0,
        contract_start: null, contract_end: null, contract_term: null, auto_renew: false, notes: '',
      }),
    }
  },
  computed: {
    orgOptions() { return [{ label: '—', value: null }, ...this.organizations.map(o => ({ label: o.name, value: o.id }))] },
    statusOptions() { return Object.entries(this.lifecycleStatuses).map(([v, l]) => ({ label: l, value: v })) },
    userOptions() { return [{ label: '—', value: null }, ...this.salesUsers.map(u => ({ label: u.name, value: u.id }))] },
    termOptions() { return [{ label: 'Monthly', value: 'monthly' }, { label: 'Quarterly', value: 'quarterly' }, { label: 'Yearly', value: 'yearly' }] },
  },
  methods: {
    store() { this.form.post('/customers') },
  },
}
</script>

<style scoped>
.page-header { margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.form-card { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.82rem; font-weight: 600; color: #334155; }
.req { color: #ef4444; }
.form-group.full { margin-bottom: 1rem; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
</style>
