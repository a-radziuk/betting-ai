<?php

namespace Tests\Unit;

use App\Services\GuardianStandingsParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GuardianStandingsParserTest extends TestCase
{
    private function groupTableHtml(string $groupName, string $teamName): string
    {
        return <<<HTML
<h3>{$groupName}</h3>
<table>
<thead><tr>
<th><abbr title="Position">P</abbr></th>
<th>Team</th>
<th><abbr title="Games played">GP</abbr></th>
<th><abbr title="Won">W</abbr></th>
<th><abbr title="Drawn">D</abbr></th>
<th><abbr title="Lost">L</abbr></th>
<th><abbr title="Goals for">F</abbr></th>
<th><abbr title="Goals against">A</abbr></th>
<th><abbr title="Goal difference">GD</abbr></th>
<th><abbr title="Points">Pts</abbr></th>
<th><abbr title="Results of previous games">Form</abbr></th>
</tr></thead>
<tbody>
<tr>
<td>1</td>
<th scope="row"><a href="/football/{$teamName}">{$teamName}</a></th>
<td>2</td><td>1</td><td>1</td><td>0</td><td>3</td><td>1</td><td>2</td><td><b>4</b></td>
<td>WD</td>
</tr>
</tbody>
</table>
HTML;
    }

    public function test_parse_multi_group_html_extracts_groups_and_rows(): void
    {
        $html = '<!DOCTYPE html><html><body>'
            .$this->groupTableHtml('Group A', 'Alpha FC')
            .$this->groupTableHtml('Group B', 'Beta FC')
            .'</body></html>';

        $parser = new GuardianStandingsParser;
        $data = $parser->parseMultiGroupHtml($html);

        $this->assertArrayHasKey('groups', $data);
        $this->assertCount(2, $data['groups']);
        $this->assertSame('Group A', $data['groups'][0]['name']);
        $this->assertSame('Alpha FC', $data['groups'][0]['rows'][0]['team']);
        $this->assertSame(4, $data['groups'][0]['rows'][0]['points']);
        $this->assertSame('Group B', $data['groups'][1]['name']);
        $this->assertSame('Beta FC', $data['groups'][1]['rows'][0]['team']);
    }

    public function test_parse_multi_group_html_throws_when_no_tables(): void
    {
        $parser = new GuardianStandingsParser;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No Guardian-style group standings tables found');

        $parser->parseMultiGroupHtml('<html><body><p>No tables</p></body></html>');
    }

    public function test_parse_html_still_returns_single_league_rows(): void
    {
        $html = '<!DOCTYPE html><html><body>'
            .$this->groupTableHtml('Ignored heading', 'Solo FC')
            .'</body></html>';

        $parser = new GuardianStandingsParser;
        $data = $parser->parseHtml($html);

        $this->assertArrayHasKey('rows', $data);
        $this->assertCount(1, $data['rows']);
        $this->assertSame('Solo FC', $data['rows'][0]['team']);
    }
}
