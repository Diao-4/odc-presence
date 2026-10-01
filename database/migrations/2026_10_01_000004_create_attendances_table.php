<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->dateTime('arrived_at');
            $table->dateTime('departed_at')->nullable();
            $table->string('status', 20);                    // present | late
            $table->unsignedInteger('late_minutes')->default(0);
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('departure_scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Une seule présence par participant et par jour (protection anti double scan)
            $table->unique(['participant_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
