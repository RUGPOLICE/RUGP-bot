<?php

namespace App\Exceptions;

class ScanningError extends \Exception
{
    private string $log_message;

    public function __construct($message = "", $log_message = "", $code = 0, \Throwable $previous = null)
    {
        $this->log_message = $log_message;
        parent::__construct($message, $code, $previous);
    }

    public function getLogMessage(): string
    {
        return $this->log_message;
    }
}
