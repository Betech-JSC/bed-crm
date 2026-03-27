<template>
  <div>
    <Head title="Chatbot Flows" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-comments" style="color:#10b981;" /> Chatbot Flows</h1>
        <p class="page-subtitle">Xây dựng chatbot tự động qualification leads</p>
      </div>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo Flow</button>
    </div>

    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-comments" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_flows }}</span><span class="stat-lbl">Total Flows</span></div></div>
      <div class="stat-card"><div class="stat-icon si-active"><i class="pi pi-play" /></div><div class="stat-body"><span class="stat-val stat-green">{{ stats.active_flows }}</span><span class="stat-lbl">Active</span></div></div>
      <div class="stat-card"><div class="stat-icon si-conv"><i class="pi pi-users" /></div><div class="stat-body"><span class="stat-val">{{ formatNum(stats.total_conversations) }}</span><span class="stat-lbl">Conversations</span></div></div>
      <div class="stat-card"><div class="stat-icon si-leads"><i class="pi pi-bullseye" /></div><div class="stat-body"><span class="stat-val">{{ formatNum(stats.total_leads) }}</span><span class="stat-lbl">Leads captured</span></div></div>
    </div>

    <!-- Flows Grid -->
    <div v-if="flows.data?.length" class="flows-grid">
      <div v-for="flow in flows.data" :key="flow.id" class="flow-card">
        <div class="fc-header">
          <h3 class="fc-name">{{ flow.name }}</h3>
          <div class="fc-status-wrap">
            <button class="fc-toggle" :class="'st-' + flow.status" @click="toggleStatus(flow.id)">
              <i :class="flow.status === 'active' ? 'pi pi-pause' : 'pi pi-play'" />
              {{ flow.status === 'active' ? 'Active' : flow.status === 'paused' ? 'Paused' : 'Draft' }}
            </button>
          </div>
        </div>
        <p v-if="flow.description" class="fc-desc">{{ flow.description }}</p>

        <div class="fc-meta">
          <span class="fm-item"><i class="pi pi-sitemap" /> {{ flow.nodes_count }} nodes</span>
          <span class="fm-item"><i class="pi pi-bolt" /> {{ triggerTypes[flow.trigger_type] }}</span>
        </div>

        <div class="fc-stats">
          <div class="fs-item"><span class="fs-val">{{ flow.conversations_count }}</span><span class="fs-lbl">Conversations</span></div>
          <div class="fs-item"><span class="fs-val">{{ flow.leads_captured }}</span><span class="fs-lbl">Leads</span></div>
          <div class="fs-item"><span class="fs-val fs-green">{{ flow.conversion_rate }}%</span><span class="fs-lbl">CVR</span></div>
        </div>

        <!-- Visual node preview -->
        <div class="fc-flow-preview">
          <div v-for="(n, i) in previewNodes(flow)" :key="i" class="fp-node" :style="{ '--nc': getNodeColor(n.type) }">
            <i :class="getNodeIcon(n.type)" />
            <span>{{ n.label }}</span>
          </div>
        </div>

        <div class="fc-actions">
          <button class="fc-btn" @click="editFlow(flow)"><i class="pi pi-pencil" /> Sửa</button>
          <button class="fc-btn fc-del" @click="deleteFlow(flow.id)"><i class="pi pi-trash" /></button>
          <span class="fc-date">{{ flow.updated_at }}</span>
        </div>
      </div>
    </div>
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-comments" /></div>
      <h3>Chưa có Chatbot Flow</h3>
      <p>Tạo flow đầu tiên để tự động qualify leads</p>
      <button class="btn-add" @click="showCreate = true"><i class="pi pi-plus" /> Tạo ngay</button>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box modal-lg">
        <div class="modal-head"><h3>{{ editingId ? 'Sửa' : 'Tạo' }} Chatbot Flow</h3><button class="modal-close" @click="showCreate = false"><i class="pi pi-times" /></button></div>
        <div class="fm-row">
          <div class="fm-group flex-2"><label>Tên Flow <span class="req">*</span></label><input v-model="form.name" type="text" class="fm-input" placeholder="VD: Lead Qualification Bot" /></div>
          <div class="fm-group flex-1"><label>Trigger</label>
            <select v-model="form.trigger_type" class="fm-input"><option v-for="(label, key) in triggerTypes" :key="key" :value="key">{{ label }}</option></select>
          </div>
        </div>
        <div class="fm-group"><label>Mô tả</label><textarea v-model="form.description" rows="2" class="fm-input" /></div>

        <!-- Node Builder -->
        <div class="node-builder">
          <div class="nb-header"><h4><i class="pi pi-sitemap" /> Flow Nodes</h4><button class="nb-add" @click="addNode"><i class="pi pi-plus" /> Thêm node</button></div>
          <div class="nb-list">
            <div v-for="(node, i) in form.nodes" :key="i" class="nb-node" :style="{ '--nc': getNodeColor(node.type) }">
              <div class="nn-header">
                <select v-model="node.type" class="nn-type">
                  <option v-for="(info, key) in nodeTypes" :key="key" :value="key">{{ info.label }}</option>
                </select>
                <button class="nn-del" @click="form.nodes.splice(i, 1)"><i class="pi pi-times" /></button>
              </div>
              <input v-model="node.data.message" type="text" class="fm-input nn-msg" :placeholder="node.type === 'collect_info' ? 'Nhập yêu cầu thu thập...' : 'Nhập nội dung tin nhắn...'" />
              <div v-if="node.type === 'options'" class="nn-options">
                <div v-for="(opt, j) in (node.data.options || [])" :key="j" class="nn-opt-row">
                  <input v-model="node.data.options[j]" type="text" class="fm-input nn-opt-input" />
                  <button class="nn-opt-del" @click="node.data.options.splice(j, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="nn-opt-add" @click="node.data.options = [...(node.data.options || []), '']"><i class="pi pi-plus" /> Option</button>
              </div>
              <div v-if="node.type === 'collect_info'" class="nn-fields">
                <label class="nn-field-label" v-for="f in ['name', 'email', 'phone', 'company']" :key="f">
                  <input type="checkbox" :checked="(node.data.fields || []).includes(f)" @change="toggleField(node, f)" /> {{ f }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <button class="btn-save" :disabled="!form.name || saving" @click="saveFlow">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> {{ editingId ? 'Lưu' : 'Tạo Flow' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { flows: Object, stats: Object, nodeTypes: Object, triggerTypes: Object },
  data() {
    return {
      showCreate: false, saving: false, editingId: null,
      form: {
        name: '', description: '', trigger_type: 'page_load', trigger_value: '',
        nodes: [
          { id: 'start', type: 'message', data: { message: 'Xin chào! 👋' }, position: { x: 250, y: 50 } },
          { id: 'q1', type: 'options', data: { message: 'Bạn cần hỗ trợ gì?', options: ['Tư vấn', 'Báo giá', 'Hỗ trợ'] }, position: { x: 250, y: 200 } },
          { id: 'collect', type: 'collect_info', data: { message: 'Vui lòng cho biết:', fields: ['name', 'email', 'phone'] }, position: { x: 250, y: 400 } },
          { id: 'end', type: 'end', data: { message: 'Cảm ơn! Chúng tôi sẽ liên hệ sớm 🙏' }, position: { x: 250, y: 600 } },
        ],
        edges: [], settings: {},
      },
    }
  },
  methods: {
    formatNum(n) { return n ? n.toLocaleString() : '0' },
    getNodeColor(type) { return this.nodeTypes[type]?.color || '#94a3b8' },
    getNodeIcon(type) { return this.nodeTypes[type]?.icon || 'pi pi-circle' },
    previewNodes(flow) {
      const types = { message: 'Tin nhắn', options: 'Lựa chọn', collect_info: 'Thu thập TT', question: 'Câu hỏi', condition: 'Điều kiện', action: 'Hành động', end: 'Kết thúc' }
      return Array.from(new Set((flow.nodes_count > 0 ? ['message', 'options', 'collect_info', 'end'] : []).slice(0, 4)))
        .map(t => ({ type: t, label: types[t] || t }))
    },
    addNode() {
      this.form.nodes.push({ id: 'n' + Date.now(), type: 'message', data: { message: '' }, position: { x: 250, y: (this.form.nodes.length + 1) * 150 } })
    },
    toggleField(node, field) {
      node.data.fields = node.data.fields || []
      const idx = node.data.fields.indexOf(field)
      if (idx > -1) node.data.fields.splice(idx, 1)
      else node.data.fields.push(field)
    },
    editFlow(flow) {
      this.editingId = flow.id
      this.form = { name: flow.name, description: flow.description || '', trigger_type: flow.trigger_type, trigger_value: '', nodes: [], edges: [], settings: {} }
      this.showCreate = true
    },
    saveFlow() {
      this.saving = true
      const method = this.editingId ? 'put' : 'post'
      const url = this.editingId ? `/chatbot-flows/${this.editingId}` : '/chatbot-flows'
      router[method](url, this.form, {
        onSuccess: () => { this.showCreate = false; this.editingId = null; this.resetForm() },
        onFinish: () => { this.saving = false },
      })
    },
    toggleStatus(id) { router.post(`/chatbot-flows/${id}/toggle`) },
    deleteFlow(id) { if (confirm('Xóa chatbot flow?')) router.delete(`/chatbot-flows/${id}`) },
    resetForm() {
      this.form = { name: '', description: '', trigger_type: 'page_load', trigger_value: '', nodes: [
        { id: 'start', type: 'message', data: { message: 'Xin chào! 👋' }, position: { x: 250, y: 50 } },
        { id: 'q1', type: 'options', data: { message: 'Bạn cần hỗ trợ gì?', options: ['Tư vấn', 'Báo giá', 'Hỗ trợ'] }, position: { x: 250, y: 200 } },
        { id: 'collect', type: 'collect_info', data: { message: 'Vui lòng cho biết:', fields: ['name', 'email', 'phone'] }, position: { x: 250, y: 400 } },
        { id: 'end', type: 'end', data: { message: 'Cảm ơn! Chúng tôi sẽ liên hệ sớm 🙏' }, position: { x: 250, y: 600 } },
      ], edges: [], settings: {} }
    },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem}.page-title{font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem}.page-subtitle{font-size:.78rem;color:#94a3b8;margin:.1rem 0 0}.btn-add{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;font-family:inherit}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;margin-bottom:1rem}.stat-card{display:flex;align-items:center;gap:.5rem;padding:.6rem .8rem;border-radius:12px;background:#fff;border:1.5px solid #f1f5f9}.stat-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0}.si-total{background:#ecfdf5;color:#10b981}.si-active{background:#d1fae5;color:#059669}.si-conv{background:#eef2ff;color:#6366f1}.si-leads{background:#fef3c7;color:#f59e0b}.stat-val{font-size:1.05rem;font-weight:800;color:#0f172a;display:block}.stat-green{color:#10b981}.stat-lbl{font-size:.6rem;color:#94a3b8}
.flows-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:.65rem}
.flow-card{background:#fff;border-radius:14px;border:1.5px solid #f1f5f9;padding:.85rem;transition:all .15s}.flow-card:hover{border-color:#10b981;box-shadow:0 4px 14px rgba(16,185,129,.08)}
.fc-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem}.fc-name{font-size:.85rem;font-weight:700;margin:0;color:#0f172a}.fc-toggle{padding:.2rem .45rem;border-radius:6px;border:none;font-size:.55rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.15rem;font-family:inherit}.st-active{background:#d1fae5;color:#059669}.st-paused{background:#fef3c7;color:#f59e0b}.st-draft{background:#f1f5f9;color:#94a3b8}.fc-desc{font-size:.65rem;color:#94a3b8;margin:0 0 .4rem;line-height:1.4}
.fc-meta{display:flex;gap:.5rem;margin-bottom:.5rem}.fm-item{font-size:.55rem;color:#64748b;display:flex;align-items:center;gap:.15rem}.fm-item i{font-size:.5rem}
.fc-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.25rem;margin-bottom:.5rem}.fs-item{text-align:center;padding:.35rem;background:#fafbfc;border-radius:6px}.fs-val{font-size:.82rem;font-weight:800;color:#0f172a;display:block}.fs-green{color:#10b981}.fs-lbl{font-size:.45rem;color:#94a3b8;text-transform:uppercase;font-weight:600}
.fc-flow-preview{display:flex;gap:.25rem;margin-bottom:.5rem;flex-wrap:wrap}.fp-node{padding:.2rem .4rem;border-radius:5px;background:color-mix(in srgb,var(--nc) 12%,white);font-size:.5rem;font-weight:600;display:flex;align-items:center;gap:.15rem;color:var(--nc)}.fp-node i{font-size:.45rem}
.fc-actions{display:flex;align-items:center;gap:.2rem}.fc-btn{display:flex;align-items:center;gap:.15rem;padding:.25rem .5rem;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:.55rem;font-weight:600;cursor:pointer;font-family:inherit}.fc-btn:hover{border-color:#10b981;color:#10b981}.fc-del:hover{border-color:#ef4444;color:#ef4444}.fc-date{font-size:.5rem;color:#94a3b8;margin-left:auto}
/* Modal */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999}.modal-box{background:#fff;border-radius:16px;padding:1.2rem;width:95%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.15);max-height:90vh;overflow-y:auto}.modal-lg{max-width:560px}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}.modal-head h3{font-size:.95rem;font-weight:800;margin:0}.modal-close{width:28px;height:28px;border:none;background:#f1f5f9;border-radius:7px;cursor:pointer;color:#94a3b8;display:flex;align-items:center;justify-content:center}
.fm-group{margin-bottom:.55rem}.fm-group label{display:block;font-size:.68rem;font-weight:600;color:#475569;margin-bottom:.15rem}.req{color:#ef4444}.fm-input{width:100%;padding:.42rem .65rem;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.78rem;color:#1e293b;outline:none;font-family:inherit;box-sizing:border-box}.fm-row{display:flex;gap:.5rem}.flex-1{flex:1}.flex-2{flex:2}
.node-builder{background:#fafbfc;border-radius:12px;border:1.5px solid #f1f5f9;padding:.75rem;margin-bottom:.5rem}.nb-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}.nb-header h4{font-size:.78rem;font-weight:700;margin:0;display:flex;align-items:center;gap:.25rem;color:#10b981}.nb-add{padding:.2rem .5rem;border-radius:6px;background:#d1fae5;border:none;color:#059669;font-size:.6rem;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:.15rem}
.nb-list{display:flex;flex-direction:column;gap:.35rem}.nb-node{background:#fff;border-radius:9px;border:1.5px solid #e2e8f0;border-left:3px solid var(--nc);padding:.5rem}.nn-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem}.nn-type{padding:.2rem .35rem;border:1px solid #e2e8f0;border-radius:5px;font-size:.6rem;font-weight:600;color:#475569;background:#fff;font-family:inherit}.nn-del{width:20px;height:20px;border:none;background:0 0;color:#cbd5e1;cursor:pointer;font-size:.5rem}.nn-msg{font-size:.7rem;padding:.3rem .5rem}
.nn-options{margin-top:.2rem;display:flex;flex-direction:column;gap:.15rem}.nn-opt-row{display:flex;gap:.15rem;align-items:center}.nn-opt-input{font-size:.65rem;padding:.2rem .4rem;flex:1}.nn-opt-del{width:18px;height:18px;border:none;background:0 0;color:#cbd5e1;cursor:pointer;font-size:.45rem;flex-shrink:0}.nn-opt-add{padding:.15rem .35rem;border:1px dashed #e2e8f0;border-radius:4px;background:0 0;color:#94a3b8;font-size:.5rem;cursor:pointer;font-family:inherit;align-self:flex-start;display:flex;align-items:center;gap:.1rem}
.nn-fields{margin-top:.25rem;display:flex;gap:.4rem;flex-wrap:wrap}.nn-field-label{font-size:.6rem;color:#475569;display:flex;align-items:center;gap:.15rem;cursor:pointer}.nn-field-label input{width:13px;height:13px}
.btn-save{width:100%;padding:.5rem;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.5rem}.btn-save:disabled{opacity:.5}
.empty-state{text-align:center;padding:3rem 1rem}.empty-icon{width:48px;height:48px;border-radius:14px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto .6rem}.empty-icon i{font-size:1.1rem;color:#10b981}.empty-state h3{font-size:.95rem;color:#1e293b;margin:0 0 .2rem}.empty-state p{font-size:.72rem;color:#94a3b8;margin:0 0 .75rem}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}}
</style>
