<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_round_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['duel_round_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_round_questions');
    }
};
