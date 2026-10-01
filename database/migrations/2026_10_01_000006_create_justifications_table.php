<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20);                      // late | absent
            $table->text('reason');
            $table->string('document_path')->nullable();     // justificatif (disque privé)
            $table->string('document_name')->nullable();
            $table->foreignId('justified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un seul justificatif par participant et par jour
            $table->unique(['participant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justifications');
    }
};
