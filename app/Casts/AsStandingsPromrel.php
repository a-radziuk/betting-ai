<?php

namespace App\Casts;

use App\Support\StandingsPromrelDecoder;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<array<string, array<string, mixed>>, array<string, array<string, mixed>>|string|null>
 */
final class AsStandingsPromrel implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        return StandingsPromrelDecoder::decode($value);
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = StandingsPromrelDecoder::decode($value);
        if ($normalized === []) {
            return null;
        }

        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }
}
