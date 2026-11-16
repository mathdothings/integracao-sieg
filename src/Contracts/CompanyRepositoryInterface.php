<?php

namespace App\Contracts;

use App\Entities\CompanyEntity;

interface CompanyRepositoryInterface
{
    /**
     * @return CompanyEntity[]
     */
    public function list(): array;
}
