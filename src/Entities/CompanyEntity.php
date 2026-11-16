<?php

namespace App\Entities;

use JsonSerializable;
use Override;

final class CompanyEntity implements JsonSerializable
{
    /**
     * If Aquarius aren't a SIEG customoer, the API integration
     * will use the Aquarius customer's API Key
     * If we have access to the OAuth token, use the OAuth token, instead
     */
    public string $name;
    public string $cnpj;
    public string $apiKey;
    public string $oAuthToken;

    #[Override]
    public function jsonSerialize(): mixed
    {
        return [
            'nome' => $this->name,
            'cnpj' => $this->cnpj,
            'api_key' => $this->apiKey,
            'o_auth_token' => $this->oAuthToken,
        ];
    }
}
