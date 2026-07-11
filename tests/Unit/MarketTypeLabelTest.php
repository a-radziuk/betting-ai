<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Market;
use App\Models\Team;
use Tests\TestCase;

class MarketTypeLabelTest extends TestCase
{
    public function test_event_page_labels_use_team_names_for_home_and_away_markets(): void
    {
        $event = new Event;
        $event->setRelation('homeTeam', new Team(['name' => 'Arsenal']));
        $event->setRelation('awayTeam', new Team(['name' => 'Chelsea']));

        $market = new Market(['type' => Market::TYPE_HOME_TOTAL_ASIAN]);
        $market->setRelation('event', $event);

        $this->assertSame('Arsenal Total', $market->typeLabelForEvent($event));
        $this->assertSame('Chelsea To Score', Market::typeLabelFor(Market::TYPE_AWAY_TO_SCORE, $event));
    }
}
