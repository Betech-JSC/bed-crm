<template>
  <div>
    <Head :title="isVi ? 'Quy trình bán hàng' : 'Sales Pipeline'" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-chart-line" /></div>
        <div>
          <h1 class="page-title">{{ isVi ? 'Quy trình bán hàng' : 'Sales Pipeline' }}</h1>
          <div class="header-badges">
            <span class="h-badge open"><i class="pi pi-circle-fill" /> {{ stats.total_open }} {{ isVi ? 'đang mở' : 'open' }}</span>
            <span class="h-badge won"><i class="pi pi-check" /> {{ stats.closed_won }} {{ isVi ? 'thành công' : 'won' }}</span>
            <span class="h-badge lost"><i class="pi pi-times" /> {{ stats.closed_lost }} {{ isVi ? 'thất bại' : 'lost' }}</span>
            <span v-if="stats.total_value" class="h-badge val"><i class="pi pi-wallet" /> {{ formatShort(stats.total_value) }}</span>
          </div>
        </div>
      </div>
      <div class="header-right">
        <button class="btn-settings" @click="showChannelManager = true"><i class="pi pi-sliders-h" /> {{ isVi ? 'Kênh bán' : 'Channels' }}</button>
        <Link :href="`/sales-pipeline/create?channel_id=${activeChannelId}`"><button class="btn-primary"><i class="pi pi-plus" /> {{ isVi ? 'Tạo mới' : 'Create' }}</button></Link>
      </div>
    </div>

    <!-- Channel Tabs -->
    <div class="channel-tabs">
      <button
        v-for="ch in channels" :key="ch.id"
        :class="['ch-tab', { active: ch.id === activeChannelId }]"
        @click="switchChannel(ch.id)"
      >
        <i :class="ch.icon || 'pi pi-tag'" :style="{ color: ch.color }" />
        <span class="ch-name">{{ ch.name }}</span>
        <span class="ch-count">{{ getChannelOCount(ch.id) }}</span>
      </button>
    </div>

    <!-- Active channel description -->
    <div v-if="activeChannelObj?.description" class="ch-desc">
      <i class="pi pi-info-circle" /> {{ activeChannelObj.description }}
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board" :key="activeChannelId">
      <div v-for="stage in computedStages" :key="stage.key" class="kanban-col">
        <div class="col-header">
          <div class="col-left">
            <span class="col-dot" :style="{ background: stage.color || '#94a3b8' }" />
            <span class="col-title">{{ stage.label }}</span>
          </div>
          <div class="col-right">
            <span class="col-total">{{ formatShort(stageValue(stage.key)) }}</span>
            <span class="col-count">{{ getStageCount(stage.key) }}</span>
          </div>
        </div>

        <draggable
          v-model="localPipelinesByStage[stage.key]"
          :group="{ name: 'pipelines', pull: true, put: true }"
          :animation="200"
          ghost-class="ghost-card"
          chosen-class="chosen-card"
          drag-class="dragging-card"
          :empty-insert-threshold="30"
          item-key="id"
          class="col-body"
          @change="onStageDrop(stage.key, $event)"
        >
          <template #item="{ element: pipeline }">
            <div :key="pipeline.id" class="pipe-card" :style="{ borderTopColor: stage.color || '#e2e8f0' }">
              <div class="pipe-top">
                <Link :href="`/sales-pipeline/${pipeline.id}/edit`" class="pipe-link">
                  <h3 class="pipe-company">{{ pipeline.company_name }}</h3>
                  <p class="pipe-contact">{{ pipeline.contact_name }}</p>
                </Link>
                <span class="priority-badge" :class="`pr-${pipeline.priority}`">{{ priorityLabel(pipeline.priority) }}</span>
              </div>

              <div v-if="pipeline.social_channel" class="pipe-social">
                <i :class="getSocialIcon(pipeline.social_channel)" />
                <span>{{ pipeline.social_account || pipeline.social_channel }}</span>
              </div>

              <div v-if="pipeline.quote_amount" class="pipe-value">
                <i class="pi pi-wallet" /> {{ formatCurrency(pipeline.quote_amount) }}
              </div>

              <div v-if="pipeline.win_probability" class="pipe-prob">
                <div class="prob-track"><div class="prob-fill" :style="{ width: pipeline.win_probability + '%' }" /></div>
                <span class="prob-label">{{ pipeline.win_probability }}%</span>
              </div>

              <div v-if="pipeline.audit_score > 0" class="pipe-audit">
                <div class="audit-track"><div class="audit-fill" :style="{ width: pipeline.audit_score + '%' }" :class="getAuditClass(pipeline.audit_score)" /></div>
                <span class="audit-label" :class="getAuditClass(pipeline.audit_score)">{{ pipeline.audit_score }}%</span>
              </div>

              <div class="pipe-bottom">
                <div v-if="pipeline.assigned_user" class="pipe-user">
                  <div class="pipe-avatar">{{ initials(pipeline.assigned_user.name) }}</div>
                  <span>{{ pipeline.assigned_user.name }}</span>
                </div>
                <div class="pipe-meta">
                  <span v-if="pipeline.stage_changed_at" class="pipe-time">{{ pipeline.stage_changed_at }}</span>
                  <div class="drag-handle"><i class="pi pi-bars" /></div>
                </div>
              </div>
            </div>
          </template>
        </draggable>

        <div v-if="getStageCount(stage.key) === 0" class="col-empty">
          <i class="pi pi-inbox" /><span>{{ isVi ? 'Chưa có' : 'Empty' }}</span>
        </div>
      </div>
    </div>

    <!-- Channel Manager Dialog -->
    <Dialog v-model:visible="showChannelManager" :header="isVi ? 'Quản lý kênh bán hàng' : 'Manage Sales Channels'" :style="{ width: '720px' }" modal :dismissableMask="true" @keydown.esc="showChannelManager = false">
      <div class="cm-body">
        <div class="cm-list">
          <div v-for="ch in channels" :key="ch.id" class="cm-item" :class="{ editing: editingChannel?.id === ch.id }">
            <div class="cm-item-main">
              <div class="cm-item-icon" :style="{ background: ch.color + '18', color: ch.color }"><i :class="ch.icon || 'pi pi-tag'" /></div>
              <div class="cm-item-info">
                <strong>{{ ch.name }}</strong>
                <span>{{ ch.stages?.length || 0 }} {{ isVi ? 'giai đoạn' : 'stages' }}</span>
              </div>
              <div class="cm-item-actions">
                <button class="cm-btn" @click="editChannelForm(ch)"><i class="pi pi-pencil" /></button>
                <button class="cm-btn danger" @click="deleteChannel(ch.id)"><i class="pi pi-trash" /></button>
              </div>
            </div>
            <!-- Stage preview -->
            <div class="cm-stages-preview">
              <span v-for="s in ch.stages" :key="s.key" class="stage-chip" :style="{ background: s.color + '18', color: s.color }">{{ s.label }}</span>
            </div>
          </div>
        </div>

        <!-- Add / Edit channel form -->
        <div class="cm-form">
          <h4>{{ editingChannel ? (isVi ? 'Chỉnh sửa kênh' : 'Edit Channel') : (isVi ? 'Thêm kênh mới' : 'Add New Channel') }}</h4>
          <div class="cm-form-row">
            <InputText v-model="channelForm.name" :placeholder="isVi ? 'Tên kênh (VD: TikTok Shop)' : 'Channel name'" class="w-full" />
          </div>
          <div class="cm-form-row two-col">
            <InputText v-model="channelForm.icon" placeholder="pi pi-..." />
            <InputText v-model="channelForm.color" placeholder="#color" />
          </div>
          <div class="cm-form-row">
            <InputText v-model="channelForm.description" :placeholder="isVi ? 'Mô tả (tuỳ chọn)' : 'Description (optional)'" class="w-full" />
          </div>
          <div class="cm-stages-editor">
            <label>{{ isVi ? 'Giai đoạn pipeline' : 'Pipeline Stages' }}</label>
            <div v-for="(s, idx) in channelForm.stages" :key="idx" class="stage-row">
              <InputText v-model="s.key" placeholder="key" style="width: 100px" />
              <InputText v-model="s.label" :placeholder="isVi ? 'Tên giai đoạn' : 'Stage name'" style="flex: 1" />
              <input type="color" v-model="s.color" style="width: 32px; height: 32px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer" />
              <button class="cm-btn danger" @click="channelForm.stages.splice(idx, 1)"><i class="pi pi-times" /></button>
            </div>
            <button class="cm-btn add-stage" @click="channelForm.stages.push({ key: '', label: '', color: '#6366f1' })">
              <i class="pi pi-plus" /> {{ isVi ? 'Thêm giai đoạn' : 'Add Stage' }}
            </button>
          </div>
          <div class="cm-form-actions">
            <button v-if="editingChannel" class="cm-btn" @click="cancelEdit"><i class="pi pi-times" /> {{ isVi ? 'Huỷ' : 'Cancel' }}</button>
            <button class="cm-btn primary" @click="saveChannel" :disabled="!channelForm.name || channelForm.stages.length < 2">
              <i class="pi pi-check" /> {{ editingChannel ? (isVi ? 'Cập nhật' : 'Update') : (isVi ? 'Tạo kênh' : 'Create') }}
            </button>
          </div>
        </div>

        <!-- Presets -->
        <div v-if="!editingChannel" class="cm-presets">
          <label>{{ isVi ? 'Kênh có sẵn' : 'Preset Channels' }}</label>
          <div class="preset-grid">
            <button v-for="pr in availablePresets" :key="pr.slug" class="preset-card" @click="applyPreset(pr)">
              <i :class="pr.icon" :style="{ color: pr.color }" />
              <span>{{ pr.name }}</span>
            </button>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import draggable from 'vuedraggable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import axios from 'axios'
