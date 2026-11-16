<?php

namespace App\Http;

use App\Http\HttpStatus;
use App\Http\Response;
use App\Log\LoggerInterface;
use App\Log\LogMessages;
use App\Core\Json;
use App\Exceptions\Http\HttpResponseException;

/**
 * 
 * A robust cURL-based HTTP client that supports SSL certificates,
 * custom headers, and automatic retry logic for transient failures.
 */
class HttpClient
{
    /** @var int The maximum number of retry attempts for a single request */
    private const MAX_RETRIES = 5;

    /** @var int The base delay (in seconds) between retry attempts */
    private const RETRY_DELAY = 1;

    /** @var array Default cURL options for every request */
    private array $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
    ];

    /** @var array SSL configurations indexed by hostname */
    private array $hostSslOptions = [];

    /** @var array Headers to be sent with every request */
    private array $headers = [];

    /** @var LoggerInterface|null Optional logger for tracking activity */
    private ?LoggerInterface $logger;

    /**
     * 
     * @param LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Sets the default headers for all subsequent requests.
     * 
     * @param array $headers An associative array of headers (e.g., ['X-Header' => 'Value'])
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Sets specific SSL options for a given host.
     * 
     * Use '*' as the host to apply options to all requests that don't have
     * a specific host configuration.
     * 
     * @param string $host The hostname or '*' for all
     * @param array $options An array of CURLOPT SSL options
     * @return self
     */
    public function setSslOptionsForHost(string $host, array $options): self
    {
        $this->hostSslOptions[$host] = $options;
        return $this;
    }

    /**
     * Configures the client certificate for a specific host.
     * 
     * @param string $certPath Path to the certificate file
     * @param string $password Password for the certificate, if any
     * @param string $host The hostname or '*' for all
     * @return self
     */
    public function setSslCert(string $certPath, string $password = '', string $host = '*'): self
    {
        $options = $this->hostSslOptions[$host] ?? [];
        $options[CURLOPT_SSLCERT] = $certPath;
        if ($password) {
            $options[CURLOPT_SSLCERTPASSWD] = $password;
        }
        $this->hostSslOptions[$host] = $options;
        return $this;
    }

    /**
     * Configures the private key for a specific host.
     * 
     * @param string $keyPath Path to the private key file
     * @param string $password Password for the private key, if any
     * @param string $host The hostname or '*' for all
     * @return self
     */
    public function setSslKey(string $keyPath, string $password = '', string $host = '*'): self
    {
        $options = $this->hostSslOptions[$host] ?? [];
        $options[CURLOPT_SSLKEY] = $keyPath;
        if ($password) {
            $options[CURLOPT_SSLKEYPASSWD] = $password;
        }
        $this->hostSslOptions[$host] = $options;
        return $this;
    }

    /**
     * Performs a GET request.
     * 
     * @param string $url The destination URL
     * @param array $params Query parameters to append to the URL
     * @param array $headers Additional headers for this specific request
     * @return Response The response object
     * @throws \Exception If the request fails after all retry attempts
     */
    public function get(string $url, $params = [], array $headers = []): Response
    {
        return $this->request('GET', $url, $params, $headers);
    }

    /**
     * Performs a POST request.
     * 
     * @param string $url The destination URL
     * @param mixed $data The body data (array or string)
     * @param array $headers Additional headers for this specific request
     * @return Response The response object
     * @throws \Exception If the request fails after all retry attempts
     */
    public function post(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * Performs a PUT request.
     * 
     * @param string $url The destination URL
     * @param mixed $data The body data (array or string)
     * @param array $headers Additional headers for this specific request
     * @return Response The response object
     * @throws \Exception If the request fails after all retry attempts
     */
    public function put(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * Performs a DELETE request.
     * 
     * @param string $url The destination URL
     * @param mixed $data The body data (array or string)
     * @param array $headers Additional headers for this specific request
     * @return Response The response object
     * @throws \Exception If the request fails after all retry attempts
     */
    public function delete(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('DELETE', $url, $data, $headers);
    }

    /**
     * Core request method that wraps result in retry logic.
     * 
     * @param string $method GET, POST, PUT, DELETE, etc.
     * @param string $url The destination URL
     * @param mixed $data Request body or query params
     * @param array $headers Additional headers for this request
     * @return Response The response object
     * @throws \Exception If the request fails after all retry attempts
     */
    public function request(string $method, string $url, $data = null, array $headers = []): Response
    {
        return $this->retry(function () use ($method, $url, $data, $headers) {
            return $this->performRawRequest($method, $url, $data, $headers);
        });
    }

    /**
     * Implements the retry and exponential backoff logic.
     * 
     * @param callable $operation The request operation to execute
     * @return Response The successful response
     * @throws \Exception if the request fails after max retries or on non-retryable errors
     */
    private function retry(callable $operation): Response
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                /** @var Response $response */
                $response = $operation();
                $status = $response->getHttpStatus();

                if ($this->logger) {
                    $this->logger->debug(LogMessages::HTTP_REQUEST_COMPLETED, [
                        'attempt' => $attempt,
                        'status' => $response->status,
                        'error' => $response->error ?: null
                    ]);
                }

                if ($status instanceof HttpStatus) {
                    return $response;
                }

                $errorMessage = $response->error ?: 'Erro desconhecido no HttpClient';
                throw new HttpResponseException("HTTP {$response->status}: {$errorMessage}", $response->status, $response->body, $response->info);
            } catch (HttpResponseException $exception) {
                $statusCode = $exception->getStatusCode();

                // Don't retry client errors (4xx) except 429 (Too Many Requests)
                if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                    if ($this->logger) {
                        $this->logger->error(LogMessages::NON_RETRYABLE_ERROR, ['status' => $statusCode]);
                    }
                    throw $exception;
                }

                if ($attempt < self::MAX_RETRIES) {
                    $delay = self::RETRY_DELAY * pow(2, $attempt - 1);
                    if ($this->logger) {
                        $this->logger->warning(LogMessages::retryingRequest($delay), [
                            'attempt' => $attempt,
                            'next_attempt' => $attempt + 1,
                            'status' => isset($status) ? $status->value : 'N/A'
                        ]);
                    }
                    sleep($delay);
                }
                $lastException = $exception;
            } catch (\Exception $exception) {
                // Handle non-HTTP exceptions (like curl setup errors)
                $lastException = $exception;
                if ($attempt < self::MAX_RETRIES) {
                    $delay = self::RETRY_DELAY * pow(2, $attempt - 1);
                    sleep($delay);
                }
            }
        }

        // If all retries failed, throw the last exception
        throw $lastException;
    }

    /**
     * Executes the actual cURL request.
     * 
     * @param string $method The HTTP method
     * @param string $url The URL
     * @param mixed $data The payload
     * @param array $headers Headers for this request
     * @return Response Response object containing body, status, error, and info
     */
    private function performRawRequest(string $method, string $url, $data = null, array $headers = []): Response
    {
        $ch = curl_init();

        $options = $this->defaultOptions;
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);

        $mergedHeaders = array_merge($this->headers, $headers);

        $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($mergedHeaders);

        if ($method === 'GET') {
            $url .= '?' . http_build_query($data, '', '&', PHP_QUERY_RFC3986);
            $options[CURLOPT_URL] = $url;
        } else {
            $options[CURLOPT_POSTFIELDS] = is_array($data)
                ? http_build_query($data, '', '&', PHP_QUERY_RFC3986)
                : ($data ?? '');

            // If data is JSON, ensure Content-Type is set if not already
            if ($this->isJson($data) && !isset($mergedHeaders['Content-Type'])) {
                $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
                $options[CURLOPT_HTTPHEADER][] = 'Accept: application/json';
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);

        unset($ch);

        return new Response(
            status: $info['http_code'],
            body: $response !== false ? $response : null,
            error: $error ?: null,
            info: $info
        );
    }

    /**
     * Formats headers from associative array to cURL format.
     * 
     * @param array $headers
     * @return array
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $formatted[] = $value;
            } else {
                $formatted[] = "$key: $value";
            }
        }
        return $formatted;
    }

    /**
     * Checks if a string is a valid JSON.
     * 
     * @param mixed $data
     * @return bool
     */
    private function isJson($data): bool
    {
        if (!is_string($data)) return false;
        return Json::isValid($data);
    }
}
