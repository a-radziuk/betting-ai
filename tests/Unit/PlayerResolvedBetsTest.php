<?php

namespace Tests\Unit;

use App\Models\UserBet;
use App\Support\PlayerResolvedBets;
use PHPUnit\Framework\TestCase;

class PlayerResolvedBetsTest extends TestCase
{
    public function test_won_lost_amount_for_won_and_lost_bets(): void
    {
        $won = new UserBet([
            'status' => UserBet::STATUS_WON,
            'stake' => '10.00',
            'potential_return' => '25.00',
        ]);
        $lost = new UserBet([
            'status' => UserBet::STATUS_LOST,
            'stake' => '10.00',
            'potential_return' => '20.00',
        ]);

        $this->assertSame(15.0, PlayerResolvedBets::wonLostAmount($won));
        $this->assertSame(-10.0, PlayerResolvedBets::wonLostAmount($lost));
    }
}
