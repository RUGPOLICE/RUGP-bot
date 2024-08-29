<?php

namespace App\Exceptions;

use App\Models\Token;

class SimulationError extends ScanningError
{
    public function __construct(Token $token, string $message = "", $code = 0, \Throwable $previous = null)
    {
        $message = __('telegram.errors.scan.simulator', ['address' => $token->address]);
        $log_message = "Scan Token Simulator: $token->address ($message)";
        parent::__construct($message, $log_message, $code, $previous);
    }
}
