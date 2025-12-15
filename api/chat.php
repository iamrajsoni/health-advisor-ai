<?php
/**
 * Chat API Endpoint
 * Handles chat requests with self-learning capability
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/gemini.php';
require_once __DIR__ . '/../includes/self_learn.php';

Logger::requestStart();
Logger::info('Chat API endpoint accessed', ['method' => $_SERVER['REQUEST_METHOD']]);

// Require login
if (!isLoggedIn()) {
    Logger::warning('Unauthenticated access attempt to chat API');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$member = getCurrentMember();
$storage = new Storage();
$gemini = new GeminiAPI();
$selfLearn = new SelfLearn();

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get the input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['message']) || empty(trim($input['message']))) {
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }

    $userMessage = trim($input['message']);
    $chatId = $input['chat_id'] ?? null;
    $chatHistory = $input['history'] ?? [];

    // STEP 1: Try to find answer in knowledge base
    $kbResult = $selfLearn->findInKnowledgeBase($userMessage);

    if ($kbResult) {
        $response = [
            'success' => true,
            'response' => $kbResult['answer'],
            'source' => 'knowledge_base',
            'similarity' => round($kbResult['similarity'] * 100) . '%',
            'learned' => true
        ];
    } else {
        // STEP 2: Try to find in member's past chats
        $memberResult = $selfLearn->searchMemberChats($member['id'], $userMessage);

        if ($memberResult) {
            $response = [
                'success' => true,
                'response' => $memberResult['answer'],
                'source' => 'your_history',
                'similarity' => round($memberResult['similarity'] * 100) . '%',
                'learned' => true
            ];
        } else {
            // STEP 3: Call Gemini API
            $model = $input['model'] ?? null;
            $apiResult = $gemini->chat($userMessage, $chatHistory, $model);

            if ($apiResult['success']) {
                // Add to knowledge base for future use
                $selfLearn->addToKnowledgeBase($userMessage, $apiResult['response']);

                $response = [
                    'success' => true,
                    'response' => $apiResult['response'],
                    'source' => 'gemini_api',
                    'learned' => false
                ];
            } else {
                echo json_encode($apiResult);
                exit;
            }
        }
    }

    // Save chat to member's folder
    saveChat($storage, $member['id'], $chatId, $userMessage, $response['response']);

    echo json_encode($response);

} elseif ($method === 'GET') {
    // Get learning stats
    $stats = $selfLearn->getStats();
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Save chat message to member's chat folder
 */
function saveChat($storage, $memberId, $chatId, $userMessage, $aiResponse)
{
    $chatsPath = $storage->getMemberChatsPath($memberId);

    // Create new chat or update existing
    if (!$chatId) {
        $chatId = $storage->generateId();
    }

    $chatFile = $chatsPath . '/' . $chatId . '.json';
    $chat = $storage->readJson($chatFile);

    if (!$chat) {
        // Create new chat file
        $chat = [
            'id' => $chatId,
            'title' => substr($userMessage, 0, 50) . (strlen($userMessage) > 50 ? '...' : ''),
            'created_at' => $storage->getTimestamp(),
            'updated_at' => $storage->getTimestamp(),
            'messages' => []
        ];
    }

    // Add messages
    $chat['messages'][] = [
        'role' => 'user',
        'content' => $userMessage,
        'timestamp' => $storage->getTimestamp()
    ];

    $chat['messages'][] = [
        'role' => 'assistant',
        'content' => $aiResponse,
        'timestamp' => $storage->getTimestamp()
    ];

    $chat['updated_at'] = $storage->getTimestamp();

    // Save chat
    $storage->writeJson($chatFile, $chat);

    return $chatId;
}
