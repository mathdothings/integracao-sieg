<?php

namespace App\Core;

use DateTime;
use DateTimeZone;

/**
 * Class Console
 * 
 * A simple utility class for logging messages to the console with timestamps.
 */
class Console
{
    /**
     * Returns the current date and time in the "America/Sao_Paulo" timezone.
     */
    private static function now(): string
    {
        return (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
    }

    /**
     * Writes a message to the console with a timestamp.
     * 
     * @param string $message The message to write to the console.
     */
    public static function out(string $message): void
    {
        echo "[" . self::now() . "] " . $message . PHP_EOL;
    }
}
