<?php

namespace App\Support;

final class PlayerWalletResultChart
{
    private const VIEW_WIDTH = 100;

    private const VIEW_HEIGHT = 40;

    private const PADDING = 4;

    /**
     * @param  list<float>  $values  Chronological wallet_total_result values (oldest → newest), excluding the origin.
     * @param  list<array{x: float, y: float, value: float, isOrigin: bool}>  $points
     */
    public function __construct(
        public readonly array $values,
        public readonly array $points,
        public readonly string $polylinePoints,
        public readonly ?float $min,
        public readonly ?float $max,
        public readonly ?float $latest,
        public readonly ?float $zeroLineY,
    ) {}

    /**
     * @param  list<float|int|string|null>  $values
     */
    public static function fromValues(array $values): self
    {
        $values = array_values(array_map(
            static fn ($value) => (float) $value,
            array_filter($values, static fn ($value) => $value !== null),
        ));

        if ($values === []) {
            return new self([], [], '', null, null, null, null);
        }

        $isFromOrigin = false;

        if (count($values) <=  30) {
            $isFromOrigin = true;
            $chartValues = array_merge([0.0], $values);
        } else {
            $chartValues = array_merge($values);
        }

        $min = min($chartValues);
        $max = max($chartValues);
        $range = $max - $min;
        if ($range < 0.000001) {
            $range = 1.0;
        }

        $plotW = self::VIEW_WIDTH - (2 * self::PADDING);
        $plotH = self::VIEW_HEIGHT - (2 * self::PADDING);
        $count = count($chartValues);

        $points = [];
        foreach ($chartValues as $i => $value) {
            $x = self::PADDING + ($count === 1 ? $plotW / 2 : ($i / ($count - 1)) * $plotW);
            $normalized = ($value - $min) / $range;
            $y = self::PADDING + $plotH - ($normalized * $plotH);
            $points[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $value,
                'isOrigin' => $i === 0 && $isFromOrigin,
            ];
        }

        $polylinePoints = implode(' ', array_map(
            static fn (array $point): string => sprintf('%.2f,%.2f', $point['x'], $point['y']),
            $points,
        ));

        $zeroLineY = null;
        if ($min < 0 && $max > 0) {
            $zeroNormalized = (0 - $min) / $range;
            $zeroLineY = self::PADDING + $plotH - ($zeroNormalized * $plotH);
        }

        return new self(
            $values,
            $points,
            $polylinePoints,
            $min,
            $max,
            $values[array_key_last($values)],
            $zeroLineY,
        );
    }

    public function hasData(): bool
    {
        return $this->values !== [];
    }
}
