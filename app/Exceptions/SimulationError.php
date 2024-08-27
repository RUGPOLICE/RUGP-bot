<?php

namespace App\Exceptions;

use App\Models\Token;

class SimulationError extends ScanningError
{
    public function __construct(Token $token, $code = 0, \Throwable $previous = null)
    {
        $message = __('telegram.errors.scan.simulator', ['address' => $token->address]);
        $log_message = "Scan Token Simulator: $token->address";
        parent::__construct($message, $log_message, $code, $previous);
    }
}
