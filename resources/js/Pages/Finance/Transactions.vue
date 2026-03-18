<template>
  <div>
    <Head title="Financial Transactions" />

    <div class="page-header">
      <div>
        <h1 class="page-title">Transactions</h1>
        <p class="page-subtitle">{{ transactions.total || 0 }} records</p>
      </div>
      <div class="header-actions">
        <Link href="/finance"><Button label="Dashboard" icon="pi pi-chart-line" severity="secondary" text /></Link>
        <Button label="Add Transaction" icon="pi pi-plus" @click="showDialog = true" />
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <span class="p-input-icon-left" style="flex:1">
        <i class="pi pi-search" />
        <InputText v-model="form.search" placeholder="Search description, reference..." class="w-full" @input="handleSearch" />
      </span>
      <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="All Types" class="filter-sel" @change="applyFilter" />
      <Select v-model="form.category" :options="allCategoryOptions" optionLabel="label" optionValue="value" placeholder="All Categories" class="filter-sel" @change="applyFilter" />
      <InputText v-model="form.date_from" type="date" class="filter-date" placeholder="From" @change="applyFilter" />
      <InputText v-model="form.date_to" type="date" class="filter-date" placeholder="To" @change="applyFilter" />
      <Button icon="pi pi-refresh" severity="secondary" text @click="resetFilters" />
    </div>

    <!-- Transactions Table -->
    <div class="table-card">
      <table class="txn-table">
        <thead>
          <tr>
            <th style="width:38px"></th>
            <th>Description</th>
            <th>Category</th>
            <th>Date</th>
            <th>Reference</th>
            <th class="text-right">Amount</th>
            <th style="width:80px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="txn in transactions.data" :key="txn.id" class="table-row">
            <td>
              <div class="type-icon" :class="txn.type === 'income' ? 'icon-in' : 'icon-out'">
                <i :class="txn.type === 'income' ? 'pi pi-arrow-down-left' : 'pi pi-arrow-up-right'" />
              </div>
            </td>
            <td>
              <span class="txn-desc">{{ txn.description }}</span>
              <span v-if="txn.is_recurring" class="recurring-badge"><i class="pi pi-sync" /> {{ txn.recurring_period }}</span>
              <span v-if="txn.notes" class="txn-notes">{{ txn.notes }}</span>
            </td>
            <td><span class="cat-tag" :class="`cat-${txn.type}`">{{ txn.category_label }}</span></td>
            <td class="text-secondary">{{ txn.transaction_date }}</td>
            <td class="text-secondary">{{ txn.reference || '—' }}</td>
            <td class="text-right">
              <span class="amount-cell" :class="txn.type === 'income' ? 'amt-in' : 'amt-out'">
                {{ txn.type === 'income' ? '+' : '−' }}{{ formatCurrency(txn.amount) }}
              </span>
            </td>
            <td>
              <div class="action-btns">
                <Button icon="pi pi-pencil" severity="secondary" text size="small" @click="editTxn(txn)" />
                <Button icon="pi pi-trash" severity="danger" text size="small" @click="deleteTxn(txn)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!transactions.data || transactions.data.length === 0" class="empty-state">
        <i class="pi pi-wallet" /><span>No transactions found.</span>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="transactions.total > 0" class="pagination-wrapper">
      <span class="pagination-info">{{ transactions.from }}–{{ transactions.to }} / {{ transactions.total }}</span>
      <Paginator :first="(transactions.current_page - 1) * transactions.per_page" :rows="transactions.per_page" :totalRecords="transactions.total" @page="onPage" template="PrevPageLink PageLinks NextPageLink" />
    </div>

    <!-- Create / Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Transaction' : 'Record Transaction'" :modal="true" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="type-toggle">
          <button class="toggle-btn" :class="{ active: txnForm.type === 'income' }" @click="txnForm.type = 'income'; txnForm.category = null">
            <i class="pi pi-arrow-down-left" /> Income
          </button>
          <button class="toggle-btn toggle-expense" :class="{ active: txnForm.type === 'expense' }" @click="txnForm.type = 'expense'; txnForm.category = null">
            <i class="pi pi-arrow-up-right" /> Expense
          </button>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <Select v-model="txnForm.category" :options="dialogCategoryOptions" optionLabel="label" optionValue="value" placeholder="Select category" class="w-full" />
        </div>
        <div class="form-group">
          <label>Description *</label>
          <InputText v-model="txnForm.description" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Amount (VND) *</label>
            <InputText v-model="txnForm.amount" type="number" class="w-full" />
          </div>
          <div class="form-group">
            <label>Date *</label>
            <InputText v-model="txnForm.transaction_date" type="date" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Reference</label>
          <InputText v-model="txnForm.reference" class="w-full" />
        </div>
        <div class="form-row">
          <div class="form-group form-check">
            <label><input type="checkbox" v-model="txnForm.is_recurring" /> Recurring</label>
          </div>
          <div class="form-group" v-if="txnForm.is_recurring">
            <Select v-model="txnForm.recurring_period" :options="[{value:'monthly',label:'Monthly'},{value:'quarterly',label:'Quarterly'},{value:'yearly',label:'Yearly'}]" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <Textarea v-model="txnForm.notes" rows="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showDialog = false" />
        <Button :label="editing ? 'Update' : 'Save'" icon="pi pi-check" @click="submitTxn" :loading="saving" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import Dialog from 'primevue/dialog'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'

