<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateToken extends Command
{
    protected $signature = 'app:create-token {user} {name}';

    protected $description = 'Creates API Token';

    public function handle(): void
    {
        $user = User::find($this->argument('user'));
        $token = $user->createToken($this->argument('name'));
        $this->info("Token: $token->plainTextToken");
    }
}
