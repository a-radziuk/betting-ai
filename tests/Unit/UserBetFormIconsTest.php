<?php

namespace Tests\Unit;

use App\Models\UserBet;
use App\Support\UserBetFormIcons;
use Tests\TestCase;

class UserBetFormIconsTest extends TestCase
{
    public function test_maps_statuses_to_letters_and_css(): void
    {
        $bets = collect([
            (new UserBet)->forceFill(['id' => 1, 'status' => UserBet::STATUS_WON, 'stake' => 10]),
            (new UserBet)->forceFill(['id' => 2, 'status' => UserBet::STATUS_LOST, 'stake' => 5]),
            (new UserBet)->forceFill(['id' => 3, 'status' => UserBet::STATUS_VOID, 'stake' => 2]),
        ]);

        $segments = UserBetFormIcons::fromBets($bets);
        $this->assertSame([
            ['letter' => 'W', 'css' => 'w', 'tooltip' => 'Bet won — stake 10.00 EUR'],
            ['letter' => 'L', 'css' => 'l', 'tooltip' => 'Bet lost — stake 5.00 EUR'],
            ['letter' => 'D', 'css' => 'd', 'tooltip' => 'Void — stake 2.00 EUR returned'],
        ], $segments);
    }

    public function test_sorts_by_id_ascending_for_display(): void
    {
        $bets = collect([
            (new UserBet)->forceFill(['id' => 30, 'status' => UserBet::STATUS_LOST, 'stake' => 1]),
            (new UserBet)->forceFill(['id' => 10, 'status' => UserBet::STATUS_WON, 'stake' => 1]),
        ]);

        $letters = array_map(fn ($s) => $s['letter'], UserBetFormIcons::fromBets($bets));
        $this->assertSame(['W', 'L'], $letters);
    }

    public function test_exclude_pending_filters_before_mapping(): void
    {
        $bets = collect([
            (new UserBet)->forceFill(['id' => 1, 'status' => UserBet::STATUS_WON, 'stake' => 1]),
            (new UserBet)->forceFill(['id' => 2, 'status' => UserBet::STATUS_PENDING, 'stake' => 1]),
            (new UserBet)->forceFill(['id' => 3, 'status' => UserBet::STATUS_LOST, 'stake' => 1]),
        ]);

        $letters = array_map(fn ($s) => $s['letter'], UserBetFormIcons::fromBets($bets, true));
        $this->assertSame(['W', 'L'], $letters);
    }
}
