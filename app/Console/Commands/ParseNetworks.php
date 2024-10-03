<?php

namespace App\Console\Commands;

use App\Models\Network;
use App\Services\GeckoTerminalService;
use Illuminate\Console\Command;

class ParseNetworks extends Command
{
    protected $signature = 'app:parse-networks';

    protected $description = 'Get Networks from Gecko';

    public function handle(GeckoTerminalService $geckoTerminalService): void
    {
        // foreach ($geckoTerminalService->getNetworks() as $network)
        //     Network::query()->firstOrCreate($network);

        Network::query()->firstOrCreate(['slug' => 'eth'],      ['name' => 'Ethereum',  'token' => '0xeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',        'priority' => 1]);
        Network::query()->firstOrCreate(['slug' => 'bsc'],      ['name' => 'BSC',       'token' => '0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c',        'priority' => 1]);
        Network::query()->firstOrCreate(['slug' => 'base'],     ['name' => 'Base',      'token' => '0x4200000000000000000000000000000000000006',        'priority' => 1]);
        Network::query()->firstOrCreate(['slug' => 'tron'],     ['name' => 'Tron',      'token' => 'TNUC9Qb1rRpS5CbWLmNMxXBjyFoydXjWFR',                'priority' => 1]);
        Network::query()->firstOrCreate(['slug' => 'solana'],   ['name' => 'Solana',    'token' => 'So11111111111111111111111111111111111111112',       'priority' => 1]);
        Network::query()->firstOrCreate(['slug' => 'ton'],      ['name' => 'TON',       'token' => 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c',  'priority' => 10]);

        $count = Network::query()->count();
        $this->info("Parsed $count networks");
    }
}
