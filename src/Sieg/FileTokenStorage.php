<?php

namespace App\Sieg;

use App\Core\Json;
use App\Contracts\TokenStorageInterface;

/**
 * Class FileTokenStorage
 * 
 * Implements a file-based storage for Sicoob API access tokens,
 * organized by Client ID and then by Scope.
 */
class FileTokenStorage implements TokenStorageInterface
{
    /** @var string The absolute path to the JSON storage file */
    private string $filePath;

    /**
     * FileTokenStorage constructor.
     * 
     * @param string $filePath Optional path to the storage file.
     */
    public function __construct(string $filePath = BASEPATH . '/storage/sieg-token.json')
    {
        $this->filePath = $filePath;
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Opens a file using a shared lock for safe parallel reading
     *
     * @param string $filepath Full path to the file
     * @return string|false
     */
    public function sharedLockRead(string $filepath): string|false
    {
        $content = false;

        $fp = fopen($filepath, 'r');
        if ($fp === false) {
            return false;
        }

        if (flock($fp, LOCK_SH)) {
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        }

        fclose($fp);

        return $content;
    }

    /**
     * Retrieves a stored token for a specific client and scope.
     *
     * @param string $clientId The unique client identifier
     * @return array|null Returns the token data array or null if missing or expired.
     */
    public function get(string $clientId): ?array
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = $this->sharedLockRead($this->filePath);
        if ($content === false || $content === '') {
            return null;
        }

        $allData = Json::decode($content) ?: [];

        // Navigate: ClientID -> Scope
        $data = $allData[$clientId] ?? null;

        if (!$data || !isset($data['access_token']) || !isset($data['expires_at'])) {
            return null;
        }

        if ($data['expires_at'] <= time()) {
            return null;
        }

        return $data;
    }

    /**
     * Persists a new token organized by client and scope.
     * 
     * @param string $clientId The client identifier
     * @param string $token The access token string
     * @param int $expiresIn Lifetime of the token in seconds
     * @return void
     */
    public function set(string $clientId, string $token, int $expiresIn): void
    {
        $content = file_exists($this->filePath) ? file_get_contents($this->filePath) : '';

        // Treat empty or whitespace-only files as empty data
        $allData = [];
        if (trim($content) !== '') {
            try {
                $allData = Json::decode($content) ?: [];
            } catch (\Throwable $e) {
                $allData = [];
            }
        }

        if (!isset($allData[$clientId])) {
            $allData[$clientId] = [];
        }

        $allData[$clientId] = [
            'access_token' => $token,
            'expires_at' => time() + $expiresIn - 10
        ];

        file_put_contents($this->filePath, Json::pretty($allData), LOCK_EX);
    }

    /**
     * Removes a stored token for a specific client and scope.
     * 
     * @param string $clientId
     * @return void
     */
    public function remove(string $clientId): void
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        $content = file_get_contents($this->filePath);
        $allData = Json::decode($content) ?: [];

        if (isset($allData[$clientId])) {
            unset($allData[$clientId]);

            // Clean up empty client entries to keep the file tidy
            if (empty($allData[$clientId])) {
                unset($allData[$clientId]);
            }

            file_put_contents($this->filePath, Json::pretty($allData), LOCK_EX);
        }
    }

    /**
     * Removes the storage file, clearing all cached tokens for all clients.
     * 
     * @return void
     */
    public function clear(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
