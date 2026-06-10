<?php

namespace Tests\Unit;

use App\Support\CookieConsentCategories;
use Tests\TestCase;

class CookieConsentCategoriesTest extends TestCase
{
    public function test_normalize_keeps_essential_enabled_and_optional_disabled_by_default(): void
    {
        $normalized = CookieConsentCategories::normalize([
            'analytics' => true,
            'marketing' => false,
        ], false);

        $this->assertTrue($normalized['essential']);
        $this->assertTrue($normalized['analytics']);
        $this->assertFalse($normalized['marketing']);
    }

    public function test_normalize_accepts_all_optional_categories_when_requested(): void
    {
        $normalized = CookieConsentCategories::normalize([], true);

        $this->assertTrue($normalized['essential']);
        $this->assertTrue($normalized['analytics']);
        $this->assertTrue($normalized['marketing']);
    }
}
