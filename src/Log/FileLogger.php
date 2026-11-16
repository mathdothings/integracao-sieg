<?php

namespace App\Log;

use DateTime;
use DateTimeZone;
use App\Core\Json;

/**
 * Class FileLogger
 * 
 * A simple file-based logger implementation that writes logs 
 * to a specified directory with timestamps and severity levels.
 */
class FileLogger implements LoggerInterface
{
    private string $logDir;

    /**
     * FileLogger constructor.
     * 
     * @param string $logDir The directory where log files will be stored.
     */
    public function __construct(string $logDir = BASEPATH . '/output/logs')
    {
        $this->logDir = $logDir;
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Writes a log entry to the current day's log file.
     * 
     * @param string $level Log severity level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $fileName = "{$this->logDir}/app_{$date->format('Y-m-d')}.log";

        if ($level === 'CRITICAL') {
            $fileName = "{$this->logDir}/critical_{$date->format('Y-m-d')}.log";
        }

        $context = !empty($context) ? ' ' . Json::encode($context) : '';

        $entry = "[{$date->format('Y-m-d H:i:s')}] [{$level}] {$message}{$context}" . PHP_EOL;

        file_put_contents($fileName, $entry, FILE_APPEND);
    }

    /**
     * Writes an API response to a daily JSON log file.
     * 
     * @param string $apiName Name of the API
     * @param mixed $response Data to log
     */
    public function logApiResponse(string $apiName, ?array $response): void
    {
        $date = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $fileName = "{$this->logDir}/{$apiName}_{$date->format('Y-m-d')}.log";

        $log = [
            'instant' => $date->format('H:i:s'),
            'response' => $response
        ];

        $entry = Json::encode($log) . PHP_EOL;
        file_put_contents($fileName, $entry, FILE_APPEND);
    }
}
