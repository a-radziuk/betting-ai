<?php

namespace Tests\Unit;

use App\Services\BbcPremierLeagueScoresParser;
use PHPUnit\Framework\TestCase;

class BbcPremierLeagueScoresParserTest extends TestCase
{
    public function test_parses_post_event_scores_from_escaped_embedded_json(): void
    {
        $escaped = <<<'HTML'
<script>
var x="prefix\",\"data\":{\"eventGroups\":[{\"displayLabel\":null,\"secondaryGroups\":[{\"displayLabel\":null,\"events\":[{\"home\":{\"fullName\":\"Test Home FC\",\"score\":\"2\"},\"away\":{\"fullName\":\"Test Away FC\",\"score\":\"1\"},\"status\":\"PostEvent\"},{\"home\":{\"fullName\":\"Later\",\"score\":\"0\"},\"away\":{\"fullName\":\"Soon\",\"score\":\"0\"},\"status\":\"PreEvent\"}]}]}]}suffix";
</script>
HTML;

        $parser = new BbcPremierLeagueScoresParser;
        $rows = $parser->parseFinishedResults($escaped);

        $this->assertCount(1, $rows);
        $this->assertSame('Test Home FC', $rows[0]['homeName']);
        $this->assertSame('Test Away FC', $rows[0]['awayName']);
        $this->assertSame(2, $rows[0]['homeGoals']);
        $this->assertSame(1, $rows[0]['awayGoals']);
    }
}
