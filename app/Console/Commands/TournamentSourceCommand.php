<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TournamentSourceCommand extends Command
{
    protected $signature = 'tournament:source
        {tournamentId : Tournament primary key}';

    protected $description = 'Display the source field for a tournament';

    public function handle(): int
    {
        if (! Schema::hasTable('tournaments')) {
            $this->components->error('The tournaments table does not exist.');

            return self::FAILURE;
        }

        $tournament = Tournament::query()->find($this->argument('tournamentId'));
        if ($tournament === null) {
            $this->components->error('Tournament not found.');

            return self::FAILURE;
        }

        $this->line((string) $tournament->source);

        return self::SUCCESS;
    }
}
