<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->constrained('users')->cascadeOnDelete();
            $table->jsonb('pool');
            $table->string('status', 20)->default('drafting')->index();
            $table->unsignedTinyInteger('current_round')->default(1);
            $table->unsignedTinyInteger('draft_step')->default(0);
            $table->timestamp('turn_deadline')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duels');
    }
};
