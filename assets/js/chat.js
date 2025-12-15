/**
 * Chat JavaScript
 * Handles chat functionality, API calls, and UI updates
 */

class HealthAdvisorChat {
    constructor() {
        this.chatId = null;
        this.chatHistory = [];
        this.isLoading = false;

        // Stop any active speech on load
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }

        this.init();
    }

    init() {
        this.bindElements();
        this.bindEvents();
        this.loadChatHistory();
        this.loadStats();
        this.checkMobileSidebar();
    }

    bindElements() {
        this.messagesContainer = document.getElementById('chatMessages');
        this.welcomeScreen = document.getElementById('welcomeScreen');
        this.chatInput = document.getElementById('chatInput');
        this.sendBtn = document.getElementById('sendBtn');
        this.newChatBtn = document.getElementById('newChatBtn');
        this.historyList = document.getElementById('historyList');
        this.statsContainer = document.getElementById('learningStats');
        this.modelSelect = document.getElementById('modelSelect');
        this.sidebar = document.querySelector('.chat-sidebar');
        this.toggleSidebarBtn = document.getElementById('toggleSidebar');
    }

    bindEvents() {
        // Send message
        this.sendBtn.addEventListener('click', () => this.sendMessage());

        // Toggle sidebar
        if (this.toggleSidebarBtn) {
            this.toggleSidebarBtn.addEventListener('click', () => {
                this.sidebar.classList.toggle('hidden');
                this.toggleSidebarBtn.textContent = this.sidebar.classList.contains('hidden') ? '☰' : '✕';
            });
        }

        // Enter to send (Shift+Enter for new line)
        this.chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize textarea
        this.chatInput.addEventListener('input', () => {
            this.chatInput.style.height = 'auto';
            this.chatInput.style.height = Math.min(this.chatInput.scrollHeight, 200) + 'px';
        });

        // New chat button
        this.newChatBtn.addEventListener('click', () => this.startNewChat());

        // Suggested questions
        document.querySelectorAll('.suggested-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.chatInput.value = btn.textContent;
                this.sendMessage();
            });
        });
    }

    async sendMessage() {
        const message = this.chatInput.value.trim();

        if (!message || this.isLoading) return;

        // Hide welcome screen
        if (this.welcomeScreen) {
            this.welcomeScreen.style.display = 'none';
            this.messagesContainer.style.display = 'flex';
        }

        // Add user message to UI
        this.addMessage('user', message);

        // Clear input
        this.chatInput.value = '';
        this.chatInput.style.height = 'auto';

        // Show typing indicator
        this.showTyping();
        this.isLoading = true;
        this.updateSendButton();

        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    chat_id: this.chatId,
                    model: this.modelSelect ? this.modelSelect.value : null,
                    history: this.chatHistory.slice(-10)
                })
            });

            const data = await response.json();

            this.hideTyping();

            if (data.success) {
                // Add AI response
                this.addMessage('assistant', data.response, data.source, data.similarity);

                // Update chat history
                this.chatHistory.push({ role: 'user', content: message });
                this.chatHistory.push({ role: 'assistant', content: data.response });

                // Reload sidebar
                this.loadChatHistory();
                this.loadStats();
            } else {
                this.addMessage('assistant', `⚠️ ${data.error}`, 'error');
            }
        } catch (error) {
            this.hideTyping();
            this.addMessage('assistant', '⚠️ Connection error. Please try again.', 'error');
        }

        this.isLoading = false;
        this.updateSendButton();
    }

    addMessage(role, content, source = null, similarity = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;

        const avatar = role === 'user' ? '👤' : '🏥';
        const roleName = role === 'user' ? 'You' : 'Health Advisor';

        let sourceHtml = '';
        if (source && source !== 'error') {
            const sourceLabels = {
                'knowledge_base': '📚 Learned',
                'your_history': '💬 From Your History',
                'gemini_api': '✨ Fresh Response'
            };
            const sourceClass = source === 'gemini_api' ? 'api' : '';
            sourceHtml = `<span class="message-source ${sourceClass}">${sourceLabels[source] || source}${similarity ? ` (${similarity})` : ''}</span>`;
        }

        // Store last user message for regenerate
        if (role === 'user') {
            this.lastUserMessage = content;
        }

        messageDiv.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">
                <div class="message-header">
                    <span class="message-role">${roleName}</span>
                    ${sourceHtml}
                </div>
                <div class="message-text">${this.formatMessage(content)}</div>
                <div class="message-actions">
                    <button class="action-btn copy-btn" title="Copy">📋</button>
                    ${role === 'assistant' ? '<button class="action-btn refresh-btn" title="Regenerate">🔄</button>' : ''}
                    <button class="action-btn speak-btn" title="Read aloud">🔊</button>
                </div>
            </div>
        `;

        // Add click handlers
        const copyBtn = messageDiv.querySelector('.copy-btn');
        copyBtn.addEventListener('click', () => this.copyToClipboard(content, copyBtn));

        messageDiv.querySelector('.speak-btn').addEventListener('click', (e) => this.speakText(content, e.target));

        const refreshBtn = messageDiv.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.regenerateResponse());
        }

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    regenerateResponse() {
        if (!this.lastUserMessage) return;

        // Remove last AI response
        const messages = this.messagesContainer.querySelectorAll('.message.assistant');
        if (messages.length > 0) {
            messages[messages.length - 1].remove();
            this.chatHistory.pop(); // Remove from history too
        }

        // Resend the last user message
        this.chatInput.value = this.lastUserMessage;
        this.sendMessage();
    }

    formatMessage(content) {
        // Basic markdown formatting
        let formatted = content
            // Escape HTML
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            // Bold
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Italic
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            // Code
            .replace(/`(.*?)`/g, '<code>$1</code>')
            // Headers
            .replace(/^### (.*$)/gm, '<h3>$1</h3>')
            .replace(/^## (.*$)/gm, '<h2>$1</h2>')
            .replace(/^# (.*$)/gm, '<h1>$1</h1>')
            // Lists
            .replace(/^\* (.*$)/gm, '<li>$1</li>')
            .replace(/^- (.*$)/gm, '<li>$1</li>')
            .replace(/^\d+\. (.*$)/gm, '<li>$1</li>')
            // Paragraphs
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');

        return `<p>${formatted}</p>`;
    }

    speakText(text, btn = null) {
        // Check if browser supports speech synthesis
        if (!('speechSynthesis' in window)) {
            console.log('Text-to-Speech not supported');
            return;
        }

        // If already speaking, stop it
        if (window.speechSynthesis.speaking) {
            window.speechSynthesis.cancel();
            if (btn) btn.textContent = '🔊';
            return;
        }

        // Clean text for speaking (remove markdown/HTML)
        const cleanText = text
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*(.*?)\*/g, '$1')
            .replace(/`(.*?)`/g, '$1')
            .replace(/#{1,3}\s/g, '')
            .replace(/[-*]\s/g, '')
            .replace(/\n/g, ' ');

        // Create and speak utterance
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 1;

        // Change button to stop icon while speaking
        if (btn) {
            btn.textContent = '⏹️';
            utterance.onend = () => { btn.textContent = '🔊'; };
        }

        window.speechSynthesis.speak(utterance);
    }

    showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message assistant';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="message-avatar">🏥</div>
            <div class="message-content">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        this.messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();
    }

    hideTyping() {
        const typing = document.getElementById('typingIndicator');
        if (typing) typing.remove();
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    updateSendButton() {
        this.sendBtn.disabled = this.isLoading;
        this.sendBtn.innerHTML = this.isLoading ?
            '<div class="spinner" style="width:20px;height:20px;border-width:2px;"></div>' :
            '➤';
    }

    startNewChat() {
        this.chatId = null;
        this.chatHistory = [];
        this.messagesContainer.innerHTML = '';
        this.welcomeScreen.style.display = 'flex';
        this.messagesContainer.style.display = 'none';

        // Remove active state from history
        document.querySelectorAll('.history-item').forEach(item => {
            item.classList.remove('active');
        });
    }

    async loadChatHistory() {
        try {
            const response = await fetch('api/history.php');
            const data = await response.json();

            if (data.success && data.chats) {
                this.renderChatHistory(data.chats);
            }
        } catch (error) {
            console.error('Failed to load chat history:', error);
        }
    }

    renderChatHistory(chats) {
        if (chats.length === 0) {
            this.historyList.innerHTML = '<div class="empty-chats">No chats yet</div>';
            return;
        }

        this.historyList.innerHTML = chats.map(chat => `
            <div class="history-item" data-id="${chat.id}">
                <span class="history-icon">💬</span>
                <span class="history-text">${this.escapeHtml(chat.title)}</span>
                <button class="history-delete" onclick="event.stopPropagation(); chat.deleteChat('${chat.id}')">🗑️</button>
            </div>
        `).join('');

        // Bind click events
        this.historyList.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('click', () => this.loadChat(item.dataset.id));
        });
    }

    async loadChat(chatId) {
        try {
            const response = await fetch(`api/history.php?chat_id=${chatId}`);
            const data = await response.json();

            if (data.success && data.chat) {
                this.chatId = chatId;
                this.chatHistory = data.chat.messages.map(m => ({
                    role: m.role,
                    content: m.content
                }));

                // Clear and render messages
                this.messagesContainer.innerHTML = '';
                this.welcomeScreen.style.display = 'none';
                this.messagesContainer.style.display = 'flex';

                data.chat.messages.forEach(msg => {
                    this.addMessage(msg.role, msg.content);
                });

                // Update active state
                document.querySelectorAll('.history-item').forEach(item => {
                    item.classList.toggle('active', item.dataset.id === chatId);
                });
            }
        } catch (error) {
            console.error('Failed to load chat:', error);
        }
    }

    async deleteChat(chatId) {
        if (!confirm('Delete this chat?')) return;

        try {
            const response = await fetch('api/history.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ chat_id: chatId })
            });

            const data = await response.json();

            if (data.success) {
                if (this.chatId === chatId) {
                    this.startNewChat();
                }
                this.loadChatHistory();
            }
        } catch (error) {
            console.error('Failed to delete chat:', error);
        }
    }

    async loadStats() {
        try {
            const response = await fetch('api/chat.php');
            const data = await response.json();

            if (data.success && data.stats) {
                this.statsContainer.innerHTML = `
                    <span>🧠 Knowledge:</span>
                    <span class="stats-badge">${data.stats.total_entries} learned</span>
                `;
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    copyToClipboard(text, btn) {
        // Prefer Clipboard API if available and secure
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.showCopyFeedback(btn);
            }).catch(err => {
                console.error('Clipboard API failed', err);
                this.fallbackCopy(text, btn);
            });
        } else {
            // Fallback for HTTP/Mobile
            this.fallbackCopy(text, btn);
        }
    }

    fallbackCopy(text, btn) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed"; // Avoid scrolling to bottom
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                this.showCopyFeedback(btn);
            } else {
                alert('Copy failed. Please copy manually.');
            }
        } catch (err) {
            console.error('Fallback copy failed', err);
            alert('Copy failed.');
        }

        document.body.removeChild(textArea);
    }

    showCopyFeedback(btn) {
        const originalText = btn.textContent;
        btn.textContent = '✅';
        setTimeout(() => { btn.textContent = originalText; }, 2000);
    }

    checkMobileSidebar() {
        if (window.innerWidth <= 768 && this.sidebar) {
            this.sidebar.classList.add('hidden');
            if (this.toggleSidebarBtn) this.toggleSidebarBtn.textContent = '☰';
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat
let chat;
document.addEventListener('DOMContentLoaded', () => {
    chat = new HealthAdvisorChat();
});
