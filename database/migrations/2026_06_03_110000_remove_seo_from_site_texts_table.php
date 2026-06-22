<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        DB::table('site_texts')->whereIn('key', [
            'seo.default.title',
            'seo.home',
        ])->delete();

        $columns = ['meta_title', 'meta_description', 'og_title', 'og_description'];
        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('site_texts', $column),
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('site_texts', function (Blueprint $table) use ($existing): void {
            $table->dropColumn($existing);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        Schema::table('site_texts', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_texts', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('value');
            }
            if (! Schema::hasColumn('site_texts', 'meta_description')) {
                $table->string('meta_description', 320)->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('site_texts', 'og_title')) {
                $table->string('og_title')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('site_texts', 'og_description')) {
                $table->string('og_description', 320)->nullable()->after('og_title');
            }
        });
    }
};
