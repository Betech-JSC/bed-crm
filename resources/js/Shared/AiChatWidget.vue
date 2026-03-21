<template>
  <div class="ai-widget" :class="{ open: isOpen }">
    <!-- Floating Button -->
    <button class="widget-fab" @click="toggle" :class="{ active: isOpen }">
      <i :class="isOpen ? 'pi pi-times' : 'pi pi-sparkles'" />
      <span v-if="!isOpen" class="fab-pulse" />
    </button>

    <!-- Chat Panel -->
    <transition name="widget-slide">
      <div v-if="isOpen" class="widget-panel">
        <!-- Panel Header -->
        <div class="panel-header">
          <div class="panel-header-left">
            <div class="panel-logo">
              <i class="pi pi-sparkles" />
            </div>
            <div>
              <h4 class="panel-title">AI Assistant</h4>
              <span class="panel-status" :class="{ online: hasProvider }">
                <i class="pi pi-circle-fill" /> {{ hasProvider ? 'Online' : 'Chưa cấu hình' }}
              </span>
            </div>
          </div>
          <div class="panel-header-actions">
            <button class="panel-btn" @click="openFullChat" title="Mở rộng">
              <i class="pi pi-external-link" />
            </button>
            <button class="panel-btn" @click="clearChat" title="Xóa chat">
              <i class="pi pi-trash" />
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div class="panel-messages" ref="panelMessages">
          <!-- Welcome if empty -->
          <div v-if="!messages.length" class="widget-welcome">
            <div class="welcome-ai-icon"><i class="pi pi-sparkles" /></div>
            <p class="welcome-text">Xin chào! Tôi là AI Assistant.<br>Hãy hỏi tôi bất kỳ điều gì.</p>
            <div class="quick-actions">
              <button v-for="(q, i) in quickActions" :key="i" class="quick-btn" @click="sendQuick(q.prompt)">
                <i :class="q.icon" /> {{ q.label }}
              </button>
            </div>
          </div>

          <!-- Messages -->
          <div v-for="msg in messages" :key="msg.id" class="w-message" :class="msg.role">
            <div class="w-message-avatar">
              <i :class="msg.role === 'assistant' ? 'pi pi-sparkles' : 'pi pi-user'" />
            </div>
            <div class="w-message-bubble">
              <div class="w-message-text" v-html="formatMsg(msg.content)" />
              <span class="w-message-time">{{ msg.time }}</span>
            </div>
          </div>

          <!-- Typing -->
          <div v-if="isThinking" class="w-message assistant">
            <div class="w-message-avatar"><i class="pi pi-sparkles" /></div>
            <div class="w-message-bubble">
              <div class="w-typing"><span /><span /><span /></div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="panel-input">
          <textarea
            v-model="input"
            @keydown.enter.exact.prevent="send"
            placeholder="Hỏi AI..."
            rows="1"
            ref="widgetInput"
            :disabled="isThinking"
          />
          <button class="send-btn" @click="send" :disabled="!input.trim() || isThinking">
            <i :class="isThinking ? 'pi pi-spin pi-spinner' : 'pi pi-send'" />
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'AiChatWidget',
  data() {
    return {
      isOpen: false,
      input: '',
      messages: [],
      isThinking: false,
      conversationId: null,
      msgIdCounter: 0,
      quickActions: [
        { icon: 'pi pi-chart-line', label: 'Phân tích', prompt: 'Phân tích hiệu suất kinh doanh tháng này' },
        { icon: 'pi pi-envelope', label: 'Viết email', prompt: 'Giúp tôi viết email chào hàng chuyên nghiệp' },
        { icon: 'pi pi-lightbulb', label: 'Tư vấn', prompt: 'Tư vấn cách cải thiện tỷ lệ chốt deal' },
      ],
    }
  },
  computed: {
    hasProvider() {
      return true // Widget always shows; actual check happens on send
    },
  },
  methods: {
    toggle() {
      this.isOpen = !this.isOpen
      if (this.isOpen) {
        this.$nextTick(() => this.$refs.widgetInput?.focus())
      }
    },

    async send() {
      const text = this.input.trim()
      if (!text || this.isThinking) return

      this.input = ''
      this.addMessage('user', text)
      this.isThinking = true
      await this.$nextTick()
      this.scrollBottom()

      try {
        // Create conversation if none
        if (!this.conversationId) {
          const res = await fetch('/ai-chat', {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify({ title: text.substring(0, 60) }),
          })
          const data = await res.json()
          this.conversationId = data.id
        }

        // Send message
        const res = await fetch(`/ai-chat/${this.conversationId}/messages`, {
          method: 'POST',
          headers: this.headers(),
          body: JSON.stringify({ message: text }),
        })

        if (!res.ok) {
          const err = await res.json()
          throw new Error(err.message || 'Lỗi server')
        }

        const data = await res.json()
        this.addMessage('assistant', data.ai_message.content, data.ai_message.provider)
      } catch (e) {
        this.addMessage('assistant', '⚠️ ' + e.message)
      } finally {
        this.isThinking = false
        await this.$nextTick()
        this.scrollBottom()
      }
    },

    sendQuick(prompt) {
      this.input = prompt
      this.send()
    },

    addMessage(role, content, provider = null) {
      this.messages.push({
        id: ++this.msgIdCounter,
        role,
        content,
        provider,
        time: new Date().toLocaleTimeString('vi', { hour: '2-digit', minute: '2-digit' }),
      })
    },

    clearChat() {
      this.messages = []
      this.conversationId = null
    },

    openFullChat() {
      window.location.href = '/ai-chat'
    },

    formatMsg(content) {
      if (!content) return ''
      return content
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\n/g, '<br>')
    },

    scrollBottom() {
      const el = this.$refs.panelMessages
      if (el) el.scrollTop = el.scrollHeight
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
/* ===== Floating Action Button ===== */
.ai-widget {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  font-family: 'Inter', -apple-system, sans-serif;
}

.widget-fab {
  width: 56px; height: 56px; border-radius: 16px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; position: relative;
  box-shadow: 0 6px 24px rgba(99,102,241,0.35);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.widget-fab:hover {
  transform: scale(1.08);
  box-shadow: 0 8px 30px rgba(99,102,241,0.45);
}
.widget-fab.active {
  background: #475569; border-radius: 14px;
  transform: rotate(0deg);
}

.fab-pulse {
  position: absolute; inset: -4px;
  border-radius: 20px; border: 2px solid #6366f1;
  animation: fabPulse 2s ease-in-out infinite;
}
@keyframes fabPulse {
  0%, 100% { transform: scale(1); opacity: 0.6; }
  50% { transform: scale(1.15); opacity: 0; }
}

/* ===== Panel ===== */
.widget-panel {
  position: absolute; bottom: 70px; right: 0;
  width: 380px; height: 520px;
  background: white; border-radius: 20px;
  box-shadow: 0 12px 48px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
  display: flex; flex-direction: column;
  overflow: hidden;
}

/* Slide transition */
.widget-slide-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.widget-slide-leave-active { transition: all 0.2s ease-in; }
.widget-slide-enter-from { opacity: 0; transform: translateY(20px) scale(0.95); }
.widget-slide-leave-to { opacity: 0; transform: translateY(10px) scale(0.98); }

/* Panel Header */
.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.85rem 1rem;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white;
}
.panel-header-left { display: flex; align-items: center; gap: 0.6rem; }
.panel-logo {
  width: 38px; height: 38px; border-radius: 12px;
  background: rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.panel-title { font-size: 0.88rem; font-weight: 700; margin: 0; }
.panel-status {
  font-size: 0.6rem; display: flex; align-items: center; gap: 0.2rem;
  opacity: 0.7;
}
.panel-status i { font-size: 0.4rem; }
.panel-status.online i { color: #4ade80; }
.panel-header-actions { display: flex; gap: 0.25rem; }
.panel-btn {
  background: rgba(255,255,255,0.1); border: none; color: white;
  width: 30px; height: 30px; border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; transition: all 0.2s;
}
.panel-btn:hover { background: rgba(255,255,255,0.2); }

/* Messages Area */
.panel-messages {
  flex: 1; overflow-y: auto; padding: 1rem;
  display: flex; flex-direction: column; gap: 0.75rem;
  background: #f8fafc;
}

/* Welcome */
.widget-welcome {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; flex: 1; text-align: center;
  padding: 1rem;
}
.welcome-ai-icon {
  width: 52px; height: 52px; border-radius: 16px;
  background: linear-gradient(135deg, #6366f1, #a78bfa);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: white; margin-bottom: 0.75rem;
  animation: float 3s ease-in-out infinite;
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}
.welcome-text {
  font-size: 0.82rem; color: #64748b; line-height: 1.6; margin: 0 0 1rem;
}
.quick-actions { display: flex; flex-direction: column; gap: 0.4rem; width: 100%; }
.quick-btn {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 0.75rem; background: white;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.75rem; color: #475569; cursor: pointer;
  transition: all 0.2s; text-align: left;
}
.quick-btn i { color: #6366f1; font-size: 0.8rem; }
.quick-btn:hover {
  border-color: #6366f1; background: #eef2ff;
  transform: translateX(3px);
}

/* Messages */
.w-message { display: flex; gap: 0.5rem; max-width: 90%; }
.w-message.user { align-self: flex-end; flex-direction: row-reverse; }
.w-message.assistant { align-self: flex-start; }
.w-message-avatar {
  width: 28px; height: 28px; min-width: 28px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; flex-shrink: 0;
}
.w-message.assistant .w-message-avatar { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.w-message.user .w-message-avatar { background: #e2e8f0; color: #475569; }

.w-message-bubble { min-width: 0; }
.w-message-text {
  padding: 0.55rem 0.75rem; border-radius: 12px;
  font-size: 0.8rem; line-height: 1.55; word-wrap: break-word;
}
.w-message.assistant .w-message-text {
  background: white; color: #334155; border: 1px solid #e2e8f0;
  border-top-left-radius: 4px;
}
.w-message.user .w-message-text {
  background: linear-gradient(135deg, #6366f1, #7c3aed); color: white;
  border-top-right-radius: 4px;
}
.w-message-text :deep(code) {
  background: rgba(0,0,0,0.06); padding: 0.1rem 0.2rem;
  border-radius: 3px; font-size: 0.75rem;
}
.w-message-text :deep(strong) { font-weight: 700; }
.w-message-time { font-size: 0.55rem; color: #94a3b8; margin-top: 0.15rem; display: block; }

/* Typing */
.w-typing {
  display: flex; gap: 0.2rem; padding: 0.6rem 0.75rem;
  background: white; border: 1px solid #e2e8f0;
  border-radius: 12px; border-top-left-radius: 4px;
}
.w-typing span {
  width: 6px; height: 6px; background: #6366f1; border-radius: 50%;
  animation: wTyping 1.4s infinite ease-in-out;
}
.w-typing span:nth-child(2) { animation-delay: 0.2s; }
.w-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes wTyping {
  0%, 80%, 100% { transform: scale(0.5); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* Input */
.panel-input {
  display: flex; align-items: flex-end; gap: 0.4rem;
  padding: 0.65rem 0.85rem; border-top: 1px solid #e2e8f0;
  background: white;
}
.panel-input textarea {
  flex: 1; border: 1.5px solid #e2e8f0; border-radius: 10px;
  padding: 0.5rem 0.7rem; font-size: 0.82rem; resize: none;
  outline: none; font-family: inherit; color: #334155;
  max-height: 100px; line-height: 1.4; background: #f8fafc;
  transition: border-color 0.2s;
}
.panel-input textarea:focus { border-color: #6366f1; background: white; }
.panel-input textarea::placeholder { color: #94a3b8; }
.send-btn {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #6366f1, #7c3aed);
  color: white; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; transition: all 0.2s; flex-shrink: 0;
}
.send-btn:hover:not(:disabled) { transform: scale(1.05); }
.send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Mobile */
@media (max-width: 480px) {
  .widget-panel {
    width: calc(100vw - 2rem);
    height: calc(100vh - 8rem);
    bottom: 65px; right: -0.5rem;
  }
}
</style>
