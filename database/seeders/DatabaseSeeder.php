<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(SettingService $settings): void
    {
        $settings->set(SettingService::DEFAULTS);

        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrateur ODC', 'email' => null,
            'password' => 'Admin@2026', 'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]);

        // La première cohorte est créée par la migration des cohortes.
        $cohort = Cohort::firstOrCreate(['name' => config('odc.cohort')], Cohort::defaults()->getAttributes());

        User::updateOrCreate(['username' => 'agent'], [
            'name' => 'Agent ODC', 'email' => null,
            'password' => 'Agent@2026', 'role' => User::ROLE_AGENT, 'cohort_id' => $cohort->id, 'is_active' => true,
        ]);

        $this->command?->warn('Comptes créés : admin / Admin@2026 et agent / Agent@2026 — changez ces mots de passe dès la première connexion.');
    }
}
