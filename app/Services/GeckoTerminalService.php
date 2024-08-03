<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeckoTerminalService
{
    const BaseURL = 'https://api.geckoterminal.com/api/v2';

    public function get(string $endpoint, array $query = []): Response
    {
        return Http::withHeader('Accept', 'application/json;version=20230302')
            ->get(self::BaseURL . $endpoint, $query);
    }

    public function getNewPools(): \Generator
    {
        $response = $this->get('/networks/ton/new_pools');
        $pools = $response->json()['data'];

        foreach ($pools as $pool) {

            try {

                $token_address = str_replace('ton_', '', $pool['relationships']['base_token']['data']['id']);
                $quote_address = str_replace('ton_', '', $pool['relationships']['quote_token']['data']['id']);

                yield [
                    'token' => [
                        'address' => $token_address,
                    ],
                    'pool' => [
                        'address' => $pool['attributes']['address'],
                        'quote_address' => $quote_address,
                        'dex' => $pool['relationships']['dex']['data']['id'],
                        'base_price_usd' => $pool['attributes']['base_token_price_usd'],
                        'fdv' => $pool['attributes']['fdv_usd'],
                        'reserve' => $pool['attributes']['reserve_in_usd'],
                        'h24_volume' => $pool['attributes']['volume_usd']['h24'],
                        'h24_price_change' => $pool['attributes']['price_change_percentage']['h24'],
                        'h24_buys' => $pool['attributes']['transactions']['h24']['buys'],
                        'h24_sells' => $pool['attributes']['transactions']['h24']['sells'],
                        'h24_buyers' => $pool['attributes']['transactions']['h24']['buyers'],
                        'h24_sellers' => $pool['attributes']['transactions']['h24']['sellers'],
                    ],
                ];

            } catch (\Throwable $e) {

                Log::error($e);

            }

        }
    }
}
