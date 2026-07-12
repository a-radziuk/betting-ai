<?php

namespace Tests\Unit;

use App\Models\Selection;
use Tests\TestCase;

class SelectionExportNameTest extends TestCase
{
    public function test_export_name_returns_name_when_value_is_null(): void
    {
        $selection = new Selection(['name' => Selection::NAME_HOME]);

        $this->assertSame('HOME', $selection->exportName());
    }

    public function test_export_name_appends_formatted_value(): void
    {
        $selection = new Selection([
            'name' => Selection::NAME_OVER,
            'value' => 2.5,
        ]);
        $selection->setRelation('market', new \App\Models\Market(['type' => \App\Models\Market::TYPE_TOTAL_ASIAN]));

        $this->assertSame('OVER 2.5', $selection->exportName());
    }
}
