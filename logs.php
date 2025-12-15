<?php
/**
 * Log Viewer Page
 * View application logs to debug errors
 */

require_once 'includes/logger.php';

// Get log type from query string
$type = $_GET['type'] ?? 'error';
$lines = (int) ($_GET['lines'] ?? 100);
$types = ['error', 'app', 'api', 'chat'];

// Validate type
if (!in_array($type, $types)) {
    $type = 'error';
}

// Get logs
$logs = Logger::getRecentLogs($type, $lines);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Health Advisor AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .logs-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logs-title {
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .log-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .log-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .log-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .log-tab:not(.active) {
            background: rgba(255, 255, 255, 0.1);
            color: #aaa;
        }

        .log-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .log-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .log-viewer {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 1rem;
            padding: 1.5rem;
            overflow: auto;
            max-height: 80vh;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .log-line {
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .log-line:last-child {
            border-bottom: none;
        }

        .log-level-INFO {
            color: #4ade80;
        }

        .log-level-DEBUG {
            color: #60a5fa;
        }

        .log-level-WARNING {
            color: #fbbf24;
        }

        .log-level-ERROR {
            color: #f87171;
        }

        .log-level-EXCEPTION {
            color: #f87171;
        }

        .log-level-PHP_ERROR {
            color: #fb923c;
        }

        .log-level-REQUEST {
            color: #a78bfa;
        }

        .log-level-API {
            color: #22d3ee;
        }

        .log-level-CHAT {
            color: #34d399;
        }

        .log-timestamp {
            color: #888;
        }

        .empty-logs {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .refresh-btn {
            padding: 0.5rem 1rem;
            background: #333;
            border: none;
            border-radius: 0.5rem;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-btn:hover {
            background: #444;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="logs-container">
        <div class="logs-header">
            <div>
                <a href="index.php" class="back-link">← Back to Chat</a>
                <h1 class="logs-title">
                    📋 Log Viewer
                </h1>
            </div>

            <div class="log-controls">
                <button class="refresh-btn" onclick="location.reload()">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <div class="log-tabs">
            <?php foreach ($types as $t): ?>
                <a href="?type=<?php echo $t; ?>&lines=<?php echo $lines; ?>"
                    class="log-tab <?php echo $t === $type ? 'active' : ''; ?>">
                    <?php
                    $icons = ['error' => '❌', 'app' => '📱', 'api' => '🌐', 'chat' => '💬'];
                    echo ($icons[$t] ?? '📄') . ' ' . ucfirst($t);
                    ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="log-viewer" style="margin-top: 1rem;">
            <?php if (empty($logs) || $logs === "No logs found for type: {$type}"): ?>
                <div class="empty-logs">
                    <p>📭 No logs found for type: <strong><?php echo $type; ?></strong></p>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem;">
                        Logs will appear here after the application starts logging.
                    </p>
                </div>
            <?php else: ?>
                <?php
                $logLines = explode("\n", $logs);
                foreach ($logLines as $line):
                    if (empty(trim($line)))
                        continue;

                    // Extract log level for coloring
                    $levelClass = '';
                    if (preg_match('/\[(\w+)\]/', $line, $matches)) {
                        $levelClass = 'log-level-' . $matches[1];
                    }
                    ?>
                    <div class="log-line <?php echo $levelClass; ?>"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: center;">
            <label>Lines to show:</label>
            <select onchange="location.href='?type=<?php echo $type; ?>&lines=' + this.value"
                style="padding: 0.5rem; border-radius: 0.25rem; background: #333; color: white; border: 1px solid #555;">
                <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100</option>
                <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>200</option>
                <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500</option>
            </select>
        </div>
    </div>

    <script>
        // Auto-refresh every 10 seconds
        // setTimeout(() => location.reload(), 10000);
    </script>
</body>

</html>