<?php

use App\Repositories\CompanyRepository;
use App\Services\SiegServiceFactory;

const BASEPATH = __DIR__;
require_once BASEPATH . '/vendor/autoload.php';

$companies = new CompanyRepository()->list();

foreach ($companies as $company) {
    $service = SiegServiceFactory::create($company);

    var_dump($service->sendXml(''));
}
