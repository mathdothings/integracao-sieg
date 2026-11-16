<?php

namespace App\Sieg;

use App\Contracts\SiegConfigInterface;
use App\Core\Environment;

/**
 * Data transfer object (DTO) that holds all Aquarius configuration 
 * for communicating with the SIEG API.
 */
class SiegConfig implements SiegConfigInterface
{
    /**
     * SiegConfig constructor.
     * 
     * @param string $clientId The SIEG Client ID provided by the developer portal.
     * @param string $clientSecret The SIEG Client Secret provided by the developer portal.
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
    ) {}

    public static function create(): self
    {
        $environment = new Environment();

        return new self(
            clientId: $environment->SIEG_AQUARIUS_CLIENT_ID,
            clientSecret: $environment->SIEG_AQUARIUS_CLIENT_SECRET
        );
    }
}
