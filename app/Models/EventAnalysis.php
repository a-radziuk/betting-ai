<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAnalysis extends Model
{
    public const LIKELY_OUTCOME_HOME_WIN = 'HOME_WIN';

    public const LIKELY_OUTCOME_DRAW = 'DRAW';

    public const LIKELY_OUTCOME_AWAY_WIN = 'AWAY_WIN';

    public const STRENGTH_MIN = 0;

    public const STRENGTH_MAX = 10;

    public const TYPE_MANUAL = 'GPT_MANUAL';

    public const TYPE_GPT1 = 'GPT_1';

    /**
     * @return list<string>
     */
    public static function likelyOutcomes(): array
    {
        return [
            self::LIKELY_OUTCOME_HOME_WIN,
            self::LIKELY_OUTCOME_DRAW,
            self::LIKELY_OUTCOME_AWAY_WIN,
        ];
    }

    public static function isValidLikelyOutcome(string $outcome): bool
    {
        return in_array($outcome, self::likelyOutcomes(), true);
    }

    public static function isValidStrength(int $strength): bool
    {
        return $strength >= self::STRENGTH_MIN && $strength <= self::STRENGTH_MAX;
    }

    public function likelyOutcomeLabel(): string
    {
        return match ($this->likely_outcome) {
            self::LIKELY_OUTCOME_HOME_WIN => 'Home win',
            self::LIKELY_OUTCOME_DRAW => 'Draw',
            self::LIKELY_OUTCOME_AWAY_WIN => 'Away win',
            default => $this->likely_outcome,
        };
    }

    /**
     * @return list<array{label: string, event_id: string|null}>
     */
    public function influencedByEntries(): array
    {
        $labels = $this->normalizedInfluencedByLabels();
        $ids = $this->normalizedInfluencedByEventIds();
        $count = max(count($labels), count($ids));

        if ($count === 0) {
            return [];
        }

        $entries = [];
        $eventIdsNeedingLabels = [];

        for ($index = 0; $index < $count; $index++) {
            $label = $labels[$index] ?? '';
            $eventId = $ids[$index] ?? null;

            if ($label === '' && $eventId !== null) {
                $eventIdsNeedingLabels[] = $eventId;
            }

            $entries[] = [
                'label' => $label,
                'event_id' => $eventId,
            ];
        }

        if ($eventIdsNeedingLabels !== []) {
            $events = Event::query()
                ->with(['homeTeam', 'awayTeam'])
                ->whereIn('id', array_unique($eventIdsNeedingLabels))
                ->get()
                ->keyBy(static fn (Event $event): string => (string) $event->id);

            foreach ($entries as &$entry) {
                if ($entry['label'] !== '' || $entry['event_id'] === null) {
                    continue;
                }

                $related = $events->get($entry['event_id']);
                $entry['label'] = $related !== null
                    ? trim(
                        ($related->homeTeam?->resolvedDisplayName() ?? ('Team #'.$related->home_team_id))
                        .' vs '
                        .($related->awayTeam?->resolvedDisplayName() ?? ('Team #'.$related->away_team_id))
                    )
                    : 'Event #'.$entry['event_id'];
            }
            unset($entry);
        }

        return array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry['label'] !== '' || $entry['event_id'] !== null,
        ));
    }

    /**
     * @return list<string>
     */
    private function normalizedInfluencedByLabels(): array
    {
        $value = $this->influenced_by;

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? [] : [$trimmed];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value,
        ), static fn (string $item): bool => $item !== ''));
    }

    /**
     * @return list<string>
     */
    private function normalizedInfluencedByEventIds(): array
    {
        $value = $this->influenced_by_event_ids;

        if (is_string($value) || is_int($value)) {
            return [(string) $value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $id): string => trim((string) $id),
            $value,
        ), static fn (string $id): bool => $id !== ''));
    }

    protected $fillable = [
        'event_id',
        'type',
        'strength',
        'event_name',
        'likely_outcome',
        'approximate_goals',
        'description',
        'home_motivation',
        'away_motivation',
        'home_class',
        'away_class',
        'influenced_by',
        'influenced_by_event_ids',
    ];

    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'strength' => 'integer',
            'approximate_goals' => 'integer',
            'home_motivation' => 'integer',
            'away_motivation' => 'integer',
            'home_class' => 'integer',
            'away_class' => 'integer',
            'influenced_by' => 'array',
            'influenced_by_event_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Shape used in export / LLM JSON responses (camelCase keys).
     *
     * @return array{
     *     eventId: string,
     *     type: string,
     *     strength: int,
     *     eventName: string,
     *     likely_outcome: string,
     *     approximate_goals: int,
     *     description: string,
     *     home_motivation: int,
     *     away_motivation: int,
     *     home_class: int,
     *     away_class: int,
     *     influenced_by: list<string>|null,
     *     influenced_by_event_ids: list<string>|null
     * }
     */
    public function toExportArray(): array
    {
        return [
            'eventId' => (string) $this->event_id,
            'type' => $this->type,
            'strength' => $this->strength,
            'eventName' => $this->event_name,
            'likely_outcome' => $this->likely_outcome,
            'approximate_goals' => $this->approximate_goals,
            'description' => $this->description,
            'home_motivation' => $this->home_motivation,
            'away_motivation' => $this->away_motivation,
            'home_class' => $this->home_class,
            'away_class' => $this->away_class,
            'influenced_by' => $this->influenced_by,
            'influenced_by_event_ids' => $this->influenced_by_event_ids === null
                ? null
                : array_map(strval(...), $this->influenced_by_event_ids),
        ];
    }
}
