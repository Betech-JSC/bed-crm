<template>
  <div>
    <Head title="Tạo Automation" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-automations" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-bolt" /></div>
        <div><h1 class="page-title">Tạo Automation Mới</h1><p class="page-subtitle">Tự động hóa quy trình email</p></div>
      </div>
    </div>
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Tên automation <span class="req">*</span></label>
            <input v-model="form.name" class="form-input" :class="{ err: form.errors.name }" placeholder="VD: Welcome Series, Follow-up Leads..." />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>
          <div class="form-group full">
            <label class="form-label">Mô tả</label>
            <textarea v-model="form.description" class="form-textarea" rows="3" placeholder="Mô tả quy trình tự động..." />
          </div>
          <div class="form-group full">
            <label class="form-label">Trigger <span class="req">*</span></label>
            <div class="trigger-grid">
              <div v-for="t in triggerTypes" :key="t" class="trigger-option" :class="{ active: form.trigger_type === t }" @click="form.trigger_type = t">
                <i :class="triggerIcon(t)" />
                <span>{{ triggerLabel(t) }}</span>
              </div>
            </div>
            <span v-if="form.errors.trigger_type" class="form-error">{{ form.errors.trigger_type }}</span>
          </div>
        </div>
        <div class="form-actions">
          <Link href="/email-automations"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing"><i v-if="form.processing" class="pi pi-spin pi-spinner" /><i v-else class="pi pi-check" /> Tạo Automation</button>
        </div>
      </form>
    </div>
  </div>
</template>
<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link }, layout: Layout,
  props: { triggerTypes: Array },
  data() {
    return { form: useForm({ name: '', description: '', trigger_type: this.triggerTypes[0] || 'lead_created', trigger_config: {} }) }
  },
  methods: {
    submit() { this.form.post('/email-automations') },
    triggerLabel(t) { return { lead_created: 'Lead mới được tạo', contact_created: 'Contact mới', deal_won: 'Deal thắng', deal_stage_changed: 'Deal đổi stage', tag_added: 'Thêm tag', segment_entered: 'Vào segment', form_submitted: 'Submit form', page_visited: 'Xem trang' }[t] || t },
    triggerIcon(t) { return { lead_created: 'pi pi-user-plus', contact_created: 'pi pi-users', deal_won: 'pi pi-star', deal_stage_changed: 'pi pi-arrows-h', tag_added: 'pi pi-tag', segment_entered: 'pi pi-filter', form_submitted: 'pi pi-file', page_visited: 'pi pi-globe' }[t] || 'pi pi-bolt' },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;margin-bottom:1.25rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#f59e0b;color:#d97706;background:#fffbeb}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}
.form-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.5rem}.form-grid{display:grid;grid-template-columns:1fr;gap:1rem}.form-group{display:flex;flex-direction:column;gap:.3rem}.form-group.full{grid-column:1/-1}.form-label{font-size:.78rem;font-weight:600;color:#374151}.req{color:#ef4444}.form-input,.form-textarea{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.82rem;color:#334155;transition:all .2s;background:#fafbfc;outline:none;font-family:inherit;width:100%;box-sizing:border-box}.form-input:focus,.form-textarea:focus{border-color:#f59e0b;background:#fff;box-shadow:0 0 0 3px rgba(245,158,11,.1)}.err{border-color:#ef4444!important}.form-textarea{resize:vertical;min-height:60px}.form-error{font-size:.68rem;color:#ef4444;font-weight:500}
.trigger-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem}
.trigger-option{display:flex;align-items:center;gap:.5rem;padding:.65rem .85rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fafbfc;cursor:pointer;transition:all .2s;font-size:.8rem;color:#475569;font-weight:500}
.trigger-option:hover{border-color:#fcd34d;background:#fffdf7}
.trigger-option.active{border-color:#f59e0b;background:#fffbeb;color:#92400e;font-weight:600}
.trigger-option i{font-size:.85rem;color:#94a3b8}.trigger-option.active i{color:#f59e0b}
.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f1f5f9}.btn-cancel{padding:.55rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-cancel:hover{background:#f8fafc}.btn-submit{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(245,158,11,.3)}.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
@media(max-width:768px){.trigger-grid{grid-template-columns:1fr}}
</style>
