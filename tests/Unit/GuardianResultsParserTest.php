<?php

namespace Tests\Unit;

use App\Services\GuardianResultsParser;
use PHPUnit\Framework\TestCase;

class GuardianResultsParserTest extends TestCase
{
    private function sampleGuardianMatchHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html><body>
<section class="dcr-jjtqpb">
<h2>Sunday, 10 May 2026</h2>
<a href="#" class="dcr-12nh7p9"><span class="dcr-yb9mnm">FT</span><div class="dcr-3l4pru"><span class="dcr-iqim6o">Alpha Town</span></div><span class="dcr-17v2nd5"><span class="dcr-79z44d">3</span><span class="dcr-13mkt9n"></span><span class="dcr-1c2czlv">1</span></span><div class="dcr-rm7qtf"><picture></picture>Beta City</div></a>
</section>
</body></html>
HTML;
    }

    public function test_parses_ft_score_and_team_labels(): void
    {
        $parser = new GuardianResultsParser;
        $rows = $parser->parseHtml($this->sampleGuardianMatchHtml());

        $this->assertCount(1, $rows);
        $this->assertSame('Alpha Town', $rows[0]['homeName']);
        $this->assertSame('Beta City', $rows[0]['awayName']);
        $this->assertSame(3, $rows[0]['homeGoals']);
        $this->assertSame(1, $rows[0]['awayGoals']);
        $this->assertSame('2026-05-10', $rows[0]['matchDate']->format('Y-m-d'));
        $this->assertSame('Sunday, 10 May 2026', $rows[0]['sectionDateHeading']);
    }
}
