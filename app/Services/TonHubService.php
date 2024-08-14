<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TonHubService
{
    const BaseURL = 'https://mainnet-v4.tonhubapi.com';

    public function get(string $endpoint, array $query = []): Response
    {
        return Http::get(self::BaseURL . $endpoint, $query);
    }

    public function getLatestBlock(): int
    {
        $response = $this->get('/block/latest')->json();
        return $response['last']['seqno'];
    }

    public function getContractData(string $address): ?array
    {
        $block = $this->getLatestBlock();
        $response = $this->get("/block/$block/$address/run/get_contract_data")->json();
        if ($response['exitCode'] !== 0)
            return null;

        return [
            'unlocks_at' => intval($response['result'][3]['value']),
            'locked_amount' => intval($response['result'][4]['value']),
            'total_amount' => intval($response['result'][8]['value']),
        ];
    }
}
