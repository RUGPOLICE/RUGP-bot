<?php

namespace App\Exceptions;

use App\Models\Token;

class MetadataError extends ScanningError
{
    public function __construct(Token $token, $code = 0, \Throwable $previous = null)
    {
        $message = __('telegram.errors.scan.metadata', ['address' => $token->address]);
        $log_message = "Scan Token Metadata: $token->address";
        parent::__construct($message, $log_message, $code, $previous);
    }
}
