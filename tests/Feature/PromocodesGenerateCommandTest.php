<?php

namespace Tests\Feature;

use App\Models\Promocode;
use App\Models\User;
use App\Support\PromocodeGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PromocodesGenerateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_requested_number_of_promocodes(): void
    {
        $exit = Artisan::call('promocodes:generate', [
            'days' => 4,
            'number' => 3,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Generated 3 promocode(s) for 4 day(s) each.', $output);
        $this->assertDatabaseCount('promocodes', 3);
        $this->assertSame(3, Promocode::query()->where('days', 4)->count());

        $firstCode = Promocode::query()->value('code');
        $this->assertStringStartsWith(PromocodeGenerator::prefix(), $firstCode);
    }

    public function test_command_uses_default_arguments(): void
    {
        Artisan::call('promocodes:generate');

        $this->assertDatabaseCount('promocodes', 20);
        $this->assertSame(20, Promocode::query()->where('days', 1)->count());
    }

    public function test_command_fails_for_invalid_arguments(): void
    {
        $this->assertSame(1, Artisan::call('promocodes:generate', ['days' => 0, 'number' => 5]));
        $this->assertSame(1, Artisan::call('promocodes:generate', ['days' => 1, 'number' => 0]));
        $this->assertDatabaseCount('promocodes', 0);
    }

    public function test_generated_codes_use_configured_prefix(): void
    {
        config(['promocodes.prefix' => 'BETAI-']);

        $promocode = PromocodeGenerator::generateUnique(2);

        $this->assertStringStartsWith('BETAI-', $promocode->code);
    }
}
