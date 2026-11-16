<?php

namespace App\Services;

use App\Core\Environment;
use App\Entities\CompanyEntity;
use App\Http\HttpClient;
use App\Log\FileLogger;
use App\Sieg\FileTokenStorage;
use App\Sieg\SiegConfig;
use App\Sieg\SiegHttpClient;

final class SiegServiceFactory
{
    public static function create(CompanyEntity $entity): SiegService
    {
        $env = new Environment();
        $logger = new FileLogger();

        return new SiegService(
            new SiegHttpClient(
                $entity,
                new SiegConfig(
                    $env->SIEG_AQUARIUS_CLIENT_ID,
                    $env->SIEG_AQUARIUS_CLIENT_SECRET
                ),
                new HttpClient($logger),
                new FileTokenStorage(),
                $logger
            )
        );
    }
}
