<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duel_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->unsignedTinyInteger('step');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('action', 10);
            $table->boolean('is_auto')->default(false);
            $table->timestamps();

            $table->unique(['duel_id', 'round_number', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duel_drafts');
    }
};
