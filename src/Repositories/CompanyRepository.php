<?php

namespace App\Repositories;

use App\Contracts\CompanyRepositoryInterface;
use App\Core\Environment;
use App\Entities\CompanyEntity;

final class CompanyRepository implements CompanyRepositoryInterface
{
    private Environment $env;

    public function __construct()
    {
        $this->env = new Environment();
    }

    private const COMPANIES = ['YETS'];
    private const ENV_FIELDS = ['NOME', 'CNPJ', 'API_KEY'];

    public function list(): array
    {
        $result = [];
        foreach (self::COMPANIES as $company) {
            $entity = new CompanyEntity();
            foreach (self::ENV_FIELDS as $field) {
                $data = $this->env->env("{$company}_{$field}");

                if (is_null($data)) {
                    die("Não foi possível localizar o dado: {$field}, da empresa: {$company} no arquivo .env."
                        . PHP_EOL .
                        'Ajuste os dados no e tente novamente.'
                        . PHP_EOL);
                }

                if ($field === 'NOME') {
                    $entity->name = $data;
                }

                if ($field === 'CNPJ') {
                    $entity->cnpj = $data;
                }

                if ($field === 'API_KEY') {
                    $entity->apiKey = $data;
                }
            }
            $result[] = $entity;
        }

        return $result;
    }
}
