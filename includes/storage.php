<?php
/**
 * Storage Manager - File-based storage utilities
 * Handles all file CRUD operations for the Health Advisor AI
 */

class Storage
{
    private $basePath;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/..';
    }

    /**
     * Get the base path of the application
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Read JSON file and return as array
     */
    public function readJson($filePath)
    {
        $fullPath = $this->resolvePath($filePath);
        if (!file_exists($fullPath)) {
            return null;
        }
        $content = file_get_contents($fullPath);
        return json_decode($content, true);
    }

    /**
     * Write array to JSON file
     */
    public function writeJson($filePath, $data)
    {
        $fullPath = $this->resolvePath($filePath);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Append to JSON array file
     */
    public function appendToJsonArray($filePath, $item)
    {
        $data = $this->readJson($filePath);
        if ($data === null) {
            $data = [];
        }
        $data[] = $item;
        return $this->writeJson($filePath, $data);
    }

    /**
     * Check if file exists
     */
    public function exists($filePath)
    {
        return file_exists($this->resolvePath($filePath));
    }

    /**
     * Delete file
     */
    public function delete($filePath)
    {
        $fullPath = $this->resolvePath($filePath);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * List files in directory
     */
    public function listFiles($dirPath, $extension = null)
    {
        $fullPath = $this->resolvePath($dirPath);
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $items = scandir($fullPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $filePath = $fullPath . '/' . $item;
            if (is_file($filePath)) {
                if ($extension === null || pathinfo($item, PATHINFO_EXTENSION) === $extension) {
                    $files[] = $item;
                }
            }
        }

        return $files;
    }

    /**
     * Create member folder structure
     */
    public function createMemberFolder($memberId)
    {
        $memberPath = $this->basePath . '/members/' . $memberId;
        $chatsPath = $memberPath . '/chats';

        if (!is_dir($memberPath)) {
            mkdir($memberPath, 0755, true);
        }
        if (!is_dir($chatsPath)) {
            mkdir($chatsPath, 0755, true);
        }

        return $memberPath;
    }

    /**
     * Get member's chat folder path
     */
    public function getMemberChatsPath($memberId)
    {
        return 'members/' . $memberId . '/chats';
    }

    /**
     * Resolve relative path to absolute
     */
    private function resolvePath($path)
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }
        return $this->basePath . '/' . $path;
    }

    /**
     * Generate unique ID
     */
    public function generateId()
    {
        return uniqid() . '_' . time();
    }

    /**
     * Get current timestamp
     */
    public function getTimestamp()
    {
        return date('Y-m-d H:i:s');
    }
    /**
     * Rename file or directory
     */
    public function rename($oldPath, $newPath)
    {
        $fullOldPath = $this->resolvePath($oldPath);
        $fullNewPath = $this->resolvePath($newPath);

        if (file_exists($fullOldPath) && !file_exists($fullNewPath)) {
            return rename($fullOldPath, $fullNewPath);
        }
        return false;
    }
}
