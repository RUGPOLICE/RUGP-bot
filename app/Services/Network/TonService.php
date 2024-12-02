<?php

namespace App\Services\Network;

use App\Enums\Lock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class TonService
{
    const BaseURL = 'https://tonapi.io/v2';

    public function get(string $endpoint, array $query = []): ?array
    {
        $keys = config('services.ton.keys');
        $key = $keys[array_rand($keys)];

        $response = Http::withHeader('Authorization', "Bearer $key")->get(self::BaseURL . $endpoint, $query)->json();
        if (isset($response['error']) || isset($response['exit_code']) && $response['exit_code'] !== 0)
            return null;

        return $response;
    }

    public function getJetton(string $address): ?array
    {
        $response = $this->get("/jettons/$address");
        if (!$response)
            return null;

        $original = in_array($address, config('app.tokens.original'));
        $decimals = intval($response['metadata']['decimals'] ?? 9);

        return [
            'name'              => $response['metadata']['name'] ?? null,
            'symbol'            => $response['metadata']['symbol'] ?? null,
            'image'             => $response['metadata']['image'] ?? null,
            'description'       => $response['metadata']['description'] ?? null,
            'owner'             => $response['admin']['address'] ?? '0:0000000000000000000000000000000000000000000000000000000000000000',
            'holders_count'     => $response['holders_count'],
            'supply'            => $response['total_supply'] / (10 ** $decimals),
            'decimals'          => $decimals,
            'is_warn_original'  => $response['verification'] === 'whitelist' || $original,
        ];
    }

    public function getJettonHolders(string $address, float $supply, int $decimals, int $limit = 20): ?array
    {
        $response = $this->get("/jettons/$address/holders", ['limit' => $limit]);
        if (!$response)
            return null;

        if (!$response['total'])
            return [[], 0];

        $holderAddresses = implode(',', array_map(fn ($a) => $a['address'], $response['addresses']));
        $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/convert.js $holderAddresses");
        $result = json_decode($result->output());

        return [
            array_map(fn (array $a) =>[
                'address' => $result->addresses->{$a['address']},
                'balance' => $a['balance'] / (10 ** $decimals),
                'percent' => $supply ? ($a['balance'] * 100 / ($supply * (10 ** $decimals))) : 0,
                'owner' => $a['owner']['address'],
                'name' => $a['owner']['name'] ?? (!$a['owner']['is_wallet'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
                'is_wallet' => $a['owner']['is_wallet'],
            ] , $response['addresses']),
            $response['total']
        ];
    }

    public function getLock(float $supply, int $decimals, array $holders): ?array
    {
        $burnedAmount = null;
        $lockedAmount = null;
        $lockedType = null;
        $unlocksAt = null;
        $locks = collect();

        foreach ($holders as $holder) {

            if ($holder['owner'] === '0:0000000000000000000000000000000000000000000000000000000000000000') {

                $burnedAmount = ($burnedAmount ?? 0) + $holder['balance'];

            } else if (!$holder['is_wallet']) {

                $response = $this->get("/blockchain/accounts/{$holder['address']}/methods/get_contract_data");
                if ($response) {

                    $lockedAmount = ($lockedAmount ?? 0) + intval($response['stack'][4]['num'], 16);
                    $lockedType = $lockedType ?? Lock::RAFFLE;

                    $time = Carbon::createFromTimestamp(intval($response['stack'][3]['num'], 16));
                    $unlocksAt = $time > now()->addYears(5) ? now()->addYears(5) : $time;

                    $locks->push([
                        'type' => Lock::RAFFLE->value,
                        'amount' => intval($response['stack'][4]['num'], 16),
                        'until' => $unlocksAt->timestamp,
                    ]);

                } else {

                    $lockedAmount = ($lockedAmount ?? 0) + $holder['balance'];
                    $lockedType = Lock::CHECK;

                }

            } else if ($holder['name'] === 'tinu-locker.ton') {

                $lockedAmount = ($lockedAmount ?? 0) + $holder['balance'];
                $lockedType = $lockedType ?? Lock::TONINU;

                $locks->push([
                    'type' => Lock::TONINU->value,
                    'amount' => $holder['balance'],
                ]);

            }

        }

        $burnedPercent = $burnedAmount !== null ? ($burnedAmount / $supply * 100) : $burnedAmount;
        $lockedPercent = $lockedAmount !== null ? ($lockedAmount / $supply * 100) : $lockedAmount;

        $locks = $locks->map(fn ($l) => array_merge($l, ['percent' => $l['amount'] / $supply * 100]));
        $isSmallLock = $lockedPercent && $lockedPercent < 100;
        $hasLargeHolders = boolval(array_filter($holders, fn ($item) => ($item['balance'] / $supply * 100) > 5));
        $isUnlocked = $unlocksAt && $unlocksAt < now();
        $dyor = $isSmallLock && $hasLargeHolders || $isUnlocked;

        return [
            'holders' => $holders,
            'locks' => $locks,
            'burned_amount' => $burnedAmount,
            'locked_amount' => $lockedAmount,
            'burned_percent' => $burnedPercent,
            'locked_percent' => $lockedPercent,
            'locked_type' => $lockedType,
            'locked_dyor' => $dyor,
            'unlocks_at' => $unlocksAt,
        ];
    }

    public function getTaxes(string $address, string $dex): array|string
    {
        $result = Process::path(base_path('utils/honeypot'))->run("npx tsx src/index.ts $address");
        $report = json_decode($result->output());

        if ($report === null || !$report->success)
            return $report->message;

        return [
            'is_known_master' => true,
            'is_known_wallet' => $report->isKnownWallet,
            'tax_buy' => isset($report->buy) ? ($report->buy * 100) : null,
            'tax_sell' => isset($report->sell) ? ($report->sell * 100) : null,
            'tax_transfer' => isset($report->transfer) ? ($report->transfer * 100) : null,
        ];
    }
}
