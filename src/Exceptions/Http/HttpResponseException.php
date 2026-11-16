<?php

namespace App\Exceptions\Http;

use App\Http\HttpStatus;

/**
 * Exception thrown when an HTTP request fails after all retries or on non-retryable errors.
 * Carries the HTTP status code and response details.
 */
class HttpResponseException extends \Exception
{
    private int $statusCode;
    private ?string $responseBody;
    private array $info;

    /**
     * HttpResponseException constructor.
     * 
     * @param string $message Exception message
     * @param int $statusCode HTTP status code (e.g., 401, 500)
     * @param string|null $responseBody Raw response body from the server
     * @param array $info cURL info array
     */
    public function __construct(string $message, int $statusCode, ?string $responseBody = null, array $info = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->info = $info;
    }

    /**
     * Gets the HTTP status code.
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Gets the raw response body.
     * 
     * @return string|null
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Gets the cURL info array.
     * 
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Tries to map the status code to a HttpStatus Enum.
     * 
     * @return HttpStatus|null
     */
    public function getStatus(): ?HttpStatus
    {
        return HttpStatus::tryFrom($this->statusCode);
    }

    /**
     * Converts this exception into a standard Response object.
     * 
     * @return \App\Http\Response
     */
    public function toResponse(): \App\Http\Response
    {
        return new \App\Http\Response(
            $this->statusCode,
            $this->responseBody,
            $this->getMessage(),
            $this->info
        );
    }
}
