<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        $now = now();

        $rows = [
            [
                'key' => 'home.hero.eyebrow',
                'group' => 'home',
                'label' => 'Home hero eyebrow',
                'value' => 'AI-powered insights',
            ],
            [
                'key' => 'home.hero.title',
                'group' => 'home',
                'label' => 'Home hero title',
                'value' => 'Smart football bets with AI',
            ],
            [
                'key' => 'home.hero.lead',
                'group' => 'home',
                'label' => 'Home hero lead',
                'value' => 'Get your best betting tips, sharper match reads, and data-driven picks for every fixture on the board.',
            ],
            [
                'key' => 'header.tagline',
                'group' => 'header',
                'label' => 'Header tagline',
                'value' => 'AI-Powered Football Betting Insights',
            ],
            [
                'key' => 'footer.tagline',
                'group' => 'footer',
                'label' => 'Footer tagline',
                'value' => 'Smart football markets, live opportunities, better decisions.',
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('site_texts')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('site_texts')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        DB::table('site_texts')->whereIn('key', [
            'home.hero.eyebrow',
            'home.hero.title',
            'home.hero.lead',
            'header.tagline',
            'footer.tagline',
        ])->delete();
    }
};
