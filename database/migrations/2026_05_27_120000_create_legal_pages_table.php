<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->timestamps();
        });

        $now = now();

        DB::table('legal_pages')->insert([
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'content' => '<p>Terms and conditions content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<p>Privacy policy content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Cookie Policy',
                'slug' => 'cookie-policy',
                'content' => '<p>Cookie policy content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Responsible Gambling Policy',
                'slug' => 'responsible-gambling-policy',
                'content' => '<p>Responsible gambling policy content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Betting Disclaimer',
                'slug' => 'betting-disclaimer',
                'content' => '<p>Betting disclaimer content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => '<p>Refund policy content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Age Restriction Notice',
                'slug' => 'age-restriction-notice',
                'content' => '<p>Age restriction notice content goes here.</p>',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
