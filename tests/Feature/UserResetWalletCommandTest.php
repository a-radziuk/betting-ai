<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserResetWalletCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_resets_wallet_fields_for_user(): void
    {
        $user = User::factory()->create();
        $wallet = UserWallet::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($wallet);

        $wallet->update([
            'balance' => 12.34,
            'amount_in_play' => 56.78,
            'total_result' => 90.12,
        ]);

        $exit = Artisan::call('user:reset-wallet', ['userId' => $user->id]);

        $this->assertSame(0, $exit);
        $wallet->refresh();
        $this->assertSame('1000.00', (string) $wallet->balance);
        $this->assertSame('0.00', (string) $wallet->amount_in_play);
        $this->assertSame('0.00', (string) $wallet->total_result);
    }

    public function test_fails_for_unknown_user(): void
    {
        $exit = Artisan::call('user:reset-wallet', ['userId' => 9999999]);

        $this->assertSame(1, $exit);
    }
}
