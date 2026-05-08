<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserMakeRandomCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_user_and_sets_wallet_balances(): void
    {
        $this->assertSame(0, User::query()->count());

        $exit = Artisan::call('user:make-random');

        $this->assertSame(0, $exit);
        $this->assertSame(1, User::query()->count());

        $user = User::query()->with('wallet')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->wallet);
        $this->assertSame('1000.00', (string) $user->wallet->balance);
        $this->assertSame('1000.00', (string) $user->wallet->start_balance);
        $this->assertSame('EUR', (string) $user->wallet->currency);
    }
}
