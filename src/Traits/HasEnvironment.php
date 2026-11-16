<?php

namespace App\Traits;

use Dotenv\Dotenv;
use App\Core\EnvironmentType;

trait HasEnvironment
{
    /**
     * The environment type instance
     */
    private static ?EnvironmentType $environmentInstance = null;

    /**
     * State to track if environment is loaded
     */
    private static bool $environmentLoaded = false;

    /**
     * Initialize the environment directly
     */
    private function initialize(): void
    {
        if (!self::$environmentLoaded) {
            $this->loadEnvironmentFile();

            self::$environmentInstance = $this->parseEnvironment($_ENV["ENVIRONMENT"]);
            self::$environmentLoaded = true;
        }
    }

    /**
     * Get the current environment (instance method)
     */
    public function getEnvironment(): EnvironmentType
    {
        if (!self::$environmentLoaded) {
            $this->initialize();
        }

        return self::$environmentInstance;
    }

    /**
     * Check if application is in production
     */
    public function isProduction(): bool
    {
        return $this->getEnvironment() === EnvironmentType::PRODUCTION;
    }

    /**
     * Check if application is in development
     */
    public function isDevelopment(): bool
    {
        return $this->getEnvironment() === EnvironmentType::DEVELOPMENT;
    }

    /**
     * Get an environment variable
     */
    public function env(string $key, $default = null): ?string
    {
        if (!self::$environmentLoaded) {
            $this->initialize();
        }

        $key = strtoupper($key);
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    /**
     * Get required environment variable (throws exception if missing)
     */
    public function required(string $key): string
    {
        $value = $this->env($key);

        if (empty($value)) {
            throw new \InvalidArgumentException(
                "A variável de ambiente '{$key}' não está definida no arquivo .env"
            );
        }

        return (string) $value;
    }

    /**
     * Load the .env file
     */
    private function loadEnvironmentFile(): void
    {
        if (!defined('BASEPATH')) {
            throw new \RuntimeException('BASEPATH não está definido');
        }

        if (file_exists(BASEPATH . '/.env')) {
            Dotenv::createImmutable(BASEPATH)->load();
        }
    }

    /**
     * Parse environment string to enum
     */
    private function parseEnvironment(string $environment): EnvironmentType
    {
        return match (strtoupper($environment)) {
            'PRODUCTION' => EnvironmentType::PRODUCTION,
            'DEVELOPMENT' => EnvironmentType::DEVELOPMENT
        };
    }
}
