<?php

namespace App\Services\Network;

use App\Enums\Dex;
use App\Enums\Lock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class GoplusService extends NetworkService
{
    const BaseURL = 'https://api.gopluslabs.io/api/v1';

    public function get(string $endpoint, array $query = []): ?array
    {
        $response = Http::get(self::BaseURL . $endpoint, $query);
        if ($response->status() !== 200)
            return null;

        return $response->json();
    }

    public function getJetton(string $address, ?string $chain = null): ?array
    {
        $response = $this->get("/token_security/{$this->getChainId($chain)}", ['contract_addresses' => $address]);
        if (!$response)
            return null;

        return [
            'name'              => $response['token_name'] ?? null,
            'symbol'            => $response['token_symbol'] ?? null,
            // 'image'             => $response['metadata']['image'] ?? null,
            // 'description'       => $response['metadata']['description'] ?? null,
            'owner'             => $response['owner_address'] ?? null,
            'holders_count'     => $response['holder_count'],
            'supply'            => $response['total_supply'],
            'is_warn_original'  => $response['trust_list'] === '1' || in_array($address, config('app.tokens.original')),
        ];
    }

    public function getJettonHolders(string $address, float $supply, int $limit = 20, ?string $chain = null): ?array
    {
        $response = $this->get("/token_security/{$this->getChainId($chain)}", ['contract_addresses' => $address]);
        if (!$response)
            return null;

        if (!$response['holders'])
            return [[], 0];

        return [
            array_map(fn (array $a) =>[
                'address' => $a['address'],
                'balance' => $a['balance'],
                'percent' => $a['percent'],
                'name' => $a['tag'] ?? (!$a['is_contract'] ? __('telegram.text.token_scanner.holders.dex_lock_stake') : null),
            ] , $response['holders']),
            $response['holder_count']
        ];
    }

    public function getLock(string $address, float $supply, array $holders, ?string $chain = null): ?array
    {
        $response = $this->get("/token_security/{$this->getChainId($chain)}", ['contract_addresses' => $address]);
        if (!$response)
            return null;

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

    public function getTaxes(string $address, ?string $chain = null): ?array
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
