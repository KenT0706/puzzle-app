<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0);
            $table->json('solved_clues')->nullable(); // ["across-3", "down-1", ...] words already paid out
        });

        Schema::table('puzzles', function (Blueprint $table) {
            // The leaderboard only counts sessions started after this moment ("new round").
            $table->timestamp('round_started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropColumn(['score', 'solved_clues']);
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn('round_started_at');
        });
    }
};
