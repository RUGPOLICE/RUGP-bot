<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DexScreenerService
{
    const BaseURL = 'https://api.dexscreener.com/latest/dex';

    public function get(string $endpoint, array $query = []): Response
    {
        return Http::get(self::BaseURL . $endpoint, $query);
    }

    public function getPoolsByTokenAddress(string $address): \Generator
    {
        $response = $this->get("/tokens/$address")->json();
        if (!isset($response['pairs']) || !$response['pairs'])
            return [];

        foreach ($response['pairs'] as $pair)
            if ($pair['quoteToken']['address'] === 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c')
                yield [
                    'token' => [
                        'name' => $pair['baseToken']['name'],
                        'symbol' => $pair['baseToken']['symbol'],
                        'websites' => $pair['info']['websites'] ?? null,
                        'socials' => $pair['info']['socials'] ?? null,
                    ],
                    'pool' => [
                        'address' => $pair['pairAddress'],
                        'dex' => $pair['dexId'],
                        'price' => $pair['priceUsd'],
                        'created_at' => mb_substr($pair['pairCreatedAt'], 0, mb_strlen($pair['pairCreatedAt']) - 3),
                        'fdv' => $pair['fdv'] ?? null,
                        'reserve' => $pair['liquidity']['usd'] ?? null,
                        'h24_volume' => $pair['volume']['h24'] ?? null,
                        'h24_price_change' => $pair['priceChange']['h24'] ?? null,
                        'h24_buys' => $pair['txns']['h24']['buys'] ?? null,
                        'h24_sells' => $pair['txns']['h24']['sells'] ?? null,
                    ],
                ];
    }

    public function getTokenAddressByPoolAddress(string $address): ?string
    {
        $response = $this->get('/search', ['q' => $address])->json();
        if (!isset($response['pairs']) || !$response['pairs'])
            return null;

        return $response['pairs'][0]['baseToken']['address'];
    }
}
