<?php

namespace App\Services\Assistant\Exceptions;

use RuntimeException;

class LLMClientException extends RuntimeException
{
    /**
     * Buat exception dengan kode HTTP opsional untuk logging.
     */
    public static function fromService(string $service, string $message, int $code = 0): self
    {
        return new self("{$service}: {$message}", $code);
    }
}
