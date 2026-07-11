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

        $this->assertSame('Arsenal', Selection::displayNameFor(Selection::NAME_HOME, $event));
        $this->assertSame('Chelsea', Selection::displayNameFor(Selection::NAME_AWAY, $event));
    }

    public function test_falls_back_to_home_and_away_without_event(): void
    {
        $this->assertSame('Home', Selection::displayNameFor(Selection::NAME_HOME));
        $this->assertSame('Away', Selection::displayNameFor(Selection::NAME_AWAY));
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

        $this->assertSame('Arsenal or Draw', Selection::displayNameFor('1X', $event));
        $this->assertSame('Draw or Chelsea', Selection::displayNameFor('X2', $event));
        $this->assertSame('Arsenal or Chelsea', Selection::displayNameFor('12', $event));
    }

    public function test_display_name_with_value_appends_line_for_asian_total_markets(): void
    {
        $event = $this->makeEventWithTeams('Arsenal', 'Chelsea');
        $selection = new Selection([
            'name' => Selection::NAME_OVER,
            'value' => 2.5,
        ]);
        $selection->setRelation('market', new \App\Models\Market(['type' => \App\Models\Market::TYPE_TOTAL_ASIAN]));
        $selection->market->setRelation('event', $event);

        $this->assertSame('Over 2.5', $selection->displayNameWithValue($event));
    }

    public function test_display_name_with_value_formats_handicap_sign(): void
    {
        $event = $this->makeEventWithTeams('Arsenal', 'Chelsea');
        $selection = new Selection([
            'name' => Selection::NAME_HOME,
            'value' => -1.5,
        ]);
        $selection->setRelation('market', new \App\Models\Market(['type' => \App\Models\Market::TYPE_HANDICAP_ASIAN]));
        $selection->market->setRelation('event', $event);

        $this->assertSame('Arsenal -1.5', $selection->displayNameWithValue($event));
    }

    private function makeEventWithTeams(string $homeName, string $awayName): Event
    {
        $event = new Event;
        $event->setRelation('homeTeam', new Team(['name' => $homeName]));
        $event->setRelation('awayTeam', new Team(['name' => $awayName]));

        return $event;
    }
}
