<?php

namespace App\Log;

/**
 * Class LogMessages
 * 
 * Central repository for all log messages used in the application.
 * Facilitates translation and maintainability of log strings.
 */
final class LogMessages
{
    // HTTP Client Messages
    public const HTTP_REQUEST_COMPLETED = "Requisição HTTP concluída";
    public const NON_RETRYABLE_ERROR = "Erro de cliente não repetível (não será tentado novamente)";
    public const UNHANDLED_EXCEPTION = "Exceção não tratada capturada";

    /**
     * @param int $delay
     * @return string
     */
    public static function retryingRequest(int $delay): string
    {
        return "A requisição falhou. Tentando novamente em {$delay}s...";
    }

    // SIEG Client Messages
    /**
     * @return string
     */
    public static function usingCachedToken(): string
    {
        return "Usando token em cache.";
    }

    /**
     * @return string
     */
    public static function fetchingNewToken(): string
    {
        return "Nenhum token válido em cache.";
    }

    public const TOKEN_REFRESH_SUCCESS = "Token de acesso atualizado com sucesso.";

    public const UNAUTHORIZED_RETRY = "Recebido 401 Não Autorizado. Removendo token inválido e tentando recuperar a sessão...";
}
