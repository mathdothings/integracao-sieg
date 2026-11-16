<?php

namespace  App\Core;

use JsonException;

class Json
{
    /**
     * Default JSON encode options
     */
    private const DEFAULT_ENCODE_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Default JSON decode options
     */
    private const DEFAULT_DECODE_OPTIONS = true; // Returns associative array

    /**
     * Encode data to JSON string
     * 
     * @param mixed $data The data to encode
     * @param int|null $options JSON encode options (null for default)
     * @param int $depth Maximum depth
     * @return string JSON encoded string
     * @throws JsonException When encoding fails
     */
    public static function encode($data, ?int $options = null, int $depth = 512): string
    {
        $options = $options ?? self::DEFAULT_ENCODE_OPTIONS;

        $json = json_encode($data, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(
                'JSON encoding failed: ' . json_last_error_msg(),
                json_last_error()
            );
        }

        return $json;
    }

    /**
     * Decode JSON string to PHP data
     * 
     * @param string $json The JSON string to decode
     * @param bool|null $associative Whether to return associative array (null for default)
     * @param int $depth Maximum depth
     * @param int|null $options JSON decode options
     * @return mixed Decoded PHP data
     * @throws JsonException When decoding fails
     */
    public static function decode(string $json, ?bool $associative = null, int $depth = 512, ?int $options = null): mixed
    {
        $associative = $associative ?? self::DEFAULT_DECODE_OPTIONS;
        $options = $options ?? 0;

        $data = json_decode($json, $associative, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(
                'JSON decoding failed: ' . json_last_error_msg(),
                json_last_error()
            );
        }

        return $data;
    }

    /**
     * Encode data to JSON string with pretty print
     * 
     * @param mixed $data The data to encode
     * @param int|null $options Additional JSON options
     * @return string Pretty printed JSON string
     * @throws JsonException When encoding fails
     */
    public static function pretty($data, ?int $options = null): string
    {
        $options = ($options ?? 0) | JSON_PRETTY_PRINT;
        return self::encode($data, $options | self::DEFAULT_ENCODE_OPTIONS);
    }

    /**
     * Check if string is valid JSON
     * 
     * @param string $json The JSON string to validate
     * @return bool True if valid JSON, false otherwise
     */
    public static function isValid(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Encode with additional error handling and logging
     * 
     * @param mixed $data The data to encode
     * @param callable|null $errorCallback Custom error callback
     * @return string|null JSON string or null on failure
     */
    public static function encodeSafe($data, ?callable $errorCallback = null): ?string
    {
        try {
            return self::encode($data);
        } catch (JsonException $e) {
            if ($errorCallback) {
                $errorCallback($e->getMessage(), $e->getCode());
            }
            return null;
        }
    }

    /**
     * Decode with additional error handling and logging
     * 
     * @param string $json The JSON string to decode
     * @param callable|null $errorCallback Custom error callback
     * @return mixed Decoded data or null on failure
     */
    public static function decodeSafe(string $json, ?callable $errorCallback = null): mixed
    {
        try {
            return self::decode($json);
        } catch (JsonException $e) {
            if ($errorCallback) {
                $errorCallback($e->getMessage(), $e->getCode());
            }
            return null;
        }
    }

    /**
     * Get the last JSON error code
     * 
     * @return int The last error code
     */
    public static function getLastErrorCode(): int
    {
        return json_last_error();
    }

    /**
     * Get the last JSON error message
     * 
     * @return string The last error message
     */
    public static function getLastError(): string
    {
        return json_last_error_msg();
    }
}
