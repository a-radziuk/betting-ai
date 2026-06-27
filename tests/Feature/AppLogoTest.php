<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_uses_logo_variant_a_by_default(): void
    {
        config(['app.logo' => 'A']);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="logo logo--a"', false)
            ->assertSee('logo-badge--outline', false);
    }

    public function test_header_uses_logo_variant_b_when_configured(): void
    {
        config(['app.logo' => 'B']);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="logo logo--b"', false)
            ->assertSee('logo-badge--outline', false);
    }

    public function test_invalid_logo_variant_falls_back_to_a(): void
    {
        $this->assertSame('A', config('app.logo', 'A'));

        putenv('APP_LOGO=invalid');
        $this->refreshApplication();

        $this->assertSame('A', config('app.logo'));
    }

    public function test_header_shows_beta_badge_when_is_beta_enabled(): void
    {
        config(['app.is_beta' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="logo-beta"', false)
            ->assertSee('>BETA<', false);
    }

    public function test_header_hides_beta_badge_when_is_beta_disabled(): void
    {
        config(['app.is_beta' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('class="logo-beta"', false);
    }
}
