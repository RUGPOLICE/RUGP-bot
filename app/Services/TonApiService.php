<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function getContractData(string $address): ?array
    {
        $response = $this->get("/blockchain/accounts/$address/methods/get_contract_data")->json();
        if (isset($response['error']) || $response['exit_code'] !== 0)
            return null;

        return [
            'unlocks_at' => intval($response['stack'][3]['num'], 16),
            'locked_amount' => intval($response['stack'][4]['num'], 16),
            'total_amount' => intval($response['stack'][8]['num'], 16),
        ];
    }
}
