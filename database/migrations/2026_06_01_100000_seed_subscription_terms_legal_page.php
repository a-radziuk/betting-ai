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

        if (DB::table('legal_pages')->where('slug', 'subscription-terms')->exists()) {
            return;
        }

        $now = now();

        DB::table('legal_pages')->insert([
            'title' => 'Subscription Terms & Conditions',
            'slug' => 'subscription-terms',
            'content' => <<<'HTML'
<h2>1. Service</h2>
<p>[WEBSITE NAME] provides access to football betting insights, including player tips and related content. A subscription grants time-limited access to features described at purchase.</p>

<h2>2. No guarantee of results</h2>
<p>Tips and analysis are for informational purposes only. We do not guarantee winnings or specific outcomes. You are solely responsible for any betting decisions.</p>

<h2>3. Eligibility</h2>
<p>You must be of legal age to use betting services in your jurisdiction and comply with all applicable laws. By subscribing, you confirm that you meet these requirements.</p>

<h2>4. Payments and access</h2>
<p>Fees are charged for the selected plan period. Access begins after successful payment and ends when the subscription period expires unless renewed.</p>

<h2>5. Refunds</h2>
<p>Except where required by law, subscription fees are non-refundable once access has been granted.</p>

<h2>6. Acceptable use</h2>
<p>You may not scrape, resell, or redistribute [WEBSITE NAME] content. We may suspend access for abuse or violation of these terms.</p>

<h2>7. Changes</h2>
<p>We may update these terms or pricing. Material changes will apply to new purchases; continued use after notice constitutes acceptance where permitted by law.</p>

<h2>8. Contact</h2>
<p>For questions about your subscription, contact us at [CONTACT EMAIL] or visit [WEBSITE URL].</p>
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

        DB::table('legal_pages')->where('slug', 'subscription-terms')->delete();
    }
};
