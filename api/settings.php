<?php
/**
 * Settings API Endpoint
 * Handles API key management
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/storage.php';
require_once __DIR__ . '/../includes/gemini.php';

$storage = new Storage();
$gemini = new GeminiAPI();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Save API key
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['api_key']) || empty(trim($input['api_key']))) {
        echo json_encode(['success' => false, 'error' => 'API key is required']);
        exit;
    }
    
    $apiKey = trim($input['api_key']);
    
    // Test the API key first (optional)
    $testFirst = $input['test_first'] ?? false;
    
    if ($testFirst) {
        $testResult = $gemini->testApiKey($apiKey);
        if ($testResult === 'invalid') {
            echo json_encode(['success' => false, 'error' => 'Invalid API key. Please check and try again.']);
            exit;
        }
        // If quota_exceeded or valid, save the key (quota issues don't mean invalid key)
    }
    
    // Save the API key
    $result = $gemini->saveApiKey($apiKey);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'API key saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save API key']);
    }
    
} elseif ($method === 'GET') {
    // Check if API key is configured
    $config = $storage->readJson('config/api_key.json');
    
    $response = [
        'success' => true,
        'configured' => ($config && isset($config['api_key']) && !empty($config['api_key'])),
        'updated_at' => $config['updated_at'] ?? null
    ];
    
    // Mask the API key for display
    if ($response['configured']) {
        $key = $config['api_key'];
        $response['masked_key'] = substr($key, 0, 8) . '...' . substr($key, -4);
    }
    
    echo json_encode($response);
    
} elseif ($method === 'DELETE') {
    // Delete API key
    $result = $storage->delete('config/api_key.json');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'API key removed']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No API key to remove']);
    }
}
