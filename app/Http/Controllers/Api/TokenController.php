<?php

namespace App\Http\Controllers\Api;

use App\Enums\Dex;
use App\Enums\Lock;
use App\Enums\Reaction;
use App\Exceptions\ScanningError;
use App\Http\Controllers\Controller;
use App\Jobs\Scanner\CheckBurnLock;
use App\Jobs\Scanner\SimulateTransactions;
use App\Jobs\Scanner\UpdateHolders;
use App\Jobs\Scanner\UpdateLiquidity;
use App\Jobs\Scanner\UpdateMetadata;
use App\Jobs\Scanner\UpdatePools;
use App\Jobs\Scanner\UpdateStatistics;
use App\Models\Token;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    public function info(string $network, string $address): \Illuminate\Http\JsonResponse
    {
        try {

            $address = Token::getAddress($address);
            if (!$address['success'])
                return response()->json($address);

            $token = Token::query()->firstOrCreate(['address' => $address['address']]);
            UpdateMetadata::dispatchSync($token);
            UpdatePools::dispatchSync($token);

            $jobs = [
                SimulateTransactions::class,
                UpdateHolders::class,
                UpdateLiquidity::class,
                CheckBurnLock::class,
                UpdateStatistics::class,
            ];

            foreach ($jobs as $job) {
                try {

                    $job::dispatchSync($token);

                } catch (ScanningError $e) {

                    Log::error($e->getLogMessage());

                }
            }

            $token->refresh();

            $pools = [];
            $links = [];

            foreach ($token->pools as $pool)
                $pools[] = [
                    'link' => Dex::link($pool->dex, $pool->address),
                    'dex' => Dex::verbose($pool->dex),
                    'address' => $pool->address,
                    'price' => $pool->price,
                    'supply' => $pool->supply,
                    'fdv' => $pool->fdv,
                    'reserve' => $pool->reserve,
                    'can_buy' => $pool->tax_buy && $pool->tax_buy < 100.0 && $pool->tax_buy >= 0.0,
                    'can_sell' => $pool->tax_sell && $pool->tax_sell < 100.0 && $pool->tax_sell >= 0.0,
                    'tax_buy' => $pool->tax_buy !== null ? ($pool->tax_buy < 0 ? null : floatval($pool->tax_buy)) : null,
                    'tax_sell' => $pool->tax_sell !== null ? ($pool->tax_sell < 0 ? null : floatval($pool->tax_sell)) : null,
                    'stats' => [
                        'm5' => [
                            'volume' => $pool->m5_volume,
                            'price_change' => $pool->m5_price_change,
                            'buys' => $pool->m5_buys,
                            'sells' => $pool->m5_sells,
                        ],
                        'h1' => [
                            'volume' => $pool->h1_volume,
                            'price_change' => $pool->h1_price_change,
                            'buys' => $pool->h1_buys,
                            'sells' => $pool->h1_sells,
                        ],
                        'h6' => [
                            'volume' => $pool->h6_volume,
                            'price_change' => $pool->h6_price_change,
                            'buys' => $pool->h6_buys,
                            'sells' => $pool->h6_sells,
                        ],
                        'h24' => [
                            'volume' => $pool->h24_volume,
                            'price_change' => $pool->h24_price_change,
                            'buys' => $pool->h24_buys,
                            'sells' => $pool->h24_sells,
                        ],
                    ],
                    'burned' => [
                        'amount' => $pool->burned_amount,
                        'percent' => $pool->burned_percent !== null ? floatval($pool->burned_percent) : null,
                    ],
                    'locked' => [
                        'type' => $pool->locked_type ? Lock::verbose($pool->locked_type) : null,
                        'percent' => $pool->locked_percent !== null ? floatval($pool->locked_percent) : null,
                        'dyor' => $pool->locked_dyor,
                        'unlocks_at' => $pool->unlocks_at ? $pool->unlocks_at->format('d M Y') : null,
                    ],
                ];

            foreach ($token->websites ?? [] as $website)
                $links[] = ['url' => $website['url'], 'label' => $website['label']];

            foreach ($token->socials ?? [] as $social)
                $links[] = ['url' => $social['url'], 'label' => ucfirst($social['type'])];

            return response()->json([
                'success' => true,
                'token' => [
                    'address' => $token->address,
                    'name' => $token->name,
                    'symbol' => $token->symbol,
                    'owner' => $token->owner,
                    'image' => $token->image,
                    'description' => $token->description,
                    'holders_count' => $token->holders_count,
                    'supply' => $token->supply,
                    'links' => $links,
                    'is_known_master' => $token->is_known_master,
                    'is_known_wallet' => $token->is_known_wallet,
                    'is_revoked' => $token->is_revoked,
                    'is_honeypot' => $token->is_warn_honeypot,
                    'is_rugpull' => $token->is_warn_rugpull,
                    'is_original' => $token->is_warn_original,
                    'is_scam' => $token->is_warn_scam,
                    'is_low_liquidity' => $token->is_warn_liquidity || $token->is_warn_liquidity_dedust || $token->is_warn_liquidity_stonfi,
                    'likes_count' => $token->reactions()->where('type', Reaction::LIKE)->count(),
                    'dislikes_count' => $token->reactions()->where('type', Reaction::DISLIKE)->count(),

                ],
                'pools' => $pools,
            ]);

        } catch (\Throwable $e) {

            Log::error($e);
            return response()->json(['success' => false, 'error' => 'Server error']);

        }
    }

    public function simulate(string $network, string $address): \Illuminate\Http\JsonResponse
    {
        try {

            $address = Token::getAddress($address);
            if (!$address['success'])
                return response()->json($address);

            $token = Token::query()->firstOrCreate(['address' => $address['address']]);
            UpdateMetadata::dispatchSync($token);
            UpdatePools::dispatchSync($token);

            $jobs = [SimulateTransactions::class, UpdateStatistics::class];
            foreach ($jobs as $job) {
                try {

                    $job::dispatchSync($token);

                } catch (ScanningError $e) {

                    Log::error($e->getLogMessage());

                }
            }

            $token->refresh();

            $pools = [];
            foreach ($token->pools as $pool)
                $pools[] = [
                    'link' => Dex::link($pool->dex, $pool->address),
                    'dex' => Dex::verbose($pool->dex),
                    'address' => $pool->address,
                    'can_buy' => $pool->tax_buy && $pool->tax_buy < 100.0 && $pool->tax_buy >= 0.0,
                    'can_sell' => $pool->tax_sell && $pool->tax_sell < 100.0 && $pool->tax_sell >= 0.0,
                    'tax_buy' => $pool->tax_buy !== null ? ($pool->tax_buy < 0 ? null : floatval($pool->tax_buy)) : null,
                    'tax_sell' => $pool->tax_sell !== null ? ($pool->tax_sell < 0 ? null : floatval($pool->tax_sell)) : null,
                ];

            return response()->json([
                'success' => true,
                'token' => [
                    'address' => $token->address,
                    'is_known_master' => $token->is_known_master,
                    'is_known_wallet' => $token->is_known_wallet,
                    'is_honeypot' => $token->is_warn_honeypot,
                    'is_rugpull' => $token->is_warn_rugpull,
                    'is_original' => $token->is_warn_original,
                    'is_scam' => $token->is_warn_scam,
                    'is_low_liquidity' => $token->is_warn_liquidity || $token->is_warn_liquidity_dedust || $token->is_warn_liquidity_stonfi,
                ],
                'pools' => $pools,
            ]);

        } catch (\Throwable $e) {

            Log::error($e);
            return response()->json(['success' => false, 'error' => 'Server error']);

        }
    }
}
