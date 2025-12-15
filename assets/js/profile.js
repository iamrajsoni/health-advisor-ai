/**
 * Profile Management JavaScript
 */

class ProfileManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindElements();
        this.bindEvents();
    }

    bindElements() {
        this.form = document.getElementById('profileForm');
        this.usernameInput = document.getElementById('username');
        this.oldPasswordInput = document.getElementById('oldPassword');
        this.newPasswordInput = document.getElementById('newPassword');
        this.confirmPasswordInput = document.getElementById('confirmPassword');
        this.saveBtn = document.getElementById('saveBtn');
        this.alertDiv = document.getElementById('alertMessage');
        this.toggleBtns = document.querySelectorAll('.toggle-password');
    }

    bindEvents() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateProfile();
            });
        }

        this.toggleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = '🙈';
                } else {
                    input.type = 'password';
                    btn.textContent = '👁️';
                }
            });
        });
    }

    async updateProfile() {
        const username = this.usernameInput.value.trim();
        const oldPassword = this.oldPasswordInput.value.trim();
        const newPassword = this.newPasswordInput.value.trim();
        const confirmPassword = this.confirmPasswordInput.value.trim();

        if (!oldPassword) {
            this.showAlert('Current password is required to make changes', 'error');
            return;
        }

        if (newPassword && newPassword !== confirmPassword) {
            this.showAlert('New passwords do not match', 'error');
            return;
        }

        const originalBtnText = this.saveBtn.innerHTML;
        this.saveBtn.disabled = true;
        this.saveBtn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px;"></div> Saving...';

        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_profile',
                    username: username,
                    old_password: oldPassword,
                    new_password: newPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Profile updated successfully!', 'success');
                this.oldPasswordInput.value = '';
                this.newPasswordInput.value = '';
                this.confirmPasswordInput.value = '';

                // Update displayed username if changed
                const userNames = document.querySelectorAll('.user-name');
                userNames.forEach(el => el.textContent = data.member.username);
                const avatars = document.querySelectorAll('.user-avatar');
                avatars.forEach(el => el.textContent = data.member.username.charAt(0).toUpperCase());
            } else {
                this.showAlert(data.error || 'Failed to update profile', 'error');
            }
        } catch (error) {
            this.showAlert('Connection error', 'error');
        }

        this.saveBtn.disabled = false;
        this.saveBtn.innerHTML = originalBtnText;
    }

    showAlert(message, type) {
        this.alertDiv.innerHTML = `
            <div class="alert alert-${type}">
                ${type === 'success' ? '✅' : '❌'} ${message}
            </div>
        `;
        setTimeout(() => {
            this.alertDiv.innerHTML = '';
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ProfileManager();
});
