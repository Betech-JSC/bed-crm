<template>
  <div>
    <Head :title="list.name" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-lists" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-list" /></div>
        <div>
          <h1 class="page-title">{{ list.name }}</h1>
          <p class="page-subtitle">
            <span class="type-badge" :class="`tp-${list.type}`">{{ list.type === 'manual' ? 'Thủ công' : 'Tự động' }}</span>
            <span class="contact-tag"><i class="pi pi-users" /> {{ list.contacts_count }} liên hệ</span>
          </p>
        </div>
      </div>
      <Link :href="`/email-lists/${list.id}/edit`"><button class="btn-edit"><i class="pi pi-pencil" /> Sửa</button></Link>
    </div>

    <!-- Add Contact Form -->
    <div v-if="list.type === 'manual'" class="add-section">
      <h3 class="section-title"><i class="pi pi-user-plus" /> Thêm liên hệ</h3>
      <form @submit.prevent="addContact" class="add-form">
        <div class="add-row">
          <select v-model="addForm.contact_type" class="form-select sm">
            <option value="contact">Contact</option>
            <option value="lead">Lead</option>
          </select>
          <select v-model="addForm.contact_id" class="form-select flex-1">
            <option :value="null">— Chọn {{ addForm.contact_type === 'contact' ? 'contact' : 'lead' }} —</option>
            <template v-if="addForm.contact_type === 'contact'">
              <option v-for="c in availableContacts" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
            </template>
            <template v-else>
              <option v-for="l in availableLeads" :key="l.id" :value="l.id">{{ l.name }} ({{ l.email }})</option>
            </template>
          </select>
          <button type="submit" class="btn-add" :disabled="!addForm.contact_id || addProcessing"><i class="pi pi-plus" /> Thêm</button>
        </div>
      </form>
    </div>

    <!-- Contacts Table -->
    <div class="data-card">
      <div v-if="!contacts.data || contacts.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-users" /></div>
        <h3>Chưa có liên hệ</h3>
        <p>{{ list.type === 'manual' ? 'Thêm liên hệ vào danh sách' : 'Danh sách tự động chưa có kết quả' }}</p>
      </div>
      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Tên</th>
            <th>Email</th>
            <th>Loại</th>
            <th>Ngày thêm</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in contacts.data" :key="c.id" class="table-row">
            <td><span class="contact-name">{{ c.name || '—' }}</span></td>
            <td><span class="text-sub">{{ c.email }}</span></td>
            <td><span class="type-mini" :class="`tp-${c.contact_type}`">{{ c.contact_type }}</span></td>
            <td><span class="text-sub">{{ c.subscribed_at || '—' }}</span></td>
            <td>
              <button class="action-btn delete" @click="removeContact(c.id)"><i class="pi pi-trash" /></button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="contacts.total > 0" class="pagination">
        <span class="page-info">{{ contacts.from }}–{{ contacts.to }} / {{ contacts.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === contacts.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link }, layout: Layout,
  props: { list: Object, contacts: Object, availableContacts: Array, availableLeads: Array },
  data() {
    return { addForm: { contact_type: 'contact', contact_id: null }, addProcessing: false }
  },
  computed: {
    pageNumbers() {
      const total = this.contacts.last_page, cur = this.contacts.current_page, pages = []
      if (total <= 7) { for (let i = 1; i <= total; i++) pages.push(i) } else {
        pages.push(1); if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...'); pages.push(total)
      }
      return pages
    },
  },
  methods: {
    addContact() {
      this.addProcessing = true
      router.post(`/email-lists/${this.list.id}/contacts`, this.addForm, {
        preserveState: true, preserveScroll: true,
        onFinish: () => { this.addProcessing = false; this.addForm.contact_id = null },
      })
    },
    removeContact(contactId) {
      if (confirm('Xóa liên hệ khỏi danh sách?'))
        router.delete(`/email-lists/${this.list.id}/contacts/${contactId}`, { preserveState: true, preserveScroll: true })
    },
    goToPage(pg) {
      const url = new URL(window.location.href); url.searchParams.set('page', pg)
      router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#10b981;color:#10b981;background:#ecfdf5}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{display:flex;align-items:center;gap:.5rem;margin:.15rem 0 0}
.type-badge{font-size:.65rem;font-weight:600;padding:.12rem .4rem;border-radius:6px}.tp-manual{background:#eff6ff;color:#3b82f6}.tp-dynamic{background:#fef3c7;color:#d97706}
.contact-tag{font-size:.72rem;color:#64748b;display:flex;align-items:center;gap:.2rem}.contact-tag i{font-size:.65rem}
.btn-edit{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-edit:hover{border-color:#10b981;color:#10b981}

.add-section{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1rem 1.25rem;margin-bottom:1rem}
.section-title{font-size:.85rem;font-weight:700;color:#1e293b;margin:0 0 .65rem;display:flex;align-items:center;gap:.4rem}.section-title i{color:#10b981;font-size:.85rem}
.add-form{width:100%}.add-row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
.form-select{padding:.5rem .65rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.8rem;color:#334155;background:#fafbfc;outline:none;font-family:inherit}.form-select:focus{border-color:#10b981;background:#fff;box-shadow:0 0 0 3px rgba(16,185,129,.1)}.form-select.sm{width:110px;flex-shrink:0}.flex-1{flex:1;min-width:180px}
.btn-add{display:inline-flex;align-items:center;gap:.3rem;padding:.5rem .85rem;border-radius:8px;background:#10b981;color:#fff;font-size:.78rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-add:hover{background:#059669}.btn-add:disabled{opacity:.5;cursor:not-allowed}

.data-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden}
.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:.6rem 1rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;text-align:left;background:#fafbfc;border-bottom:1px solid #f1f5f9}
.data-table td{padding:.6rem 1rem;font-size:.82rem;color:#334155;vertical-align:middle;border-bottom:1px solid #f8fafc}
.table-row{transition:background .15s}.table-row:hover{background:#fafbfe}
.contact-name{font-weight:600;color:#1e293b}.text-sub{font-size:.78rem;color:#64748b}
.type-mini{font-size:.62rem;font-weight:600;padding:.1rem .35rem;border-radius:5px}.tp-contact{background:#eef2ff;color:#6366f1}.tp-lead{background:#fef3c7;color:#d97706}
.action-btn{width:28px;height:28px;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.65rem;transition:all .15s;color:#94a3b8}.action-btn.delete:hover{border-color:#ef4444;color:#ef4444;background:#fef2f2}

.pagination{display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-top:1px solid #f1f5f9}.page-info{font-size:.72rem;color:#94a3b8}.page-btns{display:flex;gap:.2rem}.page-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}.page-btn:hover:not(.active):not(.dots){border-color:#10b981;color:#10b981}.page-btn.active{background:#10b981;color:#fff;border-color:#10b981}.page-btn.dots{border:none;cursor:default}

.empty-state{display:flex;flex-direction:column;align-items:center;padding:3rem 2rem}.empty-icon{width:56px;height:56px;border-radius:16px;background:#ecfdf5;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:.6rem}.empty-state h3{font-size:.95rem;font-weight:700;color:#1e293b;margin:0 0 .15rem}.empty-state p{font-size:.75rem;color:#94a3b8;margin:0}
@media(max-width:768px){.add-row{flex-direction:column}.form-select.sm,.flex-1{width:100%}}
</style>
