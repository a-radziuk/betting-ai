<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class SwedenNorwayTeamsSeeder extends Seeder
{
    /**
     * Allsvenskan and Eliteserien club teams for Sweden and Norway.
     *
     * @var list<array{
     *     id: int,
     *     name: string,
     *     short_name: string,
     *     league: string,
     *     country: string,
     *     external_name: string
     * }>
     */
    private const TEAMS = [
        // Sweden — Allsvenskan
        ['id' => 147, 'name' => 'Orgryte', 'short_name' => 'ORG', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Örgryte'],
        ['id' => 148, 'name' => 'BK Hacken', 'short_name' => 'BKH', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Häcken'],
        ['id' => 149, 'name' => 'Malmo FF', 'short_name' => 'MAL', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Malmö FF'],
        ['id' => 150, 'name' => 'IFK Goteborg', 'short_name' => 'IFK', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'IFK Göteborg'],
        ['id' => 151, 'name' => 'Vasteras SK', 'short_name' => 'VAS', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Västerås'],
        ['id' => 152, 'name' => 'Degerfors IF', 'short_name' => 'DEG', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Degerfors'],
        ['id' => 153, 'name' => 'Hammarby IF', 'short_name' => 'HAM', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Hammarby'],
        ['id' => 154, 'name' => 'Kalmar FF', 'short_name' => 'KAL', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Kalmar'],
        ['id' => 155, 'name' => 'GAIS', 'short_name' => 'GAI', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'GAIS'],
        ['id' => 156, 'name' => 'IF Elfsborg', 'short_name' => 'IFE', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Elfsborg'],
        ['id' => 157, 'name' => 'IF Brommapojkarna', 'short_name' => 'IFB', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Brommapojkarna'],
        ['id' => 158, 'name' => 'IK Sirius', 'short_name' => 'IKS', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Sirius'],
        ['id' => 159, 'name' => 'Djurgardens IF', 'short_name' => 'DJU', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Djurgården'],
        ['id' => 160, 'name' => 'Halmstads BK', 'short_name' => 'HAL', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Halmstad'],
        ['id' => 161, 'name' => 'Mjallby AIF', 'short_name' => 'MJA', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'Mjällby'],
        ['id' => 162, 'name' => 'AIK', 'short_name' => 'AIK', 'league' => 'Allsvenskan', 'country' => 'Sweden', 'external_name' => 'AIK'],

        // Norway — Eliteserien
        ['id' => 163, 'name' => 'Tromso', 'short_name' => 'TRO', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Tromsø'],
        ['id' => 164, 'name' => 'Valerenga', 'short_name' => 'VAL', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Vålerenga'],
        ['id' => 165, 'name' => 'KFUM Oslo', 'short_name' => 'KFU', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'KFUM'],
        ['id' => 166, 'name' => 'Bodo-Glimt', 'short_name' => 'BOD', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Bodø / Glimt'],
        ['id' => 167, 'name' => 'Brann', 'short_name' => 'BRA', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Brann'],
        ['id' => 168, 'name' => 'IK Start', 'short_name' => 'IKS', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Start'],
        ['id' => 169, 'name' => 'Rosenborg', 'short_name' => 'ROS', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Rosenborg'],
        ['id' => 170, 'name' => 'Kristiansund BK', 'short_name' => 'KRI', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Kristiansund'],
        ['id' => 171, 'name' => 'Sandefjord', 'short_name' => 'SAN', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Sandefjord'],
        ['id' => 172, 'name' => 'Hamarkameratene', 'short_name' => 'HAM', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'HamKam'],
        ['id' => 173, 'name' => 'Sarpsborg 08', 'short_name' => 'SAR', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Sarpsborg 08'],
        ['id' => 174, 'name' => 'Viking', 'short_name' => 'VIK', 'league' => 'Norway. Eliteserien', 'country' => 'Norway', 'external_name' => 'Viking'],
    ];

    public function run(): void
    {
        foreach (self::TEAMS as $row) {
            Team::query()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'tournament_id' => null,
                    'name' => $row['name'],
                    'display_name' => null,
                    'external_name' => $row['external_name'],
                    'short_name' => $row['short_name'],
                    'league' => $row['league'],
                    'country' => $row['country'],
                    'guardian_name' => null,
                    'fifa_name' => null,
                    'fifa_rank' => null,
                    'fifa_points' => null,
                ],
            );
        }
    }
}
