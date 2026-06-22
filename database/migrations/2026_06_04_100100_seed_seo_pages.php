<?php

use App\Models\SeoPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_pages')) {
            return;
        }

        $now = now();

        $rows = [
            [
                'key' => SeoPage::KEY_HOMEPAGE,
                'label' => 'Homepage',
                'meta_title' => ':app | Smart Football Bets with AI',
                'meta_description' => 'Get your best betting tips, sharper match reads, and data-driven picks for every football fixture.',
                'og_title' => ':app | Smart Football Bets with AI',
                'og_description' => 'Get your best betting tips, sharper match reads, and data-driven picks for every football fixture.',
            ],
            [
                'key' => SeoPage::KEY_PLAYERS_INDEX,
                'label' => 'Players page',
                'meta_title' => ':app | Players',
                'meta_description' => 'Browse player profiles, stats, and betting performance.',
                'og_title' => ':app | Players',
                'og_description' => 'Browse player profiles, stats, and betting performance.',
            ],
            [
                'key' => SeoPage::KEY_PLAYER_SHOW,
                'label' => 'Single player page',
                'meta_title' => ':name | Player stats | :app',
                'meta_description' => 'View betting stats and performance for :name.',
                'og_title' => ':name | Player stats | :app',
                'og_description' => 'View betting stats and performance for :name.',
            ],
            [
                'key' => SeoPage::KEY_TOURNAMENT_SHOW,
                'label' => 'Single tournament page',
                'meta_title' => ':tournament — Standings | :app',
                'meta_description' => 'Standings, fixtures, and results for :tournament.',
                'og_title' => ':tournament — Standings | :app',
                'og_description' => 'Standings, fixtures, and results for :tournament.',
            ],
            [
                'key' => SeoPage::KEY_EVENT_SHOW,
                'label' => 'Event page',
                'meta_title' => ':event | Event odds | :app',
                'meta_description' => 'Odds, markets, and betting insights for :event.',
                'og_title' => ':event | Event odds | :app',
                'og_description' => 'Odds, markets, and betting insights for :event.',
            ],
            [
                'key' => SeoPage::KEY_LOGIN,
                'label' => 'Login',
                'meta_title' => ':app | Login',
                'meta_description' => 'Sign in to your account.',
                'og_title' => ':app | Login',
                'og_description' => 'Sign in to your account.',
            ],
            [
                'key' => SeoPage::KEY_REGISTER,
                'label' => 'Register',
                'meta_title' => ':app | Register',
                'meta_description' => 'Create your account.',
                'og_title' => ':app | Register',
                'og_description' => 'Create your account.',
            ],
            [
                'key' => SeoPage::KEY_FORGOT_PASSWORD,
                'label' => 'Forgot password',
                'meta_title' => ':app | Forgot password',
                'meta_description' => 'Reset your account password.',
                'og_title' => ':app | Forgot password',
                'og_description' => 'Reset your account password.',
            ],
        ];

        foreach ($rows as $row) {
            if (DB::table('seo_pages')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('seo_pages')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_pages')) {
            return;
        }

        DB::table('seo_pages')->whereIn('key', [
            SeoPage::KEY_HOMEPAGE,
            SeoPage::KEY_PLAYERS_INDEX,
            SeoPage::KEY_PLAYER_SHOW,
            SeoPage::KEY_TOURNAMENT_SHOW,
            SeoPage::KEY_EVENT_SHOW,
            SeoPage::KEY_LOGIN,
            SeoPage::KEY_REGISTER,
            SeoPage::KEY_FORGOT_PASSWORD,
        ])->delete();
    }
};
