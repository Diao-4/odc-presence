<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cohortes (P11 Après-midi, P12 Matin…) : chaque cohorte a ses participants, ses agents
 * et ses horaires. Les données existantes sont rattachées à une première cohorte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();              // P12 — Matin
            $table->string('program', 100)->default('AWS re/Start');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('cutoff_time', 5)->default('08:00'); // heure limite d'arrivée
            $table->string('absence_time', 5)->nullable();      // heure limite de pointage
            $table->string('training_days', 20)->default('1,2,3,4,5');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        // Première cohorte : reprend le nom de la configuration et les horaires des paramètres.
        $settings = DB::table('settings')->pluck('value', 'key');
        $id = DB::table('cohorts')->insertGetId([
            'name'          => config('odc.cohort', 'P11'),
            'program'       => config('odc.program', 'AWS re/Start'),
            'cutoff_time'   => $settings['cutoff_time'] ?? '08:00',
            'absence_time'  => ($settings['absence_time'] ?? '') ?: null,
            'training_days' => ($settings['training_days'] ?? '') ?: '1,2,3,4,5',
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('participants')->update(['cohort_id' => $id]);
        DB::table('users')->where('role', 'agent')->update(['cohort_id' => $id]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('cohort_id'));
        Schema::table('participants', fn (Blueprint $table) => $table->dropConstrainedForeignId('cohort_id'));
        Schema::dropIfExists('cohorts');
    }
};
