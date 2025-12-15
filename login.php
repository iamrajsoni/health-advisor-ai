<?php
/**
 * Login / Register Page
 * Member authentication with file-based storage
 */

require_once 'includes/logger.php';
Logger::requestStart();

require_once 'includes/auth_check.php';

// Redirect if already logged in
if (isLoggedIn()) {
    Logger::info('Already logged in user redirected from login page');
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Health Advisor AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-xl);
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
        }

        .auth-logo {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto var(--space-lg);
            animation: pulse 2s ease infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
            }

            50% {
                box-shadow: 0 0 0 20px rgba(102, 126, 234, 0);
            }
        }

        .auth-title {
            font-size: 1.75rem;
            margin-bottom: var(--space-sm);
        }

        .auth-subtitle {
            color: var(--text-secondary);
        }

        .auth-tabs {
            display: flex;
            margin-bottom: var(--space-xl);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            padding: 4px;
        }

        .auth-tab {
            flex: 1;
            padding: var(--space-md);
            text-align: center;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
        }

        .auth-tab.active {
            background: var(--primary-gradient);
            color: white;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .form-input-wrapper {
            position: relative;
        }

        .form-input-icon {
            position: absolute;
            left: var(--space-md);
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            opacity: 0.5;
        }

        .form-input-wrapper .form-input {
            padding-left: 3rem;
        }

        .auth-submit {
            width: 100%;
            padding: var(--space-md);
            margin-top: var(--space-md);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--space-xl);
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        #alertMessage {
            margin-bottom: var(--space-lg);
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="auth-card glass-card">
            <div class="auth-header">
                <div class="auth-logo">🏥</div>
                <h1 class="auth-title text-gradient">Health Advisor AI</h1>
                <p class="auth-subtitle">Your personal AI health assistant</p>
            </div>

            <div id="alertMessage"></div>

            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Login</button>
                <button class="auth-tab" data-tab="register">Register</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form active">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">👤</span>
                        <input type="text" class="form-input" id="loginUsername" placeholder="Enter your username"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">🔒</span>
                        <input type="password" class="form-input" id="loginPassword" placeholder="Enter your password"
                            required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    Login →
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">👤</span>
                        <input type="text" class="form-input" id="regUsername" placeholder="Choose a username" required
                            minlength="3">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">🔒</span>
                        <input type="password" class="form-input" id="regPassword" placeholder="Create a password"
                            required minlength="4">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon">🔐</span>
                        <input type="password" class="form-input" id="regConfirmPassword"
                            placeholder="Confirm your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    Create Account →
                </button>
            </form>

            <div class="auth-footer">
                <p>🧠 AI-powered health guidance at your fingertips</p>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(tab.dataset.tab + 'Form').classList.add('active');
            });
        });

        // Alert function
        function showAlert(message, type) {
            document.getElementById('alertMessage').innerHTML = `
                <div class="alert alert-${type}">
                    ${type === 'success' ? '✅' : '❌'} ${message}
                </div>
            `;
        }

        // Login form
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'login',
                        username,
                        password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('Connection error', 'error');
            }
        });

        // Register form
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('regUsername').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('regConfirmPassword').value;

            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'register',
                        username,
                        password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Account created! Redirecting...', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else {
                    showAlert(data.error, 'error');
                }
            } catch (error) {
                showAlert('Connection error', 'error');
            }
        });
    </script>
</body>

</html>