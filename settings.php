<?php
/**
 * Settings Page
 * Manage Gemini API key and other settings
 */

require_once 'includes/auth_check.php';
requireLogin();

$member = getCurrentMember();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Health Advisor AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .settings-page {
            min-height: 100vh;
        }

        .settings-content {
            max-width: 600px;
            margin: 0 auto;
            padding: var(--space-2xl) var(--space-lg);
        }

        .settings-title {
            font-size: 1.75rem;
            margin-bottom: var(--space-sm);
        }

        .settings-subtitle {
            color: var(--text-secondary);
            margin-bottom: var(--space-2xl);
        }

        .settings-section {
            margin-bottom: var(--space-2xl);
        }

        .section-title {
            font-size: 1.125rem;
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .api-input-group {
            display: flex;
            gap: var(--space-sm);
        }

        .api-input-group .form-input {
            flex: 1;
        }

        .toggle-key-btn {
            width: 48px;
            height: 48px;
            background: var(--bg-glass);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .toggle-key-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .button-group {
            display: flex;
            gap: var(--space-md);
            margin-top: var(--space-lg);
        }

        .button-group .btn {
            flex: 1;
        }

        .api-info {
            margin-top: var(--space-lg);
            padding: var(--space-lg);
            background: var(--bg-glass);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .api-info h4 {
            margin-bottom: var(--space-md);
            font-size: 0.875rem;
        }

        .api-info ol {
            padding-left: var(--space-lg);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .api-info li {
            margin-bottom: var(--space-sm);
        }

        .api-info a {
            color: #667eea;
        }

        .back-btn {
            margin-bottom: var(--space-xl);
        }
    </style>
</head>

<body>
    <div class="settings-page">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <a href="index.php" class="logo">
                        <div class="logo-icon">🏥</div>
                        <span class="logo-text">Health Advisor AI</span>
                    </a>

                    <nav class="nav-links">
                        <a href="index.php" class="nav-link">Chat</a>
                        <a href="settings.php" class="nav-link active">Settings</a>
                    </nav>

                    <div class="user-menu">
                        <a href="profile.php" class="user-profile-link" title="Edit Profile">
                            <div class="user-avatar"><?php echo strtoupper(substr($member['username'], 0, 1)); ?></div>
                            <span class="user-name"><?php echo htmlspecialchars($member['username']); ?></span>
                        </a>
                        <a href="api/auth.php?logout=1" class="btn btn-secondary btn-sm"
                            onclick="logout(); return false;">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="settings-content">
            <a href="index.php" class="btn btn-secondary back-btn">← Back to Chat</a>

            <h1 class="settings-title text-gradient">⚙️ Settings</h1>
            <p class="settings-subtitle">Configure your Health Advisor AI</p>

            <div id="alertMessage"></div>

            <!-- API Key Section -->
            <div class="settings-section glass-card">
                <h3 class="section-title">
                    🔑 Gemini API Key
                </h3>

                <div id="apiStatus"></div>

                <div class="form-group">
                    <label class="form-label">Enter your Gemini API Key</label>
                    <div class="api-input-group">
                        <input type="password" class="form-input" id="apiKey" placeholder="AIza...">
                        <button type="button" id="toggleKey" class="toggle-key-btn">👁️</button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" id="saveBtn" class="btn btn-primary">
                        💾 Save API Key
                    </button>
                    <button type="button" id="testBtn" class="btn btn-secondary">
                        🔍 Test & Save
                    </button>
                </div>

                <div class="api-info">
                    <h4>📖 How to get your Gemini API Key:</h4>
                    <ol>
                        <li>Go to <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        </li>
                        <li>Click "Create API Key"</li>
                        <li>Copy the key and paste it above</li>
                        <li>Click "Save" to store it securely</li>
                    </ol>
                </div>
            </div>

            <!-- Model Info Section -->
            <div class="settings-section glass-card">
                <h3 class="section-title">
                    🤖 AI Model
                </h3>
                <p class="text-secondary">
                    This application uses <strong>Gemini Flash 2.5</strong> for fast, accurate health advice.
                </p>
                <p class="text-muted mt-2" style="font-size: 0.875rem;">
                    Model: <code>gemini-2.5-flash-preview-05-20</code>
                </p>
            </div>

            <!-- Self Learning Info -->
            <div class="settings-section glass-card">
                <h3 class="section-title">
                    🧠 Self-Learning System
                </h3>
                <p class="text-secondary">
                    The AI learns from your conversations. When you ask a question:
                </p>
                <ol
                    style="color: var(--text-secondary); padding-left: var(--space-lg); margin-top: var(--space-md); font-size: 0.875rem;">
                    <li>First, it checks the knowledge base for similar questions</li>
                    <li>Then, it searches your past chat history</li>
                    <li>If no match is found, it calls Gemini API</li>
                    <li>Every answer is saved for future use</li>
                </ol>
                <p class="text-muted mt-2" style="font-size: 0.875rem;">
                    This makes responses faster and reduces API usage over time!
                </p>
            </div>

            <!-- Data Management -->
            <div class="settings-section glass-card">
                <h3 class="section-title">
                    💾 Data Management
                </h3>
                <p class="text-secondary">
                    Manage your conversation history.
                </p>
                <div class="mt-2">
                    <p class="text-error" style="font-size: 0.9rem; margin-bottom: 10px;">
                        ⚠️ Warning: This action cannot be undone.
                    </p>
                    <button type="button" id="clearHistoryBtn" class="btn btn-secondary"
                        style="border-color: var(--error); color: var(--error);">
                        🗑️ Clear All Chat History
                    </button>
                    <p class="text-muted mt-1" style="font-size: 0.8rem;">
                        Note: This deletes your chat logs only. Learned AI knowledge is preserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function logout() {
            await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });
            window.location.href = 'login.php';
        }
    </script>
    <script src="assets/js/settings.js"></script>
</body>

</html>