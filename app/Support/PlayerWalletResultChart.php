<?php

namespace App\Support;

final class PlayerWalletResultChart
{
    private const VIEW_WIDTH = 100;

    private const VIEW_HEIGHT = 40;

    private const PADDING = 4;

    /**
     * @param  list<float>  $values  wallet_total_result values in resolved_order (asc), excluding the origin.
     * @param  list<array{x: float, y: float, value: float, isOrigin: bool, date?: string|null, axisDate?: string|null}>  $points
     */
    public function __construct(
        public readonly array $values,
        public readonly array $points,
        public readonly string $polylinePoints,
        public readonly ?float $min,
        public readonly ?float $max,
        public readonly ?float $latest,
        public readonly ?float $windowResult,
        public readonly ?float $zeroLineY,
    ) {}

    /**
     * @param  list<float|int|string|null>  $values
     * @param  list<string|null>  $dates
     * @param  list<string|null>  $axisDates
     * @param  float|null  $baselineBeforeWindow  Cumulative wallet result before the first plotted bet (when not starting at zero).
     */
    public static function fromValues(
        array $values,
        bool $startAtZero = true,
        array $dates = [],
        array $axisDates = [],
        ?float $baselineBeforeWindow = null,
    ): self {
        $values = array_values(array_map(
            static fn ($value) => (float) $value,
            array_filter($values, static fn ($value) => $value !== null),
        ));

        if ($values === []) {
            return new self([], [], '', null, null, null, null, null);
        }

        if ($dates !== []) {
            $dates = array_values($dates);
        }

        if ($axisDates !== []) {
            $axisDates = array_values($axisDates);
        }

        $chartValues = $startAtZero ? array_merge([0.0], $values) : $values;

        if ($startAtZero && $dates !== []) {
            array_unshift($dates, null);
        }

        if ($startAtZero && $axisDates !== []) {
            array_unshift($axisDates, null);
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
            $point = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $value,
                'isOrigin' => $startAtZero && $i === 0,
            ];

            if ($dates !== []) {
                $point['date'] = $dates[$i] ?? null;
            }

            if ($axisDates !== []) {
                $point['axisDate'] = $axisDates[$i] ?? null;
            }

            $points[] = $point;
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

        $latest = $values[array_key_last($values)];
        $windowResult = $startAtZero
            ? $latest
            : $latest - ($baselineBeforeWindow ?? 0.0);

        return new self(
            $values,
            $points,
            $polylinePoints,
            $min,
            $max,
            $latest,
            $windowResult,
            $zeroLineY,
        );
    }

    public function hasData(): bool
    {
        return $this->values !== [];
    }

    /**
     * @return list<array{date: string, x: float, align: string}>
     */
    public function axisDateLabels(int $maxLabels = 6): array
    {
        $points = array_values(array_filter(
            $this->points,
            static fn (array $point): bool => ! ($point['isOrigin'] ?? false)
                && ($point['axisDate'] ?? null) !== null,
        ));

        if ($points === []) {
            return [];
        }

        $count = count($points);
        if ($count <= $maxLabels) {
            $labels = array_map(
                static fn (array $point): array => [
                    'date' => $point['axisDate'],
                    'x' => $point['x'],
                ],
                $points,
            );
        } else {
            $labels = [];
            for ($i = 0; $i < $maxLabels; $i++) {
                $index = (int) round($i * ($count - 1) / ($maxLabels - 1));
                $point = $points[$index];
                $labels[] = [
                    'date' => $point['axisDate'],
                    'x' => $point['x'],
                ];
            }
        }

        $lastIndex = count($labels) - 1;
        foreach ($labels as $index => $label) {
            $labels[$index]['align'] = match (true) {
                $lastIndex === 0 => 'center',
                $index === 0 => 'start',
                $index === $lastIndex => 'end',
                default => 'center',
            };
        }

        return $labels;
    }
}
