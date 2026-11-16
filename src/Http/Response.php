<?php

namespace App\Http;

use App\Core\Json;

/**
 * Class Response
 * 
 * Represents an HTTP response with accessors for common data points 
 * like status code, body, and request information.
 */
class Response
{
    /**
     * Response constructor.
     * 
     * @param int $status The HTTP status code
     * @param string|null $body The response body
     * @param string|null $error Any cURL error message
     * @param array $info Detailed request/response information from cURL
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $body = null,
        public readonly ?string $error = null,
        public readonly array $info = []
    ) {}

    /**
     * Decodes the response body from JSON into an associative array.
     * 
     * @return array|null The decoded array or null if the body is empty or invalid JSON
     */
    public function json(): ?array
    {
        if (empty($this->body)) {
            return null;
        }

        return Json::decode($this->body);
    }

    /**
     * Decodes the response body from JSON into an associative array.
     * 
     * @return array|null The decoded array or null if the body is empty or invalid JSON
     */
    public function pretty(): ?string
    {
        if (empty($this->body)) {
            return null;
        }

        return Json::pretty(Json::decode($this->body));
    }

    /**
     * Convenience method to check if the response status is in the 2xx (Success) range.
     * 
     * @return bool
     */
    public function ok(): bool
    {
        $httpStatus = HttpStatus::fromInt($this->status);
        return $httpStatus ? $httpStatus->isSuccessful() : ($this->status >= 200 && $this->status < 300);
    }

    /**
     * Gets the HttpStatus enum instance for the current status code.
     * 
     * @return HttpStatus|null
     */
    public function getHttpStatus(): ?HttpStatus
    {
        return HttpStatus::fromInt($this->status);
    }
}
