<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Services\BadgeService;
use Illuminate\Database\Seeder;

/** Données de démonstration : php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(BadgeService $badges): void
    {
        $people = [
            ['Amadou', 'Traoré', 'Stagiaire'],
            ['Fatoumata', 'Diallo', 'Comptable'],
            ['Ibrahim', 'Coulibaly', 'Stagiaire'],
            ['Awa', 'Keita', 'Formatrice'],
            ['Mahamadou', 'Sidibé', 'Stagiaire'],
        ];

        foreach ($people as $i => [$first, $last, $function]) {
            $p = Participant::firstOrCreate(
                ['first_name' => $first, 'last_name' => $last],
                ['phone' => '6'.str_pad((string) (7890123 + $i * 1111), 7, '0'), 'organization' => config('odc.organization'), 'function' => $function]
            );

            if (! $p->activeBadge) {
                $badges->issue($p);
            }
        }
    }
}
