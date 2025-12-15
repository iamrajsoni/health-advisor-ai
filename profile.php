<?php
/**
 * Profile Page
 * Manage user profile
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
    <title>Profile - Health Advisor AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    <style>
        .settings-content {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .toggle-password {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0 15px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>

<body>
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
                    <a href="settings.php" class="nav-link">Settings</a>
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
        <a href="index.php" class="btn btn-secondary mb-3">← Back to Chat</a>

        <div class="glass-card">
            <h2 class="mb-4">👤 Edit Profile</h2>

            <div id="alertMessage"></div>

            <form id="profileForm">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" id="username" class="form-input"
                        value="<?php echo htmlspecialchars($member['username']); ?>" required>
                </div>

                <hr style="margin: 25px 0; border: 0; border-top: 1px solid rgba(255,255,255,0.1);">

                <div class="form-group">
                    <label class="form-label">Current Password (Required)</label>
                    <div class="input-group">
                        <input type="password" id="oldPassword" class="form-input" placeholder="Enter current password"
                            required>
                        <button type="button" class="toggle-password" data-target="oldPassword">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password (Optional)</label>
                    <div class="input-group">
                        <input type="password" id="newPassword" class="form-input" placeholder="New password">
                        <button type="button" class="toggle-password" data-target="newPassword">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" id="confirmPassword" class="form-input" placeholder="Confirm new password">
                </div>

                <button type="submit" id="saveBtn" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    💾 Save Changes
                </button>
            </form>
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
    <script src="assets/js/profile.js"></script>
</body>

</html>