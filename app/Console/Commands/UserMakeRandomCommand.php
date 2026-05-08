<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UserMakeRandomCommand extends Command
{
    protected $signature = 'user:make-random';

    protected $description = 'Create a random user (password: password) and seed wallet with 1000 EUR';

    public function handle(): int
    {
        $email = 'user+'.Str::lower(Str::random(10)).'@example.com';

        $user = User::query()->create([
            'name' => 'Random User',
            'email' => $email,
            'password' => 'password',
        ]);

        $user->loadMissing('wallet');
        $user->wallet?->update([
            'balance' => 1000,
            'start_balance' => 1000,
            'currency' => 'EUR',
            'amount_in_play' => 0,
            'total_result' => 0,
        ]);

        $this->components->info("Created user {$user->id} {$user->email} (password: password)");

        return self::SUCCESS;
    }
}
