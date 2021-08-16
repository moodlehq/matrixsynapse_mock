<?php

namespace App\Component\HttpFoundation;

class ErrorResponse extends MessageResponse
{
    public function __construct(
        string $key,
        string $message,
        string $returnCode = 'FAILED',
        int $status = 500,
        array $headers = []
    ) {
        parent::__construct($key, $message, $returnCode, $status, $headers);
    }
}