export default {
  components: { Head, Link, Button, InputText, Textarea, Select, Paginator, Dialog },
  layout: Layout,
  props: {
    transactions: Object,
    filters: Object,
    incomeCategories: Object,
    expenseCategories: Object,
  },
  data() {
    return {
      form: { ...this.filters },
      showDialog: false,
      editing: null,
      saving: false,
      txnForm: this.freshForm(),
    }
  },
  computed: {
    typeOptions() {
      return [
        { label: 'All Types', value: null },
        { label: '↓ Income', value: 'income' },
        { label: '↑ Expense', value: 'expense' },
      ]
    },
    allCategoryOptions() {
      const opts = [{ label: 'All Categories', value: null }]
      for (const [v, l] of Object.entries(this.incomeCategories || {})) opts.push({ label: '↓ ' + l, value: v })
      for (const [v, l] of Object.entries(this.expenseCategories || {})) opts.push({ label: '↑ ' + l, value: v })
      return opts
    },
    dialogCategoryOptions() {
      const cats = this.txnForm.type === 'income' ? this.incomeCategories : this.expenseCategories
      return Object.entries(cats || {}).map(([v, l]) => ({ value: v, label: l }))
    },
  },
  methods: {
    freshForm() {
      return {
        type: 'expense', category: null, description: '', amount: 0,
        transaction_date: new Date().toISOString().split('T')[0],
        reference: '', is_recurring: false, recurring_period: 'monthly', notes: '',
      }
    },
    handleSearch: throttle(function () {
      router.get('/finance/transactions', pickBy(this.form), { preserveState: true })
    }, 300),
    applyFilter() { router.get('/finance/transactions', pickBy(this.form), { preserveState: true }) },
    resetFilters() { this.form = {}; router.get('/finance/transactions') },
    editTxn(txn) {
      this.editing = txn
      this.txnForm = {
        type: txn.type, category: txn.category, description: txn.description,
        amount: txn.amount, transaction_date: txn.transaction_date,
        reference: txn.reference || '', is_recurring: txn.is_recurring,
        recurring_period: txn.recurring_period || 'monthly', notes: txn.notes || '',
      }
      this.showDialog = true
    },
    deleteTxn(txn) {
      if (confirm('Delete this transaction?')) {
        router.delete(`/finance/transactions/${txn.id}`)
      }
    },
    submitTxn() {
      this.saving = true
      if (this.editing) {
        router.put(`/finance/transactions/${this.editing.id}`, this.txnForm, {
          onSuccess: () => { this.showDialog = false; this.editing = null },
          onFinish: () => { this.saving = false },
        })
      } else {
        router.post('/finance/transactions', this.txnForm, {
          onSuccess: () => { this.showDialog = false; this.txnForm = this.freshForm() },
          onFinish: () => { this.saving = false },
        })
      }
    },
    formatCurrency(v) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0)
    },
    onPage(e) { router.visit(`/finance/transactions?page=${e.page + 1}`, { preserveState: true }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

.filter-bar { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-sel { min-width: 150px; }
.filter-date { max-width: 140px; }

.table-card { background: white; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow-x: auto; }
.txn-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; }
.txn-table th { font-weight: 600; color: #64748b; text-align: left; padding: 0.65rem 0.85rem; border-bottom: 2px solid #f1f5f9; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; }
.txn-table td { padding: 0.65rem 0.85rem; border-bottom: 1px solid #f8fafc; color: #334155; vertical-align: middle; }
.table-row:hover td { background: #fafbfc; }
.text-right { text-align: right; }
.text-secondary { color: #64748b; }

.type-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.icon-in { background: #d1fae5; color: #059669; } .icon-out { background: #fee2e2; color: #dc2626; }
.type-icon i { font-size: 0.72rem; }

.txn-desc { font-weight: 600; color: #1e293b; display: block; }
.recurring-badge { font-size: 0.6rem; font-weight: 600; color: #6366f1; background: #eef2ff; padding: 0.08rem 0.3rem; border-radius: 4px; margin-left: 0.4rem; display: inline-flex; align-items: center; gap: 0.15rem; }
.recurring-badge i { font-size: 0.5rem; }
.txn-notes { font-size: 0.68rem; color: #94a3b8; display: block; margin-top: 0.1rem; }

.cat-tag { font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.02em; white-space: nowrap; }
.cat-income { background: #d1fae5; color: #059669; }
.cat-expense { background: #fee2e2; color: #dc2626; }

.amount-cell { font-weight: 700; font-variant-numeric: tabular-nums; }
.amt-in { color: #059669; } .amt-out { color: #dc2626; }
.action-btns { display: flex; gap: 0.1rem; }

/* Form */
.form-grid { display: flex; flex-direction: column; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: #475569; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.form-check label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; }
.type-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.toggle-btn { padding: 0.65rem; border-radius: 10px; border: 2px solid #f1f5f9; background: white; font-size: 0.82rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem; transition: all 0.2s; }
.toggle-btn:hover { border-color: #e2e8f0; }
.toggle-btn.active { border-color: #10b981; background: #ecfdf5; color: #059669; }
.toggle-expense.active { border-color: #f43f5e; background: #fff1f2; color: #e11d48; }

.empty-state { text-align: center; padding: 3rem; color: #94a3b8; } .empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
</style>
