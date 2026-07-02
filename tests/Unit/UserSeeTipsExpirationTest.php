<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeeTipsExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_formatted_see_tips_expires_at_uses_app_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 12:00:00', 'UTC'));

        $user = User::factory()->create([
            'see_tips_expires_at' => Carbon::parse('2026-08-15 18:30:00', 'UTC'),
        ]);

        $formatted = $user->formattedSeeTipsExpiresAt();

        $this->assertNotNull($formatted);
        $this->assertStringContainsString('2026', $formatted);
        $this->assertStringContainsString('18:30', $formatted);

        Carbon::setTestNow();
    }

    public function test_formatted_see_tips_expires_at_returns_null_when_unset(): void
    {
        $user = User::factory()->create([
            'see_tips_expires_at' => null,
        ]);

        $this->assertNull($user->formattedSeeTipsExpiresAt());
    }
}
