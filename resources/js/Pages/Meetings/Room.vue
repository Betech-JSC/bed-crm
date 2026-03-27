<template>
  <div class="room-wrap">
    <!-- Top Bar -->
    <div class="room-topbar">
      <div class="topbar-left">
        <Link href="/meetings"><button class="topbar-btn"><i class="pi pi-arrow-left" /></button></Link>
        <div class="topbar-info">
          <h2 class="room-title">{{ meeting.title }}</h2>
          <div class="room-meta">
            <span class="room-id"><i class="pi pi-hashtag" /> {{ meeting.room_code }}</span>
            <span class="status-dot" :class="roomStatus" />
            <span class="status-txt">{{ statusLabel }}</span>
            <span v-if="isLive" class="timer">{{ elapsedTime }}</span>
          </div>
        </div>
      </div>
      <div class="topbar-right">
        <span class="participants-count"><i class="pi pi-users" /> {{ participantCount }}</span>
        <button v-if="meeting.is_host && !isLive" class="topbar-action start-btn" @click="startMeeting">
          <i class="pi pi-play" /> Bắt đầu
        </button>
        <button v-if="isLive" class="topbar-action end-btn" @click="endMeeting">
          <i class="pi pi-phone" /> Kết thúc
        </button>
      </div>
    </div>

    <!-- Main Area -->
    <div class="room-main">
      <!-- Video Area -->
      <div class="video-area">
        <!-- Main video -->
        <div class="video-stage">
          <div class="video-box main-video">
            <video ref="localVideo" autoplay muted playsinline class="video-el" />
            <div class="video-label">
              <span class="video-name">{{ currentUser.name }} (Bạn)</span>
              <span v-if="isMuted" class="mute-icon"><i class="pi pi-microphone-slash" /></span>
            </div>
            <div v-if="!cameraOn" class="video-off-overlay">
              <div class="avatar-circle">{{ currentUser.name.charAt(0) }}</div>
            </div>
          </div>
        </div>

        <!-- Controls Bar -->
        <div class="controls-bar">
          <button class="ctrl-btn" :class="{ off: isMuted }" @click="toggleMic" :title="isMuted ? 'Bật mic' : 'Tắt mic'">
            <i :class="isMuted ? 'pi pi-microphone-slash' : 'pi pi-microphone'" />
            <span>{{ isMuted ? 'Unmute' : 'Mute' }}</span>
          </button>
          <button class="ctrl-btn" :class="{ off: !cameraOn }" @click="toggleCamera" :title="cameraOn ? 'Tắt camera' : 'Bật camera'">
            <i :class="cameraOn ? 'pi pi-video' : 'pi pi-eye-slash'" />
            <span>{{ cameraOn ? 'Camera' : 'Camera Off' }}</span>
          </button>
          <button class="ctrl-btn" :class="{ active: screenSharing }" @click="toggleScreenShare">
            <i class="pi pi-desktop" />
            <span>{{ screenSharing ? 'Dừng share' : 'Chia sẻ' }}</span>
          </button>
          <button class="ctrl-btn" :class="{ active: isRecording, rec: isRecording }" @click="toggleRecording">
            <i class="pi pi-circle-fill" />
            <span>{{ isRecording ? 'Dừng REC' : 'Ghi hình' }}</span>
          </button>
          <div class="ctrl-sep" />
          <button class="ctrl-btn" :class="{ active: showNotes }" @click="showNotes = !showNotes; showChat = false">
            <i class="pi pi-file-edit" />
            <span>Notes</span>
          </button>
          <button class="ctrl-btn" :class="{ active: showChat }" @click="showChat = !showChat; showNotes = false">
            <i class="pi pi-comments" />
            <span>Chat</span>
          </button>
          <div class="ctrl-sep" />
          <button class="ctrl-btn end" @click="endMeeting" v-if="isLive">
            <i class="pi pi-phone" />
            <span>Kết thúc</span>
          </button>
        </div>
      </div>

      <!-- Side Panel -->
      <div class="side-panel" v-if="showNotes || showChat">
        <!-- Notes -->
        <div v-if="showNotes" class="panel-content">
          <div class="panel-header">
            <h3><i class="pi pi-file-edit" /> Meeting Notes</h3>
            <button class="panel-close" @click="showNotes = false"><i class="pi pi-times" /></button>
          </div>

          <!-- Agenda -->
          <div v-if="meeting.agenda" class="agenda-block">
            <h4><i class="pi pi-list" /> Agenda</h4>
            <pre class="agenda-text">{{ meeting.agenda }}</pre>
          </div>

          <!-- Notes editor -->
          <div class="notes-editor">
            <textarea v-model="meetingNotes" class="notes-textarea" placeholder="Ghi chú cuộc họp... &#10;&#10;- Điểm thảo luận&#10;- Quyết định&#10;- Action items" @blur="saveNotes" />
          </div>

          <div class="notes-footer">
            <button class="save-notes-btn" @click="saveNotes" :disabled="isSavingNotes">
              <i :class="isSavingNotes ? 'pi pi-spin pi-spinner' : 'pi pi-save'" />
              {{ isSavingNotes ? 'Đang lưu...' : 'Lưu notes' }}
            </button>
          </div>
        </div>

        <!-- Chat -->
        <div v-if="showChat" class="panel-content">
          <div class="panel-header">
            <h3><i class="pi pi-comments" /> Chat</h3>
            <button class="panel-close" @click="showChat = false"><i class="pi pi-times" /></button>
          </div>

          <div class="chat-messages" ref="chatMessages">
            <div v-for="(msg, i) in chatMessages" :key="i" class="chat-msg" :class="{ own: msg.isOwn }">
              <span class="chat-sender">{{ msg.sender }}</span>
              <span class="chat-text">{{ msg.text }}</span>
              <span class="chat-time">{{ msg.time }}</span>
            </div>
            <div v-if="!chatMessages.length" class="chat-empty">
              <i class="pi pi-comments" />
              <p>Chưa có tin nhắn</p>
            </div>
          </div>

          <div class="chat-input-row">
            <input v-model="chatInput" type="text" placeholder="Nhắn tin..." @keyup.enter="sendMessage" class="chat-input" />
            <button class="chat-send" @click="sendMessage"><i class="pi pi-send" /></button>
          </div>
        </div>
      </div>
    </div>

    <!-- End Meeting Modal -->
    <div v-if="showEndModal" class="modal-overlay" @click.self="showEndModal = false">
      <div class="end-modal">
        <div class="end-icon"><i class="pi pi-check-circle" /></div>
        <h3>Cuộc họp đã kết thúc</h3>
        <p>Thời lượng: <strong>{{ elapsedTime }}</strong></p>
        <div v-if="recordedBlob" class="recording-info">
          <i class="pi pi-video" /> Recording đã lưu ({{ recordingSizeMB }}MB)
        </div>
        <div class="end-actions">
          <Link :href="`/meetings/${meeting.id}/recap`">
            <button class="end-btn-recap"><i class="pi pi-sparkles" /> Xem AI Recap</button>
          </Link>
          <Link href="/meetings">
            <button class="end-btn-back"><i class="pi pi-arrow-left" /> Về Dashboard</button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'

