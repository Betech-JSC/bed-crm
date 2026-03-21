<template>
  <div class="ai-chat-container">
    <Head title="AI Chat" />

    <!-- Sidebar: Conversations -->
    <aside class="chat-sidebar" :class="{ collapsed: sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <i class="pi pi-sparkles" />
          <span v-if="!sidebarCollapsed">AI Chat</span>
        </div>
        <button class="btn-icon" @click="sidebarCollapsed = !sidebarCollapsed">
          <i :class="sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'" />
        </button>
      </div>

      <button class="btn-new-chat" @click="createConversation">
        <i class="pi pi-plus" />
        <span v-if="!sidebarCollapsed">Cuộc trò chuyện mới</span>
      </button>

      <div class="conversation-list" v-if="!sidebarCollapsed">
        <!-- Pinned -->
        <div v-if="pinnedConversations.length" class="conv-section">
          <div class="conv-section-title"><i class="pi pi-bookmark-fill" /> Đã ghim</div>
          <div
            v-for="conv in pinnedConversations"
            :key="conv.id"
            class="conv-item"
            :class="{ active: activeConversationId === conv.id }"
            @click="loadConversation(conv.id)"
          >
            <div class="conv-item-content">
              <span class="conv-title">{{ conv.title }}</span>
              <span class="conv-meta">{{ conv.last_message_at || conv.updated_at }}</span>
            </div>
            <div class="conv-actions">
              <button class="btn-icon-sm" @click.stop="togglePin(conv)" title="Bỏ ghim"><i class="pi pi-bookmark-fill" /></button>
              <button class="btn-icon-sm danger" @click.stop="deleteConversation(conv.id)" title="Xóa"><i class="pi pi-trash" /></button>
            </div>
          </div>
        </div>

        <!-- Recent -->
        <div class="conv-section">
          <div class="conv-section-title"><i class="pi pi-clock" /> Gần đây</div>
          <div
            v-for="conv in recentConversations"
            :key="conv.id"
            class="conv-item"
            :class="{ active: activeConversationId === conv.id }"
            @click="loadConversation(conv.id)"
          >
            <div class="conv-item-content">
              <span class="conv-title">{{ conv.title }}</span>
              <span class="conv-meta">{{ conv.last_message_at || conv.updated_at }}</span>
            </div>
            <div class="conv-actions">
              <button class="btn-icon-sm" @click.stop="togglePin(conv)" title="Ghim"><i class="pi pi-bookmark" /></button>
              <button class="btn-icon-sm danger" @click.stop="deleteConversation(conv.id)" title="Xóa"><i class="pi pi-trash" /></button>
            </div>
          </div>
          <div v-if="!recentConversations.length" class="conv-empty">
            <i class="pi pi-comments" />
            <span>Chưa có cuộc trò chuyện</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Chat Area -->
    <main class="chat-main">
      <!-- Welcome Screen (no conversation selected) -->
      <div v-if="!activeConversationId" class="welcome-screen">
        <div class="welcome-icon">
          <i class="pi pi-sparkles" />
        </div>
        <h2 class="welcome-title">AI Assistant</h2>
        <p class="welcome-subtitle">Trợ lý AI thông minh cho CRM của bạn</p>

        <div class="welcome-suggestions">
          <button v-for="(s, i) in suggestions" :key="i" class="suggestion-card" @click="startWithSuggestion(s.prompt)">
            <i :class="s.icon" />
            <div>
              <strong>{{ s.title }}</strong>
              <span>{{ s.desc }}</span>
            </div>
          </button>
        </div>

        <div class="welcome-input-wrapper">
          <div class="chat-input-bar welcome-input">
            <textarea
              v-model="newMessage"
              @keydown.enter.exact.prevent="sendFromWelcome"
              placeholder="Hỏi AI bất kỳ điều gì..."
              rows="1"
              ref="welcomeInput"
            />
            <button class="btn-send" @click="sendFromWelcome" :disabled="!newMessage.trim()">
              <i class="pi pi-send" />
            </button>
          </div>
        </div>
      </div>

      <!-- Active Conversation -->
      <div v-else class="chat-area">
        <!-- Chat Header -->
        <div class="chat-header">
          <div class="chat-header-left">
            <button class="btn-icon mobile-menu" @click="sidebarCollapsed = !sidebarCollapsed">
              <i class="pi pi-bars" />
            </button>
            <div>
              <h3 class="chat-title" contenteditable @blur="updateTitle($event)">{{ activeTitle }}</h3>
              <span class="chat-provider" v-if="activeProvider">
                <i class="pi pi-microchip-ai" /> {{ activeProvider }} · {{ activeModel }}
              </span>
            </div>
          </div>
          <div class="chat-header-right">
            <select v-model="selectedProvider" @change="changeProvider" class="header-select">
              <option value="">Auto</option>
              <option v-for="p in configuredProviders" :key="p" :value="p">{{ p }}</option>
            </select>
          </div>
        </div>

        <!-- Messages -->
        <div class="messages-container" ref="messagesContainer">
          <div v-for="msg in messages" :key="msg.id" class="message" :class="msg.role">
            <div class="message-avatar">
              <i v-if="msg.role === 'assistant'" class="pi pi-sparkles" />
              <i v-else class="pi pi-user" />
            </div>
            <div class="message-body">
              <div class="message-content" v-html="formatMessage(msg.content)" />
              <div class="message-footer">
                <span class="msg-time">{{ msg.created_at }}</span>
                <span v-if="msg.provider" class="msg-provider">{{ msg.provider }}</span>
                <span v-if="msg.tokens_used" class="msg-tokens">{{ msg.tokens_used }} tokens</span>
                <button class="btn-icon-sm" @click="copyMessage(msg.content)" title="Copy">
                  <i class="pi pi-copy" />
                </button>
              </div>
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="isThinking" class="message assistant">
            <div class="message-avatar"><i class="pi pi-sparkles" /></div>
            <div class="message-body">
              <div class="thinking-dots">
                <span /><span /><span />
              </div>
            </div>
          </div>

          <!-- Error -->
          <div v-if="errorMessage" class="chat-error">
            <i class="pi pi-exclamation-triangle" />
            <span>{{ errorMessage }}</span>
            <button @click="errorMessage = null" class="btn-icon-sm"><i class="pi pi-times" /></button>
          </div>
        </div>

        <!-- Input Bar -->
        <div class="chat-input-section">
          <div class="chat-input-bar">
            <textarea
              v-model="newMessage"
              @keydown.enter.exact.prevent="sendMessage"
              @input="autoResize"
              placeholder="Nhập tin nhắn..."
              rows="1"
              ref="chatInput"
              :disabled="isThinking"
            />
            <button class="btn-send" @click="sendMessage" :disabled="!newMessage.trim() || isThinking">
              <i :class="isThinking ? 'pi pi-spin pi-spinner' : 'pi pi-send'" />
            </button>
          </div>
          <div class="input-hint">
            <span>Enter để gửi · Shift+Enter để xuống dòng</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: {
    conversations: { type: Array, default: () => [] },
    configuredProviders: { type: Array, default: () => [] },
    defaultProvider: { type: String, default: '' },
    availableModels: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      sidebarCollapsed: false,
      activeConversationId: null,
      activeTitle: '',
      activeProvider: '',
      activeModel: '',
      selectedProvider: '',
      messages: [],
      newMessage: '',
      isThinking: false,
      errorMessage: null,
      localConversations: [...this.conversations],
      suggestions: [
        { icon: 'pi pi-chart-line', title: 'Phân tích doanh thu', desc: 'Đánh giá hiệu suất kinh doanh', prompt: 'Hãy giúp tôi phân tích hiệu suất doanh thu tháng này và đề xuất cải thiện' },
        { icon: 'pi pi-envelope', title: 'Viết email', desc: 'Soạn email chuyên nghiệp', prompt: 'Giúp tôi viết email follow-up cho khách hàng tiềm năng sau buổi demo sản phẩm' },
        { icon: 'pi pi-megaphone', title: 'Chiến lược Marketing', desc: 'Lên kế hoạch marketing', prompt: 'Đề xuất chiến lược marketing digital cho doanh nghiệp B2B ngành công nghệ tại Việt Nam' },
        { icon: 'pi pi-users', title: 'Quản lý khách hàng', desc: 'Tư vấn CRM', prompt: 'Tư vấn cách phân loại và chăm sóc khách hàng hiệu quả trong CRM' },
      ],
    }
  },
  computed: {
    pinnedConversations() {
      return this.localConversations.filter(c => c.is_pinned)
    },
    recentConversations() {
      return this.localConversations.filter(c => !c.is_pinned)
    },
  },
  methods: {
    async createConversation(initialMessage = null) {
      try {
        const res = await fetch('/ai-chat', {
          method: 'POST',
          headers: this.headers(),
          body: JSON.stringify({
            provider: this.selectedProvider || null,
          }),
        })
        const data = await res.json()
        this.localConversations.unshift({
          id: data.id,
          title: data.title,
          message_count: 0,
          is_pinned: false,
          last_message_at: 'vừa xong',
          updated_at: new Date().toLocaleDateString('vi'),
        })
        this.activeConversationId = data.id
        this.activeTitle = data.title
        this.activeProvider = ''
        this.activeModel = ''
        this.messages = []

        if (initialMessage) {
          this.newMessage = initialMessage
          await this.$nextTick()
          this.sendMessage()
        }
      } catch (e) {
        this.errorMessage = 'Không thể tạo conversation: ' + e.message
      }
    },

    async loadConversation(id) {
      this.activeConversationId = id
      this.messages = []
      this.errorMessage = null

      try {
        const res = await fetch(`/ai-chat/${id}`, { headers: this.headers() })
        const data = await res.json()
        this.messages = data.messages
        this.activeTitle = data.conversation.title
        this.activeProvider = data.conversation.provider
        this.activeModel = data.conversation.model
        this.selectedProvider = data.conversation.provider || ''
        await this.$nextTick()
        this.scrollToBottom()
      } catch (e) {
        this.errorMessage = 'Không thể tải conversation'
      }
    },

    async sendMessage() {
      const msg = this.newMessage.trim()
      if (!msg || this.isThinking) return

      this.newMessage = ''
      this.isThinking = true
      this.errorMessage = null

      // Add user message immediately
      this.messages.push({
        id: Date.now(),
        role: 'user',
        content: msg,
        created_at: new Date().toLocaleTimeString('vi', { hour: '2-digit', minute: '2-digit' }),
      })
      await this.$nextTick()
      this.scrollToBottom()

      try {
        const res = await fetch(`/ai-chat/${this.activeConversationId}/messages`, {
          method: 'POST',
          headers: this.headers(),
          body: JSON.stringify({ message: msg }),
        })

        if (!res.ok) {
          const err = await res.json()
          throw new Error(err.message || 'Server error')
        }

        const data = await res.json()

        // Replace temporary user message
        this.messages[this.messages.length - 1] = data.user_message

        // Add AI response
        this.messages.push(data.ai_message)

        // Update conversation in sidebar
        const conv = this.localConversations.find(c => c.id === this.activeConversationId)
        if (conv) {
          conv.title = data.conversation.title
          conv.message_count = data.conversation.message_count
          conv.last_message_at = 'vừa xong'
        }

        this.activeTitle = data.conversation.title
        this.activeProvider = data.ai_message.provider
        this.activeModel = data.ai_message.model
      } catch (e) {
        this.errorMessage = e.message
      } finally {
        this.isThinking = false
        await this.$nextTick()
        this.scrollToBottom()
        this.$refs.chatInput?.focus()
      }
    },

    async sendFromWelcome() {
      const msg = this.newMessage.trim()
      if (!msg) return
      await this.createConversation(msg)
    },

    async startWithSuggestion(prompt) {
      this.newMessage = prompt
      await this.createConversation(prompt)
    },

    async togglePin(conv) {
      conv.is_pinned = !conv.is_pinned
      await fetch(`/ai-chat/${conv.id}`, {
        method: 'PUT',
        headers: this.headers(),
        body: JSON.stringify({ is_pinned: conv.is_pinned }),
      })
    },

    async changeProvider() {
      if (!this.activeConversationId) return
      await fetch(`/ai-chat/${this.activeConversationId}`, {
        method: 'PUT',
        headers: this.headers(),
        body: JSON.stringify({ provider: this.selectedProvider || null }),
      })
    },

    async updateTitle(e) {
      const title = e.target.innerText.trim()
      if (!title || !this.activeConversationId) return
      this.activeTitle = title
      await fetch(`/ai-chat/${this.activeConversationId}`, {
        method: 'PUT',
        headers: this.headers(),
        body: JSON.stringify({ title }),
      })
      const conv = this.localConversations.find(c => c.id === this.activeConversationId)
      if (conv) conv.title = title
    },

    async deleteConversation(id) {
      if (!confirm('Xóa cuộc trò chuyện này?')) return
      await fetch(`/ai-chat/${id}`, { method: 'DELETE', headers: this.headers() })
      this.localConversations = this.localConversations.filter(c => c.id !== id)
      if (this.activeConversationId === id) {
        this.activeConversationId = null
        this.messages = []
      }
    },

    formatMessage(content) {
      if (!content) return ''
      // Basic markdown: bold, code, newlines
      return content
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
        .replace(/\n/g, '<br>')
    },

    copyMessage(content) {
      navigator.clipboard.writeText(content)
    },

    scrollToBottom() {
      const el = this.$refs.messagesContainer
      if (el) el.scrollTop = el.scrollHeight
    },

    autoResize(e) {
      const el = e.target
      el.style.height = 'auto'
      el.style.height = Math.min(el.scrollHeight, 200) + 'px'
    },

    headers() {
      return {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    },
  },
}
</script>

