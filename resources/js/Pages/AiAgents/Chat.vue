<template>
  <div class="chat-page">
    <Head :title="agent.name + ' — Chat'" />

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="sidebar" :class="{ collapsed: sidebarCollapsed }">
      <div class="sidebar-toggle" @click="sidebarCollapsed = !sidebarCollapsed">
        <i :class="sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'" />
      </div>

      <!-- Agent Profile -->
      <div class="sidebar-profile">
        <div class="profile-avatar" :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}25, ${agent.type_meta.color}08)` }">
          <i :class="agent.type_meta.icon" :style="{ color: agent.type_meta.color }" />
          <span class="profile-status" />
        </div>
        <div v-if="!sidebarCollapsed" class="profile-info">
          <h3>{{ agent.name }}</h3>
          <span class="profile-type" :style="{ color: agent.type_meta.color }">{{ agent.type_meta.label }}</span>
        </div>
      </div>

      <!-- New Chat -->
      <button v-if="!sidebarCollapsed" class="new-chat-btn" @click="newConversation">
        <i class="pi pi-plus" /> Cuộc trò chuyện mới
      </button>
      <button v-else class="new-chat-btn-mini" @click="newConversation" v-tooltip.right="'Chat mới'">
        <i class="pi pi-plus" />
      </button>

      <!-- Knowledge Bases -->
      <div v-if="agent.knowledge_bases?.length && !sidebarCollapsed" class="sidebar-block">
        <div class="block-title"><i class="pi pi-database" /> Knowledge</div>
        <div class="kb-tags">
          <span v-for="kb in agent.knowledge_bases" :key="kb" class="kb-tag">{{ kb }}</span>
        </div>
      </div>

      <!-- History -->
      <div v-if="!sidebarCollapsed" class="sidebar-block history-block">
        <div class="block-title"><i class="pi pi-history" /> Lịch sử</div>
        <div class="history-list">
          <button v-for="conv in conversations" :key="conv.id"
            :class="['history-item', activeConversation?.id === conv.id && 'active']"
            @click="loadConversation(conv)">
            <div class="history-dot" />
            <div class="history-text">
              <span class="history-title">{{ conv.title }}</span>
              <span class="history-meta">{{ conv.message_count }} tin nhắn · {{ conv.updated_at }}</span>
            </div>
          </button>
          <p v-if="!conversations.length" class="no-history">Chưa có hội thoại</p>
        </div>
      </div>

      <!-- Back -->
      <div class="sidebar-bottom">
        <button class="back-btn" @click="$inertia.visit('/ai-agents')" :title="sidebarCollapsed ? 'Danh sách Agents' : ''">
          <i class="pi pi-arrow-left" />
          <span v-if="!sidebarCollapsed">Agents</span>
        </button>
      </div>
    </aside>

    <!-- ═══ CHAT AREA ═══ -->
    <main class="chat-main">
      <!-- Top Bar -->
      <div class="chat-topbar">
        <div class="topbar-left">
          <div class="topbar-avatar" :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}30, ${agent.type_meta.color}10)` }">
            <i :class="agent.type_meta.icon" :style="{ color: agent.type_meta.color, fontSize: '.85rem' }" />
          </div>
          <div>
            <h2>{{ agent.name }}</h2>
            <div class="topbar-status">
              <span class="status-dot online" /> Online
              <span v-if="agent.model_config" class="model-label">· {{ agent.model_config.model }}</span>
            </div>
          </div>
        </div>
        <div class="topbar-right">
          <div v-if="agent.tools?.length" class="topbar-tools">
            <span v-for="tool in agent.tools.slice(0, 3)" :key="tool" class="topbar-tool-badge">
              <i :class="agentToolIcon(tool)" />
            </span>
            <span v-if="agent.tools.length > 3" class="topbar-tool-badge more">+{{ agent.tools.length - 3 }}</span>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <div class="messages-area" ref="messagesContainer">
        <!-- Welcome Screen -->
        <div v-if="!messages.length" class="welcome-screen">
          <div class="welcome-orb-wrapper">
            <div class="welcome-orb" :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}, ${agent.type_meta.color}88)` }">
              <i :class="agent.type_meta.icon" />
            </div>
            <div class="orb-ring" :style="{ borderColor: agent.type_meta.color + '30' }" />
            <div class="orb-ring orb-ring-2" :style="{ borderColor: agent.type_meta.color + '15' }" />
          </div>
          <h2 class="welcome-title">Xin chào! Tôi là <span :style="{ color: agent.type_meta.color }">{{ agent.name }}</span></h2>
          <p class="welcome-desc">{{ agent.description || agent.type_meta.description }}</p>

          <!-- Suggestion Cards -->
          <div class="suggestions-grid">
            <button v-for="(s, i) in suggestions" :key="i" class="suggestion-card" @click="sendSuggestion(s)"
              :style="{ '--delay': (i * 100) + 'ms' }">
              <div class="suggestion-icon" :style="{ background: agent.type_meta.color + '12', color: agent.type_meta.color }">
                <i :class="suggestionIcons[i]" />
              </div>
              <span>{{ s }}</span>
              <i class="pi pi-arrow-right suggestion-arrow" />
            </button>
          </div>
        </div>

        <!-- Messages List -->
        <template v-for="(msg, i) in messages" :key="i">
          <div :class="['message-wrapper', msg.role]" :style="{ animationDelay: (i * 30) + 'ms' }">
            <!-- Avatar -->
            <div v-if="msg.role === 'assistant'" class="msg-avatar assistant-avatar"
              :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}25, ${agent.type_meta.color}08)` }">
              <i :class="agent.type_meta.icon" :style="{ color: agent.type_meta.color }" />
            </div>

            <div class="msg-bubble" :class="msg.role">
              <div class="msg-text" v-html="formatMessage(msg.content)" />
              <!-- Sources -->
              <div v-if="msg.sources?.length" class="msg-sources">
                <span class="sources-label"><i class="pi pi-link" /> Sources:</span>
                <span v-for="(s, j) in msg.sources" :key="j" class="source-chip">{{ s }}</span>
              </div>
            </div>

            <!-- User Avatar -->
            <div v-if="msg.role === 'user'" class="msg-avatar user-avatar">
              <i class="pi pi-user" />
            </div>
          </div>
        </template>

        <!-- Typing Indicator -->
        <div v-if="typing" class="message-wrapper assistant typing-in">
          <div class="msg-avatar assistant-avatar" :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}25, ${agent.type_meta.color}08)` }">
            <i :class="agent.type_meta.icon" :style="{ color: agent.type_meta.color }" />
          </div>
          <div class="typing-indicator">
            <div class="typing-wave">
              <span /><span /><span />
            </div>
            <span class="typing-text">{{ agent.name }} đang trả lời...</span>
          </div>
        </div>
      </div>

      <!-- ═══ INPUT BAR ═══ -->
      <div class="input-bar">
        <div class="input-container" :class="{ focused: inputFocused }">
          <textarea v-model="inputMessage" ref="inputField"
            @keydown.enter.exact.prevent="sendMessage"
            @focus="inputFocused = true" @blur="inputFocused = false"
            :placeholder="`Hỏi ${agent.name} bất cứ điều gì...`"
            rows="1" class="message-input" :disabled="typing" />
          <div class="input-actions">
            <button class="send-button" @click="sendMessage"
              :disabled="!inputMessage.trim() || typing"
              :class="{ active: inputMessage.trim() }"
              :style="inputMessage.trim() ? { background: `linear-gradient(135deg, ${agent.type_meta.color}, ${agent.type_meta.color}cc)` } : {}">
              <i :class="typing ? 'pi pi-spin pi-spinner' : 'pi pi-arrow-up'" />
            </button>
          </div>
        </div>
        <div class="input-footer">
          <span class="footer-model" v-if="agent.model_config">
            <i class="pi pi-microchip" /> {{ agent.model_config.provider }} · {{ agent.model_config.model }}
          </span>
          <span v-if="activeConversation" class="footer-conv">Chat #{{ activeConversation.id }}</span>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

const TOOL_ICONS = {
  search_knowledge: 'pi pi-search',
  query_crm: 'pi pi-database',
  create_lead: 'pi pi-plus',
  create_task: 'pi pi-check-square',
  send_email: 'pi pi-envelope',
  generate_report: 'pi pi-file',
  web_search: 'pi pi-globe',
  analyze_data: 'pi pi-chart-bar',
}

export default {
  components: { Head, Button },
  layout: Layout,
  props: { agent: Object, conversations: Array, activeConversation: Object },
  data() {
    return {
      messages: this.activeConversation?.messages || [],
      inputMessage: '',
      typing: false,
      inputFocused: false,
      sidebarCollapsed: false,
      suggestionIcons: ['pi pi-bolt', 'pi pi-chart-line', 'pi pi-pencil'],
    }
  },
  computed: {
    suggestions() {
      const s = {
        sales: ['Phân tích pipeline tháng này', 'Deal nào có nguy cơ cao?', 'Viết email follow-up khách hàng'],
        support: ['Khách phàn nàn lỗi thanh toán', 'Hướng dẫn tạo báo giá mới', 'Troubleshoot lỗi import CSV'],
        content: ['Viết bài blog SEO 1000 từ', 'Tạo 5 caption Facebook hấp dẫn', 'Outline content marketing AI'],
        analytics: ['Báo cáo revenue tháng này', 'So sánh hiệu quả các kênh', 'Dự đoán doanh thu Q2'],
        hr: ['Chính sách nghỉ phép 2026', 'Quy trình onboarding mới', 'Training budget còn bao nhiêu?'],
        custom: ['Hỏi về quy trình nội bộ', 'Phân tích dữ liệu CRM', 'Tìm kiếm thông tin'],
      }
      return s[this.agent.type] || s.custom
    },
  },
  methods: {
    agentToolIcon(tool) { return TOOL_ICONS[tool] || 'pi pi-cog' },
    sendMessage() {
      const msg = this.inputMessage.trim()
      if (!msg || this.typing) return
      this.messages.push({ role: 'user', content: msg, sources: [], timestamp: new Date().toISOString() })
      this.inputMessage = ''
      this.typing = true
      this.scrollToBottom()

      this.$inertia.post(`/ai-agents/${this.agent.id}/chat`, {
        message: msg,
        conversation_id: this.activeConversation?.id || null,
      }, {
        preserveScroll: true,
        onSuccess: (page) => {
          const conv = page.props.activeConversation
          if (conv) this.messages = conv.messages
          this.typing = false
          this.$nextTick(() => this.scrollToBottom())
        },
        onError: () => { this.typing = false },
      })
    },
    sendSuggestion(text) { this.inputMessage = text; this.sendMessage() },
    newConversation() { this.messages = []; this.$inertia.visit(`/ai-agents/${this.agent.id}/chat`) },
    loadConversation(conv) {
      this.$inertia.visit(`/ai-agents/${this.agent.id}/chat`, { data: { conversation_id: conv.id }, preserveState: false })
    },
    scrollToBottom() {
      this.$nextTick(() => { const c = this.$refs.messagesContainer; if (c) c.scrollTop = c.scrollHeight })
    },
    formatMessage(text) {
      if (!text) return ''
      return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/^\| (.+) \|$/gm, (m) => `<div class="md-table-row">${m}</div>`)
        .replace(/\n/g, '<br>')
    },
  },
  mounted() { this.scrollToBottom() },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.chat-page { display:flex; height:calc(100vh - 60px); margin:-1.5rem; font-family:'Inter', sans-serif; background:#f8f9fb; overflow:hidden }

/* ═══ SIDEBAR ═══ */
.sidebar { width:280px; flex-shrink:0; background:linear-gradient(180deg, #fafbfc 0%, #f0f2f5 100%); border-right:1px solid #e8ecf2; display:flex; flex-direction:column; position:relative; transition:width .3s cubic-bezier(.4,0,.2,1); overflow:hidden }
.sidebar.collapsed { width:62px }
.sidebar-toggle { position:absolute; top:12px; right:-12px; width:24px; height:24px; border-radius:50%; background:white; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:5; font-size:.55rem; color:#64748b; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:all .2s }
.sidebar-toggle:hover { background:#7c3aed; color:white; border-color:#7c3aed }

/* Profile */
.sidebar-profile { display:flex; align-items:center; gap:.65rem; padding:1rem .85rem; border-bottom:1px solid #e8ecf2 }
.profile-avatar { position:relative; width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0 }
.profile-status { position:absolute; bottom:-1px; right:-1px; width:10px; height:10px; border-radius:50%; background:#10b981; border:2px solid #fafbfc }
.profile-info h3 { font-size:.82rem; font-weight:700; color:#0f172a; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.profile-type { font-size:.55rem; font-weight:600 }

/* New Chat */
.new-chat-btn { display:flex; align-items:center; justify-content:center; gap:.4rem; margin:.65rem .85rem; padding:.55rem; border:1.5px dashed #c4b5fd; border-radius:10px; background:transparent; color:#7c3aed; font-size:.72rem; font-weight:600; cursor:pointer; font-family:inherit; transition:all .2s }
.new-chat-btn:hover { background:#ede9fe; border-style:solid }
.new-chat-btn i { font-size:.62rem }
.new-chat-btn-mini { display:flex; align-items:center; justify-content:center; width:36px; height:36px; margin:.65rem auto; border:1.5px dashed #c4b5fd; border-radius:10px; background:transparent; color:#7c3aed; cursor:pointer; transition:all .2s }
.new-chat-btn-mini:hover { background:#ede9fe; border-style:solid }

/* Blocks */
.sidebar-block { padding:.65rem .85rem }
.block-title { font-size:.55rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.45rem; display:flex; align-items:center; gap:.25rem }
.block-title i { font-size:.48rem }
.kb-tags { display:flex; flex-wrap:wrap; gap:.25rem }
.kb-tag { font-size:.5rem; font-weight:600; padding:.15rem .4rem; border-radius:5px; background:#ede9fe; color:#7c3aed }

/* History */
.history-block { flex:1; overflow:hidden; display:flex; flex-direction:column }
.history-list { flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:.15rem; padding-right:.25rem }
.history-list::-webkit-scrollbar { width:3px }
.history-list::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:3px }
.history-item { display:flex; align-items:center; gap:.45rem; width:100%; text-align:left; padding:.48rem .55rem; background:transparent; border:none; border-radius:8px; cursor:pointer; transition:all .15s; font-family:inherit }
.history-item:hover { background:rgba(139,92,246,.06) }
.history-item.active { background:#ede9fe }
.history-dot { width:6px; height:6px; border-radius:50%; background:#cbd5e1; flex-shrink:0 }
.history-item.active .history-dot { background:#7c3aed }
.history-text { min-width:0; flex:1 }
.history-title { display:block; font-size:.68rem; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.history-meta { font-size:.48rem; color:#94a3b8 }
.no-history { font-size:.65rem; color:#94a3b8; margin:0; text-align:center; padding:1rem 0 }

/* Bottom */
.sidebar-bottom { padding:.65rem .85rem; border-top:1px solid #e8ecf2; margin-top:auto }
.back-btn { display:flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:600; color:#64748b; background:none; border:none; cursor:pointer; font-family:inherit; padding:.35rem; border-radius:6px; transition:all .15s; width:100% }
.back-btn:hover { color:#7c3aed; background:rgba(139,92,246,.06) }
.back-btn i { font-size:.6rem }

/* ═══ CHAT MAIN ═══ */
.chat-main { flex:1; display:flex; flex-direction:column; min-width:0; background:white }

/* Topbar */
.chat-topbar { display:flex; align-items:center; justify-content:space-between; padding:.65rem 1.5rem; border-bottom:1px solid #f1f5f9; background:rgba(255,255,255,.85); backdrop-filter:blur(12px); flex-shrink:0 }
.topbar-left { display:flex; align-items:center; gap:.6rem }
.topbar-avatar { width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0 }
.topbar-left h2 { font-size:.88rem; font-weight:700; color:#0f172a; margin:0 }
.topbar-status { display:flex; align-items:center; gap:.3rem; font-size:.55rem; color:#94a3b8 }
.status-dot { width:6px; height:6px; border-radius:50% }
.status-dot.online { background:#10b981; box-shadow:0 0 4px rgba(16,185,129,.5) }
.model-label { color:#cbd5e1 }
.topbar-tools { display:flex; gap:.2rem }
.topbar-tool-badge { width:26px; height:26px; border-radius:7px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.52rem; color:#64748b }
.topbar-tool-badge.more { font-size:.48rem; font-weight:700; background:#ede9fe; color:#7c3aed }

/* ═══ MESSAGES ═══ */
.messages-area { flex:1; overflow-y:auto; padding:1.5rem 1.5rem .5rem; display:flex; flex-direction:column; gap:.75rem }
.messages-area::-webkit-scrollbar { width:4px }
.messages-area::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:4px }

/* Welcome */
.welcome-screen { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem; margin:auto; max-width:520px; text-align:center }
.welcome-orb-wrapper { position:relative; margin-bottom:1.5rem }
.welcome-orb { width:72px; height:72px; border-radius:20px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.8rem; box-shadow:0 8px 32px rgba(0,0,0,.15); animation:orb-breathe 4s ease-in-out infinite; position:relative; z-index:2 }
@keyframes orb-breathe { 0%,100% { transform:scale(1) } 50% { transform:scale(1.06) } }
.orb-ring { position:absolute; inset:-12px; border:2px solid; border-radius:28px; animation:ring-spin 12s linear infinite }
.orb-ring-2 { inset:-24px; border-radius:36px; animation:ring-spin 18s linear infinite reverse }
@keyframes ring-spin { from { transform:rotate(0) } to { transform:rotate(360deg) } }
.welcome-title { font-size:1.35rem; font-weight:800; color:#0f172a; margin:0 0 .4rem; letter-spacing:-.02em }
.welcome-desc { font-size:.82rem; color:#64748b; margin:0 0 2rem; line-height:1.6 }

/* Suggestions */
.suggestions-grid { display:flex; flex-direction:column; gap:.45rem; width:100%; max-width:400px }
.suggestion-card { display:flex; align-items:center; gap:.65rem; padding:.7rem .85rem; background:white; border:1.5px solid #e8ecf2; border-radius:14px; cursor:pointer; font-family:inherit; transition:all .25s; animation:suggestionIn .4s ease-out both; animation-delay:var(--delay); text-align:left }
@keyframes suggestionIn { from { opacity:0; transform:translateY(8px) } to { opacity:1; transform:translateY(0) } }
.suggestion-card:hover { border-color:#c4b5fd; background:#faf5ff; transform:translateX(4px); box-shadow:0 4px 16px rgba(139,92,246,.06) }
.suggestion-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.72rem; flex-shrink:0 }
.suggestion-card span { flex:1; font-size:.75rem; font-weight:500; color:#334155 }
.suggestion-arrow { font-size:.55rem; color:#cbd5e1; transition:all .2s }
.suggestion-card:hover .suggestion-arrow { color:#7c3aed; transform:translateX(2px) }

/* Messages */
.message-wrapper { display:flex; gap:.5rem; max-width:78%; animation:msgIn .3s ease-out both }
@keyframes msgIn { from { opacity:0; transform:translateY(6px) } to { opacity:1; transform:translateY(0) } }
.message-wrapper.user { align-self:flex-end; flex-direction:row-reverse }
.message-wrapper.assistant { align-self:flex-start }
.msg-avatar { width:30px; height:30px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.68rem }
.user-avatar { background:linear-gradient(135deg, #6d28d9, #7c3aed); color:white }

/* Bubble */
.msg-bubble { max-width:100% }
.msg-bubble.user .msg-text { background:linear-gradient(135deg, #6d28d9, #8b5cf6); color:white; border-radius:18px 18px 4px 18px; padding:.75rem 1rem; font-size:.8rem; line-height:1.65 }
.msg-bubble.assistant .msg-text { background:#f8f9fb; border:1px solid #e8ecf2; border-radius:18px 18px 18px 4px; padding:.85rem 1.15rem; font-size:.8rem; line-height:1.7; color:#1e293b }
.msg-bubble.assistant .msg-text :deep(strong) { color:#0f172a; font-weight:700 }
.msg-bubble.assistant .msg-text :deep(code) { background:#ede9fe; color:#6d28d9; padding:.1rem .35rem; border-radius:4px; font-size:.72rem; font-family:'JetBrains Mono', monospace }

/* Sources */
.msg-sources { display:flex; align-items:center; gap:.25rem; margin-top:.35rem; flex-wrap:wrap }
.sources-label { display:flex; align-items:center; gap:.2rem; font-size:.48rem; color:#94a3b8; font-weight:600 }
.sources-label i { font-size:.42rem }
.source-chip { font-size:.48rem; font-weight:600; padding:.1rem .35rem; border-radius:5px; background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd }

/* Typing */
.typing-in { animation:msgIn .3s ease-out both }
.typing-indicator { display:flex; align-items:center; gap:.55rem; padding:.65rem 1rem; background:#f8f9fb; border:1px solid #e8ecf2; border-radius:18px 18px 18px 4px }
.typing-wave { display:flex; gap:.2rem }
.typing-wave span { width:7px; height:7px; border-radius:50%; background:#a78bfa; animation:waveBounce 1.2s infinite ease-in-out }
.typing-wave span:nth-child(2) { animation-delay:.15s; background:#8b5cf6 }
.typing-wave span:nth-child(3) { animation-delay:.3s; background:#7c3aed }
@keyframes waveBounce { 0%,60%,100% { transform:translateY(0) } 30% { transform:translateY(-6px) } }
.typing-text { font-size:.6rem; color:#94a3b8; font-style:italic }

/* ═══ INPUT BAR ═══ */
.input-bar { padding:.65rem 1.5rem .85rem; background:white; border-top:1px solid #f1f5f9; flex-shrink:0 }
.input-container { display:flex; align-items:flex-end; gap:.4rem; background:#f8f9fb; border:2px solid #e8ecf2; border-radius:16px; padding:.35rem .35rem .35rem .85rem; transition:all .25s }
.input-container.focused { border-color:#8b5cf6; background:white; box-shadow:0 0 0 4px rgba(139,92,246,.08) }
.message-input { flex:1; border:none; outline:none; background:transparent; resize:none; font-size:.82rem; color:#1e293b; font-family:'Inter', sans-serif; padding:.4rem 0; min-height:20px; max-height:140px }
.message-input::placeholder { color:#94a3b8 }
.input-actions { display:flex; gap:.25rem; flex-shrink:0 }
.send-button { width:36px; height:36px; border-radius:10px; border:none; background:#e2e8f0; color:white; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .25s cubic-bezier(.4,0,.2,1) }
.send-button.active { box-shadow:0 4px 12px rgba(139,92,246,.3) }
.send-button.active:hover { transform:scale(1.08) }
.send-button:disabled { opacity:.35; cursor:not-allowed }
.send-button i { font-size:.78rem; transition:transform .2s }
.send-button.active:hover i { transform:translateY(-1px) }
.input-footer { display:flex; justify-content:space-between; padding:.3rem .45rem 0; }
.footer-model { font-size:.52rem; color:#94a3b8; display:flex; align-items:center; gap:.2rem }
.footer-model i { font-size:.45rem; color:#c4b5fd }
.footer-conv { font-size:.48rem; color:#cbd5e1 }

@media (max-width:768px) {
  .chat-page { flex-direction:column; height:auto; min-height:calc(100vh - 60px) }
  .sidebar { width:100%; max-height:180px; border-right:none; border-bottom:1px solid #e8ecf2 }
  .sidebar.collapsed { width:100%; max-height:50px }
  .sidebar-toggle { display:none }
  .message-wrapper { max-width:92% }
  .welcome-screen { padding:1.5rem 1rem }
  .suggestions-grid { max-width:100% }
}
</style>
