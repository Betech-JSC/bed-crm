(function() {
'use strict';

const WIDGET_KEY = '{{ $widgetKey }}';
const API_URL = '{{ $apiUrl }}';

// Prevent multiple initializations (but allow re-initialization for preview)
if (window.ChatWidget && window.ChatWidget.initialized && !window.ChatWidget.allowReinit) {
return;
}

// Generate visitor ID (persist in localStorage)
function getVisitorId() {
let visitorId = localStorage.getItem('chat_visitor_id');
if (!visitorId) {
visitorId = 'visitor_' + Math.random().toString(36).substring(2, 15) +
Math.random().toString(36).substring(2, 15);
localStorage.setItem('chat_visitor_id', visitorId);
}
return visitorId;
}

// Chat Widget Class
class ChatWidget {
constructor() {
this.visitorId = getVisitorId();
this.conversationId = null;
this.sessionId = null;
this.isOpen = false;
this.isInitialized = false;
this.messages = [];
this.widgetSettings = null;
this.init();
}

async init() {
console.log('[ChatWidget] Starting initialization...', { widgetKey: WIDGET_KEY, apiUrl: API_URL });
try {
const response = await fetch(`${API_URL}/${WIDGET_KEY}/init`, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'Origin': window.location.origin,
'Referer': window.location.href,
},
body: JSON.stringify({
visitor_id: this.visitorId,
referrer: document.referrer,
page_url: window.location.href,
}),
});

console.log('[ChatWidget] Init API response status:', response.status, response.statusText);

if (!response.ok) {
const errorText = await response.text();
console.error('[ChatWidget] Failed to initialize chat widget:', response.status, errorText);
return;
}

const data = await response.json();
console.log('[ChatWidget] Init API response data:', data);

this.conversationId = data.conversation_id;
this.sessionId = data.session_id;
this.widgetSettings = data.widget_settings;

console.log('[ChatWidget] Creating widget UI...');
this.createWidget();
this.initBanners();
this.loadHistory();
this.isInitialized = true;
console.log('[ChatWidget] ✅ Initialization complete!');
} catch (error) {
console.error('[ChatWidget] ❌ Initialization error:', error);
console.error('[ChatWidget] Error details:', {
message: error.message,
stack: error.stack,
widgetKey: WIDGET_KEY,
apiUrl: API_URL
});
}
}

createWidget() {
// Create widget container
const widget = document.createElement('div');
widget.id = 'chat-widget-container';
widget.innerHTML = `
<div id="chat-widget-button" style="
                    position: fixed;
                    ${this.widgetSettings?.position === 'bottom-left' ? 'left: 20px;' : 'right: 20px;'}
                    bottom: 20px;
                    width: 60px;
                    height: 60px;
                    background-color: ${this.widgetSettings?.primary_color || '#ef6820'};
                    border-radius: 50%;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9998;
                    transition: transform 0.2s;
                ">
    <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
    </svg>
</div>
<div id="chat-widget-window" style="
                    position: fixed;
                    ${this.widgetSettings?.position === 'bottom-left' ? 'left: 20px;' : 'right: 20px;'}
                    bottom: 90px;
                    width: 380px;
                    max-width: calc(100vw - 40px);
                    height: 600px;
                    max-height: calc(100vh - 120px);
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                    display: none;
                    flex-direction: column;
                    z-index: 9999;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                ">
    <div id="chat-widget-header" style="
                        background: ${this.widgetSettings?.primary_color || '#ef6820'};
                        color: white;
                        padding: 16px;
                        border-radius: 12px 12px 0 0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
        <div>
            <div style="font-weight: 600; font-size: 16px;">Chat with us</div>
            <div style="font-size: 12px; opacity: 0.9;">We're here to help</div>
        </div>
        <button id="chat-widget-close" style="
                            background: none;
                            border: none;
                            color: white;
                            cursor: pointer;
                            font-size: 24px;
                            padding: 0;
                            width: 32px;
                            height: 32px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
    </div>
    <div id="chat-widget-banners" style="
                        display: ${this.widgetSettings?.show_banners && this.widgetSettings?.banners?.length > 0 ? 'block' : 'none'};
                        border-bottom: 1px solid #e5e7eb;
                        position: relative;
                        overflow: hidden;
                        max-height: 120px;
                    "></div>
    <div id="chat-widget-messages" style="
                        flex: 1;
                        overflow-y: auto;
                        padding: 16px;
                        display: flex;
                        flex-direction: column;
                        gap: 12px;
                    "></div>
    <div id="chat-widget-input-container" style="
                        padding: 16px;
                        border-top: 1px solid #e5e7eb;
                    ">
        <div id="chat-widget-form" style="display: ${this.widgetSettings?.collect_email || this.widgetSettings?.collect_phone ? 'none' : 'block'};">
            <div style="display: flex; gap: 8px;">
                <input
                    type="text"
                    id="chat-widget-input"
                    placeholder="Type your message..."
                    style="
                                        flex: 1;
                                        padding: 12px;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                        font-size: 14px;
                                        outline: none;
                                    " />
                <button
                    id="chat-widget-send"
                    style="
                                        padding: 12px 20px;
                                        background: ${this.widgetSettings?.primary_color || '#ef6820'};
                                        color: white;
                                        border: none;
                                        border-radius: 8px;
                                        cursor: pointer;
                                        font-weight: 500;
                                    ">Send</button>
            </div>
        </div>
        <div id="chat-widget-info-form" style="display: ${this.widgetSettings?.collect_email || this.widgetSettings?.collect_phone ? 'block' : 'none'};">
            <input
                type="text"
                id="chat-widget-name"
                placeholder="Your name"
                style="
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 14px;
                                    margin-bottom: 8px;
                                    outline: none;
                                " />
            ${this.widgetSettings?.collect_email ? `
            <input
                type="email"
                id="chat-widget-email"
                placeholder="Your email"
                style="
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 14px;
                                    margin-bottom: 8px;
                                    outline: none;
                                " />
            ` : ''}
            ${this.widgetSettings?.collect_phone ? `
            <input
                type="tel"
                id="chat-widget-phone"
                placeholder="Your phone"
                style="
                                    width: 100%;
                                    padding: 12px;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 14px;
                                    margin-bottom: 8px;
                                    outline: none;
                                " />
            ` : ''}
            <button
                id="chat-widget-start"
                style="
                                    width: 100%;
                                    padding: 12px;
                                    background: ${this.widgetSettings?.primary_color || '#ef6820'};
                                    color: white;
                                    border: none;
                                    border-radius: 8px;
                                    cursor: pointer;
                                    font-weight: 500;
                                ">Start Chat</button>
        </div>
    </div>
</div>
`;

document.body.appendChild(widget);

// Add event listeners
document.getElementById('chat-widget-button').addEventListener('click', () => this.toggle());
document.getElementById('chat-widget-close').addEventListener('click', () => this.close());
document.getElementById('chat-widget-send').addEventListener('click', () => this.sendMessage());
document.getElementById('chat-widget-start').addEventListener('click', () => this.startChat());

const input = document.getElementById('chat-widget-input');
if (input) {
input.addEventListener('keypress', (e) => {
if (e.key === 'Enter') {
this.sendMessage();
}
});
}

// Show welcome message
if (this.widgetSettings?.welcome_message) {
this.addMessage('assistant', this.widgetSettings.welcome_message);
}
}

initBanners() {
const bannersContainer = document.getElementById('chat-widget-banners');
if (!bannersContainer) return;

const banners = this.widgetSettings?.banners || [];
const showBanners = this.widgetSettings?.show_banners !== false;

if (!showBanners || banners.length === 0) {
bannersContainer.style.display = 'none';
return;
}

bannersContainer.style.display = 'block';

// Create banner slider
let currentBannerIndex = 0;
const rotationSeconds = this.widgetSettings?.banner_rotation_seconds || 5;

function renderBanner(index) {
if (banners.length === 0) return;

const banner = banners[index];
if (!banner) return;

bannersContainer.innerHTML = `
<div style="
position: relative;
width: 100%;
height: 100px;
background: linear-gradient(135deg, ${banner.bg_color || '#f3f4f6'} 0%, ${banner.bg_color_2 || banner.bg_color || '#e5e7eb'} 100%);
display: flex;
align-items: center;
justify-content: center;
padding: 12px 16px;
cursor: ${banner.link ? 'pointer' : 'default'};
transition: opacity 0.3s;
">
    ${banner.image ? `
    <img src="${banner.image}" alt="${banner.title || ''}" style="
max-width: 100%;
max-height: 80px;
object-fit: contain;
margin-right: ${banner.title || banner.text ? '12px' : '0'};
" />
    ` : ''}
    <div style="flex: 1; text-align: ${banner.image ? 'left' : 'center'};">
        ${banner.title ? `<div style="font-weight: 600; font-size: 14px; color: ${banner.text_color || '#1f2937'}; margin-bottom: 4px;">${banner.title}</div>` : ''}
        ${banner.text ? `<div style="font-size: 12px; color: ${banner.text_color || '#6b7280'}; line-height: 1.4;">${banner.text}</div>` : ''}
        ${banner.button_text ? `<div style="margin-top: 8px;"><span style="
display: inline-block;
padding: 4px 12px;
background: ${banner.button_color || this.widgetSettings?.primary_color || '#ef6820'};
color: white;
border-radius: 4px;
font-size: 12px;
font-weight: 500;
">${banner.button_text}</span></div>` : ''}
    </div>
    ${banners.length > 1 ? `
    <div style="position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); display: flex; gap: 4px;">
        ${banners.map((_, i) => `
        <span style="
width: 6px;
height: 6px;
border-radius: 50%;
background: ${i === index ? (this.widgetSettings?.primary_color || '#ef6820') : '#d1d5db'};
transition: background 0.3s;
"></span>
        `).join('')}
    </div>
    ` : ''}
</div>
`;

// Add click handler if link exists
if (banner.link) {
const bannerElement = bannersContainer.querySelector('div');
if (bannerElement) {
bannerElement.addEventListener('click', () => {
if (banner.link) {
window.open(banner.link, banner.link_target || '_blank');
}
});
}
}
}

// Initial render
renderBanner(0);

// Auto-rotate if multiple banners
if (banners.length > 1 && rotationSeconds > 0) {
this.bannerInterval = setInterval(() => {
currentBannerIndex = (currentBannerIndex + 1) % banners.length;
renderBanner(currentBannerIndex);
}, rotationSeconds * 1000);
}
}

toggle() {
this.isOpen = !this.isOpen;
const window = document.getElementById('chat-widget-window');
if (window) {
window.style.display = this.isOpen ? 'flex' : 'none';
}
}

close() {
this.isOpen = false;
const window = document.getElementById('chat-widget-window');
if (window) {
window.style.display = 'none';
}
// Clear banner interval when closing
if (this.bannerInterval) {
clearInterval(this.bannerInterval);
this.bannerInterval = null;
}
}

async startChat() {
const name = document.getElementById('chat-widget-name')?.value || '';
const email = document.getElementById('chat-widget-email')?.value || '';
const phone = document.getElementById('chat-widget-phone')?.value || '';

// Hide info form, show chat form
document.getElementById('chat-widget-info-form').style.display = 'none';
document.getElementById('chat-widget-form').style.display = 'block';

// Update visitor info via API
if (name || email || phone) {
await this.updateVisitorInfo(name, email, phone);
}
}

async updateVisitorInfo(name, email, phone) {
// This will be handled when sending first message
}

addMessage(role, content) {
const messagesContainer = document.getElementById('chat-widget-messages');
if (!messagesContainer) return;

const messageDiv = document.createElement('div');
messageDiv.style.cssText = `
padding: 12px 16px;
border-radius: 12px;
max-width: 80%;
word-wrap: break-word;
${role === 'user'
? 'background: #f3f4f6; margin-left: auto; text-align: right;'
: 'background: ' + (this.widgetSettings?.primary_color || '#ef6820') + '; color: white; margin-right: auto;'}
`;
messageDiv.textContent = content;
messagesContainer.appendChild(messageDiv);
messagesContainer.scrollTop = messagesContainer.scrollHeight;

this.messages.push({ role, content });
}

async sendMessage() {
const input = document.getElementById('chat-widget-input');
const message = input?.value.trim();

if (!message || !this.conversationId) return;

// Add user message to UI
this.addMessage('user', message);
input.value = '';

// Disable input while processing
input.disabled = true;
document.getElementById('chat-widget-send').disabled = true;

try {
const name = document.getElementById('chat-widget-name')?.value || '';
const email = document.getElementById('chat-widget-email')?.value || '';
const phone = document.getElementById('chat-widget-phone')?.value || '';

const response = await fetch(`${API_URL}/${WIDGET_KEY}/message`, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'Origin': window.location.origin,
},
body: JSON.stringify({
conversation_id: this.conversationId,
visitor_id: this.visitorId,
message: message,
visitor_name: name || undefined,
visitor_email: email || undefined,
visitor_phone: phone || undefined,
}),
});

if (!response.ok) {
throw new Error('Failed to send message');
}

const data = await response.json();
this.addMessage('assistant', data.content);

// Hide info form after first message
const infoForm = document.getElementById('chat-widget-info-form');
if (infoForm && infoForm.style.display !== 'none') {
infoForm.style.display = 'none';
document.getElementById('chat-widget-form').style.display = 'block';
}
} catch (error) {
console.error('Error sending message:', error);
this.addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
} finally {
input.disabled = false;
document.getElementById('chat-widget-send').disabled = false;
input.focus();
}
}

async loadHistory() {
if (!this.conversationId) return;

try {
const response = await fetch(`${API_URL}/${WIDGET_KEY}/history?conversation_id=${this.conversationId}&visitor_id=${this.visitorId}`, {
headers: {
'Origin': window.location.origin,
},
});

if (response.ok) {
const data = await response.json();
data.messages.forEach(msg => {
if (msg.role !== 'system') {
this.addMessage(msg.role, msg.content);
}
});
}
} catch (error) {
console.error('Error loading history:', error);
}
}
}

// Initialize widget when DOM is ready
function initChatWidget() {
console.log('[ChatWidget] initChatWidget() called, readyState:', document.readyState);
// Remove existing widget if any
const existingContainer = document.getElementById('chat-widget-container');
if (existingContainer) {
console.log('[ChatWidget] Removing existing container');
existingContainer.remove();
}

// Reset ChatWidget if exists
if (window.ChatWidget) {
console.log('[ChatWidget] Resetting existing ChatWidget');
window.ChatWidget = null;
}

// Initialize new widget
console.log('[ChatWidget] Creating new ChatWidget instance...');
window.ChatWidget = new ChatWidget();
console.log('[ChatWidget] ChatWidget instance created');
// Note: init() is called automatically in constructor
}

if (document.readyState === 'loading') {
console.log('[ChatWidget] DOM is loading, waiting for DOMContentLoaded');
document.addEventListener('DOMContentLoaded', initChatWidget);
} else {
console.log('[ChatWidget] DOM is ready, initializing immediately');
initChatWidget();
}
})();