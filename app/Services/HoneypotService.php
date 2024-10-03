<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HoneypotService
{
    const BaseURL = 'https://api.honeypot.is/v2';

    public function get(string $endpoint, array $query = []): Response
    {
        return Http::withHeader('X-API-KEY', config('services.honeypot.key'))
            ->get(self::BaseURL . $endpoint, $query);
    }

    public function getTokenInfo(string $address): array
    {
        return $this->get('/IsHoneypot', ['address' => $address])->json();
    }
}
