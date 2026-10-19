<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->jsonb('course_ids')->nullable();
            $table->unsignedTinyInteger('rounds')->default(3);
        });

        Schema::table('duels', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_rounds')->default(3);
            $table->smallInteger('player1_delta')->nullable();
            $table->smallInteger('player2_delta')->nullable();
        });

        Schema::table('duel_rounds', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable();
        });

        Schema::table('duel_drafts', function (Blueprint $table) {
            $table->string('auto_reason', 10)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });

        Schema::table('duel_drafts', function (Blueprint $table) {
            $table->dropColumn('auto_reason');
        });

        Schema::table('duel_rounds', function (Blueprint $table) {
            $table->dropColumn('starts_at');
        });

        Schema::table('duels', function (Blueprint $table) {
            $table->dropColumn(['max_rounds', 'player1_delta', 'player2_delta']);
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['course_ids', 'rounds']);
        });
    }
};
