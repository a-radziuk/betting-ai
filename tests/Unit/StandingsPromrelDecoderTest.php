<?php

namespace Tests\Unit;

use App\Support\StandingsPromrelDecoder;
use PHPUnit\Framework\TestCase;

class StandingsPromrelDecoderTest extends TestCase
{
    public function test_decodes_json_with_smart_quotes(): void
    {
        $json = <<<'JSON'
{
 "1": {
 "type": "promotion",
 "name": “Playoff”,
 "subtype": "champions-league",
 "positivity": 10
 },
 "3": {
 "type": "promotion",
 "name": “Possible play-off”,
 "subtype": “europa-league”,
 "positivity": 5
 }
}
JSON;

        $decoded = StandingsPromrelDecoder::decode($json);

        $this->assertCount(2, $decoded);
        $this->assertSame('Playoff', $decoded['1']['name']);
        $this->assertSame('Possible play-off', $decoded['3']['name']);
        $this->assertSame('europa-league', $decoded['3']['subtype']);
    }
}
