<?php

namespace Tests\Unit;

use App\Services\BbcPremierLeagueScoresParser;
use PHPUnit\Framework\TestCase;

class BbcPremierLeagueScoresParserTest extends TestCase
{
    public function test_parses_finished_match_from_match_div_with_ft(): void
    {
        $html = <<<'HTML'
<div data-event-id="s-test-home-away" class="ssrcss-1bjtunb-GridContainer e1efi6g55">
<div class="ssrcss-bon2fo-WithInlineFallback-TeamHome e1efi6g53">
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Test Home FC</span>
</div>
<div class="ssrcss-y5s079-WithInlineFallback-Scores e1efi6g51">
<div data-testid="score" class="ssrcss-da2sba-StyledScore e56kr2l3">
<div class="ssrcss-qsbptj-HomeScore e56kr2l2">2</div>
<div class="ssrcss-fri5a2-AwayScore e56kr2l1">1</div>
</div>
</div>
<div class="ssrcss-1bjtunb-WithInlineFallback-TeamAway e1efi6g52">
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Test Away FC</span>
</div>
<div class="ssrcss-1739un0-StyledPeriod e307mhr0"><div>FT</div></div>
</div>
HTML;

        $parser = new BbcPremierLeagueScoresParser;
        $rows = $parser->parseFinishedResults($html);

        $this->assertCount(1, $rows);
        $this->assertSame('Test Home FC', $rows[0]['homeName']);
        $this->assertSame('Test Away FC', $rows[0]['awayName']);
        $this->assertSame(2, $rows[0]['homeGoals']);
        $this->assertSame(1, $rows[0]['awayGoals']);
        $this->assertSame('FT', $rows[0]['status']);
    }

    public function test_ignores_upcoming_match_without_ft_in_match_div(): void
    {
        $html = <<<'HTML'
<div data-event-id="s-test-upcoming" class="ssrcss-1bjtunb-GridContainer e1efi6g55">
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Later FC</span>
<div class="ssrcss-qsbptj-HomeScore e56kr2l2">0</div>
<div class="ssrcss-fri5a2-AwayScore e56kr2l1">0</div>
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Soon FC</span>
<div class="ssrcss-1739un0-StyledPeriod e307mhr0"><div>15:00</div></div>
</div>
HTML;

        $parser = new BbcPremierLeagueScoresParser;
        $rows = $parser->parseFinishedResults($html);

        $this->assertSame([], $rows);
    }

    public function test_parses_post_event_scores_from_escaped_embedded_json_when_no_match_divs(): void
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