export default {
  components: { Head, Link },
  props: { meeting: Object, currentUser: Object },
  data() {
    return {
      // Media states
      localStream: null,
      screenStream: null,
      isMuted: false,
      cameraOn: true,
      screenSharing: false,

      // Recording
      isRecording: false,
      mediaRecorder: null,
      recordedChunks: [],
      recordedBlob: null,
      recordingSizeMB: 0,

      // UI
      showNotes: false,
      showChat: false,
      showEndModal: false,
      isLive: this.meeting.status === 'live',

      // Notes
      meetingNotes: this.meeting.meeting_notes || '',
      isSavingNotes: false,

      // Chat
      chatInput: '',
      chatMessages: [],

      // Timer
      startTime: this.meeting.status === 'live' ? Date.now() : null,
      elapsed: 0,
      timerInterval: null,
    }
  },
  computed: {
    roomStatus() {
      return this.isLive ? 'live' : 'waiting'
    },
    statusLabel() {
      return this.isLive ? 'Đang diễn ra' : 'Phòng chờ'
    },
    participantCount() {
      return (this.meeting.participants || []).length
    },
    elapsedTime() {
      const s = Math.floor(this.elapsed / 1000)
      const h = Math.floor(s / 3600)
      const m = Math.floor((s % 3600) / 60)
      const sec = s % 60
      return h > 0
        ? `${h}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
        : `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
    },
  },
  mounted() {
    this.initMedia()
    if (this.isLive) this.startTimer()
  },
  beforeUnmount() {
    this.cleanup()
  },
  methods: {
    async initMedia() {
      try {
        this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        if (this.$refs.localVideo) {
          this.$refs.localVideo.srcObject = this.localStream
        }
      } catch (e) {
        console.warn('Camera/Mic not available:', e.message)
        // Try audio only
        try {
          this.localStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true })
          this.cameraOn = false
        } catch (e2) {
          console.warn('No media devices:', e2.message)
        }
      }
    },

    toggleMic() {
      if (this.localStream) {
        this.localStream.getAudioTracks().forEach(t => t.enabled = !t.enabled)
        this.isMuted = !this.isMuted
      }
    },

    toggleCamera() {
      if (this.localStream) {
        this.localStream.getVideoTracks().forEach(t => t.enabled = !t.enabled)
        this.cameraOn = !this.cameraOn
      }
    },

    async toggleScreenShare() {
      if (this.screenSharing) {
        // Stop screen share
        if (this.screenStream) {
          this.screenStream.getTracks().forEach(t => t.stop())
          this.screenStream = null
        }
        // Restore camera to video element
        if (this.$refs.localVideo && this.localStream) {
          this.$refs.localVideo.srcObject = this.localStream
        }
        this.screenSharing = false
      } else {
        try {
          this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true })
          if (this.$refs.localVideo) {
            this.$refs.localVideo.srcObject = this.screenStream
          }
          this.screenSharing = true
          // When user stops sharing from browser UI
          this.screenStream.getVideoTracks()[0].onended = () => {
            this.toggleScreenShare()
          }
        } catch (e) {
          console.warn('Screen share cancelled:', e.message)
        }
      }
    },

    toggleRecording() {
      if (this.isRecording) {
        this.stopRecording()
      } else {
        this.startRecording()
      }
    },

    startRecording() {
      const stream = this.screenSharing ? this.screenStream : this.localStream
      if (!stream) return

      this.recordedChunks = []
      const options = { mimeType: 'video/webm;codecs=vp9,opus' }
      try {
        this.mediaRecorder = new MediaRecorder(stream, options)
      } catch (e) {
        try {
          this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm' })
        } catch (e2) {
          alert('Trình duyệt không hỗ trợ ghi hình')
          return
        }
      }

      this.mediaRecorder.ondataavailable = (e) => {
        if (e.data.size > 0) this.recordedChunks.push(e.data)
      }

      this.mediaRecorder.onstop = () => {
        this.recordedBlob = new Blob(this.recordedChunks, { type: 'video/webm' })
        this.recordingSizeMB = (this.recordedBlob.size / (1024 * 1024)).toFixed(1)
        this.downloadRecording()
      }

      this.mediaRecorder.start(1000)
      this.isRecording = true
    },

    stopRecording() {
      if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
        this.mediaRecorder.stop()
      }
      this.isRecording = false
    },

    downloadRecording() {
      if (!this.recordedBlob) return
      const url = URL.createObjectURL(this.recordedBlob)
      const a = document.createElement('a')
      a.href = url
      a.download = `meeting-${this.meeting.room_code}-${Date.now()}.webm`
      a.click()
      URL.revokeObjectURL(url)
    },

    async startMeeting() {
      try {
        await axios.post(`/meetings/${this.meeting.id}/start`)
        this.isLive = true
        this.startTimer()
        if (this.meeting.record_enabled) this.startRecording()
      } catch (e) {
        alert('Lỗi bắt đầu cuộc họp')
      }
    },

    async endMeeting() {
      if (this.isRecording) this.stopRecording()

      try {
        const { data } = await axios.post(`/meetings/${this.meeting.id}/end`)
        this.isLive = false
        this.stopTimer()
        this.showEndModal = true
        // Save notes if any
        if (this.meetingNotes) await this.saveNotes()
      } catch (e) {
        alert('Lỗi kết thúc cuộc họp')
      }
    },

    async saveNotes() {
      this.isSavingNotes = true
      try {
        await axios.post(`/meetings/${this.meeting.id}/notes`, { notes: this.meetingNotes })
      } catch (e) { console.error(e) }
      this.isSavingNotes = false
    },

    sendMessage() {
      if (!this.chatInput.trim()) return
      this.chatMessages.push({
        sender: this.currentUser.name,
        text: this.chatInput,
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
        isOwn: true,
      })
      this.chatInput = ''
      this.$nextTick(() => {
        const el = this.$refs.chatMessages
        if (el) el.scrollTop = el.scrollHeight
      })
    },

    startTimer() {
      this.startTime = this.startTime || Date.now()
      this.timerInterval = setInterval(() => {
        this.elapsed = Date.now() - this.startTime
      }, 1000)
    },

    stopTimer() {
      clearInterval(this.timerInterval)
    },

    cleanup() {
      if (this.localStream) this.localStream.getTracks().forEach(t => t.stop())
      if (this.screenStream) this.screenStream.getTracks().forEach(t => t.stop())
      this.stopTimer()
      if (this.isRecording) this.stopRecording()
    },
  },
}
</script>

