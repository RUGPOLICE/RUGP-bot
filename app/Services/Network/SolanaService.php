<?php

namespace App\Services\Network;

use App\Enums\Dex;
use App\Enums\Lock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class SolanaService extends NetworkService
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
        $response = parent::getJetton($address);
        if (!$response)
            return null;

        return [
            'name'              => $response['metadata']['name'] ?? null,
            'symbol'            => $response['metadata']['symbol'] ?? null,
            'image'             => $response['metadata']['image'] ?? null,
            'description'       => $response['metadata']['description'] ?? null,
            'owner'             => $response['admin']['address'] ?? null,
            'holders_count'     => $response['holders_count'],
            'supply'            => $response['total_supply'],
            'is_warn_original'  => $response['verification'] === 'whitelist' || in_array($address, config('app.tokens.original')),
        ];
    }

    public function getJettonHolders(string $address, float $supply, int $limit = 20): ?array
    {
        $response = parent::getJettonHolders($address, $limit);
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
                'balance' => $a['balance'] / 1000000000,
                'percent' => $supply ? ($a['balance'] * 100 / $supply) : 0,
                'owner' => $a['owner']['address'],
                'name' => $a['owner']['name'] ?? (!$a['owner']['is_wallet'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
            ] , $response['addresses']),
            $response['total']
        ];
    }

    public function getLock(string $address, float $supply, array $holders): ?array
    {
        $holderAddress = $holders[0]['owner'];
        if ($holderAddress === '0:0000000000000000000000000000000000000000000000000000000000000000')
            return [
                'holders' => $holders,
                'burned_amount' => $holders[0]['balance'],
                'burned_percent' => $holders[0]['balance'] / $supply * 100,
            ];

        $lockedAmount = null;
        $lockedPercent = null;
        $lockedType = null;
        $unlocksAt = null;

        $response = parent::getLock($address, $supply, $holders);
        if ($response) {

            $lockedAmount = intval($response['stack'][4]['num'], 16);
            $lockedPercent = $lockedAmount / $supply * 100;

            $time = Carbon::createFromTimestamp(intval($response['stack'][3]['num'], 16));
            $lockedType = Lock::RAFFLE;
            $unlocksAt = $time > now()->addYears(5) ? now()->addYears(5) : $time;

        }

        $toninuAmount = array_reduce(
            array_filter($holders, fn ($item) => ($item['name'] ?? '') === 'tinu-locker.ton'),
            fn ($acc, $item) => $acc + $item['balance'],
            0
        );

        if ($toninuAmount) {

            $lockedAmount = $toninuAmount;
            $lockedPercent = $toninuAmount / $supply * 100;
            $lockedType = Lock::TONINU;

        }

        $isSmallLock = $lockedPercent && $lockedPercent < 100;
        $hasLargeHolders = boolval(array_filter($holders, fn ($item) => ($item['balance'] / $supply * 100) > 5));
        $isUnlocked = $unlocksAt && $unlocksAt < now();
        $dyor = $isSmallLock && $hasLargeHolders || $isUnlocked;

        return [
            'holders' => $holders,
            'locked_amount' => $lockedAmount,
            'locked_percent' => $lockedPercent,
            'locked_type' => $lockedType,
            'locked_dyor' => $dyor,
            'unlocks_at' => $unlocksAt,
        ];
    }

    public function getTaxes(string $address): ?array
    {
        $dex = [Dex::DEDUST->value, Dex::STONFI->value];
        $dex_param = implode(',', $dex);

        $result = Process::path(base_path('utils/scanner'))->run("node --no-warnings src/main.js $address $dex_param");
        $report = json_decode($result->output());

        if (!$report->success)
            return [null, $report->message];

        $response = [
            'is_known_master' => $report->isKnownMaster,
            'is_known_wallet' => $report->isKnownWallet,
        ];

        foreach ($dex as $d) {

            $response[$d]['tax_buy'] = isset($report->{$d}->taxBuy) ? ($report->{$d}->taxBuy * 100) : null;
            $response[$d]['tax_sell'] = isset($report->{$d}->taxSell) ? ($report->{$d}->taxSell * 100) : null;
            $response[$d]['tax_transfer'] = isset($report->{$d}->taxTransfer) ? ($report->{$d}->taxTransfer * 100) : null;

        }

        return $response;
    }

    protected function getJettonEndpoint(string $address): string
    {
        return "/jettons/$address";
    }

    protected function getJettonHoldersEndpoint(string $address): string
    {
        return "/jettons/$address/holders";
    }

    protected function getLockEndpoint(string $address): string
    {
        return "/blockchain/accounts/$address/methods/get_contract_data";
    }
}
