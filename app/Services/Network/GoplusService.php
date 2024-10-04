<?php

namespace App\Services\Network;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoplusService
{
    const BaseURL = 'https://api.gopluslabs.io/api/v1';

    public function get(string $endpoint, array $query = []): ?array
    {
        $response = Http::get(self::BaseURL . $endpoint, $query);
        if ($response->status() !== 200)
            return null;

        return $response->json();
    }

    public function getEthereumLikeData(string $address, string $chain): ?array
    {
        $response = $this->get("/token_security/{$this->getChainId($chain)}", ['contract_addresses' => $address]);
        if (!$response || $response['code'] !== 1)
            return null;

        $response = $response['result'][$address];
        return [
            'token' => [
                'name'              => $response['token_name'] ?? null,
                'symbol'            => $response['token_symbol'] ?? null,
                'owner'             => (isset($response['can_take_back_ownership']) && intval($response['can_take_back_ownership'])) ? 'none' : '0:0000000000000000000000000000000000000000000000000000000000000000',
                'holders_count'     => intval($response['holder_count']),
                'supply'            => intval($response['total_supply']),

                // 'image'             => $response['metadata']['image'] ?? null,
                // 'description'       => $response['metadata']['description'] ?? null,
                // 'owner'             => $response['owner_address'] ?? null,
                // 'websites'          => null,
                // 'socials'            => null,

                'is_warn_original' => isset($response['trust_list']) && $response['trust_list'] === '1' || in_array($address, config('app.tokens.original')),
                'is_warn_honeypot' => isset($response['is_honeypot']) && intval($response['is_honeypot']),

                'is_known_master' => isset($response['is_open_source']) && intval($response['is_open_source']),
                'is_known_wallet' => isset($response['is_open_source']) && intval($response['is_open_source']),

                'holders' => array_map(fn (array $a) => [
                    'address' => $a['address'],
                    'balance' => floatval($a['balance']),
                    'percent' => floatval($a['percent']) * 100,
                    'name' => (isset($a['tag']) && $a['tag']) ? $a['tag'] : (!$a['is_contract'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : $a['address']),
                ] , $response['holders']),
            ],
            'pool' => [
                'tax_buy' => isset($response['buy_tax']) ? (floatval($response['buy_tax']) * 100) : null,
                'tax_sell' => isset($response['sell_tax']) ? (floatval($response['sell_tax']) * 100) : null,
                // 'tax_transfer' => $response['transfer_tax'],
                // supply
                // holders
            ],
        ];
    }

    protected function getChainId(string $chain): string
    {
        return match ($chain) {
            'eth' => '1',
            'bsc' => '56',
            'base' => '8453',
            'tron' => 'tron',
            default => null
        };
    }
}
