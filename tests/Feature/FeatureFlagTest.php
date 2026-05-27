<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_feature_helper_reflects_config(): void
    {
        config(['features.example_widget' => true]);
        $this->assertTrue(feature('example_widget'));

        config(['features.example_widget' => false]);
        $this->assertFalse(feature('example_widget'));
    }

    public function test_feature_helper_is_false_for_unknown_flag(): void
    {
        $this->assertFalse(feature('does_not_exist'));
    }

    public function test_blade_feature_conditional(): void
    {
        config(['features.example_widget' => true]);
        $html = Blade::render('@feature("example_widget")<span class="on">enabled</span>@endfeature');
        $this->assertStringContainsString('enabled', $html);
        $this->assertStringContainsString('class="on"', $html);

        config(['features.example_widget' => false]);
        $html = Blade::render('@feature("example_widget")<span class="on">enabled</span>@endfeature');
        $this->assertStringNotContainsString('enabled', $html);
    }
}
