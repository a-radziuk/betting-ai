<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Selection;
use App\Models\Team;
use Tests\TestCase;

class SelectionDisplayNameTest extends TestCase
{
    public function test_humanizes_home_and_away_without_team_names(): void
    {
        $event = $this->makeEventWithTeams('Arsenal', 'Chelsea');

        $this->assertSame('Home', Selection::displayNameFor(Selection::NAME_HOME, $event));
        $this->assertSame('Away', Selection::displayNameFor(Selection::NAME_AWAY, $event));
    }

    public function test_humanizes_common_selection_tokens(): void
    {
        $event = $this->makeEventWithTeams('Arsenal', 'Chelsea');

        $this->assertSame('Over', Selection::displayNameFor(Selection::NAME_OVER, $event));
        $this->assertSame('Under', Selection::displayNameFor(Selection::NAME_UNDER, $event));
        $this->assertSame('Yes', Selection::displayNameFor(Selection::NAME_YES, $event));
        $this->assertSame('No', Selection::displayNameFor(Selection::NAME_NO, $event));
        $this->assertSame('Draw', Selection::displayNameFor(Selection::NAME_DRAW, $event));
    }

    public function test_humanizes_double_chance_selection_names(): void
    {
        $event = $this->makeEventWithTeams('Arsenal', 'Chelsea');

        $this->assertSame('Home or Draw', Selection::displayNameFor('1X', $event));
        $this->assertSame('Draw or Away', Selection::displayNameFor('X2', $event));
        $this->assertSame('Home or Away', Selection::displayNameFor('12', $event));
    }

    private function makeEventWithTeams(string $homeName, string $awayName): Event
    {
        $event = new Event;
        $event->setRelation('homeTeam', new Team(['name' => $homeName]));
        $event->setRelation('awayTeam', new Team(['name' => $awayName]));

        return $event;
    }
}
