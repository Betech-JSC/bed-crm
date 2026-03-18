<template>
  <div>
    <Head :title="t('common.create_project')" />
    <div class="page-header"><h1 class="page-title">{{ t('common.create_project') }}</h1></div>
    <div class="form-card">
      <form @submit.prevent="store">
        <div class="form-grid">
          <div class="form-group"><label>{{ t('common.name') }} <span class="req">*</span></label><InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" /><small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small></div>
          <div class="form-group"><label>{{ t('common.status') }}</label><Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" /></div>
          <div class="form-group"><label>{{ t('common.priority') }}</label><Select v-model="form.priority" :options="priorityOpts" optionLabel="label" optionValue="value" /></div>
          <div class="form-group"><label>{{ t('common.customer') }}</label><Select v-model="form.customer_id" :options="customerOpts" optionLabel="label" optionValue="value" placeholder="—" /></div>
          <div class="form-group"><label>{{ t('common.manager') }}</label><Select v-model="form.manager_id" :options="userOpts" optionLabel="label" optionValue="value" placeholder="—" /></div>
          <div class="form-group"><label>{{ t('common.start_date') }}</label><Calendar v-model="form.start_date" dateFormat="yy-mm-dd" /></div>
          <div class="form-group"><label>{{ t('common.due_date') }}</label><Calendar v-model="form.due_date" dateFormat="yy-mm-dd" /></div>
          <div class="form-group"><label>{{ t('common.budget') }}</label><InputNumber v-model="form.budget" mode="currency" currency="VND" locale="vi-VN" /></div>
          <div class="form-group"><label>Revenue</label><InputNumber v-model="form.revenue" mode="currency" currency="VND" locale="vi-VN" /></div>
        </div>
        <div class="form-group full"><label>{{ t('common.description') }}</label><Textarea v-model="form.description" rows="3" /></div>
        <div class="form-actions">
          <Link href="/projects"><Button :label="t('common.cancel')" severity="secondary" outlined /></Link>
          <Button :label="t('common.create_project')" icon="pi pi-check" :loading="form.processing" type="submit" />
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
  props: { statuses: Object, priorities: Object, customers: Array, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: this.$inertia.form({
        name: '', description: '', status: 'planning', priority: 'medium',
        customer_id: null, manager_id: null, start_date: null, due_date: null,
        budget: 0, revenue: 0, notes: '',
      }),
    }
  },
  computed: {
    statusOpts() { return Object.entries(this.statuses).map(([v, l]) => ({ label: l, value: v })) },
    priorityOpts() { return Object.entries(this.priorities).map(([v, l]) => ({ label: l, value: v })) },
    customerOpts() { return [{ label: '—', value: null }, ...this.customers.map(c => ({ label: c.name, value: c.id }))] },
    userOpts() { return [{ label: '—', value: null }, ...this.salesUsers.map(u => ({ label: u.name, value: u.id }))] },
  },
  methods: { store() { this.form.post('/projects') } },
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
