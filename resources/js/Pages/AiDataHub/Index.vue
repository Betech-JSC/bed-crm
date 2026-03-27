<template>
  <div>
    <Head title="AI Data Hub" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-database" /></div>
        <div>
          <h1 class="page-title">AI Data Hub</h1>
          <p class="page-subtitle">Trung tâm dữ liệu tập trung cho AI học và phát triển</p>
        </div>
      </div>
      <div class="header-actions">
        <Button label="Thêm Knowledge Base" icon="pi pi-plus" @click="openKbDialog()" />
        <Button label="AI Agents" icon="pi pi-sparkles" severity="help" outlined @click="$inertia.visit('/ai-agents')" />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="pi pi-database" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.knowledge_bases }}</span>
          <span class="stat-label">Knowledge Bases</span>
        </div>
      </div>
      <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="pi pi-file" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.documents }}</span>
          <span class="stat-label">Documents</span>
        </div>
      </div>
      <div class="stat-card stat-green">
        <div class="stat-icon"><i class="pi pi-sparkles" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.agents }}</span>
          <span class="stat-label">AI Agents</span>
        </div>
      </div>
      <div class="stat-card stat-amber">
        <div class="stat-icon"><i class="pi pi-comments" /></div>
        <div class="stat-body">
          <span class="stat-value">{{ stats.conversations }}</span>
          <span class="stat-label">Conversations</span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <button :class="['tab-btn', tab === 'knowledge' && 'active']" @click="tab = 'knowledge'">
        <i class="pi pi-database" /> Knowledge Bases
      </button>
      <button :class="['tab-btn', tab === 'training' && 'active']" @click="tab = 'training'">
        <i class="pi pi-list" /> Training Sets
      </button>
      <button :class="['tab-btn', tab === 'logs' && 'active']" @click="tab = 'logs'">
        <i class="pi pi-history" /> Sync Logs
      </button>
    </div>

    <!-- ═══ KNOWLEDGE BASES ═══ -->
    <div v-if="tab === 'knowledge'" class="tab-content">
      <div v-if="knowledgeBases.length" class="kb-grid">
        <div v-for="kb in knowledgeBases" :key="kb.id" class="kb-card">
          <div class="kb-header">
            <div class="kb-icon" :style="{ background: kb.type_meta.color + '18', color: kb.type_meta.color }">
              <i :class="kb.type_meta.icon" />
            </div>
            <div class="kb-info">
              <h3>{{ kb.name }}</h3>
              <span class="kb-type-badge" :style="{ background: kb.type_meta.color + '15', color: kb.type_meta.color }">
                {{ kb.type_meta.label }}
              </span>
            </div>
            <div class="kb-status">
              <span class="status-dot" :style="{ background: kb.status_meta?.color }" />
              <span>{{ kb.status_meta?.label }}</span>
            </div>
          </div>
          <p v-if="kb.description" class="kb-desc">{{ kb.description }}</p>
          <div class="kb-stats-row">
            <span><i class="pi pi-file" /> {{ kb.documents_count }} tài liệu</span>
            <span><i class="pi pi-calendar" /> {{ kb.created_at }}</span>
          </div>
          <div class="kb-actions">
            <Button label="Thêm tài liệu" icon="pi pi-plus" size="small" outlined @click="openDocDialog(kb)" />
            <Button label="Sync CRM" icon="pi pi-sync" size="small" severity="help" outlined @click="openSyncDialog(kb)" />
            <Button icon="pi pi-trash" size="small" severity="danger" text rounded @click="deleteKb(kb)" />
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-database" /></div>
        <h3>Chưa có Knowledge Base</h3>
        <p>Tạo kho tri thức đầu tiên để AI bắt đầu học.</p>
        <Button label="Tạo Knowledge Base" icon="pi pi-plus" @click="openKbDialog()" />
      </div>
    </div>

    <!-- ═══ TRAINING SETS ═══ -->
    <div v-if="tab === 'training'" class="tab-content">
      <div class="section-header">
        <h2>Training Sets</h2>
        <Button label="Tạo Training Set" icon="pi pi-plus" size="small" @click="openTrainingDialog()" />
      </div>
      <div v-if="trainingSets.length" class="training-list">
        <div v-for="ts in trainingSets" :key="ts.id" class="training-card">
          <div class="training-left">
            <div class="training-icon"><i :class="ts.format_meta?.icon || 'pi pi-list'" /></div>
            <div>
              <h4>{{ ts.name }}</h4>
              <p>{{ ts.description || 'Không có mô tả' }}</p>
            </div>
          </div>
          <div class="training-meta">
            <span class="meta-badge">{{ ts.agent_type }}</span>
            <span class="meta-badge">{{ ts.item_count }} items</span>
            <span v-if="ts.quality_score" class="meta-badge meta-score">⭐ {{ ts.quality_score }}</span>
            <span :class="['status-badge', 'status-' + ts.status]">{{ ts.status }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty-state small">
        <p>Chưa có training set. Tạo bộ dữ liệu để huấn luyện AI Agent.</p>
      </div>
    </div>

    <!-- ═══ SYNC LOGS ═══ -->
    <div v-if="tab === 'logs'" class="tab-content">
      <div v-if="syncLogs.length" class="logs-list">
        <div v-for="log in syncLogs" :key="log.id" class="log-row">
          <div class="log-status-icon" :class="'log-' + log.status">
            <i :class="log.status === 'completed' ? 'pi pi-check-circle' : log.status === 'failed' ? 'pi pi-times-circle' : 'pi pi-spin pi-spinner'" />
          </div>
          <div class="log-info">
            <strong>{{ log.action }}</strong> {{ log.source_ref }}
            <span class="log-kb">→ {{ log.kb_name }}</span>
          </div>
          <div class="log-meta">
            <span>{{ log.records_processed }} records</span>
            <span v-if="log.records_failed" class="text-red">{{ log.records_failed }} failed</span>
            <span>{{ log.duration }}</span>
            <span class="log-time">{{ log.created_at }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty-state small">
        <p>Chưa có hoạt động đồng bộ nào.</p>
      </div>
    </div>

    <!-- ═══ CREATE KB DIALOG ═══ -->
    <div v-if="kbDialog" class="dialog-overlay" @click.self="kbDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon bg-purple"><i class="pi pi-database" /></div><h3>Tạo Knowledge Base</h3></div>
          <button class="dialog-close" @click="kbDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitKb" class="dialog-body">
          <div class="form-group"><label>Tên <span class="req">*</span></label><InputText v-model="kbForm.name" class="w-full" placeholder="VD: Sales Knowledge Base" /></div>
          <div class="form-group"><label>Loại</label>
            <div class="type-selector">
              <button v-for="(meta, key) in kbTypes" :key="key" type="button" :class="['type-btn', kbForm.type === key && 'selected']" @click="kbForm.type = key">
                <i :class="meta.icon" :style="{ color: meta.color }" />
                <span>{{ meta.label }}</span>
              </button>
            </div>
          </div>
          <div class="form-group"><label>Mô tả</label><Textarea v-model="kbForm.description" rows="3" class="w-full" placeholder="Mô tả nội dung và mục đích..." /></div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="kbDialog = false" type="button" />
            <Button label="Tạo" icon="pi pi-check" type="submit" :loading="kbForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ═══ ADD DOCUMENT DIALOG ═══ -->
    <div v-if="docDialog" class="dialog-overlay" @click.self="docDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon bg-blue"><i class="pi pi-file-plus" /></div><h3>Thêm tài liệu vào {{ selectedKb?.name }}</h3></div>
          <button class="dialog-close" @click="docDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitDoc" class="dialog-body">
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="docForm.title" class="w-full" placeholder="VD: Sales Playbook 2026" /></div>
          <div class="form-group"><label>Loại nguồn</label>
            <Select v-model="docForm.source_type" :options="sourceTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="form-group"><label>Nội dung</label>
            <Textarea v-model="docForm.content" rows="8" class="w-full" placeholder="Dán nội dung tài liệu vào đây..." />
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="docDialog = false" type="button" />
            <Button label="Thêm" icon="pi pi-check" type="submit" :loading="docForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ═══ SYNC CRM DIALOG ═══ -->
    <div v-if="syncDialog" class="dialog-overlay" @click.self="syncDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon bg-green"><i class="pi pi-sync" /></div><h3>Đồng bộ CRM → {{ selectedKb?.name }}</h3></div>
          <button class="dialog-close" @click="syncDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="dialog-body">
          <p class="sync-desc">Chọn nguồn dữ liệu CRM để đồng bộ vào Knowledge Base:</p>
          <div class="sync-sources">
            <button v-for="(meta, key) in syncSources" :key="key" class="sync-source-btn" @click="runSync(key)">
              <i :class="meta.icon" />
              <span>{{ meta.label }}</span>
              <small>{{ meta.table }}</small>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ CREATE TRAINING SET DIALOG ═══ -->
    <div v-if="trainingDialog" class="dialog-overlay" @click.self="trainingDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon bg-amber"><i class="pi pi-list" /></div><h3>Tạo Training Set</h3></div>
          <button class="dialog-close" @click="trainingDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitTraining" class="dialog-body">
          <div class="form-group"><label>Tên <span class="req">*</span></label><InputText v-model="trainingForm.name" class="w-full" placeholder="VD: Sales Q&A Training Data" /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Agent Type</label>
              <Select v-model="trainingForm.agent_type" :options="agentTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="form-group flex-1"><label>Format</label>
              <Select v-model="trainingForm.format" :options="formatOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
          <div class="form-group"><label>Mô tả</label><Textarea v-model="trainingForm.description" rows="2" class="w-full" /></div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="trainingDialog = false" type="button" />
            <Button label="Tạo" icon="pi pi-check" type="submit" :loading="trainingForm.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

export default {
  components: { Head, Button, InputText, Textarea, Select },
  layout: Layout,
  props: {
    knowledgeBases: Array,
    trainingSets: Array,
    syncLogs: Array,
    stats: Object,
    kbTypes: Object,
    syncSources: Object,
    trainingFormats: Object,
  },
  data() {
    return {
      tab: 'knowledge',
      kbDialog: false,
      docDialog: false,
      syncDialog: false,
      trainingDialog: false,
      selectedKb: null,
      kbForm: this.$inertia.form({ name: '', description: '', type: 'general' }),
      docForm: this.$inertia.form({ knowledge_base_id: null, title: '', source_type: 'text', source_ref: '', content: '' }),
      trainingForm: this.$inertia.form({ name: '', agent_type: 'sales', description: '', format: 'qa_pairs', data: [] }),
      sourceTypeOptions: [
        { label: 'Text', value: 'text' },
        { label: 'Upload', value: 'upload' },
        { label: 'URL', value: 'url' },
        { label: 'API', value: 'api' },
      ],
      agentTypeOptions: [
        { label: 'Sales', value: 'sales' },
        { label: 'Support', value: 'support' },
        { label: 'Content', value: 'content' },
        { label: 'Analytics', value: 'analytics' },
        { label: 'HR', value: 'hr' },
        { label: 'Custom', value: 'custom' },
      ],
    }
  },
  computed: {
    formatOptions() {
      return Object.entries(this.trainingFormats).map(([k, v]) => ({ label: v.label, value: k }))
    },
  },
  methods: {
    openKbDialog() { this.kbForm = this.$inertia.form({ name: '', description: '', type: 'general' }); this.kbDialog = true },
    openDocDialog(kb) { this.selectedKb = kb; this.docForm = this.$inertia.form({ knowledge_base_id: kb.id, title: '', source_type: 'text', source_ref: '', content: '' }); this.docDialog = true },
    openSyncDialog(kb) { this.selectedKb = kb; this.syncDialog = true },
    openTrainingDialog() { this.trainingForm = this.$inertia.form({ name: '', agent_type: 'sales', description: '', format: 'qa_pairs', data: [] }); this.trainingDialog = true },
    submitKb() { this.kbForm.post('/ai-data-hub/knowledge-bases', { preserveScroll: true, onSuccess: () => { this.kbDialog = false } }) },
    submitDoc() { this.docForm.post('/ai-data-hub/documents', { preserveScroll: true, onSuccess: () => { this.docDialog = false } }) },
    submitTraining() { this.trainingForm.post('/ai-data-hub/training-sets', { preserveScroll: true, onSuccess: () => { this.trainingDialog = false } }) },
    runSync(source) {
      this.$inertia.post('/ai-data-hub/sync', { knowledge_base_id: this.selectedKb.id, source }, { preserveScroll: true, onSuccess: () => { this.syncDialog = false } })
    },
    deleteKb(kb) { if (confirm(`Xóa "${kb.name}"?`)) this.$inertia.delete(`/ai-data-hub/knowledge-bases/${kb.id}`, { preserveScroll: true }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#8b5cf6,#6d28d9); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(139,92,246,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; gap:.5rem }

/* Stats */
.stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:.75rem; margin-bottom:1.5rem }
.stat-card { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-radius:14px; background:white; border:1.5px solid #f1f5f9; transition:all .25s }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 18px rgba(0,0,0,.06) }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem }
.stat-purple .stat-icon { background:#ede9fe; color:#7c3aed }
.stat-blue .stat-icon { background:#dbeafe; color:#2563eb }
.stat-green .stat-icon { background:#dcfce7; color:#16a34a }
.stat-amber .stat-icon { background:#fef3c7; color:#d97706 }
.stat-value { display:block; font-size:1.5rem; font-weight:800; color:#0f172a }
.stat-label { font-size:.7rem; color:#94a3b8 }

/* Tabs */
.tab-bar { display:flex; gap:.25rem; margin-bottom:1rem; border-bottom:2px solid #f1f5f9; padding-bottom:0 }
.tab-btn { display:flex; align-items:center; gap:.35rem; padding:.6rem 1rem; font-size:.78rem; font-weight:600; color:#64748b; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:all .2s; font-family:inherit }
.tab-btn:hover { color:#0f172a }
.tab-btn.active { color:#7c3aed; border-bottom-color:#7c3aed }
.tab-btn i { font-size:.72rem }

/* KB Grid */
.kb-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(380px, 1fr)); gap:.75rem }
.kb-card { background:white; border:1.5px solid #f1f5f9; border-radius:16px; padding:1.25rem; transition:all .25s }
.kb-card:hover { border-color:#8b5cf6; box-shadow:0 4px 18px rgba(139,92,246,.06) }
.kb-header { display:flex; align-items:center; gap:.65rem; margin-bottom:.5rem }
.kb-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.92rem; flex-shrink:0 }
.kb-info { flex:1; min-width:0 }
.kb-info h3 { font-size:.88rem; font-weight:700; color:#0f172a; margin:0 }
.kb-type-badge { font-size:.52rem; font-weight:700; padding:.1rem .3rem; border-radius:4px; text-transform:uppercase }
.kb-status { display:flex; align-items:center; gap:.25rem; font-size:.62rem; color:#64748b }
.status-dot { width:6px; height:6px; border-radius:50% }
.kb-desc { font-size:.72rem; color:#64748b; margin:.25rem 0 .5rem; line-height:1.5 }
.kb-stats-row { display:flex; gap:.75rem; margin-bottom:.65rem }
.kb-stats-row span { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.2rem }
.kb-stats-row i { font-size:.52rem }
.kb-actions { display:flex; gap:.35rem; flex-wrap:wrap }

/* Training */
.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem }
.section-header h2 { font-size:1rem; font-weight:700; color:#0f172a; margin:0 }
.training-list { display:flex; flex-direction:column; gap:.5rem }
.training-card { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1rem; background:white; border:1.5px solid #f1f5f9; border-radius:12px; transition:all .2s }
.training-card:hover { border-color:#f59e0b20; box-shadow:0 2px 10px rgba(0,0,0,.03) }
.training-left { display:flex; align-items:center; gap:.65rem }
.training-icon { width:36px; height:36px; border-radius:10px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:.82rem }
.training-left h4 { font-size:.82rem; font-weight:700; color:#0f172a; margin:0 }
.training-left p { font-size:.65rem; color:#94a3b8; margin:.1rem 0 0 }
.training-meta { display:flex; gap:.35rem; align-items:center }
.meta-badge { font-size:.55rem; font-weight:600; padding:.15rem .4rem; border-radius:5px; background:#f1f5f9; color:#475569 }
.meta-score { background:#fef3c7; color:#92400e }
.status-badge { font-size:.52rem; font-weight:700; padding:.12rem .35rem; border-radius:4px; text-transform:uppercase }
.status-draft { background:#f1f5f9; color:#64748b }
.status-validated { background:#dbeafe; color:#2563eb }
.status-active { background:#dcfce7; color:#16a34a }

/* Sync Logs */
.logs-list { display:flex; flex-direction:column; gap:.35rem }
.log-row { display:flex; align-items:center; gap:.65rem; padding:.55rem .75rem; background:white; border:1px solid #f1f5f9; border-radius:10px }
.log-status-icon { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.72rem }
.log-completed { background:#dcfce7; color:#16a34a }
.log-failed { background:#fee2e2; color:#dc2626 }
.log-running { background:#dbeafe; color:#2563eb }
.log-info { flex:1; font-size:.72rem; color:#334155 }
.log-info strong { text-transform:capitalize }
.log-kb { color:#8b5cf6; font-weight:600 }
.log-meta { display:flex; gap:.65rem; font-size:.6rem; color:#94a3b8 }
.log-time { color:#cbd5e1 }
.text-red { color:#ef4444 }

/* Sync Sources */
.sync-desc { font-size:.82rem; color:#475569; margin:0 0 .75rem }
.sync-sources { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:.5rem }
.sync-source-btn { display:flex; flex-direction:column; align-items:center; gap:.3rem; padding:.85rem .5rem; background:white; border:1.5px solid #e2e8f0; border-radius:12px; cursor:pointer; transition:all .2s; font-family:inherit }
.sync-source-btn:hover { border-color:#8b5cf6; background:#faf5ff; transform:translateY(-1px) }
.sync-source-btn i { font-size:1.1rem; color:#7c3aed }
.sync-source-btn span { font-size:.72rem; font-weight:600; color:#1e293b }
.sync-source-btn small { font-size:.55rem; color:#94a3b8 }

/* Type Selector */
.type-selector { display:flex; gap:.4rem; flex-wrap:wrap }
.type-btn { display:flex; align-items:center; gap:.3rem; padding:.45rem .7rem; border:1.5px solid #e2e8f0; border-radius:10px; background:white; cursor:pointer; font-size:.72rem; font-weight:600; color:#475569; transition:all .2s; font-family:inherit }
.type-btn:hover { border-color:#8b5cf640 }
.type-btn.selected { border-color:#8b5cf6; background:#faf5ff; color:#7c3aed }
.type-btn i { font-size:.82rem }

/* Dialogs */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:600px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.bg-purple { background:linear-gradient(135deg,#8b5cf6,#6d28d9) }
.bg-blue { background:linear-gradient(135deg,#3b82f6,#2563eb) }
.bg-green { background:linear-gradient(135deg,#10b981,#059669) }
.bg-amber { background:linear-gradient(135deg,#f59e0b,#d97706) }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.form-group { margin-bottom:.85rem }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.form-row { display:flex; gap:.75rem }
.flex-1 { flex:1 }
.w-full { width:100% }
.req { color:#ef4444 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:white; border-radius:0 0 18px 18px }

/* Empty */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-state.small { padding:1.5rem; border-style:solid; border-width:1px }
.empty-icon { width:56px; height:56px; border-radius:14px; background:#ede9fe; display:flex; align-items:center; justify-content:center; margin:0 auto .75rem; font-size:1.5rem; color:#7c3aed }
.empty-state h3 { font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 .3rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 0 .75rem }

@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .stats-grid { grid-template-columns:repeat(2, 1fr) }
  .kb-grid { grid-template-columns:1fr }
  .tab-bar { overflow-x:auto }
  .form-row { flex-direction:column }
  .sync-sources { grid-template-columns:repeat(2, 1fr) }
}
</style>
