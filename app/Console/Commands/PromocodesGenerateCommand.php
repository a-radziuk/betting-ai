<?php

namespace App\Console\Commands;

use App\Support\PromocodeGenerator;
use Illuminate\Console\Command;

class PromocodesGenerateCommand extends Command
{
    protected $signature = 'promocodes:generate {days=1} {number=20}';

    protected $description = 'Generate unique promocodes that grant SEE_TIPS access for a number of days';

    public function handle(): int
    {
        $days = (int) $this->argument('days');
        $number = (int) $this->argument('number');

        if ($days < 1) {
            $this->components->error('Days must be at least 1.');

            return self::FAILURE;
        }

        if ($number < 1) {
            $this->components->error('Number must be at least 1.');

            return self::FAILURE;
        }

        $codes = [];

        for ($i = 0; $i < $number; $i++) {
            $promocode = PromocodeGenerator::generateUnique($days);
            $codes[] = $promocode->code;
        }

        $this->components->info("Generated {$number} promocode(s) for {$days} day(s) each.");

        foreach ($codes as $code) {
            $this->line($code);
        }

        return self::SUCCESS;
    }
}
