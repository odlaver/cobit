<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->char('selected_answer', 1)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('served_at', 3);
            $table->timestamp('answered_at', 3)->nullable();
            $table->timestamps();

            $table->unique(['duel_round_id', 'user_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_answers');
    }
};