import { useTranslation } from '@/composables/useTranslation'

const PRESETS = [
  { name: 'Zalo', slug: 'zalo', icon: 'pi pi-comments', color: '#0068ff', stages: [
    { key: 'new_contact', label: 'Liên hệ mới', color: '#6366f1' },
    { key: 'chatting', label: 'Đang chat', color: '#3b82f6' },
    { key: 'need_identified', label: 'Xác định nhu cầu', color: '#0ea5e9' },
    { key: 'proposal_sent', label: 'Gửi báo giá', color: '#f59e0b' },
    { key: 'negotiation', label: 'Đàm phán', color: '#ef6820' },
    { key: 'closed_won', label: 'Chốt thành công', color: '#16a34a' },
    { key: 'closed_lost', label: 'Thất bại', color: '#ef4444' },
  ]},
  { name: 'Facebook', slug: 'facebook', icon: 'pi pi-facebook', color: '#1877f2', stages: [
    { key: 'inbox', label: 'Inbox', color: '#6366f1' },
    { key: 'qualify', label: 'Đánh giá', color: '#3b82f6' },
    { key: 'demo_call', label: 'Demo / Gọi điện', color: '#0ea5e9' },
    { key: 'proposal', label: 'Đề xuất', color: '#f59e0b' },
    { key: 'closed_won', label: 'Chốt thành công', color: '#16a34a' },
    { key: 'closed_lost', label: 'Thất bại', color: '#ef4444' },
  ]},
  { name: 'TikTok Shop', slug: 'tiktok-shop', icon: 'pi pi-video', color: '#010101', stages: [
    { key: 'viewer', label: 'Xem video / Live', color: '#6366f1' },
    { key: 'comment', label: 'Bình luận', color: '#3b82f6' },
    { key: 'inbox', label: 'Nhắn tin', color: '#0ea5e9' },
    { key: 'order', label: 'Đặt hàng', color: '#f59e0b' },
    { key: 'completed', label: 'Hoàn thành', color: '#16a34a' },
    { key: 'cancelled', label: 'Huỷ', color: '#ef4444' },
  ]},
  { name: 'Telesales', slug: 'telesales', icon: 'pi pi-phone', color: '#8b5cf6', stages: [
    { key: 'cold_call', label: 'Gọi lạnh', color: '#94a3b8' },
    { key: 'interested', label: 'Quan tâm', color: '#3b82f6' },
    { key: 'meeting', label: 'Hẹn gặp', color: '#0ea5e9' },
    { key: 'proposal', label: 'Báo giá', color: '#f59e0b' },
    { key: 'closed_won', label: 'Chốt thành công', color: '#16a34a' },
    { key: 'closed_lost', label: 'Thất bại', color: '#ef4444' },
  ]},
  { name: 'Referral', slug: 'referral', icon: 'pi pi-users', color: '#f59e0b', stages: [
    { key: 'referred', label: 'Được giới thiệu', color: '#f59e0b' },
    { key: 'contact', label: 'Liên hệ', color: '#3b82f6' },
    { key: 'meeting', label: 'Gặp mặt', color: '#0ea5e9' },
    { key: 'proposal', label: 'Đề xuất', color: '#ef6820' },
    { key: 'closed_won', label: 'Chốt thành công', color: '#16a34a' },
    { key: 'closed_lost', label: 'Thất bại', color: '#ef4444' },
  ]},
]

