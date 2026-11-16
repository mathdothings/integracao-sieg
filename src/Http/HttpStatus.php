<?php

namespace App\Http;

/**
 * Enum HttpStatus
 * 
 * Represents common HTTP status codes and provides helper methods 
 * for checking status ranges.
 */
enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case NOT_ACCEPTABLE = 406;
    case TOO_MANY_REQUESTS = 429;
    case INTERNAL_SERVER_ERROR = 500;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    /**
     * Checks if the status code is in the 2xx range.
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    /**
     * Checks if the status code is in the 3xx range.
     * 
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->value >= 300 && $this->value < 400;
    }

    /**
     * Checks if the status code is in the 4xx range.
     * 
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * Checks if the status code is in the 5xx range.
     * 
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    /**
     * Checks if the status code represents an error (4xx or 5xx).
     * 
     * @return bool
     */
    public function isError(): bool
    {
        return $this->value >= 400;
    }

    /**
     * Tries to create an instance from an integer, or returns a null if not defined.
     * 
     * @param int $code
     * @return self|null
     */
    public static function fromInt(int $code): ?self
    {
        return self::tryFrom($code);
    }
}
