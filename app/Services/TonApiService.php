<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TonApiService
{
    const BaseURL = 'https://tonapi.io/v2';

    public function get(string $endpoint, array $query = []): Response
    {
        $keys = config('services.ton.keys');
        $key = $keys[array_rand($keys)];
        return Http::withHeader('Authorization', "Bearer $key")->get(self::BaseURL . $endpoint, $query);
    }

    public function getJetton(string $address): ?array
    {
        $response = $this->get("/jettons/$address")->json();
        if (isset($response['error']))
            return null;

        return $response;
    }

    public function getJettonHolders(string $address, int $limit = 20): ?array
    {
        $response = $this->get("/jettons/$address/holders", ['limit' => $limit])->json();
        if (isset($response['error']))
            return null;

        return [$response['addresses'], $response['total']];
    }
}
