<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventResult;

final class EventScoreDisplay
{
    public static function forEvent(?Event $event): string
    {
        if ($event === null) {
            return '—';
        }

        return self::format($event->score, $event->score_aet, $event->score_pen);
    }

    public static function forEventResult(?EventResult $result): string
    {
        if ($result === null) {
            return '—';
        }

        return self::format($result->results, $result->results_aet, $result->results_pen);
    }

    public static function format(?string $score, ?string $aet = null, ?string $pen = null): string
    {
        if (! filled($score)) {
            return '—';
        }

        $display = (string) $score;

        if (filled($aet)) {
            $display .= ' (aet. '.$aet.')';
        }

        if (filled($pen)) {
            $display .= ' (pen. '.$pen.')';
        }

        return $display;
    }
}
