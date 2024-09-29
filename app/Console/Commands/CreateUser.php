<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'app:create-user {name} {password}';

    protected $description = 'Creates User';

    public function handle(): void
    {
        $user = new User;
        $user->name = $this->argument('name');
        $user->email = "$user->name@rugp.app";
        $user->password = Hash::make($this->argument('password'));
        $user->save();

        $this->info("User ID: $user->id");
    }
}
