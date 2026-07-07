<?php

namespace App\Console\Commands;

use App\Support\PromocodeGenerator;
use Illuminate\Console\Command;

class PromocodesGenerateMultiCommand extends Command
{
    protected $signature = 'promocodes:generate-multi {days=1}';

    protected $description = 'Generate one multi-use promocode that grants SEE_TIPS access for a number of days';

    public function handle(): int
    {
        $days = (int) $this->argument('days');

        if ($days < 1) {
            $this->components->error('Days must be at least 1.');

            return self::FAILURE;
        }

        $promocode = PromocodeGenerator::generateUniqueMulti($days);

        $this->components->info("Generated multi-use promocode for {$days} day(s).");
        $this->line($promocode->code);
        $this->line($promocode->redemptionLink());

        return self::SUCCESS;
    }
}
