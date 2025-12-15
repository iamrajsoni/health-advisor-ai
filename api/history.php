<?php
/**
 * Chat History API Endpoint
 * Manages chat history for members
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/storage.php';

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$member = getCurrentMember();
$storage = new Storage();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $chatId = $_GET['chat_id'] ?? null;

    if ($chatId) {
        // Get specific chat
        $chatFile = $storage->getMemberChatsPath($member['id']) . '/' . $chatId . '.json';
        $chat = $storage->readJson($chatFile);

        if ($chat) {
            echo json_encode(['success' => true, 'chat' => $chat]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Chat not found']);
        }
    } else {
        // List all chats
        $chatsPath = $storage->getMemberChatsPath($member['id']);
        $chatFiles = $storage->listFiles($chatsPath, 'json');

        $chats = [];
        foreach ($chatFiles as $file) {
            $chat = $storage->readJson($chatsPath . '/' . $file);
            if ($chat) {
                $chats[] = [
                    'id' => $chat['id'],
                    'title' => $chat['title'],
                    'created_at' => $chat['created_at'],
                    'updated_at' => $chat['updated_at'],
                    'message_count' => count($chat['messages'] ?? [])
                ];
            }
        }

        // Sort by updated_at descending
        usort($chats, function ($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });

        echo json_encode(['success' => true, 'chats' => $chats]);
    }

} elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // Handle Delete All
    if ($action === 'delete_all') {
        $chatsPath = $storage->getMemberChatsPath($member['id']);
        $files = $storage->listFiles($chatsPath, 'json');
        $count = 0;

        foreach ($files as $file) {
            if ($storage->delete($chatsPath . '/' . $file)) {
                $count++;
            }
        }

        echo json_encode(['success' => true, 'message' => "Deleted $count chats"]);
        exit;
    }

    $chatId = $input['chat_id'] ?? null;

    if (!$chatId) {
        echo json_encode(['success' => false, 'error' => 'Chat ID is required']);
        exit;
    }

    $chatFile = $storage->getMemberChatsPath($member['id']) . '/' . $chatId . '.json';
    $result = $storage->delete($chatFile);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Chat deleted']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chat not found']);
    }
}
