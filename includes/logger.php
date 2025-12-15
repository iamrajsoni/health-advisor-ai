<?php
/**
 * Logger Class for Health Advisor AI
 * Handles error logging, debug logging, and request tracking
 */

class Logger {
    private static $logDir;
    private static $initialized = false;
    
    /**
     * Initialize the logger
     */
    public static function init() {
        if (self::$initialized) return;
        
        self::$logDir = dirname(__DIR__) . '/logs';
        
        // Create logs directory if it doesn't exist
        if (!file_exists(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        // Set custom error handler
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        
        self::$initialized = true;
    }
    
    /**
     * Get log file path for today
     */
    private static function getLogFile($type = 'app') {
        $date = date('Y-m-d');
        return self::$logDir . "/{$type}_{$date}.log";
    }
    
    /**
     * Write to log file
     */
    private static function write($level, $message, $context = [], $type = 'app') {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        $logFile = self::getLogFile($type);
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        return $logLine;
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        return self::write('INFO', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = []) {
        return self::write('DEBUG', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        return self::write('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        return self::write('ERROR', $message, $context, 'error');
    }
    
    /**
     * Log API request
     */
    public static function api($endpoint, $method, $data = [], $response = null) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => $data,
            'response' => $response
        ];
        return self::write('API', "API Call: {$method} {$endpoint}", $context, 'api');
    }
    
    /**
     * Log chat message
     */
    public static function chat($username, $message, $response = null) {
        $context = [
            'username' => $username,
            'user_message' => $message,
            'ai_response' => $response ? substr($response, 0, 200) . '...' : null
        ];
        return self::write('CHAT', "Chat from {$username}", $context, 'chat');
    }
    
    /**
     * Custom error handler
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE_ERROR',
            E_NOTICE => 'NOTICE',
            E_STRICT => 'STRICT',
            E_DEPRECATED => 'DEPRECATED'
        ];
        
        $type = $errorTypes[$errno] ?? 'UNKNOWN';
        $message = "{$type}: {$errstr} in {$errfile} on line {$errline}";
        
        self::write('PHP_ERROR', $message, [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline
        ], 'error');
        
        // Don't execute PHP internal error handler
        return false;
    }
    
    /**
     * Custom exception handler
     */
    public static function exceptionHandler($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage();
        
        self::write('EXCEPTION', $message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], 'error');
    }
    
    /**
     * Log request start (call at beginning of each request)
     */
    public static function requestStart() {
        self::init();
        
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        
        self::write('REQUEST', ">>> Request Start: {$method} {$uri}", [
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
        ]);
    }
    
    /**
     * Log request end
     */
    public static function requestEnd($status = 200) {
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        
        self::write('REQUEST', "<<< Request End: {$method} {$uri} [{$status}]");
    }
    
    /**
     * Get recent logs from a specific log file
     */
    public static function getRecentLogs($type = 'error', $lines = 50) {
        self::init();
        
        $logFile = self::getLogFile($type);
        
        if (!file_exists($logFile)) {
            return "No logs found for type: {$type}";
        }
        
        $content = file_get_contents($logFile);
        $allLines = explode(PHP_EOL, trim($content));
        $recentLines = array_slice($allLines, -$lines);
        
        return implode(PHP_EOL, $recentLines);
    }
    
    /**
     * Clear old log files (older than 7 days)
     */
    public static function cleanOldLogs($daysToKeep = 7) {
        self::init();
        
        $files = glob(self::$logDir . '/*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }
        
        self::info("Log cleanup completed", ['deleted_files' => $deleted]);
        return $deleted;
    }
}

// Auto-initialize logger
Logger::init();
