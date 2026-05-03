<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Alice Tester',
            'email' => 'alice@example.com',
        ]);

        User::factory()->create([
            'name' => 'Bob Tester',
            'email' => 'bob@example.com',
        ]);
    }
}
