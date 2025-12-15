<?php
/**
 * Authentication API Endpoint
 * Handles member registration and login
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/auth_check.php';

Logger::requestStart();

$storage = new Storage();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    Logger::info('Auth action requested', ['action' => $action]);

    switch ($action) {
        case 'register':
            handleRegister($storage, $input);
            break;
        case 'login':
            handleLogin($storage, $input);
            break;
        case 'logout':
            handleLogout();
            break;
        case 'update_profile':
            handleUpdateProfile($storage, $input);
            break;
        default:
            Logger::warning('Invalid auth action', ['action' => $action]);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} elseif ($method === 'GET') {
    // Check login status
    echo json_encode([
        'success' => true,
        'logged_in' => isLoggedIn(),
        'member' => getCurrentMember()
    ]);
}

/**
 * Handle member profile update
 */
function handleUpdateProfile($storage, $input)
{
    requireLogin();
    $currentMember = getCurrentMember();
    $memberId = $currentMember['id'];

    $oldPassword = $input['old_password'] ?? '';
    $newUsername = trim($input['username'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (empty($oldPassword)) {
        echo json_encode(['success' => false, 'error' => 'Current password is required']);
        return;
    }

    // Verify current password
    $profile = $storage->readJson("members/$memberId/profile.json");
    if (!$profile || !password_verify($oldPassword, $profile['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        return;
    }

    $changes = false;
    $newMemberId = $memberId;

    // Handle Username Change
    if (!empty($newUsername) && $newUsername !== $profile['username']) {
        if (strlen($newUsername) < 3) {
            echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters']);
            return;
        }

        $targetId = sanitizeUsername($newUsername);

        // If ID changed (folder rename required)
        if ($targetId !== $memberId) {
            if ($storage->exists("members/$targetId/profile.json")) {
                echo json_encode(['success' => false, 'error' => 'Username already exists']);
                return;
            }

            // Rename folder
            if ($storage->rename("members/$memberId", "members/$targetId")) {
                $memberId = $targetId;
                $newMemberId = $targetId;
                $profile['id'] = $targetId;
                $_SESSION['member_id'] = $targetId; // Update session
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update username']);
                return;
            }
        }

        $profile['username'] = $newUsername;
        $_SESSION['member_username'] = $newUsername; // Update session
        $changes = true;
    }

    // Handle Password Change
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 4) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 4 characters']);
            return;
        }
        $profile['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $changes = true;
    }

    if ($changes) {
        $storage->writeJson("members/$memberId/profile.json", $profile);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'member' => ['id' => $newMemberId, 'username' => $profile['username']]
        ]);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes made']);
    }
}

/**
 * Handle member registration
 */
function handleRegister($storage, $input)
{
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    // Validation
    if (empty($username) || strlen($username) < 3) {
        echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters']);
        return;
    }

    if (empty($password) || strlen($password) < 4) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 4 characters']);
        return;
    }

    // Check if username already exists
    $memberId = sanitizeUsername($username);
    $memberPath = 'members/' . $memberId;

    if ($storage->exists($memberPath . '/profile.json')) {
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
        return;
    }

    // Create member folder and profile
    $storage->createMemberFolder($memberId);

    $profile = [
        'id' => $memberId,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => $storage->getTimestamp()
    ];

    $storage->writeJson($memberPath . '/profile.json', $profile);

    // Log in the member
    $_SESSION['member_id'] = $memberId;
    $_SESSION['member_username'] = $username;

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'member' => ['id' => $memberId, 'username' => $username]
    ]);
}

/**
 * Handle member login
 */
function handleLogin($storage, $input)
{
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Username and password are required']);
        return;
    }

    $memberId = sanitizeUsername($username);
    $profile = $storage->readJson('members/' . $memberId . '/profile.json');

    if (!$profile) {
        echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        return;
    }

    if (!password_verify($password, $profile['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        return;
    }

    // Set session
    $_SESSION['member_id'] = $memberId;
    $_SESSION['member_username'] = $profile['username'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'member' => ['id' => $memberId, 'username' => $profile['username']]
    ]);
}

/**
 * Handle logout
 */
function handleLogout()
{
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Sanitize username for use as folder name
 */
function sanitizeUsername($username)
{
    $username = strtolower($username);
    $username = preg_replace('/[^a-z0-9_]/', '_', $username);
    return $username;
}
