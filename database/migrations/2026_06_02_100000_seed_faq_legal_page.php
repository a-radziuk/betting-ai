<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('legal_pages')) {
            return;
        }

        if (DB::table('legal_pages')->where('slug', 'faq')->exists()) {
            return;
        }

        $now = now();

        DB::table('legal_pages')->insert([
            'title' => 'FAQ',
            'slug' => 'faq',
            'content' => <<<'HTML'
<h2>General</h2>
<p>Add frequently asked questions and answers here. You can edit this page in the admin under Legal Pages.</p>

<h2>Subscriptions</h2>
<p>Explain how tips access, promocodes, and subscription plans work on [WEBSITE NAME].</p>

<h2>Contact</h2>
<p>If your question is not answered here, contact us at [CONTACT EMAIL].</p>
HTML,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('legal_pages')) {
            return;
        }

        DB::table('legal_pages')->where('slug', 'faq')->delete();
    }
};
