<?php

namespace Tests\Unit;

use App\Services\BbcStandingsParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BbcStandingsParserTest extends TestCase
{
    private function minimalBbcTableHtml(string $teamName = 'Tromsø', string $form = 'DWL'): string
    {
        $formHtml = '';
        foreach (str_split($form) as $letter) {
            $formHtml .= '<li><div data-testid="letter-content">'.$letter.'</div></li>';
        }

        return <<<HTML
<!DOCTYPE html>
<html><body>
<table data-testid="football-table">
<thead><tr>
<th><span class="visually-hidden">Team</span></th>
<th><span class="visually-hidden">Played</span></th>
<th><span class="visually-hidden">Won</span></th>
<th><span class="visually-hidden">Drawn</span></th>
<th><span class="visually-hidden">Lost</span></th>
<th><span class="visually-hidden">Goals For</span></th>
<th><span class="visually-hidden">Goals Against</span></th>
<th><span class="visually-hidden">Goal Difference</span></th>
<th><span class="visually-hidden">Points</span></th>
<th><span class="visually-hidden">Form, Last 6 games, Oldest first</span></th>
</tr></thead>
<tbody>
<tr>
<td aria-label="Team"><span class="ssrcss-1mnw0cb-Rank">1</span><span data-600="{$teamName}" aria-hidden="true"></span><span class="visually-hidden">{$teamName}</span></td>
<td aria-label="Played">14</td>
<td aria-label="Won">8</td>
<td aria-label="Drawn">4</td>
<td aria-label="Lost">2</td>
<td aria-label="Goals For">22</td>
<td aria-label="Goals Against">14</td>
<td aria-label="Goal Difference">8</td>
<td aria-label="Points"><span>28</span></td>
<td aria-label="Form, Last 6 games, Oldest first"><ul>{$formHtml}</ul></td>
</tr>
</tbody>
</table>
</body></html>
HTML;
    }

    public function test_parse_html_extracts_bbc_standings_row(): void
    {
        $parser = new BbcStandingsParser;
        $data = $parser->parseHtml($this->minimalBbcTableHtml());

        $this->assertArrayHasKey('rows', $data);
        $this->assertCount(1, $data['rows']);
        $row = $data['rows'][0];
        $this->assertSame(1, $row['position']);
        $this->assertSame('Tromsø', $row['team']);
        $this->assertNull($row['team_path']);
        $this->assertSame(14, $row['played']);
        $this->assertSame(8, $row['won']);
        $this->assertSame(4, $row['drawn']);
        $this->assertSame(2, $row['lost']);
        $this->assertSame(22, $row['goals_for']);
        $this->assertSame(14, $row['goals_against']);
        $this->assertSame(8, $row['goal_difference']);
        $this->assertSame(28, $row['points']);
        $this->assertSame('DWL', $row['form']);
    }

    public function test_parse_html_throws_when_no_table(): void
    {
        $parser = new BbcStandingsParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No BBC standings table found');

        $parser->parseHtml('<html><body><p>No table</p></body></html>');
    }
}
