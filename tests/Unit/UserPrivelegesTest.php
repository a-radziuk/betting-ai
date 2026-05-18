<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPrivelegesTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_has_all_priveleges(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => true,
            'priveleges' => null,
        ]);

        $this->assertTrue($user->isSuperadmin());
        $this->assertTrue($user->hasPrivelege(User::PRIVELEGE_SEE_TIPS));
        $this->assertTrue($user->hasPrivelege(User::PRIVELEGE_PLACE_BETS));
    }

    public function test_user_with_priveleges_string(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_SEE_TIPS.','.User::PRIVELEGE_PLACE_BETS,
        ]);

        $this->assertFalse($user->isSuperadmin());
        $this->assertTrue($user->hasPrivelege(User::PRIVELEGE_SEE_TIPS));
        $this->assertTrue($user->hasPrivelege(User::PRIVELEGE_PLACE_BETS));
    }

    public function test_user_without_priveleges_has_none(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => null,
        ]);

        $this->assertFalse($user->hasPrivelege(User::PRIVELEGE_SEE_TIPS));
        $this->assertFalse($user->hasPrivelege(User::PRIVELEGE_PLACE_BETS));
    }

    public function test_has_privelege_is_case_sensitive(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->assertTrue($user->hasPrivelege('SEE_TIPS'));
        $this->assertFalse($user->hasPrivelege('see_tips'));
    }
}
