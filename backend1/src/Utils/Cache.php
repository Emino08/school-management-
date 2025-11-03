<?php

namespace App\Utils;

/**
 * Simple File-based Cache Implementation
 *
 * Provides caching for expensive database queries to improve performance.
 * Particularly useful for dashboard statistics that don't change frequently.
 *
 * Usage:
 *   $cache = new Cache();
 *   $data = $cache->remember('dashboard_stats_' . $adminId, 300, function() {
 *       // Expensive database query here
 *       return $result;
 *   });
 */
class Cache
{
    private $cacheDir;
    private $enabled;

    public function __construct()
    {
        // Cache directory in backend root
        $this->cacheDir = __DIR__ . '/../../cache';
        $this->enabled = true;

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        // Disable cache if directory is not writable
        if (!is_writable($this->cacheDir)) {
            $this->enabled = false;
        }
    }

    /**
     * Get cache file path for a given key
     */
    private function getCachePath($key)
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Check if cache exists and is not expired
     */
    public function has($key, $ttl = 300)
    {
        if (!$this->enabled) {
            return false;
        }

        $path = $this->getCachePath($key);

        if (!file_exists($path)) {
            return false;
        }

        // Check if cache has expired
        $age = time() - filemtime($path);
        if ($age > $ttl) {
            @unlink($path);
            return false;
        }

        return true;
    }

    /**
     * Get cached value
     */
    public function get($key, $default = null)
    {
        if (!$this->enabled) {
            return $default;
        }

        $path = $this->getCachePath($key);

        if (!file_exists($path)) {
            return $default;
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return $default;
        }

        $decoded = json_decode($data, true);
        return $decoded !== null ? $decoded : $default;
    }

    /**
     * Store value in cache
     */
    public function set($key, $value, $ttl = 300)
    {
        if (!$this->enabled) {
            return false;
        }

        $path = $this->getCachePath($key);
        $data = json_encode($value);

        return @file_put_contents($path, $data) !== false;
    }

    /**
     * Delete cached value
     */
    public function forget($key)
    {
        if (!$this->enabled) {
            return false;
        }

        $path = $this->getCachePath($key);

        if (file_exists($path)) {
            return @unlink($path);
        }

        return true;
    }

    /**
     * Clear all cache
     */
    public function flush()
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return false;
        }

        $files = glob($this->cacheDir . '/*.cache');
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get or set cache value using a callback
     *
     * If cache exists and is valid, return cached value.
     * Otherwise, execute callback, cache result, and return it.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     * @param callable $callback Function to execute if cache miss
     * @return mixed
     */
    public function remember($key, $ttl, callable $callback)
    {
        if ($this->has($key, $ttl)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Clean up expired cache files
     */
    public function cleanup()
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return 0;
        }

        $files = glob($this->cacheDir . '/*.cache');
        $count = 0;
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete files older than 1 hour
                if (($now - filemtime($file)) > 3600) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check if caching is enabled
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return [
                'enabled' => false,
                'total_files' => 0,
                'total_size' => 0
            ];
        }

        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return [
            'enabled' => true,
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'cache_dir' => $this->cacheDir
        ];
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
