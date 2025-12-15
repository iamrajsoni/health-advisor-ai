/**
 * Settings JavaScript
 * Handles API key management
 */

class SettingsManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindElements();
        this.bindEvents();
        this.loadSettings();
    }

    bindElements() {
        this.apiKeyInput = document.getElementById('apiKey');
        this.saveBtn = document.getElementById('saveBtn');
        this.statusDiv = document.getElementById('apiStatus');
        this.toggleBtn = document.getElementById('toggleKey');
        this.testBtn = document.getElementById('testBtn');
        this.alertDiv = document.getElementById('alertMessage');
    }

    bindEvents() {
        if (this.saveBtn) {
            this.saveBtn.addEventListener('click', () => this.saveApiKey());
        }

        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggleKeyVisibility());
        }

        if (this.testBtn) {
            this.testBtn.addEventListener('click', () => this.testApiKey());
        }

        if (this.apiKeyInput) {
            this.apiKeyInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.saveApiKey();
                }
            });
        }

        this.clearHistoryBtn = document.getElementById('clearHistoryBtn');
        if (this.clearHistoryBtn) {
            this.clearHistoryBtn.addEventListener('click', () => this.clearHistory());
        }
    }

    async clearHistory() {
        if (!confirm('Are you sure you want to delete ALL chat history?\n\nThis cannot be undone. AI knowledge will be kept.')) {
            return;
        }

        const btn = this.clearHistoryBtn;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Deleting...';

        try {
            const response = await fetch('api/history.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_all' })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('All chat history cleared successfully!', 'success');
            } else {
                this.showAlert(data.error || 'Failed to clear history', 'error');
            }
        } catch (error) {
            this.showAlert('Connection error', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = originalText;
    }

    async loadSettings() {
        try {
            const response = await fetch('api/settings.php');
            const data = await response.json();

            if (data.configured) {
                this.statusDiv.innerHTML = `
                    <div class="alert alert-success">
                        ✅ API Key Configured: <code>${data.masked_key}</code>
                        <br><small>Last updated: ${data.updated_at}</small>
                    </div>
                `;
                this.apiKeyInput.placeholder = 'Enter new key to update...';
            } else {
                this.statusDiv.innerHTML = `
                    <div class="alert alert-warning">
                        ⚠️ No API Key configured. Please add your Gemini API key to use the Health Advisor.
                    </div>
                `;
            }
        } catch (error) {
            this.showAlert('Failed to load settings', 'error');
        }
    }

    async saveApiKey() {
        const apiKey = this.apiKeyInput.value.trim();

        if (!apiKey) {
            this.showAlert('Please enter an API key', 'error');
            return;
        }

        this.saveBtn.disabled = true;
        this.saveBtn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px;"></div> Saving...';

        try {
            const response = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    api_key: apiKey,
                    test_first: false
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('API key saved successfully!', 'success');
                this.apiKeyInput.value = '';
                this.loadSettings();
            } else {
                this.showAlert(data.error || 'Failed to save API key', 'error');
            }
        } catch (error) {
            this.showAlert('Connection error', 'error');
        }

        this.saveBtn.disabled = false;
        this.saveBtn.innerHTML = '💾 Save API Key';
    }

    async testApiKey() {
        const apiKey = this.apiKeyInput.value.trim();

        if (!apiKey) {
            this.showAlert('Please enter an API key to test', 'error');
            return;
        }

        this.testBtn.disabled = true;
        this.testBtn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px;"></div> Testing...';

        try {
            const response = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    api_key: apiKey,
                    test_first: true
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('API key is valid and saved!', 'success');
                this.apiKeyInput.value = '';
                this.loadSettings();
            } else {
                this.showAlert(data.error || 'Invalid API key', 'error');
            }
        } catch (error) {
            this.showAlert('Connection error', 'error');
        }

        this.testBtn.disabled = false;
        this.testBtn.innerHTML = '🔍 Test & Save';
    }

    toggleKeyVisibility() {
        if (this.apiKeyInput.type === 'password') {
            this.apiKeyInput.type = 'text';
            this.toggleBtn.textContent = '🙈';
        } else {
            this.apiKeyInput.type = 'password';
            this.toggleBtn.textContent = '👁️';
        }
    }

    showAlert(message, type) {
        this.alertDiv.innerHTML = `
            <div class="alert alert-${type}">
                ${type === 'success' ? '✅' : '❌'} ${message}
            </div>
        `;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.alertDiv.innerHTML = '';
        }, 5000);
    }
}

// Initialize settings
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});
