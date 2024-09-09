<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeckoTerminalService
{
    const BaseURL = 'https://api.geckoterminal.com/api/v2';
    const TON_ADDRESS = 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c';

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

                if ($quote_address !== self::TON_ADDRESS)
                    continue;

                yield [
                    'token' => [
                        'address' => $token_address,
                    ],
                    'pool' => [
                        'address' => $pool['attributes']['address'],
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

    public function getTokenAddressByQuery(string $query): ?string
    {
        $response = $this->get('/search/pools', ['query' => $query, 'network' => 'ton'])->json();
        if (!isset($response['data']) || !$response['data'])
            return null;

        foreach ($response['data'] as $pool) {

            $base_token = str_replace('ton_', '', $pool['relationships']['base_token']['data']['id']);
            $quote_token = str_replace('ton_', '', $pool['relationships']['quote_token']['data']['id']);
            $dex = $pool['relationships']['dex']['data']['id'];

            if (in_array(self::TON_ADDRESS, [$base_token, $quote_token]) && in_array($dex, ['dedust', 'stonfi']))
                return $base_token === self::TON_ADDRESS ? $quote_token : $base_token;

        }

        return null;
    }

    public function getToken(string $address): array
    {
        $response = $this->get("/networks/ton/tokens/$address")->json();
        if (!isset($response['data']) || !$response['data'])
            return [];

        return [
            'name' => $response['data']['attributes']['name'],
            'symbol' => $response['data']['attributes']['symbol'],
            'supply' => $response['data']['attributes']['total_supply'],
        ];
    }

    public function getTokenInfo(string $address): array
    {
        $response = $this->get("/networks/ton/tokens/$address/info")->json();
        if (!isset($response['data']) || !$response['data'])
            return [];

        $websites = [];
        foreach ($response['data']['attributes']['websites'] as $website)
            $websites[] = [
                'label' => 'Website',
                'url' => $website,
            ];

        $socials = [];
        if ($response['data']['attributes']['telegram_handle'])
            $socials[] = [
                'type' => 'telegram',
                'url' => 'https://t.me/' . $response['data']['attributes']['telegram_handle'],
            ];

        if ($response['data']['attributes']['twitter_handle'])
            $socials[] = [
                'type' => 'twitter',
                'url' => 'https://x.com/' . $response['data']['attributes']['twitter_handle'],
            ];

        return [
            'name' => $response['data']['attributes']['name'],
            'symbol' => $response['data']['attributes']['symbol'],
            'websites' => $websites,
            'socials' => $socials,
        ];
    }

    public function getPoolsByTokenAddress(string $address): \Generator
    {
        $response = $this->get("/networks/ton/tokens/$address/pools")->json();
        if (!isset($response['data']) || !$response['data'])
            return [];

        foreach ($response['data'] as $pool)
            if (in_array($pool['relationships']['dex']['data']['id'], ['dedust', 'stonfi']) && $pool['relationships']['quote_token']['data']['id'] === 'ton_EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c')
                yield [
                    'address' => $pool['attributes']['address'],
                    'dex' => $pool['relationships']['dex']['data']['id'],
                    'price' => $pool['attributes']['base_token_price_usd'],
                    'created_at' => Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $pool['attributes']['pool_created_at']),
                    'fdv' => $pool['attributes']['fdv_usd'] ?? null,
                    'reserve' => $pool['attributes']['reserve_in_usd'] ?? null,
                    'm5_volume' => $pool['attributes']['volume_usd']['m5'] ?? null,
                    'm5_price_change' => $pool['attributes']['price_change_percentage']['m5'] ?? null,
                    'm5_buys' => $pool['attributes']['transactions']['m5']['buys'] ?? null,
                    'm5_sells' => $pool['attributes']['transactions']['m5']['sells'] ?? null,
                    'h1_volume' => $pool['attributes']['volume_usd']['h1'] ?? null,
                    'h1_price_change' => $pool['attributes']['price_change_percentage']['h1'] ?? null,
                    'h1_buys' => $pool['attributes']['transactions']['m30']['buys'] ?? null,
                    'h1_sells' => $pool['attributes']['transactions']['m30']['sells'] ?? null,
                    'h6_volume' => $pool['attributes']['volume_usd']['h6'] ?? null,
                    'h6_price_change' => $pool['attributes']['price_change_percentage']['h6'] ?? null,
                    'h6_buys' => $pool['attributes']['transactions']['h1']['buys'] ?? null,
                    'h6_sells' => $pool['attributes']['transactions']['h1']['sells'] ?? null,
                    'h24_volume' => $pool['attributes']['volume_usd']['h24'] ?? null,
                    'h24_price_change' => $pool['attributes']['price_change_percentage']['h24'] ?? null,
                    'h24_buys' => $pool['attributes']['transactions']['h24']['buys'] ?? null,
                    'h24_sells' => $pool['attributes']['transactions']['h24']['sells'] ?? null,
                ];
    }

    public function getOhlcv(string $pool, bool $is_new): ?array
    {
        $frame = $is_new ? 'hour' : 'day';
        $response = $this->get("/networks/ton/pools/$pool/ohlcv/$frame", ['limit' => 50]);
        if (!isset($response->json()['data'])) return null;

        return array_map(fn ($item) => [
            'timestamp' => $item[0],
            'close' => $item[4],
            'volume' => $item[5],
        ], array_reverse($response->json()['data']['attributes']['ohlcv_list']));
    }
}
