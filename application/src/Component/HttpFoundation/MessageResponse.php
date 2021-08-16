<?php

namespace App\Component\HttpFoundation;

class MessageResponse extends XmlResponse
{
    public function __construct(
        string $key,
        string $message,
        string $returnCode = 'SUCCESS',
        int $status = 200,
        array $headers = []
    ) {
        $response = (object) [
            'messageKey' => $key,
            'message' => $message,
        ];

        parent::__construct($response, $returnCode, $status, $headers);
    }
}
