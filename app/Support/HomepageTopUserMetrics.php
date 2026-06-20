<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageTopUserMetrics
{
    /**
     * @return Collection<int, UserMetric>
     */
    public static function forHomepage(): Collection
    {
        if (! Schema::hasTable('user_metrics')) {
            return collect();
        }

        $visibleUserMetrics = UserMetric::query()
            ->whereHas('user', fn (Builder $query) => $query->visibleOnSite());

        if ((clone $visibleUserMetrics)->distinct()->count('user_id') < 3) {
            return collect();
        }

        $selectedMetrics = collect();
        $seenUserIds = [];

        foreach ((clone $visibleUserMetrics)
            ->orderByDesc('amount')
            ->orderBy('id')
            ->with(['user.wallet'])
            ->get() as $metric) {
            if (in_array($metric->user_id, $seenUserIds, true)) {
                continue;
            }

            $seenUserIds[] = $metric->user_id;
            $selectedMetrics->push($metric);

            if ($selectedMetrics->count() === 3) {
                break;
            }
        }

        if ($selectedMetrics->count() < 3) {
            return collect();
        }

        return $selectedMetrics;
    }

    public static function bestForHero(): ?UserMetric
    {
        if (! Schema::hasTable('user_metrics')) {
            return null;
        }

        return UserMetric::query()
            ->whereHas('user', fn (Builder $query) => $query->visibleOnSite())
            ->orderByDesc('amount')
            ->orderBy('id')
            ->with(['user.wallet'])
            ->first();
    }
}
