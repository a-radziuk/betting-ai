<?php

namespace Tests\Feature;

use App\Models\Promocode;
use App\Support\PromocodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PromocodesGenerateMultiCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_one_multi_use_promocode(): void
    {
        $exit = Artisan::call('promocodes:generate-multi', [
            'days' => 7,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Generated multi-use promocode for 7 day(s).', $output);
        $this->assertDatabaseCount('promocodes', 1);

        $promocode = Promocode::query()->first();
        $this->assertNotNull($promocode);
        $this->assertTrue($promocode->is_multiple);
        $this->assertSame(7, $promocode->days);
        $this->assertStringStartsWith(PromocodeGenerator::prefix(), $promocode->code);
        $this->assertStringContainsString($promocode->code, $output);
        $this->assertStringContainsString($promocode->redemptionLink(), $output);
    }

    public function test_command_uses_default_days(): void
    {
        Artisan::call('promocodes:generate-multi');

        $this->assertSame(1, Promocode::query()->value('days'));
        $this->assertTrue((bool) Promocode::query()->value('is_multiple'));
    }

    public function test_command_fails_for_invalid_days(): void
    {
        $this->assertSame(1, Artisan::call('promocodes:generate-multi', ['days' => 0]));
        $this->assertDatabaseCount('promocodes', 0);
    }
}
