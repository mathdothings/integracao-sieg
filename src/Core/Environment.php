<?php

namespace App\Core;

use App\Traits\HasEnvironment;

/**
 * Class Environment
 * 
 * Manages environment-specific configuration by loading and exposing
 * variables from the .env file.
 */
final class Environment
{
    use HasEnvironment;

    public readonly ?string $SIEG_AQUARIUS_CLIENT_ID;
    public readonly ?string $SIEG_AQUARIUS_CLIENT_SECRET;

    public function __construct()
    {
        $this->SIEG_AQUARIUS_CLIENT_ID = $this->env("SIEG_AQUARIUS_CLIENT_ID");
        $this->SIEG_AQUARIUS_CLIENT_SECRET = $this->env("SIEG_AQUARIUS_CLIENT_SECRET");
    }
}
