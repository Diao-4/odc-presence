<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20)->index();          // numéro imprimé : ODC-00452
            $table->string('token', 40)->unique();          // contenu du QR Code : ODC-BDG-8F72A91C4D2E
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20)->default('active')->index(); // active | disabled | replaced
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
