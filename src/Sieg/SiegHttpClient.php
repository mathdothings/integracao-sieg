<?php

namespace App\Sieg;

use App\Constants\SiegEndpoints;
use App\Http\HttpClient;
use App\Http\HttpStatus;
use App\Http\Response;
use App\Exceptions\Http\HttpResponseException;
use App\Log\LoggerInterface;
use App\Log\LogMessages;
use App\Contracts\TokenStorageInterface;
use App\Core\Json;
use App\Entities\CompanyEntity;

/**
 * Class SiegHttpClient
 * 
 * Main client class for SIEG API integration.
 * Handles authentication (OAuth2 Client Credentials) and authorized requests 
 * with automatic token caching and management.
 */
class SiegHttpClient
{
    /** @var string The base URL for the Brazil Bank REST API */
    private const BASE_URL = 'https://api.sieg.com/api/v1/';

    /** @var CompanyEntity Company-related data entity */
    private CompanyEntity $company;

    /** @var HttpClient Internal HTTP client for making requests */
    private HttpClient $httpClient;

    /** @var SiegConfig Current configuration for API access */
    private SiegConfig $config;

    /** @var TokenStorageInterface Mechanism for storing and retrieving tokens */
    private TokenStorageInterface $storage;

    /** @var LoggerInterface|null Optional logger for tracking activity */
    private ?LoggerInterface $logger;

    /**
     * SiegHttpClient constructor.
     * 
     * @param CompanyEntity $company Here is where we got the company api key and company-related stuff
     * @param SiegConfig $config API credentials and certificate paths
     * @param HttpClient $httpClient Already configured HTTP client (SSL, etc.)
     * @param TokenStorageInterface $storage Configured token storage mechanism
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        CompanyEntity $company,
        SiegConfig $config,
        HttpClient $httpClient,
        TokenStorageInterface $storage,
        ?LoggerInterface $logger = null
    ) {
        $this->company = $company;
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Authenticates and retrieves the access token for a specific scope.
     * 
     * Tries to return a cached token from storage first. If no valid token is found,
     * it performs a new authentication request.
     * 
     * @return string The valid access token
     * @throws \Exception If authentication failed
     */
    public function getAccessToken(): string
    {
        $cached = $this->storage->get($this->config->clientId);

        if ($cached) {
            if ($this->logger) {
                $this->logger->debug(LogMessages::usingCachedToken());
            }
            return $cached['access_token'];
        }

        if ($this->logger) {
            $this->logger->info(LogMessages::fetchingNewToken());
        }

        return $this->refreshToken();
    }

    /**
     * Performs the actual OAuth2 client_credentials token request.
     * 
     * @return string The newly acquired access token
     * @throws \Exception If the response is unsuccessful or the token is missing
     */
    private function refreshToken(): string
    {
        $response = $this->httpClient->request('POST', SiegEndpoints::CREATE_JWT_TOKEN, [], [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Client-Id: ' . $this->config->clientId,
            'X-Secret-Key: ' . $this->config->clientSecret,
        ]);

        if ($response->getHttpStatus() !== HttpStatus::OK) {
            throw new \Exception("Falha ao se autenticar ao SIEG." . $response->body);
        }

        $accessToken = Json::decode($response->body);

        if (empty($accessToken)) {
            throw new \Exception("Autenticação falhou: Token de acesso não encontrado na resposta.");
        }

        $expiresIn = time() + 3600 * 23; // Tokens are valid for 23 hours

        if ($this->logger) {
            $this->logger->info(LogMessages::TOKEN_REFRESH_SUCCESS, ['expires_in' => $expiresIn]);
        }

        $this->storage->set($this->config->clientId, $accessToken, $expiresIn);

        return $accessToken;
    }

    /**
     * Performs an authorized request to SEIG API.
     * 
     * This method automatically handles token acquisition, Bearer authentication headers,
     * and client_id identification required by SEIG.
     * 
     * @param string $method HTTP Method (GET, POST, PUT, DELETE, etc)
     * @param string $endpoint API Endpoint path (e.g., 'cobranca/v2/boletos')
     * @param mixed $data Request payload (query params for GET, body for others)
     * @return Response The response object
     * @throws \Exception If the request or authentication fails
     */
    public function apiRequest(string $method, string $endpoint, $data = null): Response
    {
        $url = self::BASE_URL . '/' . ltrim($endpoint, '/');
        $token = $this->getAccessToken();

        try {
            return $this->httpClient->request($method, $url, $data, [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'X-API-Key: ' . $this->company->apiKey,
            ]);
        } catch (HttpResponseException $e) {
            if ($e->getStatus() === HttpStatus::UNAUTHORIZED) {
                if ($this->logger) {
                    $this->logger->warning(LogMessages::UNAUTHORIZED_RETRY, [
                        'endpoint' => $endpoint
                    ]);
                }

                $this->storage->remove($this->config->clientId);

                $freshToken = $this->getAccessToken();

                return $this->httpClient->request($method, $url, $data, [
                    'Authorization' => 'Bearer ' . $freshToken,
                    'Accept'        => 'application/json'
                ]);
            }

            // Re-throw if it's not a 401 or if the second attempt fails
            throw $e;
        } catch (\Exception $e) {
            // Handle other non-HTTP exceptions
            throw $e;
        }
    }

    /**
     * Gets the current configuration object used by this client.
     * 
     * @return SiegConfig
     */
    public function getConfig(): SiegConfig
    {
        return $this->config;
    }

    /**
     * Gets the optional logger instance.
     * 
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
