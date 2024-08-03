<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TonApiService
{
    const BaseURL = 'https://tonapi.io/v2';

    public function get(string $endpoint, array $query = []): Response
    {
        $key = config('services.ton.api_key');
        return Http::withHeader('Authorization', "Bearer $key")->get(self::BaseURL . $endpoint, $query);
    }

    public function getJetton(string $address): ?array
    {
        $response = $this->get("/jettons/$address")->json();
        if (isset($response['error']))
            return null;

        return $response;
    }

    public function getJettonHolders(string $address): ?array
    {
        $response = $this->get("/jettons/$address/holders", ['limit' => 10])->json();
        if (isset($response['error']))
            return null;

        return array_map(fn ($a) => ['address' => $a['address'], 'balance' => $a['balance']], $response['addresses']);
    }
}
