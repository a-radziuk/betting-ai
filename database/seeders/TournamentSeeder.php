<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    /**
     * Exported from `tournaments` (stable ids for FK references).
     *
     * @var list<array{id: int, name: string}>
     */
    private const ROWS = [
        ['id' => 1, 'name' => 'Premier League'],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::ROWS as $row) {
            DB::table('tournaments')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
