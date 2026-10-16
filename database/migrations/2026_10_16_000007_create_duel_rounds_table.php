<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->foreignId('first_ban_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('drafting');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['duel_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_rounds');
    }
};
