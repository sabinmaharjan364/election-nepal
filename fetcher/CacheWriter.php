<?php
require_once __DIR__ . '/../api/config.php';

/**
 * CacheWriter
 *
 * Writes fetched ECN data to disk as JSON files.
 * Each file has a standard envelope: { fetched_at, ttl, data }
 *
 * File layout:
 *   cache/summary.json
 *   cache/parties.json
 *   cache/constituencies/index.json    (list of all constituencies)
 *   cache/constituencies/{id}.json     (per-constituency results)
 */
class CacheWriter
{
    public function writeSummary(array $data): void
    {
        $this->write(CACHE_DIR . '/summary.json', $data);
    }

    public function writeParties(array $data): void
    {
        $this->write(CACHE_DIR . '/parties.json', $data);
    }

    public function writeConstituency(int $id, array $data): void
    {
        $dir = CACHE_DIR . '/constituencies';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->write("{$dir}/{$id}.json", $data);
    }

    public function writeConstituencyIndex(array $list): void
    {
        $dir = CACHE_DIR . '/constituencies';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->write("{$dir}/index.json", $list);
    }

    /**
     * Check if a cache file is still fresh (within TTL).
     */
    public function isFresh(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }
        $cache = json_decode(file_get_contents($file), true);
        return isset($cache['fetched_at']) && (time() - $cache['fetched_at']) < CACHE_TTL;
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function write(string $path, array $data): void
    {
        $envelope = [
            'fetched_at' => time(),
            'ttl'        => CACHE_TTL,
            'data'       => $data,
        ];

        // Write atomically: write to .tmp then rename to avoid partial reads
        $tmp = $path . '.tmp';
        file_put_contents($tmp, json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmp, $path);
    }
}
