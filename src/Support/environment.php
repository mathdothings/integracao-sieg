<?php

use App\Core\EnvironmentType;

function environment(): EnvironmentType
{
    $env = env('ENVIRONMENT', 'PRODUCTION');

    return match (strtoupper($env)) {
        'DEVELOPMENT' => EnvironmentType::DEVELOPMENT,
        'PRODUCTION' => EnvironmentType::PRODUCTION,
    };
}