<style scoped>
.ai-chat-container {
  display: flex; height: calc(100vh - 56px);
  margin: -1.5rem -2rem; /* negate layout padding */
  background: #f8fafc;
}

/* ===== Sidebar ===== */
.chat-sidebar {
  width: 300px; min-width: 300px; background: #0f172a;
  display: flex; flex-direction: column;
  border-right: 1px solid rgba(255,255,255,0.06);
  transition: all 0.3s ease;
}
.chat-sidebar.collapsed { width: 60px; min-width: 60px; }
.sidebar-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06);
}
.sidebar-brand {
  display: flex; align-items: center; gap: 0.5rem;
  color: white; font-weight: 700; font-size: 0.92rem;
}
.sidebar-brand i { color: #a78bfa; font-size: 1.1rem; }
.btn-icon {
  background: rgba(255,255,255,0.05); border: none; color: rgba(255,255,255,0.5);
  width: 32px; height: 32px; border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.btn-icon:hover { background: rgba(255,255,255,0.1); color: white; }

.btn-new-chat {
  display: flex; align-items: center; gap: 0.5rem; justify-content: center;
  margin: 0.75rem; padding: 0.6rem; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; border: none; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-new-chat:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(99,102,241,0.4); }

.conversation-list { flex: 1; overflow-y: auto; padding: 0 0.5rem; }
.conv-section { margin-bottom: 0.5rem; }
.conv-section-title {
  display: flex; align-items: center; gap: 0.3rem;
  font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.08em; color: rgba(255,255,255,0.3);
  padding: 0.5rem 0.5rem 0.25rem;
}
.conv-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.55rem 0.65rem; border-radius: 8px; cursor: pointer;
  transition: all 0.15s; margin-bottom: 2px;
}
.conv-item:hover { background: rgba(255,255,255,0.05); }
.conv-item.active { background: rgba(99,102,241,0.15); }
.conv-item-content { flex: 1; min-width: 0; }
.conv-title {
  display: block; font-size: 0.78rem; color: rgba(255,255,255,0.8);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.conv-meta { font-size: 0.6rem; color: rgba(255,255,255,0.3); }
.conv-actions { display: none; gap: 0.2rem; }
.conv-item:hover .conv-actions { display: flex; }
.btn-icon-sm {
  background: none; border: none; color: rgba(255,255,255,0.3);
  width: 24px; height: 24px; border-radius: 6px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; transition: all 0.15s;
}
.btn-icon-sm:hover { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8); }
.btn-icon-sm.danger:hover { color: #ef4444; }
.conv-empty {
  display: flex; flex-direction: column; align-items: center; gap: 0.4rem;
  padding: 2rem 1rem; color: rgba(255,255,255,0.2); font-size: 0.75rem;
}
.conv-empty i { font-size: 1.5rem; }

/* ===== Main Chat ===== */
.chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* Welcome Screen */
.welcome-screen {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; padding: 2rem;
}
.welcome-icon {
  width: 72px; height: 72px; border-radius: 20px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6, #a78bfa);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; color: white; margin-bottom: 1rem;
  box-shadow: 0 8px 30px rgba(99,102,241,0.3);
  animation: float 3s ease-in-out infinite;
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.welcome-title { font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0; }
.welcome-subtitle { font-size: 0.88rem; color: #64748b; margin: 0.3rem 0 2rem; }

.welcome-suggestions {
  display: grid; grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem; max-width: 640px; width: 100%; margin-bottom: 2rem;
}
.suggestion-card {
  display: flex; align-items: flex-start; gap: 0.65rem;
  padding: 0.85rem 1rem; background: white; border: 1.5px solid #e2e8f0;
  border-radius: 12px; text-align: left; cursor: pointer;
  transition: all 0.2s;
}
.suggestion-card:hover {
  border-color: #6366f1; transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(99,102,241,0.1);
}
.suggestion-card i { font-size: 1.1rem; color: #6366f1; margin-top: 0.1rem; }
.suggestion-card strong { display: block; font-size: 0.82rem; color: #1e293b; }
.suggestion-card span { font-size: 0.72rem; color: #64748b; }

.welcome-input-wrapper { max-width: 640px; width: 100%; }

/* Chat Header */
.chat-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: white;
}
.chat-header-left { display: flex; align-items: center; gap: 0.65rem; }
.chat-title {
  font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0;
  outline: none; cursor: text; padding: 0.1rem 0.3rem; border-radius: 4px;
}
.chat-title:focus { background: #f1f5f9; }
.chat-provider { font-size: 0.65rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.header-select {
  font-size: 0.72rem; padding: 0.3rem 0.5rem; border: 1.5px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #475569; outline: none; cursor: pointer;
}
.header-select:focus { border-color: #6366f1; }
.mobile-menu { display: none; }

/* Messages */
.messages-container {
  flex: 1; overflow-y: auto; padding: 1.5rem;
  display: flex; flex-direction: column; gap: 1.25rem;
}
.message { display: flex; gap: 0.75rem; max-width: 85%; }
.message.user { align-self: flex-end; flex-direction: row-reverse; }
.message.assistant { align-self: flex-start; }
.message-avatar {
  width: 36px; height: 36px; min-width: 36px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; flex-shrink: 0;
}
.message.assistant .message-avatar {
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
}
.message.user .message-avatar {
  background: #e2e8f0; color: #475569;
}
.message-body { min-width: 0; }
.message-content {
  padding: 0.75rem 1rem; border-radius: 14px;
  font-size: 0.85rem; line-height: 1.65; word-wrap: break-word;
}
.message.assistant .message-content {
  background: white; color: #334155;
  border: 1px solid #e2e8f0; border-top-left-radius: 4px;
}
.message.user .message-content {
  background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
  border-top-right-radius: 4px;
}
.message-content :deep(code) {
  background: rgba(0,0,0,0.06); padding: 0.1rem 0.3rem; border-radius: 4px;
  font-size: 0.8rem; font-family: 'SF Mono', monospace;
}
.message-content :deep(pre) {
  background: #1e293b; color: #e2e8f0; padding: 0.75rem; border-radius: 8px;
  overflow-x: auto; margin: 0.5rem 0;
}
.message-content :deep(pre code) { background: none; padding: 0; color: inherit; }
.message-content :deep(strong) { font-weight: 700; }

.message-footer {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.2rem 0.3rem 0;
}
.msg-time { font-size: 0.58rem; color: #94a3b8; }
.msg-provider { font-size: 0.58rem; color: #6366f1; font-weight: 600; }
.msg-tokens { font-size: 0.55rem; color: #cbd5e1; }
.message-footer .btn-icon-sm { color: #cbd5e1; width: 20px; height: 20px; font-size: 0.6rem; }
.message-footer .btn-icon-sm:hover { color: #6366f1; background: #eef2ff; }

/* Thinking Dots */
.thinking-dots {
  display: flex; gap: 0.3rem; padding: 0.75rem 1rem;
  background: white; border: 1px solid #e2e8f0;
  border-radius: 14px; border-top-left-radius: 4px;
}
.thinking-dots span {
  width: 8px; height: 8px; background: #6366f1; border-radius: 50%;
  animation: dotPulse 1.4s infinite ease-in-out;
}
.thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
.thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes dotPulse {
  0%, 80%, 100% { transform: scale(0.5); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* Error */
.chat-error {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.6rem 0.85rem; background: #fef2f2; border: 1px solid #fecaca;
  border-radius: 10px; font-size: 0.78rem; color: #dc2626;
  align-self: center;
}

/* Input Bar */
.chat-input-section { padding: 0.75rem 1.5rem 1rem; background: #f8fafc; }
.chat-input-bar {
  display: flex; align-items: flex-end; gap: 0.5rem;
  background: white; border: 2px solid #e2e8f0; border-radius: 14px;
  padding: 0.5rem 0.5rem 0.5rem 1rem;
  transition: border-color 0.2s;
}
.chat-input-bar:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.chat-input-bar textarea {
  flex: 1; border: none; outline: none; resize: none;
  font-size: 0.88rem; line-height: 1.5; color: #334155;
  background: transparent; font-family: inherit;
  max-height: 200px;
}
.chat-input-bar textarea::placeholder { color: #94a3b8; }
.btn-send {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; transition: all 0.2s; flex-shrink: 0;
}
.btn-send:hover:not(:disabled) { transform: scale(1.05); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.btn-send:disabled { opacity: 0.4; cursor: not-allowed; }
.input-hint { text-align: center; margin-top: 0.3rem; font-size: 0.6rem; color: #94a3b8; }

.welcome-input { max-width: 640px; }

/* Responsive */
@media (max-width: 768px) {
  .chat-sidebar { position: fixed; left: 0; top: 56px; bottom: 0; z-index: 50; }
  .chat-sidebar.collapsed { left: -300px; }
  .mobile-menu { display: flex; }
  .welcome-suggestions { grid-template-columns: 1fr; }
  .message { max-width: 95%; }
}
</style>
