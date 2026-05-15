<?php

namespace Tests\Unit;

use App\Models\EventPrediction;
use PHPUnit\Framework\TestCase;

class EventPredictionTypeMapTest extends TestCase
{
    public function test_maps_numeric_keys_to_prediction_type_constants(): void
    {
        $this->assertSame(
            EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            EventPrediction::predictionTypeFor(1),
        );
        $this->assertSame(
            EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            EventPrediction::predictionTypeFor(2),
        );
        $this->assertSame(
            EventPrediction::PREDICTION_TYPE_GET_ONE_UPSET_FOR_EVENT_DEFAULT,
            EventPrediction::predictionTypeFor(3),
        );
        $this->assertNull(EventPrediction::predictionTypeFor(0));
        $this->assertNull(EventPrediction::predictionTypeFor(4));
    }
}
