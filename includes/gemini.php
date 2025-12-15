<?php
/**
 * Gemini API Wrapper
 * Connects to Google Gemini Flash 2.5 for health advice
 */

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/logger.php';

class GeminiAPI
{
    private $apiKey;
    private $model = 'gemini-2.5-flash';
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $storage;

    public function __construct()
    {
        $this->storage = new Storage();
        $this->loadApiKey();
    }

    /**
     * Load API key from config file
     */
    private function loadApiKey()
    {
        $config = $this->storage->readJson('config/api_key.json');
        if ($config && isset($config['api_key'])) {
            $this->apiKey = $config['api_key'];
        }
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the health advisor system prompt
     */
    private function getSystemPrompt()
    {
        return "You are form now 'Health Advisor AI'. Your role is to provide helpful, accurate, and caring health advice to users.

STRICT DOMAIN RESTRICTION:
- You must ONLY answer questions related to health, wellness, nutrition, fitness, mental health, medical conditions, and lifestyle.
- If a user asks about ANY other topic (e.g., coding, sports, politics, general knowledge, math), you must politely decline.
- Example refusal: \"I apologize, but as a Health Advisor, I can only provide assistance with health and wellness related topics.\"

LANGUAGE INSTRUCTIONS:
- You must DETECT the language of the user's message.
- You must REPLY in the SAME language as the user.
- If the user asks in Hindi, reply in Hindi. If Spanish, reply in Spanish.
- Do not translate the user's message, just answer in their language.

Guidelines:
1. Always be empathetic and understanding
2. Provide clear, actionable health advice
3. Recommend consulting a doctor for serious symptoms
4. Never diagnose conditions - only provide general health information
5. Include relevant lifestyle tips (diet, exercise, sleep)
6. Be encouraging and supportive
7. If unsure, acknowledge limitations and suggest professional consultation
8. Use simple language that anyone can understand
9. Format responses with clear sections when appropriate

IMPORTANT: Always remind users that your advice is for informational purposes only and does not replace professional medical consultation.";
    }

    /**
     * Send a message to Gemini API
     */
    public function chat($userMessage, $chatHistory = [], $model = null)
    {
        if ($model) {
            $this->model = $model;
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API key not configured. Please go to Settings to add your Gemini API key.'
            ];
        }

        // Build the conversation with system prompt
        $contents = [];

        // Add system instruction
        $systemInstruction = [
            'role' => 'user',
            'parts' => [['text' => $this->getSystemPrompt()]]
        ];

        // Add chat history
        foreach ($chatHistory as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        // Add current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        // API request
        $url = $this->apiUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $postData = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $this->getSystemPrompt()]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH']
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        Logger::api('Gemini API', 'POST', ['message_length' => strlen($userMessage)], ['http_code' => $httpCode]);

        if ($error) {
            Logger::error('Gemini API curl error', ['error' => $error]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $error
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            Logger::error('Gemini API HTTP error', ['http_code' => $httpCode, 'error' => $errorMsg]);
            return [
                'success' => false,
                'error' => 'API Error: ' . $errorMsg
            ];
        }

        // Extract the response text
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            Logger::info('Gemini API success', ['response_length' => strlen($data['candidates'][0]['content']['parts'][0]['text'])]);
            return [
                'success' => true,
                'response' => $data['candidates'][0]['content']['parts'][0]['text'],
                'source' => 'api'
            ];
        }

        Logger::error('Gemini API invalid response', ['response' => substr($response, 0, 500)]);
        return [
            'success' => false,
            'error' => 'Invalid response from API'
        ];
    }

    /**
     * Save API key to config file
     */
    public function saveApiKey($apiKey)
    {
        return $this->storage->writeJson('config/api_key.json', [
            'api_key' => $apiKey,
            'updated_at' => $this->storage->getTimestamp()
        ]);
    }

    /**
     * Test API key validity
     * Returns: 'valid', 'invalid', or 'quota_exceeded'
     */
    public function testApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        // Make a simple API call
        $url = $this->apiUrl . $this->model . ':generateContent?key=' . $apiKey;

        $postData = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'Hi']]]
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        // 200 = success, key is valid
        if ($httpCode === 200) {
            return 'valid';
        }

        // 429 = quota exceeded but key is valid
        if ($httpCode === 429) {
            return 'quota_exceeded';
        }

        // 400/401/403 = invalid key
        if ($httpCode === 400 || $httpCode === 401 || $httpCode === 403) {
            return 'invalid';
        }

        // Check error message for API key issues
        if (isset($data['error']['message'])) {
            $msg = strtolower($data['error']['message']);
            if (strpos($msg, 'api key') !== false || strpos($msg, 'invalid') !== false) {
                return 'invalid';
            }
            if (strpos($msg, 'quota') !== false || strpos($msg, 'exceeded') !== false) {
                return 'quota_exceeded';
            }
        }

        // Default to valid if we can't determine
        return 'valid';
    }
}
