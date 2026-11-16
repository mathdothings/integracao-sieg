<?php

namespace App\Contracts;

/**
 * Interface TokenStorageInterface
 * 
 * Defines the contract for persistence layers that handle SIEG access tokens.
 * Implementations should support multiple scopes and handle token expiration.
 */
interface TokenStorageInterface
{
    /**
     * Retrieves a stored token for a specific scope.
     * 
     * @return array|null An array containing ['access_token', 'expires_at'] or null if not found/expired.
     */
    public function get(string $clientId): ?array;

    /**
     * Stores a retrieved access token.
     * 
     * @param string $token The raw access token string.
     * @param int $expiresIn The expiration time in seconds (relative to now).
     * @return void
     */
    public function set(string $clientId, string $token, int $expiresIn): void;

    /**
     * Removes a stored token for a specific scope.
     * 
     * @return void
     */
    public function remove(string $clientId): void;

    /**
     * Clears all cached tokens from the storage.
     * 
     * @return void
     */
    public function clear(): void;
}
