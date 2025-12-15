<?php
/**
 * Self-Learning Engine
 * Learns from past conversations to provide faster, cached answers
 */

require_once __DIR__ . '/storage.php';

class SelfLearn {
    private $storage;
    private $knowledgeBasePath = 'knowledge_base/health_qa.json';
    private $similarityThreshold = 0.6; // 60% match threshold
    
    public function __construct() {
        $this->storage = new Storage();
        $this->initKnowledgeBase();
    }
    
    /**
     * Initialize knowledge base if not exists
     */
    private function initKnowledgeBase() {
        if (!$this->storage->exists($this->knowledgeBasePath)) {
            $this->storage->writeJson($this->knowledgeBasePath, [
                'version' => '1.0',
                'created_at' => $this->storage->getTimestamp(),
                'entries' => []
            ]);
        }
    }
    
    /**
     * Extract keywords from text
     */
    private function extractKeywords($text) {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove common words (stopwords)
        $stopwords = ['i', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used',
            'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until',
            'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between',
            'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to',
            'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again',
            'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how',
            'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor',
            'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'just',
            'don', 'now', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those',
            'my', 'your', 'his', 'her', 'its', 'our', 'their', 'me', 'him', 'we', 'they',
            'you', 'it', 'get', 'got', 'getting', 'please', 'tell', 'give', 'want', 'know'];
        
        // Split into words
        $words = preg_split('/[\s,.\?!;:]+/', $text);
        
        // Filter out stopwords and short words
        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && !in_array($word, $stopwords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Calculate similarity between two texts
     */
    private function calculateSimilarity($text1, $text2) {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);
        
        if (empty($keywords1) || empty($keywords2)) {
            return 0;
        }
        
        // Find common keywords
        $common = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));
        
        // Jaccard similarity
        $similarity = count($common) / count($union);
        
        // Boost for health-related keywords match
        $healthKeywords = ['symptoms', 'pain', 'headache', 'fever', 'cold', 'cough', 
            'stomach', 'heart', 'blood', 'pressure', 'diabetes', 'diet', 'exercise',
            'sleep', 'stress', 'anxiety', 'depression', 'weight', 'vitamin', 'medicine',
            'doctor', 'treatment', 'remedy', 'health', 'healthy', 'disease', 'infection'];
        
        $healthMatch = array_intersect($common, $healthKeywords);
        if (count($healthMatch) > 0) {
            $similarity += 0.1 * count($healthMatch);
        }
        
        return min($similarity, 1.0);
    }
    
    /**
     * Find similar question in knowledge base
     */
    public function findInKnowledgeBase($question) {
        $kb = $this->storage->readJson($this->knowledgeBasePath);
        
        if (!$kb || empty($kb['entries'])) {
            return null;
        }
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($kb['entries'] as $entry) {
            $similarity = $this->calculateSimilarity($question, $entry['question']);
            
            if ($similarity >= $this->similarityThreshold && $similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $entry;
            }
        }
        
        if ($bestMatch) {
            // Update usage count
            $this->incrementUsageCount($bestMatch['id']);
            
            return [
                'found' => true,
                'answer' => $bestMatch['answer'],
                'similarity' => $bestScore,
                'source' => 'knowledge_base',
                'original_question' => $bestMatch['question']
            ];
        }
        
        return null;
    }
    
    /**
     * Search member's past chats for similar questions
     */
    public function searchMemberChats($memberId, $question) {
        $chatsPath = $this->storage->getMemberChatsPath($memberId);
        $chatFiles = $this->storage->listFiles($chatsPath, 'json');
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($chatFiles as $file) {
            $chat = $this->storage->readJson($chatsPath . '/' . $file);
            
            if (!$chat || empty($chat['messages'])) continue;
            
            // Check each user message in the chat
            for ($i = 0; $i < count($chat['messages']); $i++) {
                $msg = $chat['messages'][$i];
                
                if ($msg['role'] !== 'user') continue;
                
                $similarity = $this->calculateSimilarity($question, $msg['content']);
                
                if ($similarity >= $this->similarityThreshold && $similarity > $bestScore) {
                    // Get the assistant's response
                    if (isset($chat['messages'][$i + 1]) && $chat['messages'][$i + 1]['role'] === 'assistant') {
                        $bestScore = $similarity;
                        $bestMatch = [
                            'question' => $msg['content'],
                            'answer' => $chat['messages'][$i + 1]['content']
                        ];
                    }
                }
            }
        }
        
        if ($bestMatch) {
            return [
                'found' => true,
                'answer' => $bestMatch['answer'],
                'similarity' => $bestScore,
                'source' => 'member_history',
                'original_question' => $bestMatch['question']
            ];
        }
        
        return null;
    }
    
    /**
     * Add Q&A to knowledge base
     */
    public function addToKnowledgeBase($question, $answer) {
        $kb = $this->storage->readJson($this->knowledgeBasePath);
        
        if (!$kb) {
            $kb = ['version' => '1.0', 'entries' => []];
        }
        
        // Check if similar question already exists
        foreach ($kb['entries'] as &$entry) {
            $similarity = $this->calculateSimilarity($question, $entry['question']);
            if ($similarity >= 0.8) {
                // Update existing entry with better answer if needed
                $entry['updated_at'] = $this->storage->getTimestamp();
                $entry['usage_count'] = ($entry['usage_count'] ?? 0) + 1;
                $this->storage->writeJson($this->knowledgeBasePath, $kb);
                return true;
            }
        }
        
        // Add new entry
        $kb['entries'][] = [
            'id' => $this->storage->generateId(),
            'question' => $question,
            'answer' => $answer,
            'keywords' => $this->extractKeywords($question),
            'created_at' => $this->storage->getTimestamp(),
            'usage_count' => 1
        ];
        
        return $this->storage->writeJson($this->knowledgeBasePath, $kb);
    }
    
    /**
     * Increment usage count for a knowledge base entry
     */
    private function incrementUsageCount($entryId) {
        $kb = $this->storage->readJson($this->knowledgeBasePath);
        
        if (!$kb) return;
        
        foreach ($kb['entries'] as &$entry) {
            if ($entry['id'] === $entryId) {
                $entry['usage_count'] = ($entry['usage_count'] ?? 0) + 1;
                $entry['last_used'] = $this->storage->getTimestamp();
                break;
            }
        }
        
        $this->storage->writeJson($this->knowledgeBasePath, $kb);
    }
    
    /**
     * Get knowledge base statistics
     */
    public function getStats() {
        $kb = $this->storage->readJson($this->knowledgeBasePath);
        
        if (!$kb || empty($kb['entries'])) {
            return [
                'total_entries' => 0,
                'total_usage' => 0
            ];
        }
        
        $totalUsage = 0;
        foreach ($kb['entries'] as $entry) {
            $totalUsage += $entry['usage_count'] ?? 0;
        }
        
        return [
            'total_entries' => count($kb['entries']),
            'total_usage' => $totalUsage
        ];
    }
}
