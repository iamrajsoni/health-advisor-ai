<?php
/**
 * Main Chat Interface
 * Health Advisor AI Chat Page
 */

require_once 'includes/logger.php';
Logger::requestStart();

require_once 'includes/auth_check.php';
requireLogin();

$member = getCurrentMember();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Advisor AI - Your Personal Health Assistant</title>
    <meta name="description"
        content="Get personalized health advice from our AI-powered health advisor. Ask questions about symptoms, nutrition, exercise, and more.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/chat.css">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <button id="toggleSidebar" class="sidebar-toggle" title="Toggle Sidebar">☰</button>
                <div class="logo">
                    <div class="logo-icon">🏥</div>
                    <span class="logo-text">Health Advisor AI</span>
                </div>

                <nav class="nav-links">
                    <a href="index.php" class="nav-link active">Chat</a>
                    <a href="settings.php" class="nav-link">Settings</a>
                </nav>

                <div class="user-menu">
                    <a href="profile.php" class="user-profile-link" title="Edit Profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($member['username'], 0, 1)); ?></div>
                        <span class="user-name"><?php echo htmlspecialchars($member['username']); ?></span>
                    </a>
                    <button class="btn btn-secondary btn-sm" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Chat Layout -->
    <div class="chat-layout">
        <!-- Sidebar -->
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <button id="newChatBtn" class="new-chat-btn">
                    ✨ New Chat
                </button>
            </div>

            <div class="chat-history">
                <div class="history-title">Recent Chats</div>
                <div id="historyList">
                    <div class="empty-chats">Loading...</div>
                </div>
            </div>

            <div class="sidebar-footer">
                <div id="learningStats" class="learning-stats">
                    <span>🧠 Knowledge:</span>
                    <span class="stats-badge">Loading...</span>
                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="chat-main">
            <!-- Welcome Screen -->
            <div id="welcomeScreen" class="welcome-screen">
                <div class="welcome-icon">🏥</div>
                <h1 class="welcome-title">Health Advisor AI</h1>
                <p class="welcome-subtitle">
                    I'm your personal AI health assistant. Ask me anything about health, nutrition,
                    symptoms, exercise, or wellness. I learn from our conversations to provide
                    better answers over time!
                </p>

                <div class="suggested-questions">
                    <button class="suggested-btn">What are symptoms of common cold?</button>
                    <button class="suggested-btn">How can I improve my sleep quality?</button>
                    <button class="suggested-btn">What foods are good for heart health?</button>
                    <button class="suggested-btn">How to reduce stress naturally?</button>
                </div>
            </div>

            <!-- Messages Container -->
            <div id="chatMessages" class="chat-messages" style="display: none;"></div>

            <!-- Input Area -->
            <div class="chat-input-area">
               <!-- <div class="model-selector">
                    <label for="modelSelect">Model:</label>
                    <select id="modelSelect">
                        <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
                        <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                    </select>
                </div> -->
                <div class="chat-input-wrapper">
                    <div class="chat-input-container">
                        <textarea id="chatInput" class="chat-input"
                            placeholder="Ask me about health, symptoms, nutrition, exercise..." rows="1"></textarea>
                    </div>
                    <button id="sendBtn" class="send-btn">➤</button>
                </div>
                <p class="input-hint">
                    💡 I learn from our conversations to provide faster, personalized responses
                </p>
                <div class="chat-footer">
                    &copy; 2025 Health Advisor AI. All rights reserved.
                </div>
            </div>
        </main>
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
    <script src="assets/js/chat.js"></script>
</body>

</html>