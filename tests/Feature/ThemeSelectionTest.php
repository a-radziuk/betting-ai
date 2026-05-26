<?php

namespace Tests\Feature;

use Tests\TestCase;

class ThemeSelectionTest extends TestCase
{
    public function test_light_theme_is_applied_on_login_screen(): void
    {
        config(['app.theme' => 'light']);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
        $response->assertSee('var(--bg-soft) !important;', false);
    }

    public function test_light_theme_renders_light_styles(): void
    {
        config(['app.theme' => 'light']);

        $html = view('layouts.partials.betai-styles')->render();

        $this->assertStringContainsString('data-theme="light"', $html);
        $this->assertStringContainsString('--bg: #cfe0f4;', $html);
        $this->assertStringContainsString('repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0 2px, rgba(255, 255, 255, 0) 2px 20px)', $html);
        $this->assertStringContainsString('color: var(--ok) !important;', $html);
        $this->assertStringContainsString('color: #c45466 !important;', $html);
        $this->assertStringContainsString('.subscribe-plan-duration {', $html);
        $this->assertStringContainsString('color: #1c3954 !important;', $html);
    }

    public function test_default_theme_renders_current_default_styles(): void
    {
        config(['app.theme' => 'default']);

        $html = view('layouts.partials.betai-styles')->render();

        $this->assertStringContainsString('data-theme="default"', $html);
        $this->assertStringContainsString('--bg: #060b16;', $html);
        $this->assertStringContainsString('.header {', $html);
    }

    public function test_unknown_theme_falls_back_to_default_styles(): void
    {
        config(['app.theme' => 'unknown-theme']);

        $html = view('layouts.partials.betai-styles')->render();

        $this->assertStringContainsString('data-theme="default"', $html);
        $this->assertStringContainsString('--bg: #060b16;', $html);
        $this->assertStringContainsString('.welcome-top-bettors {', $html);
    }
}
