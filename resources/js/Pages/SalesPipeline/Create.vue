<template>
  <div>
    <Head title="Tạo quy trình bán hàng" />

    <div class="page-header">
      <div>
        <h1 class="page-title">Tạo quy trình bán hàng</h1>
        <p class="page-subtitle">Bước đầu: Nhập thông tin khách hàng và thực hiện audit</p>
      </div>
      <Link href="/sales-pipeline">
        <Button label="Quay lại" icon="pi pi-arrow-left" severity="secondary" text />
      </Link>
    </div>

    <form @submit.prevent="submit" class="create-form">
      <!-- Section: Customer Info -->
      <div class="form-section">
        <h2 class="section-title">
          <i class="pi pi-user" />
          Thông tin khách hàng
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label>Liên kết Lead <span class="optional">(tùy chọn)</span></label>
            <select v-model="form.lead_id" class="form-control" @change="onLeadSelect">
              <option :value="null">-- Chọn lead --</option>
              <option v-for="lead in leads" :key="lead.id" :value="lead.id">
                {{ lead.name }} {{ lead.company ? `(${lead.company})` : '' }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Tên công ty <span class="required">*</span></label>
            <input v-model="form.company_name" type="text" class="form-control" placeholder="VD: Công ty ABC" />
            <span v-if="form.errors.company_name" class="error">{{ form.errors.company_name }}</span>
          </div>
          <div class="form-group">
            <label>Người liên hệ <span class="required">*</span></label>
            <input v-model="form.contact_name" type="text" class="form-control" placeholder="VD: Nguyễn Văn A" />
            <span v-if="form.errors.contact_name" class="error">{{ form.errors.contact_name }}</span>
          </div>
          <div class="form-group">
            <label>Số điện thoại</label>
            <input v-model="form.phone" type="text" class="form-control" placeholder="0901234567" />
          </div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="form.email" type="email" class="form-control" placeholder="email@company.com" />
          </div>
          <div class="form-group">
            <label>Website</label>
            <input v-model="form.website_url" type="url" class="form-control" placeholder="https://example.com" />
          </div>
        </div>
      </div>

      <!-- Section: Assignment & Priority -->
      <div class="form-section">
        <h2 class="section-title">
          <i class="pi pi-sliders-h" />
          Phân công & Ưu tiên
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label>Người phụ trách</label>
            <select v-model="form.assigned_to" class="form-control">
              <option :value="null">-- Chọn nhân viên --</option>
              <option v-for="user in salesUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Mức độ ưu tiên</label>
            <div class="priority-selector">
              <button
                v-for="(label, key) in priorities"
                :key="key"
                type="button"
                class="priority-btn"
                :class="{ active: form.priority === key, [`btn-${key}`]: true }"
                @click="form.priority = key"
              >
                {{ label }}
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Kênh kết bạn</label>
            <select v-model="form.social_channel" class="form-control">
              <option :value="null">-- Chọn kênh --</option>
              <option v-for="(label, key) in socialChannels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tài khoản social</label>
            <input v-model="form.social_account" type="text" class="form-control" placeholder="Số Zalo hoặc link FB" />
          </div>
        </div>
      </div>

      <!-- Section: Audit -->
      <div class="form-section">
        <h2 class="section-title">
          <i class="pi pi-search" />
          Audit tình trạng khách hàng
        </h2>

        <!-- Website Audit -->
        <div class="audit-group">
          <h3 class="audit-group-title">
            <i class="pi pi-globe" />
            Website
          </h3>
          <div class="audit-checklist">
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.website.has_website" />
              <span>Có website</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.website.has_ssl" />
              <span>Có SSL (HTTPS)</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.website.is_responsive" />
              <span>Responsive (mobile-friendly)</span>
            </label>
          </div>
          <div class="form-grid form-grid-small">
            <div class="form-group">
              <label>Điểm tốc độ (0-100)</label>
              <input v-model.number="form.audit_data.website.speed_score" type="number" min="0" max="100" class="form-control" />
            </div>
            <div class="form-group">
              <label>Điểm SEO (0-100)</label>
              <input v-model.number="form.audit_data.website.seo_score" type="number" min="0" max="100" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label>Ghi chú website</label>
            <textarea v-model="form.audit_data.website.notes" class="form-control" rows="2" placeholder="Nhận xét về website..." />
          </div>
        </div>

        <!-- Marketing Audit -->
        <div class="audit-group">
          <h3 class="audit-group-title">
            <i class="pi pi-megaphone" />
            Marketing
          </h3>
          <div class="audit-checklist">
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.marketing.has_ads" />
              <span>Có chạy quảng cáo</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.marketing.has_fanpage" />
              <span>Có fanpage</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.marketing.has_seo" />
              <span>Có SEO</span>
            </label>
            <label class="check-item">
              <input type="checkbox" v-model="form.audit_data.marketing.has_content" />
              <span>Có nội dung/blog</span>
            </label>
          </div>
          <div class="form-group">
            <label>Link fanpage</label>
            <input v-model="form.audit_data.marketing.fanpage_url" type="url" class="form-control" placeholder="https://facebook.com/..." />
          </div>
          <div class="form-group">
            <label>Ghi chú marketing</label>
            <textarea v-model="form.audit_data.marketing.notes" class="form-control" rows="2" placeholder="Nhận xét về marketing..." />
          </div>
        </div>

        <!-- Business Audit -->
        <div class="audit-group">
          <h3 class="audit-group-title">
            <i class="pi pi-chart-bar" />
            Kinh doanh
          </h3>
          <div class="form-grid">
            <div class="form-group">
              <label>Quy mô công ty</label>
              <select v-model="form.audit_data.business.company_size" class="form-control">
                <option value="">-- Chọn --</option>
                <option value="1-10">1-10 người</option>
                <option value="11-50">11-50 người</option>
                <option value="51-200">51-200 người</option>
                <option value="201-500">201-500 người</option>
                <option value="500+">Trên 500 người</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ngành nghề</label>
              <input v-model="form.audit_data.business.industry" type="text" class="form-control" placeholder="VD: Thương mại điện tử" />
            </div>
            <div class="form-group">
              <label>Doanh thu ước tính</label>
              <input v-model="form.audit_data.business.estimated_revenue" type="text" class="form-control" placeholder="VD: 5-10 tỷ/năm" />
            </div>
            <div class="form-group">
              <label>Đối thủ cạnh tranh</label>
              <input v-model="form.audit_data.business.competitors" type="text" class="form-control" placeholder="VD: Công ty X, Y" />
            </div>
          </div>
          <div class="form-group">
            <label>Pain points / Vấn đề gặp phải</label>
            <textarea v-model="form.audit_data.business.pain_points" class="form-control" rows="3" placeholder="Khách hàng đang gặp vấn đề gì?" />
          </div>
          <div class="form-group">
            <label>Ghi chú kinh doanh</label>
            <textarea v-model="form.audit_data.business.notes" class="form-control" rows="2" placeholder="Nhận xét thêm..." />
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="form-section">
        <h2 class="section-title">
          <i class="pi pi-pencil" />
          Ghi chú thêm
        </h2>
        <div class="form-group">
          <textarea v-model="form.notes" class="form-control" rows="3" placeholder="Ghi chú chung..." />
        </div>
      </div>

      <!-- Submit -->
      <div class="form-actions">
        <Link href="/sales-pipeline">
          <Button label="Hủy" severity="secondary" text />
        </Link>
        <Button type="submit" label="Tạo quy trình" icon="pi pi-check" :loading="form.processing" />
      </div>
    </form>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: {
    leads: Array,
    priorities: Object,
    socialChannels: Object,
    salesUsers: Array,
    auditTemplate: Object,
  },
  setup(props) {
    const form = useForm({
      lead_id: null,
      company_name: '',
      contact_name: '',
      phone: '',
      email: '',
      website_url: '',
      assigned_to: null,
      social_channel: null,
      social_account: '',
      priority: 'warm',
      notes: '',
      audit_data: JSON.parse(JSON.stringify(props.auditTemplate)),
    })

    return { form }
  },
  methods: {
    onLeadSelect() {
      if (this.form.lead_id) {
        const lead = this.leads.find(l => l.id === this.form.lead_id)
        if (lead) {
          this.form.company_name = lead.company || ''
          this.form.contact_name = lead.name || ''
          this.form.phone = lead.phone || ''
          this.form.email = lead.email || ''
        }
      }
    },
    submit() {
      this.form.post('/sales-pipeline')
    },
  },
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

.create-form {
  max-width: 860px;
}

.form-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.section-title i { color: #6366f1; font-size: 0.95rem; }

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-grid-small {
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.form-group {
  margin-bottom: 0.75rem;
}
.form-group label {
  display: block;
  font-size: 0.78rem;
  font-weight: 500;
  color: #475569;
  margin-bottom: 0.35rem;
}
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; }

.form-control {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #1e293b;
  background: white;
  transition: all 0.2s;
  outline: none;
  font-family: inherit;
}
.form-control:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

select.form-control { cursor: pointer; }
textarea.form-control { resize: vertical; min-height: 60px; }

.error { font-size: 0.72rem; color: #ef4444; margin-top: 0.2rem; display: block; }

/* Priority selector */
.priority-selector {
  display: flex;
  gap: 0.5rem;
}
.priority-btn {
  flex: 1;
  padding: 0.45rem 0.75rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
  border: 2px solid #e2e8f0;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}
.priority-btn:hover { border-color: #cbd5e1; }
.priority-btn.active.btn-hot { border-color: #ef4444; background: #fef2f2; color: #ef4444; }
.priority-btn.active.btn-warm { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
.priority-btn.active.btn-cold { border-color: #3b82f6; background: #eff6ff; color: #3b82f6; }

/* Audit group */
.audit-group {
  background: #f8fafc;
  border-radius: 10px;
  padding: 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid #f1f5f9;
}
.audit-group-title {
  font-size: 0.88rem;
  font-weight: 600;
  color: #334155;
  margin: 0 0 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.audit-group-title i { color: #6366f1; font-size: 0.85rem; }

.audit-checklist {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.check-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: #475569;
  cursor: pointer;
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  background: white;
  border: 1px solid #e2e8f0;
  transition: all 0.15s;
}
.check-item:hover { border-color: #6366f1; }
.check-item input[type="checkbox"] {
  accent-color: #6366f1;
  cursor: pointer;
}

/* Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 0;
}

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .priority-selector { flex-direction: column; }
}
</style>
