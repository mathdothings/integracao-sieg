<?php

namespace App\Contracts;

interface SiegConfigInterface
{
    public function __construct(
        string $clientId,
        string $clientSecret
    );
}
