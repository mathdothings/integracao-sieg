<?php

namespace App\Services;

use App\Constants\SiegEndpoints;
use App\Entities\CompanyEntity;
use App\Http\Response;
use App\Sieg\SiegHttpClient;
use App\Core\Json;

final class SiegService
{
    public function __construct(
        private SiegHttpClient $client
    ) {}

    public function sendXml(string $xml): Response
    {
        return $this->client->apiRequest('POST', SiegEndpoints::SEND_XML, ['Xml' => base64_encode($xml)]);
    }

    public function countXml(array $payload): Response
    {
        return $this->client->apiRequest(
            'POST',
            SiegEndpoints::COUNT_XML,
            Json::encode($payload)
        );
    }

    public function downloadXml(array $payload): Response
    {
        return $this->client->apiRequest(
            'POST',
            SiegEndpoints::DOWNLOAD_XML,
            Json::encode($payload)
        );
    }
}
