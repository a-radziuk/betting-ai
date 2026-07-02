<?php

namespace Tests\Unit;

use App\Support\PlayoffGameHistoryExport;
use Tests\TestCase;

class PlayoffGameHistoryExportTest extends TestCase
{
    public function test_builds_readable_game_history_with_goal_totals(): void
    {
        $history = PlayoffGameHistoryExport::fromStandings([
            'rows' => [
                [
                    'team' => 'Alpha FC',
                    'form' => 'Won 2-0 against FulhamLost 1-2 to EvertonDrew 1-1 with Leeds',
                ],
            ],
        ]);

        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertSame('Alpha FC', $history[0]['team']);
        $this->assertCount(3, $history[0]['games']);
        $this->assertSame('win', $history[0]['games'][0]['result']);
        $this->assertSame('Won 2-0 against Fulham', $history[0]['games'][0]['summary']);
        $this->assertSame('Fulham', $history[0]['games'][0]['opponent']);
        $this->assertSame(2, $history[0]['games'][0]['goals_scored']);
        $this->assertSame(0, $history[0]['games'][0]['goals_conceded']);
        $this->assertSame('loss', $history[0]['games'][1]['result']);
        $this->assertSame(1, $history[0]['games'][1]['goals_scored']);
        $this->assertSame(2, $history[0]['games'][1]['goals_conceded']);
        $this->assertSame(4, $history[0]['goals_scored']);
        $this->assertSame(3, $history[0]['goals_conceded']);
    }

    public function test_supports_grouped_standings(): void
    {
        $history = PlayoffGameHistoryExport::fromStandings([
            'groups' => [
                [
                    'name' => 'Group A',
                    'rows' => [
                        [
                            'team' => 'Mexico',
                            'form' => 'Won 2-0 against South Africa',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $history);
        $this->assertSame('Group A', $history[0]['group']);
        $this->assertSame(2, $history[0]['goals_scored']);
        $this->assertSame(0, $history[0]['goals_conceded']);
    }
}