<style scoped>
.room-wrap { height: 100vh; display: flex; flex-direction: column; background: #0f172a; color: white; margin: -1.5rem; padding: 0; overflow: hidden; }

/* Top bar */
.room-topbar { display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 1rem; background: rgba(15,23,42,0.95); border-bottom: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.topbar-left { display: flex; align-items: center; gap: 0.65rem; }
.topbar-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: rgba(255,255,255,0.6); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.topbar-btn:hover { border-color: rgba(255,255,255,0.2); color: white; }
.room-title { font-size: 0.9rem; font-weight: 700; margin: 0; }
.room-meta { display: flex; align-items: center; gap: 0.4rem; font-size: 0.62rem; color: rgba(255,255,255,0.5); }
.room-id { color: #818cf8; font-weight: 600; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; }
.status-dot.live { background: #ef4444; animation: livePulse 1s ease-in-out infinite; }
.status-dot.waiting { background: #f59e0b; }
.status-txt { font-weight: 500; }
.timer { background: rgba(239,68,68,0.15); color: #fca5a5; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700; font-family: 'SF Mono', monospace; }
@keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.topbar-right { display: flex; align-items: center; gap: 0.5rem; }
.participants-count { font-size: 0.72rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; gap: 0.2rem; }
.participants-count i { font-size: 0.65rem; }
.topbar-action { padding: 0.4rem 0.8rem; border-radius: 8px; border: none; font-size: 0.72rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; }
.start-btn { background: #10b981; color: white; }
.start-btn:hover { background: #059669; }
.end-btn { background: #ef4444; color: white; }
.end-btn:hover { background: #dc2626; }

/* Main */
.room-main { flex: 1; display: flex; min-height: 0; }
.video-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* Video Stage */
.video-stage { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.video-box { position: relative; border-radius: 14px; overflow: hidden; background: #1e293b; }
.main-video { width: 100%; max-width: 900px; aspect-ratio: 16/9; }
.video-el { width: 100%; height: 100%; object-fit: cover; display: block; background: #1e293b; }
.video-label { position: absolute; bottom: 0.5rem; left: 0.5rem; display: flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.55rem; border-radius: 6px; background: rgba(0,0,0,0.6); }
.video-name { font-size: 0.65rem; font-weight: 600; }
.mute-icon { color: #ef4444; font-size: 0.6rem; }
.video-off-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: #1e293b; }
.avatar-circle { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; }

/* Controls */
.controls-bar { display: flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.65rem 1rem; background: rgba(15,23,42,0.95); border-top: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; }
.ctrl-btn { display: flex; flex-direction: column; align-items: center; gap: 0.15rem; padding: 0.5rem 0.8rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.7); cursor: pointer; transition: all 0.15s; font-family: inherit; min-width: 55px; }
.ctrl-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.ctrl-btn i { font-size: 1rem; }
.ctrl-btn span { font-size: 0.5rem; font-weight: 500; }
.ctrl-btn.off { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #fca5a5; }
.ctrl-btn.active { background: rgba(99,102,241,0.15); border-color: rgba(99,102,241,0.3); color: #a5b4fc; }
.ctrl-btn.rec { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.4); color: #ef4444; }
.ctrl-btn.rec i { animation: livePulse 1s ease-in-out infinite; }
.ctrl-btn.end { background: #ef4444; border-color: #ef4444; color: white; }
.ctrl-btn.end:hover { background: #dc2626; }
.ctrl-sep { width: 1px; height: 30px; background: rgba(255,255,255,0.08); margin: 0 0.25rem; }

/* Side Panel */
.side-panel { width: 320px; background: rgba(15,23,42,0.95); border-left: 1px solid rgba(255,255,255,0.06); display: flex; flex-direction: column; flex-shrink: 0; }
.panel-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.panel-header { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.06); }
.panel-header h3 { font-size: 0.82rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.3rem; }
.panel-header h3 i { color: #818cf8; font-size: 0.75rem; }
.panel-close { width: 26px; height: 26px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: rgba(255,255,255,0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; }

/* Agenda */
.agenda-block { padding: 0.65rem 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.06); }
.agenda-block h4 { font-size: 0.68rem; font-weight: 600; color: #818cf8; margin: 0 0 0.35rem; display: flex; align-items: center; gap: 0.25rem; }
.agenda-block h4 i { font-size: 0.6rem; }
.agenda-text { font-size: 0.68rem; color: rgba(255,255,255,0.6); margin: 0; white-space: pre-wrap; font-family: inherit; line-height: 1.5; }

/* Notes */
.notes-editor { flex: 1; padding: 0.65rem 0.85rem; }
.notes-textarea { width: 100%; height: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 0.55rem; color: rgba(255,255,255,0.85); font-size: 0.75rem; line-height: 1.6; resize: none; outline: none; font-family: inherit; }
.notes-textarea:focus { border-color: rgba(99,102,241,0.3); }
.notes-textarea::placeholder { color: rgba(255,255,255,0.2); }
.notes-footer { padding: 0.5rem 0.85rem; border-top: 1px solid rgba(255,255,255,0.06); }
.save-notes-btn { width: 100%; padding: 0.4rem; border-radius: 8px; background: #6366f1; border: none; color: white; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.3rem; font-family: inherit; }
.save-notes-btn:disabled { opacity: 0.6; }

/* Chat */
.chat-messages { flex: 1; padding: 0.65rem 0.85rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.4rem; }
.chat-msg { display: flex; flex-direction: column; padding: 0.35rem 0.55rem; border-radius: 8px; background: rgba(255,255,255,0.04); max-width: 85%; }
.chat-msg.own { align-self: flex-end; background: rgba(99,102,241,0.15); }
.chat-sender { font-size: 0.55rem; font-weight: 600; color: #818cf8; }
.chat-text { font-size: 0.72rem; color: rgba(255,255,255,0.85); line-height: 1.4; }
.chat-time { font-size: 0.5rem; color: rgba(255,255,255,0.3); text-align: right; }
.chat-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: rgba(255,255,255,0.2); }
.chat-empty i { font-size: 1.5rem; margin-bottom: 0.3rem; }
.chat-empty p { font-size: 0.68rem; margin: 0; }
.chat-input-row { display: flex; gap: 0.3rem; padding: 0.5rem 0.85rem; border-top: 1px solid rgba(255,255,255,0.06); }
.chat-input { flex: 1; padding: 0.4rem 0.65rem; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; background: rgba(255,255,255,0.04); color: white; font-size: 0.75rem; outline: none; font-family: inherit; }
.chat-input:focus { border-color: rgba(99,102,241,0.3); }
.chat-input::placeholder { color: rgba(255,255,255,0.2); }
.chat-send { width: 34px; height: 34px; border-radius: 8px; background: #6366f1; border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; }
.chat-send:hover { background: #4f46e5; }

/* End Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; backdrop-filter: blur(6px); }
.end-modal { background: #1e293b; border-radius: 18px; padding: 2rem; text-align: center; width: 380px; max-width: 90vw; border: 1px solid rgba(255,255,255,0.06); }
.end-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(16,185,129,0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; }
.end-icon i { font-size: 1.5rem; color: #10b981; }
.end-modal h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.3rem; }
.end-modal p { font-size: 0.82rem; color: rgba(255,255,255,0.6); margin: 0 0 0.75rem; }
.end-modal p strong { color: white; }
.recording-info { font-size: 0.72rem; color: #818cf8; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; }
.recording-info i { font-size: 0.65rem; }
.end-actions { display: flex; flex-direction: column; gap: 0.4rem; }
.end-btn-recap { width: 100%; padding: 0.55rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.3rem; font-family: inherit; }
.end-btn-recap:hover { transform: translateY(-1px); }
.end-btn-back { width: 100%; padding: 0.55rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: rgba(255,255,255,0.6); font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.3rem; font-family: inherit; }
.end-btn-back:hover { background: rgba(255,255,255,0.05); }
</style>
