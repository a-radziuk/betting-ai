<?php

namespace Tests\Unit;

use App\Support\LegalPageContent;
use Tests\TestCase;

class LegalPageContentTest extends TestCase
{
    public function test_replaces_all_supported_parameters(): void
    {
        config([
            'legal.date' => '2026-05-27',
            'legal.contact_email' => 'legal@example.com',
            'app.name' => 'BetAI',
            'app.url' => 'https://betai.example',
        ]);

        $content = '<p>[DATE] · [WEBSITE NAME] · [CONTACT EMAIL] · [WEBSITE URL]</p>';

        $this->assertSame(
            '<p>2026-05-27 · BetAI · legal@example.com · https://betai.example</p>',
            LegalPageContent::render($content),
        );
    }

    public function test_leaves_unknown_placeholders_unchanged(): void
    {
        config([
            'legal.date' => '',
            'legal.contact_email' => '',
            'app.name' => 'BetAI',
            'app.url' => 'https://betai.example',
        ]);

        $content = '<p>[UNKNOWN] stays</p>';

        $this->assertSame('<p>[UNKNOWN] stays</p>', LegalPageContent::render($content));
    }
}
