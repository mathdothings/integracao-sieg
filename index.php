<?php

use App\Repositories\CompanyRepository;
use App\Services\SiegServiceFactory;

const BASEPATH = __DIR__;
require_once BASEPATH . '/vendor/autoload.php';

$companies = new CompanyRepository()->list();

foreach ($companies as $company) {
    $service = SiegServiceFactory::create($company);

    $tz = new \DateTimeZone('America/Sao_Paulo');

    $inicio = new \DateTimeImmutable('2026-11-11 16:00:00', $tz);
    $fim    = new \DateTimeImmutable('2026-11-15 23:59:59', $tz);

    $payload = [
        'CnpjEmit'         => '54685054000167',
        'DataUploadInicio' => $inicio->format('Y-m-d\TH:i:s.0000P\Z'),
        'DataUploadFim'    => $fim->format('Y-m-d\TH:i:s.9999P\Z'),
    ];

    var_dump($service->countXml($payload)->body);
}