export default {
  components: { Head, Link, draggable, Dialog, InputText },
  layout: Layout,
  props: {
    channels: Array,
    activeChannelId: Number,
    pipelinesByStage: Object,
    stages: Array,
    allStages: Array,
    priorities: Object,
    salesUsers: Array,
    stats: Object,
    channelStats: Array,
  },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      localPipelinesByStage: {},
      showChannelManager: false,
      editingChannel: null,
      channelForm: this.emptyChannelForm(),
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    activeChannelObj() { return this.channels?.find(c => c.id === this.activeChannelId) },
    computedStages() { return this.stages || [] },
    availablePresets() {
      const existingSlugs = (this.channels || []).map(c => c.slug)
      return PRESETS.filter(p => !existingSlugs.includes(p.slug))
    },
  },
  watch: {
    pipelinesByStage: {
      immediate: true, deep: true,
      handler(v) {
        this.localPipelinesByStage = {}
        ;(this.stages || []).forEach(s => {
          this.localPipelinesByStage[s.key] = [...(v[s.key] || [])]
        })
      },
    },
  },
  methods: {
    emptyChannelForm() {
      return {
        name: '', icon: 'pi pi-tag', color: '#6366f1', description: '',
        stages: [
          { key: 'new', label: 'Mới', color: '#6366f1' },
          { key: 'closed_won', label: 'Chốt thành công', color: '#16a34a' },
          { key: 'closed_lost', label: 'Thất bại', color: '#ef4444' },
        ],
      }
    },
    getStageCount(s) { return this.localPipelinesByStage[s]?.length || 0 },
    stageValue(s) { return (this.localPipelinesByStage[s] || []).reduce((sum, p) => sum + (p.quote_amount || 0), 0) },
    getChannelOCount(chId) {
      const cs = (this.channelStats || []).find(c => c.id === chId)
      return cs?.open_count ?? 0
    },
    initials(n) { if (!n) return '?'; return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    priorityLabel(p) { return { hot: '🔥 Hot', warm: '☀️ Warm', cold: '❄️ Cold' }[p] || p },
    getSocialIcon(ch) { return { zalo: 'pi pi-comment', facebook: 'pi pi-facebook' }[ch] || 'pi pi-globe' },
    getAuditClass(s) { return s >= 70 ? 'a-good' : s >= 40 ? 'a-warn' : 'a-low' },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    formatShort(v) { return v >= 1e9 ? (v/1e9).toFixed(1)+'B' : v >= 1e6 ? (v/1e6).toFixed(0)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : (v || 0).toString() },

    switchChannel(chId) {
      router.get('/sales-pipeline', { channel_id: chId }, { preserveState: false })
    },

    onStageDrop(target, event) {
      if (!event.added) return
      const p = event.added.element, old = p.stage
      if (old === target) return
      p.stage = target
      router.patch(`/sales-pipeline/${p.id}/stage`, { stage: target }, {
        preserveScroll: true, preserveState: true,
        onError: () => { p.stage = old; this.syncLocalState() },
      })
    },
    syncLocalState() {
      this.$nextTick(() => { (this.stages || []).forEach(s => { this.localPipelinesByStage[s.key] = [...(this.pipelinesByStage[s.key] || [])] }) })
    },

    // ── Channel Management ──
    editChannelForm(ch) {
      this.editingChannel = ch
      this.channelForm = {
        name: ch.name,
        icon: ch.icon || 'pi pi-tag',
        color: ch.color || '#6366f1',
        description: ch.description || '',
        stages: JSON.parse(JSON.stringify(ch.stages || [])),
      }
    },
    cancelEdit() {
      this.editingChannel = null
      this.channelForm = this.emptyChannelForm()
    },
    applyPreset(pr) {
      this.editingChannel = null
      this.channelForm = {
        name: pr.name,
        icon: pr.icon,
        color: pr.color,
        description: '',
        stages: JSON.parse(JSON.stringify(pr.stages)),
      }
    },
    async saveChannel() {
      try {
        if (this.editingChannel) {
          await axios.put(`/sales-channels/${this.editingChannel.id}`, this.channelForm)
        } else {
          await axios.post('/sales-channels', this.channelForm)
        }
        this.editingChannel = null
        this.channelForm = this.emptyChannelForm()
        router.reload()
      } catch (e) { console.error(e) }
    },
    async deleteChannel(id) {
      if (!confirm(this.isVi ? 'Xóa kênh bán này? Pipeline sẽ không bị xóa.' : 'Delete this channel? Pipelines will not be deleted.')) return
      try {
        await axios.delete(`/sales-channels/${id}`)
        router.reload()
      } catch (e) { console.error(e) }
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-right { display: flex; align-items: center; gap: 0.5rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }
.header-badges { display: flex; gap: 0.35rem; margin-top: 0.15rem; flex-wrap: wrap; }
.h-badge { font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.5rem; border-radius: 20px; display: flex; align-items: center; gap: 0.2rem; }
.h-badge i { font-size: 0.45rem; }
.h-badge.open { background: #eff6ff; color: #3b82f6; }
.h-badge.won { background: #ecfdf5; color: #10b981; }
.h-badge.lost { background: #fef2f2; color: #ef4444; }
.h-badge.val { background: #f0fdf4; color: #059669; }

.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.btn-settings { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; border-radius: 10px; background: white; color: #475569; font-size: 0.82rem; font-weight: 600; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.btn-settings:hover { border-color: #8b5cf6; color: #8b5cf6; }

/* Channel Tabs */
.channel-tabs { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; overflow-x: auto; padding-bottom: 0.3rem; }
.ch-tab { display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.85rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; font-size: 0.78rem; font-weight: 600; color: #475569; transition: all 0.2s; flex-shrink: 0; }
.ch-tab:hover { border-color: #cbd5e1; background: #f8fafc; }
.ch-tab.active { border-color: #8b5cf6; background: #faf5ff; color: #7c3aed; box-shadow: 0 2px 8px rgba(139,92,246,0.15); }
.ch-tab i { font-size: 0.85rem; }
.ch-name { white-space: nowrap; }
.ch-count { font-size: 0.6rem; background: #f1f5f9; padding: 0.08rem 0.35rem; border-radius: 8px; color: #64748b; }
.ch-tab.active .ch-count { background: #ede9fe; color: #7c3aed; }

.ch-desc { font-size: 0.7rem; color: #94a3b8; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.3rem; }
.ch-desc i { font-size: 0.65rem; }

/* Kanban */
.kanban-board { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 1rem; min-height: calc(100vh - 240px); }
.kanban-col { flex-shrink: 0; width: 290px; display: flex; flex-direction: column; }
.col-header { display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.75rem; margin-bottom: 0.5rem; background: white; border-radius: 10px; border: 1.5px solid #e2e8f0; }
.col-left { display: flex; align-items: center; gap: 0.45rem; }
.col-dot { width: 8px; height: 8px; border-radius: 50%; }
.col-title { font-size: 0.78rem; font-weight: 700; color: #334155; }
.col-right { display: flex; align-items: center; gap: 0.35rem; }
.col-total { font-size: 0.6rem; font-weight: 700; color: #10b981; }
.col-count { font-size: 0.65rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.4rem; border-radius: 8px; }
.col-body { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; min-height: 200px; padding: 0.3rem; border-radius: 10px; background: #f8fafc; }

/* Card */
.pipe-card { background: white; border-radius: 12px; padding: 0.85rem; border: 1.5px solid #e2e8f0; border-top: 3px solid; cursor: grab; transition: all 0.2s; }
.pipe-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-color: #cbd5e1; transform: translateY(-1px); }
.pipe-card:active { cursor: grabbing; }
.pipe-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem; }
.pipe-link { text-decoration: none; flex: 1; min-width: 0; }
.pipe-company { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; }
.pipe-link:hover .pipe-company { color: #8b5cf6; }
.pipe-contact { font-size: 0.72rem; color: #64748b; margin: 0.15rem 0 0; }
.priority-badge { font-size: 0.55rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 6px; flex-shrink: 0; white-space: nowrap; }
.pr-hot { background: #fef2f2; color: #ef4444; } .pr-warm { background: #fffbeb; color: #f59e0b; } .pr-cold { background: #eff6ff; color: #3b82f6; }

.pipe-social { display: flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; color: #64748b; margin-bottom: 0.35rem; }
.pipe-social i { font-size: 0.75rem; color: #3b82f6; }

.pipe-value { display: flex; align-items: center; gap: 0.25rem; font-size: 0.82rem; font-weight: 800; color: #10b981; margin-bottom: 0.35rem; padding: 0.25rem 0.5rem; background: #ecfdf5; border-radius: 8px; }
.pipe-value i { font-size: 0.65rem; }

.pipe-prob { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; }
.prob-track { flex: 1; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.prob-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.3s; }
.prob-label { font-size: 0.6rem; font-weight: 700; color: #8b5cf6; }

.pipe-audit { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.35rem; }
.audit-track { flex: 1; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.audit-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }
.a-good { background: #10b981; } .a-warn { background: #f59e0b; } .a-low { background: #ef4444; }
.audit-label { font-size: 0.6rem; font-weight: 700; }
.audit-label.a-good { color: #10b981; } .audit-label.a-warn { color: #f59e0b; } .audit-label.a-low { color: #ef4444; }

.pipe-bottom { display: flex; align-items: center; justify-content: space-between; }
.pipe-user { display: flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; color: #64748b; }
.pipe-avatar { width: 20px; height: 20px; border-radius: 6px; background: #f0e7ff; color: #8b5cf6; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.pipe-meta { display: flex; align-items: center; gap: 0.3rem; }
.pipe-time { font-size: 0.55rem; color: #94a3b8; }
.drag-handle { padding: 0.2rem; color: #cbd5e1; cursor: grab; }
.drag-handle:hover { color: #8b5cf6; }
.drag-handle i { font-size: 0.72rem; }

.col-empty { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 2rem; color: #cbd5e1; font-size: 0.72rem; }
.col-empty i { font-size: 1rem; }

.ghost-card { opacity: 0.4; background: #f1f5f9 !important; border: 2px dashed #94a3b8 !important; }
.chosen-card { cursor: grabbing !important; box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important; transform: rotate(1deg); }
.dragging-card { opacity: 0.9; }

.kanban-board::-webkit-scrollbar { height: 6px; }
.kanban-board::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.kanban-board::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* ═══ Channel Manager ═══ */
.cm-body { display: flex; flex-direction: column; gap: 1rem; }
.cm-list { display: flex; flex-direction: column; gap: 0.5rem; max-height: 250px; overflow-y: auto; }
.cm-item { background: #fafbfc; border: 1.5px solid #f1f5f9; border-radius: 10px; padding: 0.65rem 0.75rem; transition: all 0.2s; }
.cm-item:hover { border-color: #e2e8f0; }
.cm-item.editing { border-color: #8b5cf6; background: #faf5ff; }
.cm-item-main { display: flex; align-items: center; gap: 0.6rem; }
.cm-item-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
.cm-item-info { flex: 1; display: flex; flex-direction: column; }
.cm-item-info strong { font-size: 0.82rem; color: #1e293b; }
.cm-item-info span { font-size: 0.62rem; color: #94a3b8; }
.cm-item-actions { display: flex; gap: 0.25rem; }
.cm-stages-preview { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.4rem; }
.stage-chip { font-size: 0.55rem; font-weight: 600; padding: 0.1rem 0.4rem; border-radius: 6px; }

.cm-form { background: #fafbfc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
.cm-form h4 { font-size: 0.82rem; font-weight: 700; color: #1e293b; margin: 0 0 0.65rem; }
.cm-form-row { margin-bottom: 0.5rem; }
.cm-form-row.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.w-full { width: 100%; }
.cm-stages-editor { margin-bottom: 0.65rem; }
.cm-stages-editor label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem; }
.stage-row { display: flex; gap: 0.35rem; align-items: center; margin-bottom: 0.3rem; }
.cm-form-actions { display: flex; gap: 0.35rem; justify-content: flex-end; }

.cm-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.6rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-size: 0.72rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.cm-btn:hover { border-color: #8b5cf6; color: #8b5cf6; }
.cm-btn.danger { color: #ef4444; }
.cm-btn.danger:hover { border-color: #ef4444; background: #fef2f2; }
.cm-btn.primary { background: #8b5cf6; color: white; border-color: #8b5cf6; }
.cm-btn.primary:hover { background: #7c3aed; }
.cm-btn.primary:disabled { opacity: 0.5; cursor: not-allowed; }
.cm-btn.add-stage { border-style: dashed; color: #8b5cf6; border-color: #c4b5fd; }

.cm-presets { margin-top: 0.25rem; }
.cm-presets label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem; }
.preset-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.4rem; }
.preset-card { display: flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.65rem; border: 1.5px dashed #e2e8f0; border-radius: 8px; background: white; cursor: pointer; font-size: 0.72rem; font-weight: 600; color: #475569; transition: all 0.15s; }
.preset-card:hover { border-color: #8b5cf6; background: #faf5ff; color: #7c3aed; }
.preset-card i { font-size: 0.85rem; }
</style>
